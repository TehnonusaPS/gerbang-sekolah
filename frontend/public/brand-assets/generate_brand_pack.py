#!/usr/bin/env python3
import os
import base64
import math
import subprocess
from PIL import Image, ImageFont, ImageDraw, ImageFilter

BASE_DIR = '/Users/am/Jobs/school-app/brand-assets'
PNG_DIR = os.path.join(BASE_DIR, 'png')
SVG_DIR = os.path.join(BASE_DIR, 'svg')
VIDEO_DIR = os.path.join(BASE_DIR, 'video')
SRC_LOGO = '/Users/am/Jobs/school-app/frontend/src/assets/logo/logoround-tight.png'
SRC_LOGO_WHITE = '/Users/am/Jobs/school-app/frontend/src/assets/logo/logoround-white.png'
FONT_PATH = os.path.join(BASE_DIR, 'Montserrat.ttf')

os.makedirs(PNG_DIR, exist_ok=True)
os.makedirs(SVG_DIR, exist_ok=True)
os.makedirs(VIDEO_DIR, exist_ok=True)

emblem_color = Image.open(SRC_LOGO).convert('RGBA')
emblem_white = Image.open(SRC_LOGO_WHITE).convert('RGBA')

print("1. Generating High-Res Emblem PNGs...")
# 4K Emblem Color & White
w_4k = 4000
h_4k = int(w_4k * emblem_color.size[1] / emblem_color.size[0])
emblem_color_4k = emblem_color.resize((w_4k, h_4k), Image.Resampling.LANCZOS)
emblem_color_4k.save(os.path.join(PNG_DIR, 'emblem-color-4k.png'), optimize=True)

emblem_white_4k = emblem_white.resize((w_4k, h_4k), Image.Resampling.LANCZOS)
emblem_white_4k.save(os.path.join(PNG_DIR, 'emblem-white-4k.png'), optimize=True)

print("2. Generating Horizontal Lockup (Teks di Samping) PNGs...")
# Horizontal lockup: Emblem on left, GERBANG above SEKOLAH on right
# Let emblem height be 800px.
h_target = 800
w_target = int(h_target * emblem_color.size[0] / emblem_color.size[1]) # ~1332px
emblem_h = emblem_color.resize((w_target, h_target), Image.Resampling.LANCZOS)
emblem_h_white = emblem_white.resize((w_target, h_target), Image.Resampling.LANCZOS)

# Font size to match emblem height nicely: font_size ~ 340px
font_size_h = 360
font_h = ImageFont.truetype(FONT_PATH, font_size_h)

text_g = "GERBANG"
text_s = "SEKOLAH"

bbox_g = font_h.getbbox(text_g)
w_g = bbox_g[2] - bbox_g[0]
bbox_s = font_h.getbbox(text_s)
w_s = bbox_s[2] - bbox_s[0]

# Add tracking to SEKOLAH so it matches GERBANG
needed_extra_s = max(0, w_g - w_s)
extra_per_char_s = needed_extra_s / 6.0

gap_x = 240
total_w = w_target + gap_x + max(w_g, w_s + int(needed_extra_s)) + 100
total_h = h_target + 100

for mode, text_color, emb_img, filename in [
    ('color', (0, 113, 255, 255), emblem_h, 'logo-horizontal-color-4k.png'),
    ('white', (255, 255, 255, 255), emblem_h_white, 'logo-horizontal-white-4k.png')
]:
    img = Image.new('RGBA', (total_w, total_h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    # Paste emblem centered vertically
    offset_y_emb = (total_h - h_target) // 2
    img.paste(emb_img, (50, offset_y_emb), emb_img)
    
    # Draw GERBANG and SEKOLAH
    x_text = 50 + w_target + gap_x
    y_g = offset_y_emb + 20
    draw.text((x_text, y_g), text_g, font=font_h, fill=text_color)
    
    # SEKOLAH with matching leading and tracking
    y_s = y_g + 360 - 60
    cur_x = x_text
    for i, ch in enumerate(text_s):
        draw.text((cur_x, y_s), ch, font=font_h, fill=text_color)
        cur_x += font_h.getlength(ch) + (extra_per_char_s if i < len(text_s) - 1 else 0)
        
    # Crop to non-transparent bounding box with 60px margin
    bbox = img.getbbox()
    cropped = img.crop((bbox[0] - 60, bbox[1] - 60, bbox[2] + 60, bbox[3] + 60))
    cropped.save(os.path.join(PNG_DIR, filename), optimize=True)

print("3. Generating Vertical Lockup (Teks di Bawah) PNGs...")
# Vertical lockup: Emblem on top, GERBANG and SEKOLAH centered below
font_size_v = 400
font_v = ImageFont.truetype(FONT_PATH, font_size_v)

bbox_gv = font_v.getbbox(text_g)
w_gv = bbox_gv[2] - bbox_gv[0]
bbox_sv = font_v.getbbox(text_s)
w_sv = bbox_sv[2] - bbox_sv[0]
needed_extra_sv = max(0, w_gv - w_sv)
extra_per_char_sv = needed_extra_sv / 6.0

# Emblem width matches text width w_gv
target_w_v = w_gv
target_h_v = int(target_w_v * emblem_color.size[1] / emblem_color.size[0])

emblem_v = emblem_color.resize((target_w_v, target_h_v), Image.Resampling.LANCZOS)
emblem_v_white = emblem_white.resize((target_w_v, target_h_v), Image.Resampling.LANCZOS)

gap_y = 120
lead_y = 330 # tight leading
canvas_w_v = target_w_v + 300
canvas_h_v = target_h_v + gap_y + (font_size_v * 2) + 200

for mode, text_color, emb_img, filename in [
    ('color', (0, 113, 255, 255), emblem_v, 'logo-vertical-color-4k.png'),
    ('white', (255, 255, 255, 255), emblem_v_white, 'logo-vertical-white-4k.png')
]:
    img = Image.new('RGBA', (canvas_w_v, canvas_h_v), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    x_center = canvas_w_v // 2
    x_emb = x_center - (target_w_v // 2)
    y_emb = 100
    img.paste(emb_img, (x_emb, y_emb), emb_img)
    
    # Text GERBANG
    y_g = y_emb + target_h_v + gap_y
    draw.text((x_emb, y_g), text_g, font=font_v, fill=text_color)
    
    # Text SEKOLAH
    y_s = y_g + lead_y
    cur_x = x_emb
    for i, ch in enumerate(text_s):
        draw.text((cur_x, y_s), ch, font=font_v, fill=text_color)
        cur_x += font_v.getlength(ch) + (extra_per_char_sv if i < len(text_s) - 1 else 0)
        
    bbox = img.getbbox()
    cropped = img.crop((bbox[0] - 60, bbox[1] - 60, bbox[2] + 60, bbox[3] + 60))
    cropped.save(os.path.join(PNG_DIR, filename), optimize=True)

print("4. Generating Scalable Vector Graphics (SVG)...")
# Encode high-res png base64 for pure SVG wrappers
with open(SRC_LOGO, 'rb') as f:
    b64_c = base64.b64encode(f.read()).decode('utf-8')
with open(SRC_LOGO_WHITE, 'rb') as f:
    b64_w = base64.b64encode(f.read()).decode('utf-8')

# Emblem SVG
svg_emblem_c = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1114 669" width="100%" height="100%">
  <image href="data:image/png;base64,{b64_c}" width="1114" height="669" />
</svg>'''
with open(os.path.join(SVG_DIR, 'emblem-color.svg'), 'w') as f: f.write(svg_emblem_c)

svg_emblem_w = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1114 669" width="100%" height="100%">
  <image href="data:image/png;base64,{b64_w}" width="1114" height="669" />
</svg>'''
with open(os.path.join(SVG_DIR, 'emblem-white.svg'), 'w') as f: f.write(svg_emblem_w)

# Horizontal Lockup SVG (with Google Fonts Montserrat embedded)
svg_horiz_c = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 740 220" width="100%" height="100%">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@800&amp;display=swap');
    .txt {{ font-family: 'Montserrat', sans-serif; font-weight: 800; text-transform: uppercase; }}
  </style>
  <g transform="translate(10, 10)">
    <image href="data:image/png;base64,{b64_c}" x="0" y="10" width="300" height="180" />
    <g transform="translate(325, 82)">
      <text class="txt" x="0" y="0" font-size="82" fill="#0071FF">GERBANG</text>
      <text class="txt" x="0" y="80" font-size="82" letter-spacing="0.04em" fill="#0071FF">SEKOLAH</text>
    </g>
  </g>
</svg>'''
with open(os.path.join(SVG_DIR, 'logo-horizontal-color.svg'), 'w') as f: f.write(svg_horiz_c)

svg_horiz_w = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 740 220" width="100%" height="100%">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@800&amp;display=swap');
    .txt {{ font-family: 'Montserrat', sans-serif; font-weight: 800; text-transform: uppercase; }}
  </style>
  <g transform="translate(10, 10)">
    <image href="data:image/png;base64,{b64_w}" x="0" y="10" width="300" height="180" />
    <g transform="translate(325, 82)">
      <text class="txt" x="0" y="0" font-size="82" fill="#FFFFFF">GERBANG</text>
      <text class="txt" x="0" y="80" font-size="82" letter-spacing="0.04em" fill="#FFFFFF">SEKOLAH</text>
    </g>
  </g>
</svg>'''
with open(os.path.join(SVG_DIR, 'logo-horizontal-white.svg'), 'w') as f: f.write(svg_horiz_w)

# Vertical Lockup SVG
svg_vert_c = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 440 450" width="100%" height="100%">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@800&amp;display=swap');
    .txt {{ font-family: 'Montserrat', sans-serif; font-weight: 800; text-transform: uppercase; }}
  </style>
  <g transform="translate(20, 20)">
    <image href="data:image/png;base64,{b64_c}" x="0" y="0" width="400" height="240" />
    <g transform="translate(0, 318)">
      <text class="txt" x="0" y="0" font-size="78" fill="#0071FF">GERBANG</text>
      <text class="txt" x="0" y="74" font-size="78" letter-spacing="0.04em" fill="#0071FF">SEKOLAH</text>
    </g>
  </g>
</svg>'''
with open(os.path.join(SVG_DIR, 'logo-vertical-color.svg'), 'w') as f: f.write(svg_vert_c)

svg_vert_w = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 440 450" width="100%" height="100%">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@800&amp;display=swap');
    .txt {{ font-family: 'Montserrat', sans-serif; font-weight: 800; text-transform: uppercase; }}
  </style>
  <g transform="translate(20, 20)">
    <image href="data:image/png;base64,{b64_w}" x="0" y="0" width="400" height="240" />
    <g transform="translate(0, 318)">
      <text class="txt" x="0" y="0" font-size="78" fill="#FFFFFF">GERBANG</text>
      <text class="txt" x="0" y="74" font-size="78" letter-spacing="0.04em" fill="#FFFFFF">SEKOLAH</text>
    </g>
  </g>
</svg>'''
with open(os.path.join(SVG_DIR, 'logo-vertical-white.svg'), 'w') as f: f.write(svg_vert_w)

print("Master PNG & SVG assets successfully created in /Users/am/Jobs/school-app/brand-assets!")
