import argparse
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

for y in range(height):
    for x in range(width):
        red, green, blue, _ = pixels[x, y]
        strongest_other = max(red, blue)
        green_dominance = green - strongest_other
        alpha = int(max(0, min(255, 255 - (green_dominance - 18) * 255 / 105)))
        if green > strongest_other:
            green = strongest_other
        pixels[x, y] = (red, green, blue, alpha)

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
