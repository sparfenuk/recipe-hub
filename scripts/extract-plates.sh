#!/usr/bin/env bash
# Extract plate-only images from recipe card crops.
#
# Pipeline:
#   1. pre-crop  1200x1400+100+0   (drop left ingredient text + bottom instructions)
#   2. contrast  -level 5%,95%     (helps rembg see low-contrast plate edges)
#   3. rembg     isnet-general-use (saliency-based background removal)
#   4. clean     drop alpha blobs < 5000 px  (kills stray text fragments)
#   5. trim      -fuzz 5% -trim    (tight bounding box)
#
# Input:  pdf_pages/recipe_images/*.jpg  (161 right-column recipe crops, 1350x1688)
# Output: pdf_pages/plate_only/*.png     (plate-only, transparent background)
#
# Runs on the Windows host (not inside Sail).
#
# Prerequisites (one-time):
#   python -m pip install --user "rembg[cli]" "rembg[cpu]"
#   ImageMagick 7+ on PATH
#
# Usage from Git Bash on Windows:
#   bash scripts/extract-plates.sh

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
INPUT_DIR="$REPO_ROOT/pdf_pages/recipe_images"
TMP_DIR="$REPO_ROOT/pdf_pages/_tmp_precrop"
OUT_DIR="$REPO_ROOT/pdf_pages/plate_only"

REMBG="${REMBG:-$APPDATA/Python/Python311/Scripts/rembg.exe}"
[ -x "$REMBG" ] || REMBG="rembg"

MODEL="${REMBG_MODEL:-isnet-general-use}"
CROP_GEOM="${CROP_GEOM:-1200x1400+100+0}"
CONTRAST_LEVEL="${CONTRAST_LEVEL:-5%,95%}"
TRIM_FUZZ="${TRIM_FUZZ:-5%}"

mkdir -p "$TMP_DIR" "$OUT_DIR"
rm -f "$TMP_DIR"/*.jpg "$OUT_DIR"/*.png

echo "==> Stage 1/4: pre-crop ($CROP_GEOM) + contrast ($CONTRAST_LEVEL)"
for src in "$INPUT_DIR"/*.jpg; do
  name=$(basename "$src")
  magick "$src" -crop "$CROP_GEOM" +repage -level "$CONTRAST_LEVEL" "$TMP_DIR/$name"
done

echo "==> Stage 2/4: rembg ($MODEL)"
"$REMBG" p -m "$MODEL" "$TMP_DIR" "$OUT_DIR"

echo "==> Stage 3/4: alpha-blob cleanup (drop fragments < 5000 px)"
python "$REPO_ROOT/scripts/clean-alpha.py" "$OUT_DIR"

echo "==> Stage 4/4: trim transparent borders (fuzz=$TRIM_FUZZ)"
magick mogrify -fuzz "$TRIM_FUZZ" -trim +repage "$OUT_DIR"/*.png

echo "==> Stage 5/5: rectangular fallback for known low-contrast failures"
# These slugs have a white/beige plate on a white card background, so rembg's
# saliency model can't find an edge and erases the plate. For each, we overwrite
# the broken transparent PNG with a plain rectangular crop of the original card.
FALLBACKS=(
  040-breakfast-healthy-bowl-with-greek-yogurt
  043-ham-and-cheese-sandwich
  044-tuna-and-avocado-sandwich
  061-buckwheat-bowl-with-feta-and-nuts
  067-wild-forest
  069-seafood-risotto
  072-pasta-in-cream-sauce
  075-healthy-carbonara
  106-steak-with-avocado-sauce-on-a-crispy-buckwheat-bed
  114-green-salad-with-tuna-avocado-and-lime
  120-teriyaki-chicken-with-peanuts
  139-quince-pudding
  172-fanta-stico
)
for slug in "${FALLBACKS[@]}"; do
  magick "$INPUT_DIR/$slug.jpg" -crop "$CROP_GEOM" +repage "$OUT_DIR/$slug.png"
done

rm -rf "$TMP_DIR"

count=$(ls -1 "$OUT_DIR"/*.png 2>/dev/null | wc -l)
echo "==> Done. $count files in $OUT_DIR (${#FALLBACKS[@]} rectangular fallbacks)"
