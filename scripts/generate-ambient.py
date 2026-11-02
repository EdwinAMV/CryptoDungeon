"""Generate an original quiet dungeon soundscape using only Python's stdlib.

Usage: python3 scripts/generate-ambient.py /tmp/dungeon-ambient.wav
Then on macOS:
afconvert -f m4af -d aac -b 64000 /tmp/dungeon-ambient.wav public/audio/dungeon-ambient.m4a

No third-party recordings or downloaded samples are used.
"""

import array
import math
import sys
import wave

SAMPLE_RATE = 22050
DURATION = 48
CHORDS = [(110.0, 130.8128, 164.8138), (87.3071, 110.0, 130.8128),
          (73.4162, 110.0, 146.8324), (82.4069, 123.4708, 164.8138)]
TAU = 2 * math.pi
samples = array.array('h')

for index in range(SAMPLE_RATE * DURATION):
    t = index / SAMPLE_RATE
    chord_index = int(t // 12) % 4
    within = t % 12
    blend = min(1.0, within / 2)
    signal = 0.0
    for chord, strength in [(CHORDS[(chord_index - 1) % 4], 1 - blend), (CHORDS[chord_index], blend)]:
        for note in chord:
            signal += strength * .035 * (math.sin(TAU * note * t) + .25 * math.sin(TAU * (note * 2 + .18) * t))
    # A sparse glass-like note every four seconds; the sequence is composed here.
    strike = int(t // 4)
    elapsed = t % 4
    note = CHORDS[chord_index][(strike * 2 + 1) % 3] * 4
    envelope = (1 - math.exp(-elapsed * 18)) * math.exp(-elapsed * 1.2)
    signal += .065 * envelope * (math.sin(TAU * note * elapsed) + .18 * math.sin(TAU * note * 2.01 * elapsed))
    fade = min(1.0, t / 2, (DURATION - t) / 2)
    samples.append(int(max(-1, min(1, signal * fade)) * 32767))

if sys.byteorder != 'little':
    samples.byteswap()
with wave.open(sys.argv[1], 'wb') as output:
    output.setnchannels(1)
    output.setsampwidth(2)
    output.setframerate(SAMPLE_RATE)
    output.writeframes(samples.tobytes())
