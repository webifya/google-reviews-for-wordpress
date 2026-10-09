#!/usr/bin/env python3
"""Reproducible worker ZIP. No credentials, node_modules, reviews or screenshots."""
from pathlib import Path
import sys, zipfile
root=Path(__file__).resolve().parents[1]/'worker'
out=Path(sys.argv[1]) if len(sys.argv)>1 else root.parent.parent/'google-reviews-browser-worker-v1.5.0.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for name in ['collector.cjs','run.cjs','README.md','package.json','package-lock.json','LICENSE']:
  path=(root.parent/'LICENSE') if name=='LICENSE' else root/name
  if not path.is_file(): raise SystemExit('Missing worker input: '+name)
  info=zipfile.ZipInfo('google-reviews-browser-worker/'+name,date_time=(2026,10,9,0,0,0));info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED
  z.writestr(info,path.read_bytes())
with zipfile.ZipFile(out) as z: assert z.testzip() is None
print(out.resolve())
