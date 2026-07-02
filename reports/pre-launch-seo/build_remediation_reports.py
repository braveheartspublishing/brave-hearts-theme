#!/usr/bin/env python3
"""Build conservative remediation reports from the preserved public audit."""
from __future__ import annotations
import csv, re, shutil, urllib.parse
from difflib import SequenceMatcher
from pathlib import Path
import importlib.util

OUT=Path(__file__).resolve().parent
spec=importlib.util.spec_from_file_location('audit',OUT/'build_migration_audit.py')
audit=importlib.util.module_from_spec(spec); spec.loader.exec_module(audit)

def rows(name):
    return list(csv.DictReader((OUT/name).open(encoding='utf-8-sig')))
def write(name,fields,data): audit.write_csv(name,fields,data)
def norm(s): return re.sub(r'[^a-z0-9]+',' ',(s or '').lower()).strip()

old=rows('old-production-url-inventory.csv'); new=rows('new-staging-url-inventory.csv')
comparison=rows('url-comparison-report.csv'); validation={r['old_url'].rstrip('/'):r for r in rows('redirect-validation-report.csv')}
cmp_old={r['old_url']:r for r in comparison if r['old_url']}
new_url={r['intended_production_url'].rstrip('/'):r for r in new}

reclass=[]; resolved=[]; review=[]
for o in old:
    c=cmp_old[o['url']]; path=urllib.parse.urlparse(o['url']).path
    corrected='Blog post' if path.startswith('/blog/') else 'Standard page'
    target=c['new_url']; n=new_url.get(target.rstrip('/')) if target else None
    similarity=SequenceMatcher(None,norm(o['title']),norm(n['title'] if n else '')).ratio() if n else 0
    confidence='high' if similarity>=.70 or (target and o['slug']==(n or {}).get('slug')) else 'none'
    if c['classification']=='UNCHANGED': final='UNCHANGED'; approval='approved'; action='Preserve URL'
    elif c['classification']=='REDIRECT': final='REDIRECT_APPROVED'; approval='approved'; action='301 after final host and Rank Math conflict check'
    elif corrected=='Blog post': final='RESTORE_OR_IMPORT'; approval='manual approval required'; action='Restore/import before considering retirement'
    else: final='MANUAL_REVIEW_REQUIRED'; approval='manual review required'; action='Determine page intent and traffic/backlinks'
    reclass.append({'Old URL':o['url'],'Original Type':o['object_type'],'Corrected Type':corrected,'Title':o['title'],'Status':o['status'],'Canonical':o['canonical'],'Matching New URL':target,'Match Confidence':confidence,'Action':action,'Review Required':'no' if final in ('UNCHANGED','REDIRECT_APPROVED') else 'yes','Notes':f'title similarity={similarity:.2f}; public sitemap/HTML review'})
    resolved.append({'Old URL':o['url'],'Final Classification':final,'New URL':target,'Relevance Score':f'{similarity*100:.0f}' if target else '0','Traffic Priority':'unknown—GSC unavailable','Backlink Priority':'unknown—backlink data unavailable','Reason':action,'Approval Status':approval,'Implementation Status':'not implemented','Notes':'No content was deleted or rewritten'})
    if c['classification']=='REDIRECT':
        v=validation.get(path.rstrip('/')) or validation.get(o['url'].rstrip('/')) or {}
        review.append({'Old URL':o['url'],'Proposed Target':target,'Source Topic':o['title'],'Target Topic':n['title'] if n else '','Intent Match':'strong' if confidence=='high' else 'review','Relevance Score':f'{similarity*100:.0f}','Decision':'APPROVED' if confidence=='high' and v.get('validation_result')=='PASS' else 'MANUAL_REVIEW','Revised Target':'','Reason':'Same slug, compatible object, HTTP 200, and matching title intent' if confidence=='high' else 'Human content review required','Notes':'Approval is planning-only; not imported'})

write('old-content-reclassification.csv',['Old URL','Original Type','Corrected Type','Title','Status','Canonical','Matching New URL','Match Confidence','Action','Review Required','Notes'],reclass)
write('resolved-url-classification.csv',['Old URL','Final Classification','New URL','Relevance Score','Traffic Priority','Backlink Priority','Reason','Approval Status','Implementation Status','Notes'],resolved)
write('redirect-human-review.csv',['Old URL','Proposed Target','Source Topic','Target Topic','Intent Match','Relevance Score','Decision','Revised Target','Reason','Notes'],review)

links=rows('internal-link-audit.csv')
write('staging-reference-remediation.csv',['source_type','source_id','source_url','linked_url','classification','recommended_action','implementation_status','notes'],[
 {'source_type':r['source_type'],'source_id':r['source_id'],'source_url':r['source_url'],'linked_url':r['linked_url'],'classification':'runtime-generated or stored staging reference','recommended_action':'Use production-safe WordPress URL functions or serialized-safe launch search/replace after database backup','implementation_status':'blocked—backup/authentication required','notes':'GUIDs excluded; review each stored occurrence'} for r in links if r['issue_type']=='staging_domain'])
write('http-reference-remediation.csv',['source_type','source_id','source_url','linked_url','classification','https_test','recommended_action','implementation_status','notes'],[
 {'source_type':r['source_type'],'source_id':r['source_id'],'source_url':r['source_url'],'linked_url':r['linked_url'],'classification':'internal' if 'braveheartspublishing.com' in r['linked_url'] else 'external','https_test':'required','recommended_action':'Update internal URL to HTTPS canonical; test external HTTPS before changing','implementation_status':'not changed—source backup/authentication required','notes':''} for r in links if r['issue_type']=='insecure_http'])
write('broken-download-remediation.csv',['source_type','source_id','source_url','broken_url','expected_file','file_type','media_status','repository_status','recommended_action','implementation_status','notes'],[
 {'source_type':r['source_type'],'source_id':r['source_id'],'source_url':r['source_url'],'broken_url':r['linked_url'],'expected_file':Path(urllib.parse.urlparse(r['linked_url']).path).name,'file_type':Path(urllib.parse.urlparse(r['linked_url']).path).suffix,'media_status':'not found through public URL','repository_status':'not found in deployed runtime theme','recommended_action':'Locate approved original in media backup/Squarespace; restore or remove only after editorial approval','implementation_status':'unresolved—do not fabricate','notes':'Repeated source occurrences may reference the same missing file'} for r in links if r['issue_type']=='broken_download'])
write('redirecting-internal-link-remediation.csv',['source_type','source_id','source_url','old_linked_url','final_url','http_status','recommended_action','implementation_status','notes'],[
 {'source_type':r['source_type'],'source_id':r['source_id'],'source_url':r['source_url'],'old_linked_url':r['linked_url'],'final_url':r['final_url'],'http_status':r['http_status'],'recommended_action':'Update stored source to final canonical URL after backup','implementation_status':'not changed—backup/authentication required','notes':'Re-test after approved edit'} for r in links if r['issue_type']=='redirect'])

tests=[('Homepage','/'),('Books','/books/'),('One product','/product/adventures-of-charlotte-and-henry-the-mariana-trench-hardcover/'),('One blog post','/science-books-for-kids-that-feel-like-adventures/'),('Explorer Expedition Guides','/teachers/'),('One topic hub','/teachers/#science'),('One destination hub','/teachers/#mariana-trench'),('About','/about/'),('Contact','/contact/'),('Cart','/cart/'),('Checkout','/checkout/'),('Search','/?s=adventure'),('One taxonomy archive','/category/uncategorized/')]
protect=[]
for label,path in tests:
    m=audit.page_meta(audit.STAGE+path.split('#')[0]); body=m['body']
    og=re.search(r'<meta[^>]+property=["\']og:url["\'][^>]+content=["\']([^"\']+)',body,re.I)
    protect.append({'URL':audit.STAGE+path,'HTTP Status':m['status'],'Robots':m['robots'],'Canonical':m['canonical'],'Open Graph URL':og.group(1) if og else '','Auth Protected':'no','Indexable':'yes' if m['indexable'] else 'no','Result':'FAIL' if m['indexable'] else 'PASS','Notes':label+'; section anchors inherit /teachers/ metadata' if '#' in path else label})
write('staging-protection-validation.csv',['URL','HTTP Status','Robots','Canonical','Open Graph URL','Auth Protected','Indexable','Result','Notes'],protect)

# Final inventories preserve audit evidence; final classifications replace unexplained outcomes.
shutil.copyfile(OUT/'old-production-url-inventory.csv',OUT/'old-production-url-inventory-final.csv')
shutil.copyfile(OUT/'new-staging-url-inventory.csv',OUT/'new-staging-url-inventory-final.csv')
write('url-comparison-report-final.csv',['old_url','new_url','classification','relevance','action','status_code','review_required','notes'],[
 {'old_url':r['Old URL'],'new_url':r['New URL'],'classification':r['Final Classification'],'relevance':r['Relevance Score'],'action':r['Reason'],'status_code':'301' if r['Final Classification']=='REDIRECT_APPROVED' else '','review_required':'yes' if 'REVIEW' in r['Final Classification'] or r['Final Classification']=='RESTORE_OR_IMPORT' else 'no','notes':r['Notes']} for r in resolved])
approved={r['Old URL']:r for r in review if r['Decision']=='APPROVED'}
write('bhp-redirect-map-final.csv',['old_url','new_url','redirect_type','reason','review_status'],[
 {'old_url':urllib.parse.urlparse(r['Old URL']).path,'new_url':urllib.parse.urlparse(r['Proposed Target']).path or '/','redirect_type':'301','reason':r['Reason'],'review_status':'approved'} for r in approved.values()])
valid=rows('redirect-validation-report.csv')
write('redirect-validation-report-final.csv',list(valid[0].keys()) if valid else ['old_url'],[r for r in valid if any(urllib.parse.urlparse(x['Old URL']).path==r['old_url'] for x in approved.values())])
write('redirect-exceptions-final.csv',['old_url','classification','reason','required_action'],[
 {'old_url':r['Old URL'],'classification':r['Final Classification'],'reason':r['Reason'],'required_action':'Manual editorial/traffic/backlink decision before launch'} for r in resolved if r['Final Classification'] in ('RESTORE_OR_IMPORT','MANUAL_REVIEW_REQUIRED')])
shutil.copyfile(OUT/'new-hub-seo-checklist.csv',OUT/'new-hub-seo-checklist-final.csv')
shutil.copyfile(OUT/'internal-link-audit.csv',OUT/'internal-link-audit-final.csv')
print(f'reclassified={len(reclass)} resolved={len(resolved)} redirects_reviewed={len(review)} approved={len(approved)} protection_tests={len(protect)}')

if __name__=='__main__': pass
