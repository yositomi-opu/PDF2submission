#!/usr/bin/python3
# SPDX-License-Identifier: GPL-3.0-or-later
"""Local-only PDF processing. No shell commands and no network requests."""
import json
import os
from pathlib import Path
import resource
import subprocess
import sys


class ProcessingError(Exception):
    def __init__(self, code, page=0):
        super().__init__(code)
        self.code = code
        self.page = page


def run(root):
    root = Path(root)
    resource.setrlimit(resource.RLIMIT_CPU, (900, 900))
    resource.setrlimit(resource.RLIMIT_AS, (1024**3, 1024**3))
    reader = PdfReader(root / 'source.pdf')
    if reader.is_encrypted:
        raise ProcessingError('encrypted')
    if not reader.pages:
        raise ProcessingError('empty')
    pages = []
    for number, page in enumerate(reader.pages, 1):
        if max(float(page.mediabox.width), float(page.mediabox.height)) > 1700:
            raise ProcessingError('pagesize', number)
        writer = PdfWriter()
        writer.add_page(page)
        with (root / f'page-{number}.pdf').open('wb') as output:
            writer.write(output)
        png = root / f'page-{number}.png'
        try:
            subprocess.run(['/usr/bin/gs', '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE',
                        '-sDEVICE=pnggray', '-r300', '-dNumRenderingThreads=1',
                        f'-sOutputFile={png}', str(root / f'page-{number}.pdf')],
                       check=True, capture_output=True, timeout=15)
        except FileNotFoundError:
            raise ProcessingError('ghostscriptmissing', number)
        except subprocess.TimeoutExpired:
            raise ProcessingError('pagetimeout', number)
        except subprocess.CalledProcessError:
            raise ProcessingError('renderfailed', number)
        try:
            result = subprocess.run(['/usr/bin/zbarimg', '--quiet', '--raw',
                                 '--set', '*.disable', '--set', 'qrcode.enable', str(png)],
                                capture_output=True, timeout=10)
        except FileNotFoundError:
            raise ProcessingError('zbarmissing', number)
        except subprocess.TimeoutExpired:
            raise ProcessingError('pagetimeout', number)
        if result.returncode not in (0, 4):
            raise ProcessingError('qrfailed', number)
        codes = sorted(set(result.stdout.decode('utf-8', errors='replace').splitlines()))
        pages.append({'page': number, 'codes': codes})
        png.unlink()
    return {'pages': pages}


def merge(root):
    root = Path(root)
    groups = json.loads((root / 'groups.json').read_text())
    for user, pages in groups.items():
        if not str(user).isdigit() or not pages:
            raise ValueError('Invalid user group.')
        writer = PdfWriter()
        for number in pages:
            if not isinstance(number, int) or number < 1:
                raise ValueError('Invalid page number.')
            writer.add_page(PdfReader(root / f'page-{number}.pdf').pages[0])
        with (root / f'user-{user}.pdf').open('wb') as output:
            writer.write(output)
    return {'ok': True}


if __name__ == '__main__':
    os.umask(0o077)
    try:
        from pypdf import PdfReader, PdfWriter
        result = run(sys.argv[2]) if sys.argv[1] == 'scan' else merge(sys.argv[2])
        print(json.dumps(result))
    except Exception as error:
        code = error.code if isinstance(error, ProcessingError) else ('pypdfmissing' if isinstance(error, ModuleNotFoundError) else 'invalidpdf')
        print(json.dumps({'error': str(error), 'errorcode': code, 'page': getattr(error, 'page', 0)}))
        sys.exit(1)
