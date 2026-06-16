#!/usr/bin/env python3
"""Fix mojibake in markdown files and save as UTF-8 with BOM for Windows PDF tools."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

# UTF-8 bytes mis-decoded as Windows-1252 (common in PowerShell / older editors)
MOJIBAKE = [
    ("\u00e2\u20ac\u201d", "-"),      # em dash (—)
    ("\u00e2\u20ac\u201c", "-"),      # en dash variant
    ("\u00e2\u20ac\u2019", "'"),      # apostrophe (')
    ("\u00e2\u20ac\u0153", '"'),      # left double quote (")
    ("\u00e2\u20ac\u009d", '"'),      # right double quote (") alt
    ("\u00e2\u20ac\u009c", '"'),      # left double quote alt
    ("\u00e2\u2020\u2019", "->"),     # arrow (→)
    ("\u00e2\u20ac\u00a6", "..."),    # ellipsis
    ("\u00e2\u20ac\u2011", "-"),      # non-breaking hyphen
    ("\u00e2\u20ac\u2122", "'"),      # apostrophe (') mojibake
    ("\u00e2\u20ac\u02dc", "'"),      # apostrophe alt
    ("â€™", "'"),
    ("â€'", "-"),
    ("Wiâ€'Fi", "Wi-Fi"),
    ("Wiâ€\u2011Fi", "Wi-Fi"),
]

# Normalize any correct Unicode punctuation to ASCII for reliable PDF export
ASCII_SAFE = [
    ("\u2014", "-"),
    ("\u2013", "-"),
    ("\u2192", "->"),
    ("\u201c", '"'),
    ("\u201d", '"'),
    ("\u2018", "'"),
    ("\u2019", "'"),
    ("\u2011", "-"),
    ("\u2026", "..."),
    ("\u00a0", " "),  # non-breaking space
]

FILES = [
    ROOT / "docs" / "USER_GUIDE.md",
    ROOT / "docs" / "WORKFLOW_SUMMARY.md",
    ROOT / "docs" / "WORKFLOW_DIAGRAM.md",
]


def fix_file(path: Path) -> bool:
    if not path.exists():
        return False
    text = path.read_text(encoding="utf-8")
    original = text
    for old, new in MOJIBAKE + ASCII_SAFE:
        text = text.replace(old, new)
    text = text.replace("Wi-Fi", "Wi-Fi")  # already ASCII
    path.write_text(text, encoding="utf-8-sig")
    return text != original


def main() -> None:
    for f in FILES:
        if not f.exists():
            print(f"{f.name}: skip (not found)")
            continue
        changed = fix_file(f)
        # Report leftover mojibake marker
        text = f.read_text(encoding="utf-8-sig")
        if "\u00e2" in text and "â" in text:
            print(f"{f.name}: updated (warning: may still contain â)")
        else:
            print(f"{f.name}: {'fixed' if changed else 'saved UTF-8 BOM only'}")


if __name__ == "__main__":
    main()
