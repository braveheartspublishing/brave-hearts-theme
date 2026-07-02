#!/usr/bin/env python3
"""Prepare the verified 35-post path-preservation reports without mutating WordPress."""
import csv, urllib.parse
from pathlib import Path

OUT=Path(__file__).resolve().parent
def read(name): return list(csv.DictReader((OUT/name).open(encoding='utf-8-sig')))
def write(name,fields,rows):
    with (OUT/name).open('w',newline='',encoding='utf-8-sig') as f:
        w=csv.DictWriter(f,fieldnames=fields); w.writeheader(); w.writerows(rows)

blog=read('real-blog-url-map.csv')
staging=read('new-staging-url-inventory.csv')
posts={x['slug']:x for x in staging if x['object_type']=='post'}
mapped=[]
for r in blog:
    old=urllib.parse.urlparse(r['Old URL']); slug=old.path.rstrip('/').split('/')[-1]; p=posts[slug]
    intended=f'https://www.braveheartspublishing.com/blog/{slug}/'
    mapped.append({'Post Title':r['Post Title'],'Old Production URL':r['Old URL'].rstrip('/')+'/','Old Path':f'/blog/{slug}/','Old Slug':slug,'WordPress Post ID':p['object_id'],'WordPress Slug':p['slug'],'Intended Production URL':intended,'Match Status':'EXACT_MATCH','Notes':'No redirect required after /blog/%postname%/ is applied'})
write('blog-url-preservation-map.csv',['Post Title','Old Production URL','Old Path','Old Slug','WordPress Post ID','WordPress Slug','Intended Production URL','Match Status','Notes'],mapped)

focused=read('bhp-redirect-map-focused.csv')
hubs=[r for r in focused if r['content_type']!='real_blog_post']
write('bhp-redirect-map-hubs-only.csv',['old_url','new_url','redirect_type','content_type','reason','approval_status'],hubs)
print({'posts':len(mapped),'exact_matches':sum(r['Match Status']=='EXACT_MATCH' for r in mapped),'blog_redirects_removed':len(focused)-len(hubs),'hubs_only_redirects':len(hubs)})
