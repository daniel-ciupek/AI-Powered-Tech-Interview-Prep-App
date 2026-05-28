<?php

declare(strict_types=1);

/**
 * One-off script to generate PrepMind PWA icons.
 * Run: ./vendor/bin/sail php scripts/generate-pwa-icons.php
 *
 * Outputs:
 *  - public/icons/icon-192.png            (standard, any purpose)
 *  - public/icons/icon-512.png            (standard, any purpose)
 *  - public/icons/icon-maskable-512.png   (maskable, larger safe area)
 *  - public/icons/apple-touch-icon.png    (180x180)
 *  - public/favicon.ico                   (32x32 png, written as .ico)
 */
const OUT_DIR = __DIR__.'/../public/icons';
const INDIGO = [0x4F, 0x46, 0xE5];
const INDIGO_DARK = [0x37, 0x32, 0xA0];
const WHITE = [0xFF, 0xFF, 0xFF];

if (! is_dir(OUT_DIR)) {
    mkdir(OUT_DIR, 0755, true);
}

/**
 * @param  array{0:int,1:int,2:int}  $background
 * @param  array{0:int,1:int,2:int}  $foreground
 */
function renderIcon(int $size, string $path, array $background, array $foreground, float $padding = 0.0): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    $bg = imagecolorallocate($img, $background[0], $background[1], $background[2]);
    $bgDark = imagecolorallocate($img, INDIGO_DARK[0], INDIGO_DARK[1], INDIGO_DARK[2]);
    $fg = imagecolorallocate($img, $foreground[0], $foreground[1], $foreground[2]);

    imagefill($img, 0, 0, $bg);

    for ($y = 0; $y < $size; $y++) {
        $t = $y / $size;
        $r = (int) (INDIGO[0] * (1 - $t * 0.4) + INDIGO_DARK[0] * ($t * 0.4));
        $g = (int) (INDIGO[1] * (1 - $t * 0.4) + INDIGO_DARK[1] * ($t * 0.4));
        $b = (int) (INDIGO[2] * (1 - $t * 0.4) + INDIGO_DARK[2] * ($t * 0.4));
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $size - 1, $y, $color);
    }

    $cx = $size / 2;
    $cy = $size / 2;
    $r1 = (int) ($size * (0.32 - $padding * 0.5));
    $r2 = (int) ($size * (0.16 - $padding * 0.25));

    $ring = imagecolorallocatealpha($img, WHITE[0], WHITE[1], WHITE[2], 90);
    imagesetthickness($img, max(2, (int) ($size * 0.025)));
    imageellipse($img, (int) $cx, (int) $cy, $r1 * 2, $r1 * 2, $ring);
    imageellipse($img, (int) $cx, (int) $cy, $r2 * 2, $r2 * 2, $ring);

    $brainBody = imagecolorallocate($img, WHITE[0], WHITE[1], WHITE[2]);
    $core = (int) ($size * 0.08);
    imagefilledellipse($img, (int) $cx, (int) $cy, $core, $core, $brainBody);

    $petal = (int) ($size * 0.06);
    $offset = (int) ($size * 0.13);
    imagefilledellipse($img, (int) ($cx - $offset), (int) ($cy - $offset), $petal, $petal, $brainBody);
    imagefilledellipse($img, (int) ($cx + $offset), (int) ($cy - $offset), $petal, $petal, $brainBody);
    imagefilledellipse($img, (int) ($cx - $offset), (int) ($cy + $offset), $petal, $petal, $brainBody);
    imagefilledellipse($img, (int) ($cx + $offset), (int) ($cy + $offset), $petal, $petal, $brainBody);

    imagepng($img, $path, 9);
    imagedestroy($img);
    echo "Wrote {$path}\n";
}

renderIcon(192, OUT_DIR.'/icon-192.png', INDIGO, WHITE);
renderIcon(512, OUT_DIR.'/icon-512.png', INDIGO, WHITE);
renderIcon(512, OUT_DIR.'/icon-maskable-512.png', INDIGO, WHITE, 0.2);
renderIcon(180, OUT_DIR.'/apple-touch-icon.png', INDIGO, WHITE);
renderIcon(32, OUT_DIR.'/favicon-32.png', INDIGO, WHITE);

echo "Done. Generated PrepMind PWA icons in public/icons/.\n";
