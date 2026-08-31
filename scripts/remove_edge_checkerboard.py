from collections import deque
from pathlib import Path

from PIL import Image


SOURCE = Path(r"C:\BHP\brave-hearts-theme\assets\covers\everest-back-bottom-banner-57-adventures-inside-source-v1.png")
OUTPUT = Path(r"C:\BHP\brave-hearts-theme\assets\covers\everest-back-bottom-banner-57-adventures-inside-transparent-final-v1.png")
PROOF = Path(r"C:\BHP\brave-hearts-theme\assets\covers\everest-back-bottom-banner-57-adventures-inside-dark-proof-v1.jpg")


image = Image.open(SOURCE).convert("RGBA")
pixels = image.load()
width, height = image.size

candidate = bytearray(width * height)
for y in range(height):
    offset = y * width
    for x in range(width):
        r, g, b, _ = pixels[x, y]
        if min(r, g, b) > 205 and max(r, g, b) - min(r, g, b) < 30:
            candidate[offset + x] = 1

connected = bytearray(width * height)
queue = deque()

def seed(x: int, y: int) -> None:
    index = y * width + x
    if candidate[index] and not connected[index]:
        connected[index] = 1
        queue.append((x, y))

for x in range(width):
    seed(x, 0)
    seed(x, height - 1)
for y in range(height):
    seed(0, y)
    seed(width - 1, y)

while queue:
    x, y = queue.popleft()
    if x:
        seed(x - 1, y)
    if x + 1 < width:
        seed(x + 1, y)
    if y:
        seed(x, y - 1)
    if y + 1 < height:
        seed(x, y + 1)

for y in range(height):
    offset = y * width
    for x in range(width):
        if connected[offset + x]:
            r, g, b, _ = pixels[x, y]
            pixels[x, y] = (r, g, b, 0)

box = image.getbbox()
if box:
    padding = 24
    left = max(0, box[0] - padding)
    top = max(0, box[1] - padding)
    right = min(width, box[2] + padding)
    bottom = min(height, box[3] + padding)
    image = image.crop((left, top, right, bottom))

image.save(OUTPUT)

proof = Image.new("RGB", image.size, (8, 26, 66))
proof.paste(image, mask=image.getchannel("A"))
proof.save(PROOF, quality=94)

print(f"Saved {OUTPUT} | mode={image.mode} | size={image.size} | alpha={image.getchannel('A').getextrema()}")
