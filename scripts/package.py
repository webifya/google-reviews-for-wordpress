#!/usr/bin/env python3
"""Build a reproducible installable plugin ZIP; excludes all development artifacts."""
from pathlib import Path
import zipfile,sys
root=Path(__file__).resolve().parents[1]
out=Path(sys.argv[1]) if len(sys.argv)>1 else root.parent/'google-reviews-for-wordpress-v1.0.0.zip'
include=['google-reviews-for-wordpress.php','uninstall.php','readme.txt','LICENSE','includes','assets','languages']
files=[]
for name in include:
 p=root/name
 if not p.exists():raise SystemExit('Missing required package input: '+name)
 files.extend(sorted(p.rglob('*')) if p.is_dir() else [p])
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for f in files:
  if not f.is_file():continue
  info=zipfile.ZipInfo('google-reviews-for-wordpress/'+str(f.relative_to(root)),date_time=(2026,10,9,0,0,0));info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED
  z.writestr(info,f.read_bytes())
with zipfile.ZipFile(out) as z:
 assert z.testzip() is None
 assert 'google-reviews-for-wordpress/assets/frontend.js' in z.namelist()
 assert all(n.startswith('google-reviews-for-wordpress/') for n in z.namelist())
print(out.resolve())
