# Usage: python resources/kintsugi-backdrop.py public/assets/marble.webp 13   (needs numpy pillow scipy; last arg = seed)
# Original backdrop: cloudy white-grey marble broken by glowing gold veins (kintsugi style).
# Single image used as a fixed, cover-sized page backdrop (no tiling needed).
import sys, math, numpy as np
from scipy import ndimage as nd
from PIL import Image, ImageDraw

W, H = 1856, 2268
SS = 2                                   # supersample the vein drawing for smooth edges
rng = np.random.default_rng(int(sys.argv[2]) if len(sys.argv) > 2 else 5)
out = sys.argv[1] if len(sys.argv) > 1 else "kintsugi.webp"

def fnoise(h, w, beta):
    f = np.fft.fftfreq(h)[:, None]; g = np.fft.fftfreq(w)[None, :]
    r = np.sqrt(f * f + g * g); r[0, 0] = 1
    amp = r ** (-beta); amp[0, 0] = 0
    n = np.real(np.fft.ifft2(amp * np.exp(2j * np.pi * rng.random((h, w)))))
    return (n - n.min()) / (n.max() - n.min())

# ---- cloudy white-grey marble base ----
c1, c2, c3 = fnoise(H, W, 2.3), fnoise(H, W, 1.6), fnoise(H, W, 2.8)
light = np.array([.955, .957, .965]); grey = np.array([.72, .74, .77]); mist = np.array([1, 1, 1.0])
t = np.clip((c1 - .3) * 1.5, 0, 1)[..., None]
img = light * (1 - t * .7) + grey * (t * .7)
grain = fnoise(H, W, 1.0)[..., None]
img = img * (1 + (grain - .5) * .035)
img = img * (1 + (c2[..., None] - .5) * .05)
m = np.clip((c3 - .6) * 2.5, 0, 1)[..., None] * .5
img = img * (1 - m) + mist * m

# ---- vein paths (random walks), drawn as width-varying jagged strokes ----
def walk(x, y, ang, length, step, wob, jag):
    pts = [(x, y)]; base = ang
    for _ in range(int(length / step)):
        ang += rng.normal(0, wob)
        if rng.random() < .18: ang += rng.choice([-1, 1]) * rng.uniform(.35, .8)   # zig-zag kink
        ang = base + max(-.7, min(.7, ang - base))                                # keep the overall direction
        x += math.cos(ang) * step + rng.normal(0, jag); y += math.sin(ang) * step + rng.normal(0, jag)
        pts.append((x, y))
    return pts

canvas = Image.new("L", (W * SS, H * SS), 0)
dr = ImageDraw.Draw(canvas)
def stroke(pts, w0, w1, taper=True):
    n = len(pts)
    for i in range(n - 1):
        k = i / max(n - 2, 1)
        w = w0 + (w1 - w0) * (0.5 + 0.5 * math.sin(k * math.pi * rng.uniform(1.5, 3)))
        if taper: w *= min(1, (k + .08) * 6, (1 - k + .08) * 6)
        w = max(w * rng.uniform(.75, 1.3), .6)
        a, b = pts[i], pts[i + 1]
        dr.line([(a[0] * SS, a[1] * SS), (b[0] * SS, b[1] * SS)], fill=255, width=max(1, int(w * SS)))
        r = w * SS / 2
        dr.ellipse([b[0] * SS - r, b[1] * SS - r, b[0] * SS + r, b[1] * SS + r], fill=255)

mains = []
# two bold diagonal cracks across the page + a few secondary ones
for (x, y, a, L, w0, w1) in [(-40, 520, .9, 2900, 6, 15), (120, -40, .35, 2100, 4, 10), (W * .55, -40, 1.75, 900, 2.5, 6),
                             (-40, 1750, -.15, 1100, 2.5, 6), (W * .78, H * .55, 1.5, 1200, 2.5, 7)]:
    p = walk(x, y, a, L, 16, .1, 2.2); mains.append(p); stroke(p, w0, w1)
# branches off the main cracks
for p in mains:
    for _ in range(rng.integers(3, 6)):
        k = rng.integers(10, len(p) - 10); bx, by = p[k]
        sub = walk(bx, by, rng.uniform(0, 2 * math.pi), rng.uniform(180, 520), 12, .14, 1.4)
        stroke(sub, .9, 2.6)
        for _ in range(rng.integers(0, 3)):  # fine hairlines off the branches
            k2 = rng.integers(1, len(sub) - 1); hx, hy = sub[k2]
            stroke(walk(hx, hy, rng.uniform(0, 2 * math.pi), rng.uniform(80, 240), 9, .2, 1), .5, 1.1)
# scattered faint hairline web
for _ in range(22):
    stroke(walk(rng.uniform(0, W), rng.uniform(0, H), rng.uniform(0, 2 * math.pi), rng.uniform(150, 480), 10, .2, 1.2), .4, 1.0)

vein = np.asarray(canvas.resize((W, H), Image.LANCZOS)).astype(np.float32) / 255

# ---- gold: metallic variation, hot highlights, warm glow ----
gnoise = fnoise(H, W, 1.3); hot = fnoise(H, W, 2.2)
dark = np.array([.62, .42, .10]); mid = np.array([.86, .63, .20]); bright = np.array([1.0, .86, .42]); white = np.array([1.0, .97, .82])
tt = gnoise[..., None]
gold = np.where(tt < .5, dark + (mid - dark) * tt / .5, mid + (bright - mid) * (tt - .5) / .5)
hl = np.clip((hot - .55) * 3.0, 0, 1)[..., None]                    # glowing stretches along the vein
gold = gold * (1 - hl * .8) + white * hl * .8

glow = nd.gaussian_filter(vein, 8) * 1.7 + nd.gaussian_filter(vein, 26) * 0.7
glow = np.clip(glow * (0.55 + hl[..., 0] * 1.6), 0, 1)[..., None]
img = img * (1 - glow * .6) + np.array([1.0, .84, .45]) * glow * .6   # warm light bleeding into the stone

# gold dust near the cracks
dust = (rng.random((H, W)) > .9965).astype(np.float32) * np.clip(nd.gaussian_filter(vein, 14) * 8, 0, 1)
dust = np.clip(nd.gaussian_filter(dust, .7) * 5, 0, 1)[..., None] * .8
img = img * (1 - dust) + bright * dust

a = vein[..., None]
img = img * (1 - a) + gold * a

Image.fromarray((np.clip(img, 0, 1) * 255).astype(np.uint8)).save(out, quality=82, method=6)
Image.open(out).resize((464, 567)).save(out.replace(".webp", "_prev.jpg"), quality=88)
import os; print(os.path.getsize(out) // 1024, "KB")
