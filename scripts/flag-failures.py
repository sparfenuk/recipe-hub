"""Sort plate_only PNGs by trimmed area to surface likely failures.

When the plate vanishes (e.g. white plate on white card background), rembg
preserves only stray food/garnish pixels, so the trimmed bounding box ends
up much smaller than a healthy plate output.
"""

from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image

OUT_DIR = Path("//wsl.localhost/ubuntu-22.04/home/sparf/recipe-hub/pdf_pages/plate_only")


def main() -> None:
    rows = []
    for p in sorted(OUT_DIR.glob("*.png")):
        im = Image.open(p)
        w, h = im.size
        arr = np.array(im)
        alpha = arr[..., 3] if arr.shape[-1] == 4 else None
        coverage = (alpha > 8).sum() / (w * h) if alpha is not None else 1.0
        rows.append((p.name, w, h, w * h, coverage))

    rows.sort(key=lambda r: r[3])

    print(f"{'file':62s}  {'wxh':>11s}  {'area':>10s}  {'cov':>5s}")
    print("-" * 95)
    for name, w, h, area, cov in rows[:40]:
        print(f"{name:62s}  {w:4d}x{h:<5d}  {area:>10,d}  {cov:5.2f}")

    print()
    print(f"--- Stats (n={len(rows)}) ---")
    areas = [r[3] for r in rows]
    print(f"min area={min(areas):,}  max={max(areas):,}  median={sorted(areas)[len(areas)//2]:,}")


if __name__ == "__main__":
    main()
