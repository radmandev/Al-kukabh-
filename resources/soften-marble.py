# Usage: python resources/soften-marble.py photo.jpg public/assets/marble.webp 0.62 0.24 1856 noseam 0.45
#   args: gold fade (0-1), cream wash (0-1), width px, "noseam" = single backdrop image (no tiling), gold sheen (0-1)
# Needs: numpy pillow scipy
# Soften a marble photo (fainter gold veins, cream wash) and make it a seamless tile.
import sys, numpy as np
from scipy import ndimage as nd
from PIL import Image
src, out, k_gold, wash = sys.argv[1], sys.argv[2], float(sys.argv[3]), float(sys.argv[4])
im = Image.open(src).convert("RGB")
W = int(sys.argv[5]) if len(sys.argv) > 5 else 1300; im = im.resize((W, round(im.height * W / im.width)), Image.LANCZOS)
a = np.asarray(im).astype(np.float32) / 255
lum = a.mean(2)
light = nd.gaussian_filter(lum, 140, mode='reflect')
a = np.clip(a * (lum.mean() / (light + 1e-6))[..., None], 0, 1)

# 1) fade the gold veins toward the surrounding marble
mx, mn = a.max(2), a.min(2)
sat = (mx - mn) / (mx + 1e-6)
gold = np.clip((sat - .14) / .22, 0, 1)                     # warm, saturated pixels = gold leaf
gold = nd.gaussian_filter(gold, 1.2)
around = np.stack([nd.gaussian_filter(a[..., c], 9) for c in range(3)], 2)
calm = np.stack([nd.gaussian_filter(a[..., c], 1.0) for c in range(3)], 2)  # tiny blur for a soft, not crisp, edge
m = (gold * k_gold)[..., None]
a = (a * .5 + calm * .5) * (1 - m) + around * m

g = a.mean(2, keepdims=True)
a = g + (a - g) * .7
# 2) gentle cream wash and slight lift
cream = np.array([.925, .885, .815])
a = a * (1 - wash) + cream * wash

SEAMLESS = len(sys.argv) <= 6 or sys.argv[6] != 'noseam'
# 2b) gold sheen: lift and warm the remaining vein pixels so they glint like gold leaf
GLOW = float(sys.argv[7]) if len(sys.argv) > 7 else 0
if GLOW:
    gm = (nd.gaussian_filter(gold, .8) * GLOW)[..., None]
    shine = np.array([.9, .72, .36])                    # warm metallic gold
    a = a * (1 - gm * .55) + shine * gm * .55
    spec = np.clip((gold - .6) / .4, 0, 1)[..., None] * GLOW * .35   # brightest cores get a pale highlight
    a = a * (1 - spec) + np.array([1.0, .97, .84]) * spec
# 3) seamless, one axis at a time: blend with a copy shifted half a tile along that axis.
#    The copy's seam sits mid-tile where the original has full weight, so it never shows.
def ramp(n):
    d = np.minimum(np.arange(n), n - 1 - np.arange(n)) / (n * .38)
    w = np.clip(d, 0, 1)
    return w * w * (3 - 2 * w)
H = a.shape[0]
if SEAMLESS:
  wx = ramp(W)[None, :, None]
  a = a * wx + np.roll(a, W // 2, 1) * (1 - wx)
  wy = ramp(H)[:, None, None]
  a = a * wy + np.roll(a, H // 2, 0) * (1 - wy)

img = Image.fromarray((np.clip(a, 0, 1) * 255).astype(np.uint8))
img.save(out, quality=80, method=6)
t = Image.new("RGB", (W * 2, H * 2))
for i in range(2):
    for j in range(2): t.paste(img, (i * W, j * H))
t.resize((W, H)).save(out.replace(".webp", "_tiled.jpg"), quality=85)
import os; print(img.size, os.path.getsize(out) // 1024, "KB")
