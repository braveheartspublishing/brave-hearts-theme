#!/usr/bin/env python3
"""Read-only public crawl used to prepare the BHP same-domain migration package."""
from __future__ import annotations

import csv, json, re, ssl, sys, urllib.error, urllib.parse, urllib.request
import xml.etree.ElementTree as ET
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from html import unescape
from pathlib import Path

OUT = Path(__file__).resolve().parent
PROD = "https://www.braveheartspublishing.com"
STAGE = "https://staging2.braveheartspublishing.com"
UA = "Brave-Hearts-SEO-Migration-Audit/1.0"
CTX = ssl.create_default_context()

def fetch(url, method="GET", timeout=35):
    req = urllib.request.Request(url, headers={"User-Agent": UA}, method=method)
    try:
        with urllib.request.urlopen(req, timeout=timeout, context=CTX) as r:
            return r.status, r.geturl(), dict(r.headers), r.read()
    except urllib.error.HTTPError as e:
        return e.code, e.geturl(), dict(e.headers), e.read()
    except Exception as e:
        return 0, url, {}, str(e).encode()

def text(url):
    s, final, h, b = fetch(url)
    return s, final, h, b.decode("utf-8", "replace")

def strip_html(value):
    return re.sub(r"\s+", " ", unescape(re.sub(r"<[^>]+>", " ", value or ""))).strip()

def page_meta(url):
    s, final, h, body = text(url)
    canonical = re.search(r'<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)', body, re.I)
    if not canonical:
        canonical = re.search(r'<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical', body, re.I)
    robots = re.search(r'<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']+)', body, re.I)
    title = re.search(r'<title[^>]*>(.*?)</title>', body, re.I|re.S)
    return {"status":s,"final_url":final,"canonical":canonical.group(1) if canonical else "",
            "robots":robots.group(1) if robots else "","title":strip_html(title.group(1)) if title else "",
            "indexable": "noindex" not in (robots.group(1).lower() if robots else "") and s == 200,
            "body":body}

def sitemap_urls(seed):
    seen, urls, sources, failures = set(), [], {}, []
    todo=[seed]
    while todo:
        sm=todo.pop(0)
        if sm in seen: continue
        seen.add(sm)
        s, final, h, raw = fetch(sm)
        if s != 200:
            failures.append((sm,s)); continue
        try: root=ET.fromstring(raw)
        except Exception:
            failures.append((sm,"invalid_xml")); continue
        locs=[(x.text or "").strip() for x in root.iter() if x.tag.endswith("loc")]
        if root.tag.endswith("sitemapindex"): todo.extend(locs)
        else:
            for u in locs:
                if u not in sources: urls.append(u); sources[u]=sm
    return urls,sources,failures

def rest_collection(route):
    items=[]; page=1
    while True:
        sep='&' if '?' in route else '?'
        u=f"{STAGE}{route}{sep}per_page=100&page={page}"
        s, final, h, raw=fetch(u)
        if s != 200: return items, s
        batch=json.loads(raw); items.extend(batch)
        total_pages=int(h.get("X-WP-TotalPages",h.get("x-wp-totalpages",1)))
        if page>=total_pages: break
        page+=1
    return items,200

def write_csv(name, headers, rows):
    with (OUT/name).open("w",newline="",encoding="utf-8-sig") as f:
        w=csv.DictWriter(f,fieldnames=headers,extrasaction="ignore"); w.writeheader(); w.writerows(rows)

def path_of(url):
    p=urllib.parse.urlparse(url)
    path=p.path or "/"
    return path if path.endswith("/") or "." in path.rsplit('/',1)[-1] else path+'/'

def main():
    OUT.mkdir(parents=True,exist_ok=True)
    now=datetime.now(timezone.utc).isoformat()
    # Production sitemap: required Rank Math index does not exist; dynamically use discovered Squarespace sitemap.
    prod_urls,prod_sources,prod_sm_fail=sitemap_urls(PROD+"/sitemap.xml")
    with ThreadPoolExecutor(max_workers=8) as ex: prod_meta=list(ex.map(page_meta,prod_urls))
    old=[]
    for u,m in zip(prod_urls,prod_meta):
        path=urllib.parse.urlparse(u).path
        typ="post" if "/blog/" in path else "product" if "/shop/p/" in path else "page"
        old.append({"url":u,"object_type":typ,"object_id":"","title":m["title"],"slug":path.rstrip('/').split('/')[-1],
          "status":"published" if m['status']==200 else str(m['status']),"canonical":m['canonical'],"indexable":"yes" if m['indexable'] else "no",
          "sitemap_source":prod_sources.get(u,""),"notes":"Squarespace sitemap inventory"})
    write_csv("old-production-url-inventory.csv",["url","object_type","object_id","title","slug","status","canonical","indexable","sitemap_source","notes"],old)

    root_s,_,_,root_raw=fetch(STAGE+"/wp-json/")
    root=json.loads(root_raw) if root_s==200 else {}
    types_s,_,_,types_raw=fetch(STAGE+"/wp-json/wp/v2/types")
    types=json.loads(types_raw) if types_s==200 else {}
    tax_s,_,_,tax_raw=fetch(STAGE+"/wp-json/wp/v2/taxonomies")
    tax=json.loads(tax_raw) if tax_s==200 else {}
    new=[]; content=[]; endpoint_notes=[]
    type_specs=[]
    for key,t in types.items():
        if not t.get("viewable") or key in ("attachment","wp_block","wp_template","wp_template_part","wp_navigation"): continue
        type_specs.append((key,t.get("rest_base") or key))
    if not type_specs:
        type_specs=[("post","posts"),("page","pages"),("product","product")]
    for key,base in type_specs:
        items,status=rest_collection(f"/wp-json/wp/v2/{base}?status=publish&_embed=1")
        endpoint_notes.append((key,len(items),status))
        for x in items:
            url=x.get("link",""); intended=PROD+path_of(url)
            title=strip_html((x.get("title") or {}).get("rendered",""))
            new.append({"staging_url":url,"intended_production_url":intended,"object_type":key,"object_id":x.get("id",""),"title":title,
             "slug":x.get("slug",""),"canonical":"public HTML audit","indexable_after_launch":"yes","sitemap_expected":"yes","notes":"Published REST object"})
            content.append((key,x.get('id',''),url,(x.get('content') or {}).get('rendered','')+' '+(x.get('excerpt') or {}).get('rendered','')))
    tax_specs=[]
    for key,t in tax.items():
        if not t.get("visibility",{}).get("publicly_queryable"): continue
        tax_specs.append((key,t.get("rest_base") or key))
    if not tax_specs:
        tax_specs=[("category","categories"),("post_tag","tags"),("product_cat","product_cat"),("product_tag","product_tag")]
    for key,base in tax_specs:
        items,status=rest_collection(f"/wp-json/wp/v2/{base}?hide_empty=false")
        endpoint_notes.append((key,len(items),status))
        for x in items:
            url=x.get("link",""); intended=PROD+path_of(url)
            new.append({"staging_url":url,"intended_production_url":intended,"object_type":key,"object_id":x.get("id",""),"title":x.get("name",""),
             "slug":x.get("slug",""),"canonical":"public HTML audit","indexable_after_launch":"review","sitemap_expected":"review","notes":f"count={x.get('count',0)}"})
    # Utility URLs explicitly in launch scope.
    for p,title,idx in [("/","Home","yes"),("/shop/","Shop","yes"),("/cart/","Cart","no"),("/checkout/","Checkout","no"),("/my-account/","My Account","no"),("/?s=test","Search results","no"),("/__migration-audit-404__/","404","no")]:
        if '?' in p or not any(path_of(n['staging_url'])==path_of(STAGE+p) for n in new):
            new.append({"staging_url":STAGE+p,"intended_production_url":PROD+p,"object_type":"utility","object_id":"","title":title,"slug":p.strip('/'),"canonical":"public HTML audit","indexable_after_launch":idx,"sitemap_expected":"no" if idx=='no' else 'yes',"notes":"Explicit scope URL"})
    write_csv("new-staging-url-inventory.csv",["staging_url","intended_production_url","object_type","object_id","title","slug","canonical","indexable_after_launch","sitemap_expected","notes"],new)

    new_by_path={path_of(x['intended_production_url']):x for x in new}
    content_by_slug={}
    for x in new:
        if x['slug'] and x['object_type'] in ('post','page','product'):
            content_by_slug.setdefault(x['slug'],[]).append(x)
    comparisons=[]; redirects=[]
    matched=set()
    for o in old:
        op=path_of(o['url']); candidate=None; classification="REVIEW_REQUIRED"; relevance="unclear"; action="manual review"; code=""; review="yes"
        if op in new_by_path:
            candidate=new_by_path[op]; classification="UNCHANGED"; relevance="exact URL"; action="preserve"; review="no"; matched.add(id(candidate))
        elif o['slug'] in content_by_slug:
            allowed={'post':('post',),'page':('page','product'),'product':('product',)}.get(o['object_type'],())
            candidates=[x for x in content_by_slug[o['slug']] if x['object_type'] in allowed]
            if len(candidates)==1:
                candidate=candidates[0]; classification="REDIRECT"; relevance="same slug and compatible object intent"; action="301"; code="301"; review="yes"; matched.add(id(candidate))
        elif op in ("/teachers-guide/","/teachers/guide/") and "/teachers/" in new_by_path:
            candidate=new_by_path["/teachers/"]; classification="REDIRECT"; relevance="direct educator hub replacement"; action="301"; code="301"; review="yes"
        nu=candidate['intended_production_url'] if candidate else ""
        comparisons.append({"old_url":o['url'],"new_url":nu,"classification":classification,"relevance":relevance,"action":action,"status_code":code,"review_required":review,"notes":"No homepage fallback proposed"})
        if classification=="REDIRECT": redirects.append({"old_url":op,"new_url":path_of(nu),"redirect_type":"301","reason":relevance,"review_status":"review_required"})
    for n in new:
        if path_of(n['intended_production_url']) not in {path_of(o['url']) for o in old} and not any(c['new_url']==n['intended_production_url'] for c in comparisons):
            comparisons.append({"old_url":"","new_url":n['intended_production_url'],"classification":"NEW_URL","relevance":"new staging object","action":"launch/indexability review","status_code":"","review_required":"yes","notes":""})
    write_csv("url-comparison-report.csv",["old_url","new_url","classification","relevance","action","status_code","review_required","notes"],comparisons)
    write_csv("bhp-redirect-map.csv",["old_url","new_url","redirect_type","reason","review_status"],redirects)

    # Validate proposed staging targets; do not execute redirects.
    validation=[]
    for r in redirects:
        target=STAGE+r['new_url']; m=page_meta(target)
        validation.append({"old_url":r['old_url'],"proposed_new_url":r['new_url'],"target_status":m['status'],"final_url":m['final_url'],"hop_count":0 if m['final_url']==target else 1,
          "loop":"no","relevance_check":r['reason'],"validation_result":"PASS" if m['status']==200 and m['indexable'] else "REVIEW","notes":f"canonical={m['canonical']}; robots={m['robots']}"})
    write_csv("redirect-validation-report.csv",["old_url","proposed_new_url","target_status","final_url","hop_count","loop","relevance_check","validation_result","notes"],validation)
    write_csv("redirect-exceptions.csv",["old_url","issue","recommended_action","review_required"],[{"old_url":c['old_url'],"issue":"No unambiguous relevant replacement","recommended_action":"Review traffic/backlinks; retain or retire 404/410","review_required":"yes"} for c in comparisons if c['classification']=="REVIEW_REQUIRED"])

    # Public HTML metadata for new URLs, bounded to actual intended pages and terms.
    public_new=[n for n in new if n['object_type']!='utility' or n['title'] in ('Home','Shop')]
    with ThreadPoolExecutor(max_workers=8) as ex: new_meta=list(ex.map(lambda n:page_meta(n['staging_url']),public_new))
    meta_by_url={n['staging_url']:m for n,m in zip(public_new,new_meta)}
    for n in new:
        m=meta_by_url.get(n['staging_url'])
        if m:
            n['canonical']=m['canonical']; n['indexable_after_launch']='no' if 'noindex' in m['robots'].lower() else n['indexable_after_launch']
    write_csv("new-staging-url-inventory.csv",["staging_url","intended_production_url","object_type","object_id","title","slug","canonical","indexable_after_launch","sitemap_expected","notes"],new)

    # Internal links from REST content plus staging homepage shell.
    home=page_meta(STAGE+'/'); content.append(('site_shell','',STAGE+'/',home['body']))
    links=[]
    attr=re.compile(r'''(?:href|src)\s*=\s*(["'])(.*?)\1''',re.I|re.S)
    for stype,sid,source,html in content:
        for _,raw in attr.findall(html):
            url=urllib.parse.urljoin(source,unescape(raw.strip()))
            host=urllib.parse.urlparse(url).hostname or ''
            if host.endswith('braveheartspublishing.com') or url.startswith(('mailto:','tel:')):
                links.append((stype,sid,source,url))
    unique=sorted(set(x[3] for x in links if not x[3].startswith(('mailto:','tel:'))))
    with ThreadPoolExecutor(max_workers=10) as ex: checks=list(ex.map(page_meta,unique))
    check={u:m for u,m in zip(unique,checks)}; link_rows=[]
    for stype,sid,source,url in sorted(set(links)):
        if url.startswith('mailto:'):
            valid=bool(re.match(r'^mailto:[^@\s]+@[^@\s]+\.[^@\s]+$',url)); status='N/A'; final=url; hops=0; issue='' if valid else 'broken_mailto'
        elif url.startswith('tel:'):
            status='N/A'; final=url; hops=0; issue=''
        else:
            m=check[url]; status=m['status']; final=m['final_url']; hops=0 if final==url else 1
            issue='staging_domain' if 'staging2.' in url else 'insecure_http' if url.startswith('http:') else 'broken' if status in (0,404,410) or status>=500 else 'redirect' if final!=url else ''
            if re.search(r'\.(pdf|docx?|xlsx?|zip)(?:\?|$)',url,re.I) and status!=200: issue='broken_download'
        link_rows.append({"source_type":stype,"source_id":sid,"source_url":source,"linked_url":url,"http_status":status,"final_url":final,"redirect_hops":hops,"issue_type":issue,"recommended_action":"Update source to final URL" if issue=='redirect' else "Review and repair" if issue else ""})
    write_csv("internal-link-audit.csv",["source_type","source_id","source_url","linked_url","http_status","final_url","redirect_hops","issue_type","recommended_action"],link_rows)

    # Hub checklist: pages may be section anchors, not standalone indexable URLs.
    hubs=[('Explorer Expedition Guides','/teachers/'),('Animals','/teachers/#animals'),('Science','/teachers/#science'),('Geography','/teachers/#geography'),('Conservation','/teachers/#conservation'),('Explorers','/teachers/#explorers'),('Activities','/teachers/#activities'),('Reading & Growing','/teachers/#reading-growing'),('Mariana Trench','/teachers/#mariana-trench'),('Mount Everest','/teachers/#mount-everest'),('Amazon Rainforest','/teachers/#amazon-rainforest'),('Educator section','/teachers/#for-educators'),('Family section','/teachers/#family-resources')]
    hub_rows=[]
    for name,p in hubs:
        base=p.split('#')[0]; m=page_meta(STAGE+base); body=m['body']; h1s=re.findall(r'<h1\b[^>]*>(.*?)</h1>',body,re.I|re.S)
        is_anchor='#' in p
        hub_rows.append({"hub":name,"url":PROD+p,"h1":strip_html(h1s[0]) if h1s and not is_anchor else "section anchor—not standalone H1","seo_title":m['title'] if not is_anchor else "inherits hub page",
          "meta_description":"review required","canonical":m['canonical'] if not is_anchor else PROD+base,"indexable":"page only" if is_anchor else ('yes' if m['indexable'] else 'no'),"sitemap":"page only" if is_anchor else 'expected',
          "inbound_links":"theme registry","outbound_post_links":"theme registry","navigation_path":"Expedition Guides","status":"PASS" if not is_anchor and m['status']==200 else "REVIEW: anchor is not standalone indexable hub"})
    write_csv("new-hub-seo-checklist.csv",["hub","url","h1","seo_title","meta_description","canonical","indexable","sitemap","inbound_links","outbound_post_links","navigation_path","status"],hub_rows)

    # Empty exports where authenticated plugin/analytics data is unavailable.
    write_csv("existing-rank-math-redirects.csv",["redirect_id","source","destination","status_code","status","hits","last_accessed","notes"],
      [{"notes":"NOT EXPORTED: authenticated WordPress/database access and installed Rank Math schema/version were unavailable. No table assumptions made."}])
    write_csv("priority-url-report.csv",["old_url","clicks","impressions","backlinks","current_rank_signal","migration_priority","notes"],
      [{"old_url":o['url'],"clicks":"unavailable","impressions":"unavailable","backlinks":"unavailable","current_rank_signal":"sitemap-listed","migration_priority":"manual review","notes":"GSC/Analytics/backlink data unavailable"} for o in old])

    # Machine-readable facts used by the Markdown checklists.
    facts={"generated":now,"production_sitemap_count":len(prod_urls),"production_sitemap_failures":prod_sm_fail,"old_counts":{},"new_counts":{},"comparison_counts":{},"redirect_count":len(redirects),
      "redirect_validation":validation,"internal_link_count":len(link_rows),"internal_issue_counts":{},"rest_namespaces":root.get('namespaces',[]),"rest_endpoints":endpoint_notes}
    for x in old: facts['old_counts'][x['object_type']]=facts['old_counts'].get(x['object_type'],0)+1
    for x in new: facts['new_counts'][x['object_type']]=facts['new_counts'].get(x['object_type'],0)+1
    for x in comparisons: facts['comparison_counts'][x['classification']]=facts['comparison_counts'].get(x['classification'],0)+1
    for x in link_rows: facts['internal_issue_counts'][x['issue_type'] or 'direct']=facts['internal_issue_counts'].get(x['issue_type'] or 'direct',0)+1
    (OUT/'audit-facts.json').write_text(json.dumps(facts,indent=2),encoding='utf-8')
    print(json.dumps(facts,indent=2))

if __name__=='__main__': main()
