#!/usr/bin/env python3
"""Remove small floating alpha blobs from rembg output PNGs.

Keeps only blobs whose area is at least MIN_AREA pixels. This drops stray text
fragments (a few hundred px) while preserving the plate (~500k px), basil leaf
(~10-30k px), and utensils.
"""

from __future__ import annotations

import sys
from pathlib import Path

import numpy as np
from PIL import Image
from scipy import ndimage

MIN_AREA = 5000  # blobs smaller than this are erased


def clean(path: Path) -> str:
    im = Image.open(path).convert("RGBA")
    arr = np.array(im)
    alpha = arr[..., 3]
    mask = alpha > 8  # treat near-transparent pixels as background

    labeled, num = ndimage.label(mask)
    if num <= 1:
        return f"  {path.name}: {num} blob(s) — unchanged"

    sizes = ndimage.sum(mask, labeled, range(1, num + 1))
    keep_labels = np.where(sizes >= MIN_AREA)[0] + 1
    keep = np.isin(labeled, keep_labels)

    new_alpha = np.where(keep, alpha, 0).astype(np.uint8)
    arr[..., 3] = new_alpha
    Image.fromarray(arr, "RGBA").save(path, optimize=True)

    dropped = num - len(keep_labels)
    return f"  {path.name}: kept {len(keep_labels)}/{num} blobs (dropped {dropped})"


def main(argv: list[str]) -> int:
    if len(argv) < 2:
        print("usage: clean-alpha.py FILE [FILE ...]", file=sys.stderr)
        return 1
    for arg in argv[1:]:
        p = Path(arg)
        if p.is_dir():
            for f in sorted(p.glob("*.png")):
                print(clean(f))
        else:
            print(clean(p))
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
