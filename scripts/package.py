#!/usr/bin/env python3
"""Build a reproducible installable plugin ZIP; excludes all development artifacts."""
from pathlib import Path
import zipfile,sys,json
root=Path(__file__).resolve().parents[1]
out=Path(sys.argv[1]) if len(sys.argv)>1 else root.parent/'google-reviews-for-wordpress-v1.5.0.zip'
include=['google-reviews-for-wordpress.php','uninstall.php','readme.txt','LICENSE','includes','assets','languages']
worker=['collector.cjs','local.cjs','run.cjs','README.md','package.json','package-lock.json']
files_worker=[root/'worker'/name for name in worker]
for name in ['playwright','playwright-core']:
 base=root/'node_modules'/name
 if not (base/'package.json').is_file():raise SystemExit('Run npm ci before packaging the bundled collector')
 expected=json.loads((root/'worker/package.json').read_text())['dependencies']['playwright']
 if json.loads((base/'package.json').read_text())['version']!=expected:raise SystemExit('Unexpected browser runtime version: '+name)
 files_worker.extend(sorted(base.rglob('*')))
files=[]
for name in include:
 p=root/name
 if not p.exists():raise SystemExit('Missing required package input: '+name)
 files.extend(sorted(p.rglob('*')) if p.is_dir() else [p])
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for f in files+files_worker:
  if not f.is_file():continue
  rel=str(f.relative_to(root))
  if rel.startswith('node_modules/'):rel='worker/'+rel
  info=zipfile.ZipInfo('google-reviews-for-wordpress/'+rel,date_time=(2026,10,9,0,0,0));info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED
  z.writestr(info,f.read_bytes())
with zipfile.ZipFile(out) as z:
 assert z.testzip() is None
 assert 'google-reviews-for-wordpress/assets/frontend.js' in z.namelist()
 assert all(n.startswith('google-reviews-for-wordpress/') for n in z.namelist())
print(out.resolve())
