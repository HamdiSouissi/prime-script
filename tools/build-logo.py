"""Génère les déclinaisons du logo Prime Vision Solutions pour l'application.

Source : visuel carré fourni (fond #F5F8FC opaque, sans transparence).
Sortie  : PNG transparents, recadrés sur le contenu réel et calés sur l'usage.

    python3 tools/build-logo.py
"""
from PIL import Image, ImageDraw, ImageFilter
import os

SRC = 'tools/brand/logo-source.png'
OUT = 'public/assets/img/'

BG = (245, 248, 252)
WHITE = (244, 247, 252)    # pendant clair du bleu nuit, pour les fonds sombres


def cutout(image):
    """Détoure le fond uni : alpha = 1 - min(C/bg), puis « dé-mélange » de l'antialiasing."""
    rgb = image.convert('RGB')
    w, h = rgb.size
    src = rgb.load()
    out = Image.new('RGBA', (w, h))
    dst = out.load()
    for y in range(h):
        for x in range(w):
            r, g, b = src[x, y]
            a = 1.0 - min(r / BG[0], g / BG[1], b / BG[2])
            if a <= 0.004:
                dst[x, y] = (0, 0, 0, 0)
                continue
            a = min(1.0, a)
            # couleur d'origine retrouvée : C = a*F + (1-a)*bg
            fr = (r - (1 - a) * BG[0]) / a
            fg = (g - (1 - a) * BG[1]) / a
            fb = (b - (1 - a) * BG[2]) / a
            dst[x, y] = (
                max(0, min(255, int(round(fr)))),
                max(0, min(255, int(round(fg)))),
                max(0, min(255, int(round(fb)))),
                int(round(a * 255)),
            )
    return out


def recolor_dark(image):
    """Version fond sombre : le bleu nuit devient blanc cassé, le dégradé bleu s'éclaircit.

    Les pixels de bord (alpha partiel) sont repeints à partir de leur voisinage :
    sans ça, l'anti-aliasing du visuel d'origine laisse des halos gris.
    """
    w, h = image.size
    src = image.load()
    solid = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    dst = solid.load()
    for y in range(h):
        for x in range(w):
            r, g, b, a = src[x, y]
            if a < 200:
                continue
            t = max(0.0, min(1.0, (b - r) / 200.0))
            dst[x, y] = (
                int(round(WHITE[0] * (1 - t) + 66 * t)),
                int(round(WHITE[1] * (1 - t) + 153 * t)),
                int(round(WHITE[2] * (1 - t) + 255 * t)),
                a,
            )

    # Propage la couleur des pixels pleins vers la bande anti-aliasée (flou prémultiplié).
    premul = solid.copy()
    px = premul.load()
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            px[x, y] = (r * a // 255, g * a // 255, b * a // 255, a)
    blurred = premul.filter(ImageFilter.GaussianBlur(2.2))
    bp = blurred.load()
    solid_px = solid.load()
    for y in range(h):
        for x in range(w):
            r, g, b, a = bp[x, y]
            if a > 0:
                solid_px[x, y] = (min(255, r * 255 // a), min(255, g * 255 // a), min(255, b * 255 // a), 255)

    # On réapplique l'alpha d'origine (bords doux).
    out = solid.copy()
    op = out.load()
    for y in range(h):
        for x in range(w):
            r, g, b, _ = op[x, y]
            op[x, y] = (r, g, b, src[x, y][3])
    return out


def trim(image, pad=0):
    box = image.split()[3].getbbox()
    if box is None:
        return image
    x0, y0, x1, y1 = box
    x0, y0 = max(0, x0 - pad), max(0, y0 - pad)
    x1, y1 = min(image.width, x1 + pad), min(image.height, y1 + pad)
    return image.crop((x0, y0, x1, y1))


def scaled_h(image, height):
    w = round(image.width * height / image.height)
    return image.resize((w, height), Image.LANCZOS)


src = Image.open(SRC)
transparent = cutout(src)

# Recadrages d'après les boîtes mesurées sur le visuel d'origine.
mark_box = (543, 490, 1079, 837)
lockup_box = (226, 490, 1376, 1112)
mark = trim(transparent.crop(mark_box), 2)
lockup = trim(transparent.crop(lockup_box), 2)
print('marque', mark.size, '· verrou vertical', lockup.size)

# --- Versions claires (fonds clairs) -------------------------------------
# Tailles calées sur l'usage réel : un logo affiché à ~197 px de large n'a pas
# besoin d'une source de 1150 px (le poids de la page compte plus que le zoom).
scaled_h(lockup, 300).quantize(colors=128, method=Image.Quantize.FASTOCTREE).save(
    OUT + 'logo-full.png', optimize=True)
scaled_h(mark, 240).quantize(colors=128, method=Image.Quantize.FASTOCTREE).save(
    OUT + 'logo-mark.png', optimize=True)

# --- Versions pour fonds sombres ----------------------------------------
dark_lockup = recolor_dark(lockup)
dark_mark = recolor_dark(mark)
scaled_h(dark_lockup, 300).quantize(colors=128, method=Image.Quantize.FASTOCTREE).save(
    OUT + 'logo-full-dark.png', optimize=True)
scaled_h(dark_mark, 240).quantize(colors=128, method=Image.Quantize.FASTOCTREE).save(
    OUT + 'logo-mark-dark.png', optimize=True)


# --- Version horizontale : marque à gauche, nom à droite -----------------
# Le visuel fourni est vertical (marque au-dessus du nom). L'en-tête de
# l'application est une barre basse : on recompose la même marque et le même
# nom côte à côte, sans rien redessiner.
wordmark = trim(transparent.crop((226, 900, 1376, 1112)), 2)


def horizontal(mark_img, word_img, mark_h=420, gap_ratio=0.30, word_ratio=0.60):
    m = scaled_h(mark_img, mark_h)
    word_h = round(mark_h * word_ratio)
    wd = scaled_h(word_img, word_h)
    gap = round(mark_h * gap_ratio)
    canvas = Image.new('RGBA', (m.width + gap + wd.width, mark_h), (0, 0, 0, 0))
    canvas.alpha_composite(m, (0, 0))
    # nom centré verticalement sur la marque
    canvas.alpha_composite(wd, (m.width + gap, (mark_h - wd.height) // 2))
    return trim(canvas, 4)


hor = horizontal(mark, wordmark, mark_h=320)
hor_dark = horizontal(dark_mark, recolor_dark(wordmark), mark_h=320)

# 96 px de haut = environ 2,5x la taille d'affichage dans l'en-tête (2,35rem).
# 256 couleurs : indiscernable à l'affichage, 5x plus léger qu'un PNG 32 bits.
scaled_h(hor, 96).quantize(colors=256, method=Image.Quantize.FASTOCTREE).save(
    OUT + 'logo-horizontal.png', optimize=True)
scaled_h(hor_dark, 96).quantize(colors=256, method=Image.Quantize.FASTOCTREE).save(
    OUT + 'logo-horizontal-dark.png', optimize=True)


# --- Icônes de navigateur : pastille bleu nuit, marque claire -----------
def app_icon(size):
    canvas = Image.new('RGBA', (size * 4, size * 4), (0, 0, 0, 0))
    radius = int(size * 0.22)
    tile = Image.new('RGBA', (size * 4, size * 4), (5, 19, 42, 255))
    mask = Image.new('L', (size * 4, size * 4), 0)
    ImageDraw.Draw(mask).rounded_rectangle(
        (0, 0, size * 4 - 1, size * 4 - 1), radius=radius * 4, fill=255
    )
    canvas.paste(tile, (0, 0), mask)

    inner = round(size * 4 * 0.66)
    glyph = scaled_h(dark_mark, inner)
    if glyph.width > inner:
        glyph = glyph.resize((inner, round(glyph.height * inner / glyph.width)), Image.LANCZOS)
    canvas.alpha_composite(
        glyph, ((size * 4 - glyph.width) // 2, (size * 4 - glyph.height) // 2)
    )
    return canvas.resize((size, size), Image.LANCZOS)


for size, name in [(64, 'favicon.png'), (32, 'favicon-32.png'), (180, 'apple-touch-icon.png')]:
    app_icon(size).save(OUT + name, optimize=True)

total = sum(os.path.getsize(OUT + f) for f in os.listdir(OUT) if f.endswith('.png'))
print('écrit dans', OUT, '· poids total des PNG : %.1f Ko' % (total / 1024))
