#!/usr/bin/env python3
"""Safely update verified internal post href destinations through authenticated REST."""
from __future__ import annotations
import base64, json, os, re, sys, urllib.parse, urllib.request

BASE='https://staging2.braveheartspublishing.com'
HOSTS={'staging2.braveheartspublishing.com','braveheartspublishing.com','www.braveheartspublishing.com'}
HUBS={
 '/bridge-books':'/teachers/#reading-growing',
 '/reading-levels':'/teachers/#reading-growing',
 '/reluctant-readers':'/teachers/#reading-growing',
 '/read-alouds':'/teachers/#for-educators',
 '/mariana-trench':'/teachers/#mariana-trench',
 '/home':'/',
}
USER=os.environ.get('BHP_WP_USER',''); PASSWORD=os.environ.get('BHP_WP_APP_PASSWORD','')
if not USER or not PASSWORD: raise SystemExit('Credentials must be supplied in temporary environment variables.')
AUTH='Basic '+base64.b64encode(f'{USER}:{PASSWORD}'.encode()).decode()
HEAD={'Authorization':AUTH,'User-Agent':'Mozilla/5.0 Chrome/138','Accept':'application/json'}
apply='--apply' in sys.argv

def request(url,method='GET',data=None):
    body=json.dumps(data).encode() if data is not None else None
    h=dict(HEAD)
    if body is not None: h['Content-Type']='application/json'
    with urllib.request.urlopen(urllib.request.Request(url,data=body,headers=h,method=method),timeout=90) as r: return json.load(r)

posts=request(BASE+'/wp-json/wp/v2/posts?per_page=100&context=edit')
slugs={p['slug'] for p in posts}
changes=[]
href_re=re.compile(r'''(href\s*=\s*)(["'])(.*?)(\2)''',re.I|re.S)

def replace_href(match):
    raw=match.group(3); parsed=urllib.parse.urlparse(raw)
    if parsed.scheme and (parsed.hostname or '').lower() not in HOSTS: return match.group(0)
    path=parsed.path.rstrip('/') or '/'; target=HUBS.get(path)
    if not target and path.startswith('/') and not path.startswith('/blog/'):
        candidate=path.strip('/').lower()
        if candidate in slugs: target=f'/blog/{candidate}/'
    if not target: return match.group(0)
    if parsed.query: target += ('&' if '?' in target else '?')+parsed.query
    if parsed.fragment and '#' not in target: target += '#'+parsed.fragment
    return match.group(1)+match.group(2)+target+match.group(4)

for p in posts:
    raw=p['content']['raw']; updated=href_re.sub(replace_href,raw)
    if updated!=raw:
        replacements=sum(1 for a,b in zip(href_re.findall(raw),href_re.findall(updated)) if a[2]!=b[2])
        changes.append((p['id'],p['slug'],replacements,updated))

print(json.dumps({'mode':'apply' if apply else 'dry-run','posts_scanned':len(posts),'posts_to_update':len(changes),'link_replacements':sum(x[2] for x in changes),'posts':[{'id':x[0],'slug':x[1],'replacements':x[2]} for x in changes]},indent=2))
if apply:
    for post_id,slug,count,content in changes:
        result=request(f'{BASE}/wp-json/wp/v2/posts/{post_id}','POST',{'content':content})
        if result.get('id')!=post_id: raise RuntimeError(f'Update failed for post {post_id}')
    print(json.dumps({'applied_posts':len(changes),'applied_links':sum(x[2] for x in changes)}))

