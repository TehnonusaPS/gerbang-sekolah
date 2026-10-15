#!/usr/bin/env python3
import os
import math
import subprocess
from PIL import Image, ImageFont, ImageDraw, ImageFilter

BASE_DIR = '/Users/am/Jobs/school-app/brand-assets'
VIDEO_DIR = os.path.join(BASE_DIR, 'video')
FRAMES_DIR = '/tmp/logo_anim_frames'
FONT_PATH = os.path.join(BASE_DIR, 'Montserrat.ttf')
SRC_LOGO = '/Users/am/Jobs/school-app/frontend/src/assets/logo/logoround-tight.png'

os.makedirs(VIDEO_DIR, exist_ok=True)
os.makedirs(FRAMES_DIR, exist_ok=True)

emblem_raw = Image.open(SRC_LOGO).convert('RGBA')

W, H = 1920, 1080
FPS = 60
DURATION = 4.0
TOTAL_FRAMES = int(FPS * DURATION) # 240 frames

font_size = 90
font = ImageFont.truetype(FONT_PATH, font_size)

text_g = "GERBANG"
text_s = "SEKOLAH"

bbox_g = font.getbbox(text_g)
w_g = bbox_g[2] - bbox_g[0]
bbox_s = font.getbbox(text_s)
w_s = bbox_s[2] - bbox_s[0]
needed_extra_s = max(0, w_g - w_s)
extra_per_char_s = needed_extra_s / 6.0

# In final frame, emblem width matches w_g * 1.05
emb_final_w = int(w_g * 1.02)
emb_final_h = int(emb_final_w * emblem_raw.size[1] / emblem_raw.size[0])

# Pre-render base background (Dark Luxury Theme)
bg_base = Image.new('RGBA', (W, H), (10, 15, 30, 255))
# Radial glow in center
glow_center = Image.new('RGBA', (W, H), (0, 0, 0, 0))
glow_draw = ImageDraw.Draw(glow_center)
for r in range(450, 0, -10):
    alpha = int(45 * (1.0 - r / 450.0))
    glow_draw.ellipse(
        (W//2 - r, H//2 - r - 30, W//2 + r, H//2 + r - 30),
        fill=(0, 113, 255, alpha)
    )

print(f"Rendering {TOTAL_FRAMES} animation frames...")

def ease_out_cubic(x):
    return 1.0 - math.pow(1.0 - x, 3)

def ease_in_out_quad(x):
    return 2.0 * x * x if x < 0.5 else 1.0 - math.pow(-2.0 * x + 2.0, 2) / 2.0

for f in range(TOTAL_FRAMES):
    t = f / float(TOTAL_FRAMES)
    sec = f / float(FPS)
    
    frame = bg_base.copy()
    frame.alpha_composite(glow_center)
    
    # ── Phase 1 & 2: Emblem Entrance (0.0s to 1.8s) ──
    if sec < 1.4:
        progress = min(1.0, sec / 1.4)
        eased = ease_out_cubic(progress)
        emb_scale = 0.5 + (0.5 * eased)
        emb_alpha = min(1.0, progress * 1.25)
        # Emblem is centered vertically at first, then settles slightly upward
        emb_y = int((H // 2 - emb_final_h // 2) - (80 * eased))
    else:
        emb_scale = 1.0
        emb_alpha = 1.0
        # Subtle gentle float
        float_t = (sec - 1.4) / (DURATION - 1.4)
        emb_y = int((H // 2 - emb_final_h // 2 - 80) + math.sin(float_t * math.pi * 2) * 5)
        
    cur_emb_w = max(10, int(emb_final_w * emb_scale))
    cur_emb_h = max(10, int(emb_final_h * emb_scale))
    emb_scaled = emblem_raw.resize((cur_emb_w, cur_emb_h), Image.Resampling.LANCZOS)
    
    # Apply alpha
    if emb_alpha < 1.0:
        r, g, b, a = emb_scaled.split()
        a = a.point(lambda p: int(p * emb_alpha))
        emb_scaled.putalpha(a)
        
    emb_x = W // 2 - cur_emb_w // 2
    frame.alpha_composite(emb_scaled, (emb_x, emb_y))
    
    # ── Phase 2: Text Entrance (starts at 1.0s, settles by 2.2s) ──
    if sec > 0.8:
        t_prog = min(1.0, (sec - 0.8) / 1.1)
        t_ease = ease_out_cubic(t_prog)
        t_alpha = int(255 * t_ease)
        
        # Text position: rises up into place
        base_y_g = emb_y + emb_final_h + 35
        offset_slide = int((1.0 - t_ease) * 40)
        cur_y_g = base_y_g + offset_slide
        cur_y_s = cur_y_g + 82 # tight leading
        
        # Draw texts
        txt_layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
        t_draw = ImageDraw.Draw(txt_layer)
        
        x_text = W // 2 - (w_g // 2)
        # GERBANG in bright white-blue gradient
        t_draw.text((x_text, cur_y_g), text_g, font=font, fill=(255, 255, 255, t_alpha))
        
        # SEKOLAH with tracking
        cur_x = x_text
        for i, ch in enumerate(text_s):
            t_draw.text((cur_x, cur_y_s), ch, font=font, fill=(0, 160, 255, t_alpha))
            cur_x += font.getlength(ch) + (extra_per_char_s if i < len(text_s) - 1 else 0)
            
        frame.alpha_composite(txt_layer)
        
    # ── Phase 3: Shimmer Light Sweep (2.2s to 3.4s) ──
    if 2.2 <= sec <= 3.5:
        shim_prog = (sec - 2.2) / 1.3
        shim_x = int(emb_x - 100 + shim_prog * (cur_emb_w + 200))
        
        shimmer_layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
        shim_draw = ImageDraw.Draw(shimmer_layer)
        # Slanted bright white beam
        beam_w = 40
        points = [
            (shim_x, emb_y - 20),
            (shim_x + beam_w, emb_y - 20),
            (shim_x + beam_w - 60, emb_y + cur_emb_h + 20),
            (shim_x - 60, emb_y + cur_emb_h + 20)
        ]
        shim_draw.polygon(points, fill=(255, 255, 255, 90))
        shimmer_layer = shimmer_layer.filter(ImageFilter.GaussianBlur(12))
        
        # Mask shimmer to only emblem pixels
        # Create emblem mask on full frame
        emb_mask = Image.new('L', (W, H), 0)
        emb_mask.paste(emb_scaled.split()[3], (emb_x, emb_y))
        
        frame.paste(shimmer_layer, (0, 0), emb_mask)
        
    # Save frame
    frame_path = os.path.join(FRAMES_DIR, f"frame_{f:04d}.png")
    frame.convert('RGB').save(frame_path)

print("Frames rendered! Now compiling with ffmpeg...")

out_mp4 = os.path.join(VIDEO_DIR, 'logo-animation-1080p.mp4')
out_webm = os.path.join(VIDEO_DIR, 'logo-animation.webm')
out_gif = os.path.join(VIDEO_DIR, 'logo-animation.gif')

# 1. MP4 (H.264, 60fps, YUV420p, high quality crf 18)
cmd_mp4 = [
    '/opt/homebrew/bin/ffmpeg', '-y',
    '-framerate', str(FPS),
    '-i', os.path.join(FRAMES_DIR, 'frame_%04d.png'),
    '-c:v', 'libx264',
    '-profile:v', 'high',
    '-crf', '18',
    '-pix_fmt', 'yuv420p',
    '-movflags', '+faststart',
    out_mp4
]
subprocess.run(cmd_mp4, check=True)
print(f"✓ Created {out_mp4}")

# 2. WebM (VP9)
cmd_webm = [
    '/opt/homebrew/bin/ffmpeg', '-y',
    '-framerate', str(FPS),
    '-i', os.path.join(FRAMES_DIR, 'frame_%04d.png'),
    '-c:v', 'libvpx-vp9',
    '-crf', '24',
    '-b:v', '0',
    out_webm
]
subprocess.run(cmd_webm, check=True)
print(f"✓ Created {out_webm}")

# 3. High Quality Animated GIF (30fps scaled down to 720p for fast web preview)
palette_path = '/tmp/palette.png'
subprocess.run([
    '/opt/homebrew/bin/ffmpeg', '-y',
    '-framerate', str(FPS),
    '-i', os.path.join(FRAMES_DIR, 'frame_%04d.png'),
    '-vf', 'fps=30,scale=720:-1:flags=lanczos,palettegen',
    palette_path
], check=True)

subprocess.run([
    '/opt/homebrew/bin/ffmpeg', '-y',
    '-framerate', str(FPS),
    '-i', os.path.join(FRAMES_DIR, 'frame_%04d.png'),
    '-i', palette_path,
    '-lavfi', 'fps=30,scale=720:-1:flags=lanczos [x]; [x][1:v] paletteuse',
    out_gif
], check=True)
print(f"✓ Created {out_gif}")

# Clean up temp frames
subprocess.run(['rm', '-rf', FRAMES_DIR, palette_path])
print("All video assets generated successfully!")
