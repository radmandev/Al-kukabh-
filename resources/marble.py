# Regenerate: python3 -m venv .venv && .venv/bin/pip install numpy pillow scipy && .venv/bin/python resources/marble.py  → copy marble.webp to public/assets/
# Seamless cream marble with gold-leaf veins (tileable: FFT noise + toroidal Voronoi + wrap-mode filters).
import numpy as np
from scipy import ndimage as nd
from PIL import Image
rng = np.random.default_rng(23)
N = 1200

def fnoise(beta, lo=0):
    """periodic fractal noise in [0,1] via 1/f^beta spectrum (FFT is inherently tileable)"""
    f = np.fft.fftfreq(N)[:, None]; g = np.fft.fftfreq(N)[None, :]
    r = np.sqrt(f * f + g * g); r[0, 0] = 1
    amp = r ** (-beta); amp[r < lo] = 0; amp[0, 0] = 0
    ph = np.exp(2j * np.pi * rng.random((N, N)))
    n = np.real(np.fft.ifft2(amp * ph))
    return (n - n.min()) / (n.max() - n.min())

yy, xx = np.mgrid[0:N, 0:N].astype(np.float32)
# domain warp → organic, wobbly cell borders
wx = (fnoise(2.2) - .5) * 70; wy = (fnoise(2.2) - .5) * 70
wx2 = (fnoise(1.6) - .5) * 14; wy2 = (fnoise(1.6) - .5) * 14
wx3 = (fnoise(1.1) - .5) * 9; wy3 = (fnoise(1.1) - .5) * 9
X = (xx + wx + wx2 + wx3) % N; Y = (yy + wy + wy2 + wy3) % N

# toroidal Voronoi: F2-F1 is small along cell borders
K = 26
seeds = rng.random((K, 2)) * N
seeds[:, 1] = (seeds[:, 1] * .8 + seeds[:, 0] * .2) % N  # slight diagonal drift like the reference
d1 = np.full((N, N), 1e9, np.float32); d2 = d1.copy()
for sx, sy in seeds:
    dx = np.abs(X - sx); dx = np.minimum(dx, N - dx)
    dy = np.abs(Y - sy); dy = np.minimum(dy, N - dy)
    d = np.sqrt(dx * dx + dy * dy)
    m = d < d1
    d2 = np.where(m, d1, np.minimum(d2, d)); d1 = np.where(m, d, d1)
edge = d2 - d1

# vein strength varies along its length; some segments fade out (broken, natural look)
along = fnoise(1.9)
pool = fnoise(1.7)
width = 0.9 + 2.2 * fnoise(2.0) + 5.5 * np.clip(pool - .72, 0, 1) / .28
vein = np.clip(1 - edge / width, 0, 1) ** 1.6
vein *= np.clip((along - .28) * 2.2, 0, 1)
# a long diagonal main vein (ridged noise, stretched along the diagonal)
rid = fnoise(2.4); rid2 = np.roll(rid, 0, axis=0)
diag = (xx + yy) / N
main = np.clip(1 - np.abs(np.sin((diag + rid * .9) * np.pi)) / .03, 0, 1) ** 1.4
main *= np.clip((fnoise(2.0) - .35) * 2.5, 0, 1)
vein = np.maximum(vein, main * .9)

# soft, blurred secondary veins underneath (depth)
ghost = nd.gaussian_filter(np.clip(1 - edge / 9, 0, 1) * fnoise(1.8), 6, mode='wrap')

def streaks():
    f = np.fft.fftfreq(N)[:, None]; g = np.fft.fftfreq(N)[None, :]
    r = np.sqrt(f * f + g * g); r[0, 0] = 1
    across = np.abs(f - g) / np.sqrt(2)  # energy only across the diagonal → wisps run along it
    amp = r ** (-1.7) * np.exp(-(across / (np.abs(f + g) / np.sqrt(2) * .25 + 1e-4)) ** 2); amp[0, 0] = 0
    n = np.real(np.fft.ifft2(amp * np.exp(2j * np.pi * rng.random((N, N)))))
    return (n - n.min()) / (n.max() - n.min())
wisp = streaks()
# cloudy cream base
cloud = fnoise(2.6); cloud2 = fnoise(1.4)
base = np.array([246, 238, 222], np.float32)
img = base[None, None, :] * (1 + (cloud[..., None] - .5) * .07 + (cloud2[..., None] - .5) * .025)
milk = np.array([252, 248, 239], np.float32)
img = img * (1 - cloud[..., None] * .35) + milk * cloud[..., None] * .35
tan = np.array([214, 190, 150], np.float32)
img = img * (1 - ghost[..., None] * .5) + tan * ghost[..., None] * .5
w = np.clip((wisp - .55) * 2.4, 0, 1)[..., None] * .22
img = img * (1 - w) + np.array([226, 208, 176], np.float32) * w

# gold leaf: metallic banding + bright flecks + darker edges
band = fnoise(1.2)
gold_dark = np.array([176, 128, 44], np.float32)
gold_mid = np.array([222, 172, 72], np.float32)
gold_hi = np.array([255, 242, 178], np.float32)
t = band[..., None]
gold = np.where(t < .5, gold_dark + (gold_mid - gold_dark) * (t / .5), gold_mid + (gold_hi - gold_mid) * ((t - .5) / .5))
fleck = (rng.random((N, N)) > .975).astype(np.float32)
fleck = nd.gaussian_filter(fleck, .8, mode='wrap') * 6
near = nd.gaussian_filter(vein, 3, mode='wrap')
spark = np.clip(fleck * near * 3, 0, 1)[..., None]
gold = gold * (1 - spark) + np.array([255, 248, 214], np.float32) * spark
halo = nd.gaussian_filter(vein, 2.2, mode='wrap')[..., None] * .35
img = img * (1 - halo) + gold_mid * halo
a = np.clip(vein * 1.15, 0, 1)[..., None] * .95
img = img * (1 - a) + gold * a
# loose gold-leaf flakes drifting off the veins
flakes = nd.gaussian_filter((rng.random((N, N)) > .9993).astype(np.float32), .9, mode='wrap') * 4
flakes = np.clip(flakes * np.clip(near * 6, 0, 1), 0, 1)[..., None] * .8
img = img * (1 - flakes) + gold_hi * flakes

Image.fromarray(np.clip(img, 0, 255).astype(np.uint8)).save("marble.webp", quality=82, method=6)
im = Image.open("marble.webp"); t2 = Image.new("RGB", (N * 2, N * 2))
for i in range(2):
    for j in range(2): t2.paste(im, (i * N, j * N))
t2.resize((1200, 1200)).save("marble_tiled.jpg", quality=85)
im.crop((300, 300, 762, 743)).save("marble_crop.jpg", quality=90)
import os; print(os.path.getsize("marble.webp") // 1024, "KB")
