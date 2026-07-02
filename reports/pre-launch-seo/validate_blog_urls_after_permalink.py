#!/usr/bin/env python3
"""Validate preserved blog paths, hub reciprocity, and non-blog URL regression."""
from __future__ import annotations
import csv, re, urllib.parse, urllib.request, json, importlib.util
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

OUT=Path(__file__).resolve().parent
spec=importlib.util.spec_from_file_location('audit',OUT/'build_migration_audit.py'); audit=importlib.util.module_from_spec(spec); spec.loader.exec_module(audit)
def read(n): return list(csv.DictReader((OUT/n).open(encoding='utf-8-sig')))
def write(n,f,r): audit.write_csv(n,f,r)

mapping=read('blog-url-preservation-map.csv')
def check_blog(r):
    slug=r['Old Slug']; url=audit.STAGE+f'/blog/{slug}/'; m=audit.page_meta(url)
    path=urllib.parse.urlparse(m['final_url']).path
    old=r['Old Path']; canon_path=urllib.parse.urlparse(m['canonical']).path if m['canonical'] else path
    return {'Post ID':r['WordPress Post ID'],'Post Title':r['Post Title'],'Old Production Path':old,'New WordPress Path':path,'Exact Match':'yes' if path==old else 'no','HTTP Status':m['status'],'Redirect':'no' if m['final_url']==url else 'yes','Canonical Path':canon_path,'Result':'PASS' if m['status']==200 and path==old and m['final_url']==url else 'FAIL','Notes':'Staging noindex may suppress canonical output'},m
with ThreadPoolExecutor(max_workers=5) as ex: checked=list(ex.map(check_blog,mapping))
rows=[x[0] for x in checked]; meta={r['Old Slug']:m for r,(_,m) in zip(mapping,checked)}
write('blog-url-validation-after-permalink.csv',['Post ID','Post Title','Old Production Path','New WordPress Path','Exact Match','HTTP Status','Redirect','Canonical Path','Result','Notes'],rows)

# Hub reciprocity is rendered by the deployed guide registry/components.
teacher=audit.page_meta(audit.STAGE+'/teachers/'); teacher_links=set(re.findall(r'href=["\']([^"\']+)',teacher['body'],re.I))
hubrows=[]
for r in mapping:
    m=meta[r['Old Slug']]; links=set(re.findall(r'href=["\']([^"\']+)',m['body'],re.I))
    hub=[u for u in links if '/teachers/' in u]
    related=[u for u in links if '/blog/' in u and r['Old Slug'] not in u]
    books=[u for u in links if '/product/' in u or '/books/' in u]
    canonical=audit.STAGE+f'/blog/{r["Old Slug"]}/'
    backlink=any(urllib.parse.urljoin(audit.STAGE+'/',u).rstrip('/')==canonical.rstrip('/') for u in teacher_links)
    result='PASS' if hub and backlink else 'REVIEW'
    hubrows.append({'Post ID':r['WordPress Post ID'],'Post URL':canonical,'Primary Hub':'Explorer Expedition Guides','Primary Hub Link':sorted(hub)[0] if hub else '','Secondary Hubs':'; '.join(sorted(hub)[1:]),'Destination Hub':next((u for u in hub if any(x in u for x in ('mariana','mount-everest','amazon'))),''),'Related Book':sorted(books)[0] if books else '','Related Posts':str(len(related)),'Issues':'' if result=='PASS' else 'Missing rendered hub link or hub backlink','Result':result})
write('blog-hub-link-validation.csv',['Post ID','Post URL','Primary Hub','Primary Hub Link','Secondary Hubs','Destination Hub','Related Book','Related Posts','Issues','Result'],hubrows)

# Compare public page/product paths recorded before the permalink change.
before=[x for x in read('new-staging-url-inventory.csv') if x['object_type'] in ('page','product')]
reg=[]
def check_nonblog(x):
    m=audit.page_meta(x['staging_url']); before_path=urllib.parse.urlparse(x['staging_url']).path; after_path=urllib.parse.urlparse(m['final_url']).path
    expected_after={'/teachers-guide/':'/teachers/','/checkout/':'/cart/'}.get(before_path,before_path)
    expected='intentional teacher-guide consolidation' if before_path=='/teachers-guide/' else 'empty checkout redirects to cart' if before_path=='/checkout/' else 'unchanged'
    return {'Object Type':x['object_type'],'Object':x['title'],'URL Before':before_path,'URL After':after_path,'Changed':'yes' if before_path!=after_path else 'no','Expected':expected,'Result':'PASS' if m['status']==200 and after_path==expected_after else 'FAIL','Notes':f'HTTP {m["status"]}'}
with ThreadPoolExecutor(max_workers=5) as ex: reg=list(ex.map(check_nonblog,before))
write('non-blog-url-regression-check.csv',['Object Type','Object','URL Before','URL After','Changed','Expected','Result','Notes'],reg)
print(json.dumps({'blog_urls':len(rows),'blog_pass':sum(x['Result']=='PASS' for x in rows),'hub_links_pass':sum(x['Result']=='PASS' for x in hubrows),'nonblog_checked':len(reg),'nonblog_pass':sum(x['Result']=='PASS' for x in reg)},indent=2))
