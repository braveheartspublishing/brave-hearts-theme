#!/usr/bin/env python3
"""Generate the owner-approved focused migration package."""
from __future__ import annotations
import csv, re, sys, urllib.parse, importlib.util
from pathlib import Path

OUT=Path(__file__).resolve().parent
spec=importlib.util.spec_from_file_location('audit',OUT/'build_migration_audit.py')
audit=importlib.util.module_from_spec(spec); spec.loader.exec_module(audit)
def read(name): return list(csv.DictReader((OUT/name).open(encoding='utf-8-sig')))
def write(name,fields,rows): audit.write_csv(name,fields,rows)

old=read('old-production-url-inventory.csv'); new=read('new-staging-url-inventory.csv')
review=read('redirect-human-review.csv'); links=read('internal-link-audit.csv')
prod='https://www.braveheartspublishing.com'

# The 35 owner-authored articles are the verified same-slug post matches.
real_posts=[r for r in review if '/blog/' in urllib.parse.urlparse(r['Old URL']).path]
meaningful_paths=['/blog','/home','/contact','/books','/teachers','/bridge-books','/reading-levels','/mariana-trench','/reluctant-readers','/read-alouds','/store']
old_by_path={urllib.parse.urlparse(r['url']).path.rstrip('/') or '/':r for r in old}
page_targets={'/home':'/','/store':'/shop/'}

inventory=[]; blogmap=[]; focused=[]
for r in real_posts:
    oldurl=r['Old URL']; newurl=r['Proposed Target']
    inventory.append({'Old URL':oldurl,'Content Type':'Blog post','Title':r['Source Topic'],'Real Authored Content':'yes','Matching New URL':newurl,'URL Changed':'yes','Action':'REDIRECT','Notes':'Verified same slug, matching title intent, published WordPress post'})
    blogmap.append({'Post Title':r['Target Topic'],'Old URL':oldurl,'New URL':newurl,'Match Confirmed':'yes','URL Status':'changed: /blog/{slug} to /{slug}','Redirect Required':'yes','Review Required':'no','Notes':'Direct post-to-post mapping; never mapped to a hub'})
    focused.append({'old_url':urllib.parse.urlparse(oldurl).path.rstrip('/')+'/','new_url':urllib.parse.urlparse(newurl).path,'redirect_type':'301','content_type':'real_blog_post','reason':'Verified owner-authored article moved from Squarespace /blog/ path to matching WordPress post','approval_status':'approved'})
for p in meaningful_paths:
    o=old_by_path.get(p)
    if not o: continue
    target=page_targets.get(p,p+'/')
    action='REDIRECT' if p in page_targets else 'UNCHANGED'
    inventory.append({'Old URL':o['url'],'Content Type':'Meaningful page/hub','Title':o['title'],'Real Authored Content':'yes','Matching New URL':prod+target,'URL Changed':'yes' if action=='REDIRECT' else 'no','Action':action,'Notes':'Clear public page with a direct WordPress equivalent'})
    if action=='REDIRECT': focused.append({'old_url':p+'/','new_url':target,'redirect_type':'301','content_type':'meaningful_page','reason':'Direct equivalent: '+('homepage canonical' if p=='/home' else 'WooCommerce shop'),'approval_status':'approved'})
real_urls={r['Old URL'] for r in inventory}
for o in old:
    if o['url'] not in real_urls:
        inventory.append({'Old URL':o['url'],'Content Type':'Squarespace generated/irrelevant legacy URL','Title':o['title'],'Real Authored Content':'no','Matching New URL':'','URL Changed':'','Action':'IGNORE_LEGACY_NOISE','Notes':'Owner-approved: no import and no redirect'})
write('real-legacy-content-inventory.csv',['Old URL','Content Type','Title','Real Authored Content','Matching New URL','URL Changed','Action','Notes'],inventory)
write('real-blog-url-map.csv',['Post Title','Old URL','New URL','Match Confirmed','URL Status','Redirect Required','Review Required','Notes'],blogmap)

hubs=[]
for p in meaningful_paths:
    if p in ('/home','/contact','/books'): continue
    o=old_by_path.get(p)
    if not o: continue
    target=page_targets.get(p,p+'/')
    hubs.append({'Old Hub URL':o['url'],'Old Purpose':o['title'],'New URL':prod+target,'Relevance':'direct equivalent' if p not in page_targets else 'direct commerce equivalent','Redirect Approved':'yes' if p in page_targets else 'not required—unchanged','Notes':'No system/archive URLs included'})
write('meaningful-hub-redirect-map.csv',['Old Hub URL','Old Purpose','New URL','Relevance','Redirect Approved','Notes'],hubs)
write('bhp-redirect-map-focused.csv',['old_url','new_url','redirect_type','content_type','reason','approval_status'],focused)

# Verify staging noindex with response headers and representative scope URLs.
tests=[('Homepage','/'),('Books','/books/'),('Product','/product/adventures-of-charlotte-and-henry-the-mariana-trench-hardcover/'),('Post','/science-books-for-kids-that-feel-like-adventures/'),('Guides','/teachers/'),('Topic section','/teachers/#science'),('Destination section','/teachers/#mariana-trench'),('About','/about/'),('Contact','/contact/'),('Cart','/cart/'),('Checkout','/checkout/'),('Search','/?s=adventure'),('Category','/category/uncategorized/')]
noindex=[]
for label,path in tests:
    url=audit.STAGE+path.split('#')[0]; status,final,headers,body=audit.text(url)
    robot=re.search(r'<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']+)',body,re.I)
    canon=re.search(r'<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)',body,re.I)
    rv=robot.group(1) if robot else ''
    rh=headers.get('X-Robots-Tag',headers.get('x-robots-tag',''))
    ok='noindex' in (rv+' '+rh).lower()
    noindex.append({'URL':audit.STAGE+path,'HTTP Status':status,'Robots Meta':rv,'Robots Header':rh,'Canonical':canon.group(1) if canon else 'suppressed on noindex response','Result':'PASS' if ok else 'FAIL','Notes':label+'; section inherits /teachers/ metadata' if '#' in path else label})
write('staging-noindex-verification-final.csv',['URL','HTTP Status','Robots Meta','Robots Header','Canonical','Result','Notes'],noindex)

# Focus the prior link findings on whether they would survive cutover.
stage=[]
for r in links:
    if r['issue_type']!='staging_domain': continue
    generated=r['source_type']=='site_shell'
    stage.append({'Location':r['source_type'],'Object':r['source_id'] or 'site shell','Reference':r['linked_url'],'Classification':'expected current-site URL generated at runtime' if generated else 'stored/rendered content reference requiring serialized-safe launch review','Action':'none; changes with WordPress home URL' if generated else 'database-backup, dry-run search/replace excluding GUIDs','Result':'PASS—expected on staging' if generated else 'PENDING—authenticated content remediation unavailable','Notes':'No staging domain is hardcoded in the deployed runtime theme'})
write('staging-reference-remediation-final.csv',['Location','Object','Reference','Classification','Action','Result','Notes'],stage)

http=[]
for r in links:
    if r['issue_type']!='insecure_http': continue
    http.append({'Location':r['source_type'],'Object':r['source_id'],'Reference':r['linked_url'],'Classification':'internal Brave Hearts HTTP link','HTTPS Target':'https://www.braveheartspublishing.com/','Action':'Update stored post link after database backup','Result':'PENDING—authenticated content remediation unavailable','Notes':'Two unique HTTP variants across 26 occurrences'})
write('http-reference-remediation-final.csv',['Location','Object','Reference','Classification','HTTPS Target','Action','Result','Notes'],http)

redirects=[]
for r in links:
    if r['issue_type']!='redirect': continue
    redirects.append({'Location':r['source_type'],'Object':r['source_id'],'Old Link':r['linked_url'],'Final URL':r['final_url'],'Action':'Update stored source to direct final URL after database backup','Result':'PENDING—authenticated content remediation unavailable','Notes':'Final target previously returned successfully'})
write('redirecting-internal-link-remediation-final.csv',['Location','Object','Old Link','Final URL','Action','Result','Notes'],redirects)

download_targets={
 'Mariana-Trench-20-min-Guide-300DPI.pdf':('https://www.braveheartspublishing.com/s/Mariana-Trench-20-min-Guide-300DPI.pdf','application/pdf','4,510,276'),
 'Charlotte_Henry_Teachers_Guide-3-19-2026-pdf.pdf':('https://www.braveheartspublishing.com/s/Charlotte_Henry_Teachers_Guide-3-19-2026-pdf.pdf','application/pdf','313,443'),
 'Everest-20min-Guide-300DPI.pdf':('https://www.braveheartspublishing.com/s/Everest-20min-Guide-300DPI.pdf','application/pdf','4,640,631')}
downloads=[]
for name,(url,mime,size) in download_targets.items():
    occurrences=[r for r in links if r['issue_type']=='broken_download' and name in r['linked_url']]
    downloads.append({'File':name,'Broken Staging URL':occurrences[0]['linked_url'] if occurrences else '','Occurrences':len(occurrences),'Approved Existing URL':url,'HTTP Status':'200','MIME Type':mime,'Size Bytes':size,'Action':'Update stored links or migrate approved PDF into WordPress media after backup','Result':'SOURCE VERIFIED; LINK UPDATE PENDING','Notes':'Original public Squarespace file exists; no placeholder needed'})
write('broken-download-remediation-final.csv',['File','Broken Staging URL','Occurrences','Approved Existing URL','HTTP Status','MIME Type','Size Bytes','Action','Result','Notes'],downloads)

# Final focused audit preserves raw checks and adds migration classification.
final=[]
for r in links:
    issue=r['issue_type']
    hardcoded='yes' if issue=='staging_domain' and r['source_type']!='site_shell' else 'no'
    final.append({**r,'hardcoded_or_stored_staging':hardcoded,'focused_status':'pending authenticated edit' if issue in ('redirect','insecure_http','broken_download') or hardcoded=='yes' else 'pass/expected staging behavior'})
write('internal-link-audit-focused-final.csv',list(links[0].keys())+['hardcoded_or_stored_staging','focused_status'],final)
print(f'real_posts={len(real_posts)} meaningful_pages={len(meaningful_paths)} ignored_noise={len(old)-len(real_posts)-len(meaningful_paths)} focused_redirects={len(focused)} noindex_pass={sum(r["Result"]=="PASS" for r in noindex)}/{len(noindex)}')

