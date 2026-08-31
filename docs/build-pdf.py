#!/usr/bin/env python3
"""Render a docs/*.md manual to PDF using the shared print stylesheet.

Usage:
    python3 docs/build-pdf.py docs/MARIA_END_USER_GUIDE.md [docs/maria-user-manual.css]

Requires: python3-markdown, weasyprint.
    pip3 install --break-system-packages markdown weasyprint

The Markdown file's first `# Heading` and the non-blank lines directly under it
become the centred title block; every `##`/`###` becomes a Contents entry. This
mirrors the structure the CSS in docs/*.css was written for.
"""
from __future__ import annotations

import pathlib
import re
import sys

import markdown
from weasyprint import CSS, HTML

DEFAULT_CSS = "docs/maria-user-manual.css"


def split_front_matter(text: str) -> tuple[str, list[str], str]:
    lines = text.splitlines()
    title, subtitle, start = "", [], 0
    for i, line in enumerate(lines):
        if line.startswith("# "):
            title = line[2:].strip()
            j = i + 1
            while j < len(lines) and not lines[j].strip():  # skip blank line(s)
                j += 1
            while j < len(lines) and lines[j].strip() and not lines[j].startswith(("#", "-", ">", "|")):
                subtitle.append(lines[j].strip())
                j += 1
            start = j
            break
    return title, subtitle, "\n".join(lines[start:]).lstrip("\n")


def checkbox_to_glyph(text: str) -> str:
    text = re.sub(r"^(\s*[-*]\s+)\[ \]\s+", r"\1☐ ", text, flags=re.M)
    text = re.sub(r"^(\s*[-*]\s+)\[[xX]\]\s+", r"\1☑ ", text, flags=re.M)
    return text


def build(md_path: pathlib.Path, css_path: pathlib.Path) -> pathlib.Path:
    raw = md_path.read_text(encoding="utf-8")
    title, subtitle, body_md = split_front_matter(raw)
    body_md = checkbox_to_glyph(body_md)

    md = markdown.Markdown(
        extensions=["extra", "toc", "sane_lists", "attr_list"],
        extension_configs={"toc": {"toc_depth": "2-3"}},
    )
    body_html = md.convert(body_md)
    toc_html = getattr(md, "toc", "")

    sub_html = "<br>".join(subtitle)
    document = f"""<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>{title}</title></head>
<body>
<div id="title-block-header">
  <h1>{title}</h1>
  <p>{sub_html}</p>
</div>
<nav id="TOC" role="doc-toc">
{toc_html}
</nav>
{body_html}
</body>
</html>
"""

    out_path = md_path.with_suffix(".pdf")
    HTML(string=document, base_url=str(md_path.parent)).write_pdf(
        out_path, stylesheets=[CSS(filename=str(css_path))]
    )
    return out_path


def main(argv: list[str]) -> int:
    if len(argv) < 2:
        print(__doc__)
        return 2
    md_path = pathlib.Path(argv[1])
    if len(argv) > 2:
        css_path = pathlib.Path(argv[2])
    else:
        # Prefer a stylesheet named after the document (docs/<name>.css),
        # falling back to the shared manual stylesheet.
        sibling = md_path.with_name(md_path.stem.lower().replace("_", "-") + ".css")
        css_path = sibling if sibling.is_file() else pathlib.Path(DEFAULT_CSS)
    if not md_path.is_file():
        print(f"not found: {md_path}", file=sys.stderr)
        return 1
    out = build(md_path, css_path)
    print(f"wrote {out} ({out.stat().st_size // 1024} KB)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
