import argparse
from collections import deque
from pathlib import Path

from PIL import Image


parser = argparse.ArgumentParser()
parser.add_argument("source", type=Path)
parser.add_argument("output", type=Path)
parser.add_argument("--proof", type=Path)
args = parser.parse_args()

image = Image.open(args.source).convert("RGBA")
pixels = image.load()
width, height = image.size

candidate = bytearray(width * height)
for y in range(height):
    offset = y * width
    for x in range(width):
        red, green, blue, _ = pixels[x, y]
        if min(red, green, blue) > 205 and max(red, green, blue) - min(red, green, blue) < 30:
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
            red, green, blue, _ = pixels[x, y]
            pixels[x, y] = (red, green, blue, 0)

box = image.getbbox()
if box:
    padding = 24
    image = image.crop((
        max(0, box[0] - padding),
        max(0, box[1] - padding),
        min(width, box[2] + padding),
        min(height, box[3] + padding),
    ))

args.output.parent.mkdir(parents=True, exist_ok=True)
image.save(args.output)

if args.proof:
    proof = Image.new("RGB", image.size, (8, 26, 66))
    proof.paste(image, mask=image.getchannel("A"))
    proof.save(args.proof, quality=94)

print(f"Saved {args.output} | mode={image.mode} | size={image.size} | alpha={image.getchannel('A').getextrema()}")
