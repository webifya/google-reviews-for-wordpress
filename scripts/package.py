#!/usr/bin/env python3
"""Build the Menjar Financial-only plugin without legacy source or collector code."""
from pathlib import Path
import zipfile,sys
root=Path(__file__).resolve().parents[1]
out=Path(sys.argv[1]) if len(sys.argv)>1 else root.parent/'menjar-financial-reviews-v1.7.0.zip'
files=['google-reviews-for-wordpress.php','uninstall.php','readme.txt','LICENSE','API-SETUP.md']
files+=['includes/'+x+'.php' for x in ['Security','Cache','Installer','Locations','GoogleBusiness','Widgets','Project','Renderer','Admin','Plugin']]
files+=['assets/'+x for x in ['admin.js','admin.css','frontend.js','frontend.css','block.js']]
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for name in files:
  f=root/name
  if not f.is_file():raise SystemExit('Missing required package file: '+name)
  info=zipfile.ZipInfo('google-reviews-for-wordpress/'+name,date_time=(2026,10,14,0,0,0));info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED;z.writestr(info,f.read_bytes())
with zipfile.ZipFile(out) as z:assert z.testzip() is None
print(out.resolve())
