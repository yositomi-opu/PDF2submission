#!/usr/bin/env python3
"""Offline syntax/regression checks and Moodle-installable plugin ZIPs."""
import argparse
import ast
from pathlib import Path
import re
import subprocess
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parent.parent
BLOCKS = ("pdf2submissionbeta",)
TESTS = ("pdf2_limits.php", "pdf2_page_groups.php", "pdf2_teacher_sheets.php", "pdf2_student_order.php", "pdf2_beta.php", "pdf2_order_privacy.php")

def files(block):
    for path in sorted((ROOT / "plugins/blocks" / block).rglob("*")):
        if path.is_file() and not any(p.startswith(".") or p == "__pycache__" for p in path.relative_to(ROOT / "plugins/blocks" / block).parts) and not path.name.endswith(("~", ".pyc")):
            yield path

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=("check", "package"))
    parser.add_argument("--php", default="php")
    args = parser.parse_args()
    for block in BLOCKS:
        source = ROOT / "plugins/blocks" / block
        if args.action == "check":
            for path in files(block):
                if path.suffix == ".php":
                    subprocess.run([args.php, "-l", str(path)], check=True, stdout=subprocess.DEVNULL)
                elif path.suffix == ".py":
                    ast.parse(path.read_text())
                elif path.name == "install.xml":
                    ET.parse(path)
        else:
            version = re.search(r"release = '([0-9.]+)", (source / "version.php").read_text()).group(1)
            target = ROOT / "dist" / f"{block}-{version}.zip"
            target.parent.mkdir(exist_ok=True)
            with zipfile.ZipFile(target, "w", zipfile.ZIP_DEFLATED) as archive:
                for path in files(block):
                    archive.write(path, Path(block) / path.relative_to(source))
            with zipfile.ZipFile(target) as archive:
                assert archive.testzip() is None
                assert f"{block}/version.php" in archive.namelist()
            print(target.relative_to(ROOT))
    if args.action == "check":
        for test in TESTS:
            subprocess.run([args.php, str(ROOT / "scripts/tests" / test)], check=True)
        print("Syntax and standalone regression checks passed; Moodle integration not run.")

if __name__ == "__main__":
    main()
