<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Self-contained GD image toolbox.
 *
 * Wraps a single truecolour \GdImage and exposes the operations GifBuilder
 * needs — creation, format detection, file loading, drawing, text,
 * compositing, level adjustment, resizing and effects.
 *
 * Every mutating method returns $this for chaining. Methods that must
 * produce a differently-sized canvas (rotate, resize, crop, shear, wave)
 * replace the internal \GdImage transparently. All canvases are always
 * truecolour: palette sources are promoted on load, and quantising
 * promotes them back.
 *
 * Use resource() as an escape hatch when a raw \GdImage is needed by
 * code not yet covered by this API.
 *
 * @internal not part of the TYPO3 Core API, the signatures expose raw GD
 *           semantics (colour indices, 0-127 alpha) and will be reshaped
 *           once image processing is split into per-processor strategies.
 */
final class GraphicsCanvas
{
    private function __construct(private \GdImage $image) {}

    /**
     * Whether the GD extension is available in this PHP build.
     * Check this before calling any factory method when GD may be absent.
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('gd');
    }

    // ---------------------------------------------------------------
    // Format support
    // ---------------------------------------------------------------

    /**
     * Whether the current GD build can decode the given file extension.
     * GD reads and writes the same set of formats, so canRead() and
     * canWrite() are equivalent — both are provided for semantic clarity.
     */
    public static function canRead(string $ext): bool
    {
        return self::supportsFormat($ext);
    }

    public static function canWrite(string $ext): bool
    {
        return self::supportsFormat($ext);
    }

    /**
     * imagetypes() reports the format bits the running GD build was
     * compiled with, which is the same answer gd_info() spells out in
     * prose. Without the extension there is no build to ask.
     */
    private static function supportsFormat(string $ext): bool
    {
        if (!self::isAvailable()) {
            return false;
        }
        $imageTypes = imagetypes();
        return match (strtolower($ext)) {
            'gif' => (bool)($imageTypes & IMG_GIF),
            'jpg', 'jpeg' => (bool)($imageTypes & IMG_JPEG),
            'png' => (bool)($imageTypes & IMG_PNG),
            'webp' => (bool)($imageTypes & IMG_WEBP),
            'avif' => (bool)($imageTypes & IMG_AVIF),
            default => false,
        };
    }

    // ---------------------------------------------------------------
    // Factory / loading
    // ---------------------------------------------------------------

    /**
     * Create a truecolour canvas filled with the given colour. $alpha is
     * GD-style: 0 = opaque, 127 = fully transparent. When $alpha > 0 the
     * canvas is configured to preserve the alpha channel on save.
     */
    public static function create(int $width, int $height, int $r = 255, int $g = 255, int $b = 255, int $alpha = 0): self
    {
        if (!self::isAvailable()) {
            throw new \RuntimeException('GraphicsCanvas requires the PHP GD extension', 1746200000);
        }
        $image = imagecreatetruecolor($width, $height);
        if (!$image instanceof \GdImage) {
            throw new \RuntimeException('imagecreatetruecolor failed', 1746000000);
        }
        $canvas = new self($image);
        $fill = $alpha > 0
            ? $canvas->enableAlpha()->allocateColorAlpha($r, $g, $b, $alpha)
            : $canvas->enableBlending()->allocateColor($r, $g, $b);
        // A span fill, not imagefill()'s seed fill: the canvas is uniformly blank.
        return $canvas->fillRect(0, 0, $width, $height, $fill);
    }

    /**
     * A fully transparent truecolour image, the starting point for every
     * operation that has to place the source onto a larger canvas.
     */
    private static function createTransparentImage(int $width, int $height): ?\GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        if (!$image instanceof \GdImage) {
            return null;
        }
        $canvas = new self($image)->enableAlpha();
        $canvas->fillRect(0, 0, $width, $height, max(0, $canvas->allocateColorAlpha(0, 0, 0, 127)));
        return $image;
    }

    /**
     * Load an existing \GdImage resource into a canvas.
     */
    public static function load(\GdImage $image): self
    {
        return new self($image);
    }

    /**
     * Load an image file. Palette images are promoted to truecolour on
     * load so every downstream operation can assume a uniform format.
     * Returns null when the extension is unsupported or the file cannot
     * be decoded.
     */
    public static function loadFile(string $filePath, bool $preserveAlpha = false, bool $autoOrient = false): ?self
    {
        if (!self::isAvailable()) {
            throw new \RuntimeException('GraphicsCanvas requires the PHP GD extension', 1746200001);
        }
        $ext = strtolower(PathUtility::pathinfo($filePath, PATHINFO_EXTENSION));
        if (!self::supportsFormat($ext)) {
            return null;
        }
        $image = match ($ext) {
            'gif' => @imagecreatefromgif($filePath),
            'png' => @imagecreatefrompng($filePath),
            'jpg', 'jpeg' => @imagecreatefromjpeg($filePath),
            'webp' => @imagecreatefromwebp($filePath),
            'avif' => @imagecreatefromavif($filePath),
            default => false,
        };
        if (!$image instanceof \GdImage) {
            return null;
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        if ($preserveAlpha && in_array($ext, ['png', 'webp', 'avif'], true)) {
            // GIF expresses transparency as a palette index and JPEG has no alpha
            // at all, so the flag is meaningless for both. Of the three that are
            // left, only the PNG decoder leaves it switched off by itself.
            imagesavealpha($image, true);
        }
        $canvas = new self($image);
        return $autoOrient
            ? $canvas->autoOrient($filePath)
            : $canvas;
    }

    // ---------------------------------------------------------------
    // Resource access
    // ---------------------------------------------------------------

    /**
     * Return the underlying \GdImage for use with GD functions not yet
     * covered by this API.
     */
    public function resource(): \GdImage
    {
        return $this->image;
    }

    /**
     * Return a deep copy of this canvas as a new independent instance.
     */
    public function duplicate(): self
    {
        $w = $this->width();
        $h = $this->height();
        $copy = imagecreatetruecolor($w, $h);
        if (!$copy instanceof \GdImage) {
            throw new \RuntimeException('imagecreatetruecolor failed during duplicate()', 1746000002);
        }
        return new self($copy)
            ->enableAlpha()
            ->copy($this, 0, 0, 0, 0, $w, $h);
    }

    // ---------------------------------------------------------------
    // Dimensions
    // ---------------------------------------------------------------

    public function width(): int
    {
        return imagesx($this->image);
    }

    public function height(): int
    {
        return imagesy($this->image);
    }

    // ---------------------------------------------------------------
    // Alpha / interlace control
    // ---------------------------------------------------------------

    /**
     * Preserve the full alpha channel on save and disable blending so
     * pixels are written verbatim (useful when building masks or
     * stamping pre-multiplied layers).
     */
    public function enableAlpha(): self
    {
        imagesavealpha($this->image, true);
        imagealphablending($this->image, false);
        return $this;
    }

    /**
     * Drop the alpha channel on save and blend onto the existing content
     * instead — the counterpart of enableAlpha() and the mode the pixel
     * filters need, since a convolution over pre-multiplied pixels has no
     * meaningful result.
     */
    public function disableAlpha(): self
    {
        imagesavealpha($this->image, false);
        imagealphablending($this->image, true);
        return $this;
    }

    /**
     * Enable alpha blending so subsequent draw calls composite onto the
     * existing content using the source pixel's alpha value.
     */
    public function enableBlending(): self
    {
        imagealphablending($this->image, true);
        return $this;
    }

    /**
     * Define one colour index as the transparent colour (palette-mode
     * semantics). Pass -1 to remove transparency.
     */
    public function setTransparentColor(int $color): self
    {
        imagecolortransparent($this->image, $color);
        return $this;
    }

    /**
     * Enable or disable interlacing (progressive scan for JPEG/PNG).
     */
    public function setInterlace(bool $enable): self
    {
        imageinterlace($this->image, $enable);
        return $this;
    }

    // ---------------------------------------------------------------
    // Color allocation
    // ---------------------------------------------------------------

    /**
     * Return the palette index of the colour closest to ($r, $g, $b).
     * Useful for palette images where an exact match may not exist.
     */
    public function findClosestColor(int $r, int $g, int $b): int
    {
        return imagecolorclosest($this->image, $r, $g, $b);
    }

    /**
     * Return the palette index of an exact ($r, $g, $b) match, or -1 if
     * the colour is not in the palette.
     */
    public function findExactColor(int $r, int $g, int $b): int
    {
        return imagecolorexact($this->image, $r, $g, $b);
    }

    /**
     * Allocate an opaque colour and return its index.
     * Returns -1 if allocation fails (palette exhausted).
     */
    public function allocateColor(int $r, int $g, int $b): int
    {
        $color = imagecolorallocate($this->image, $r, $g, $b);
        return $color === false ? -1 : $color;
    }

    /**
     * Allocate a colour with transparency and return its index.
     * $alpha ranges from 0 (fully opaque) to 127 (fully transparent).
     * Returns -1 if allocation fails.
     */
    public function allocateColorAlpha(int $r, int $g, int $b, int $alpha): int
    {
        $color = imagecolorallocatealpha($this->image, $r, $g, $b, $alpha);
        return $color === false ? -1 : $color;
    }

    // ---------------------------------------------------------------
    // Drawing primitives
    // ---------------------------------------------------------------

    public function setPixel(int $x, int $y, int $color): self
    {
        imagesetpixel($this->image, $x, $y, $color);
        return $this;
    }

    /**
     * Return the raw colour integer at ($x, $y).
     * On truecolour images this encodes as 0xAARRGGBB (GD convention:
     * alpha in the high 7 bits, 0 = opaque). Returns 0 on failure.
     */
    public function getPixel(int $x, int $y): int
    {
        $color = imagecolorat($this->image, $x, $y);
        return $color === false ? 0 : $color;
    }

    /**
     * Replace every pixel matching one of $sourceColors with $targetColor.
     * Palette images get the cheap path via imagecolorset(); truecolour
     * images get a per-pixel scan with a hashed lookup table.
     *
     * @param list<array{0: int, 1: int, 2: int}> $sourceColors
     * @param array{0: int, 1: int, 2: int} $targetColor
     */
    public function remapColors(array $sourceColors, array $targetColor): self
    {
        if ($sourceColors === []) {
            return $this;
        }
        if (!imageistruecolor($this->image)) {
            $total = imagecolorstotal($this->image);
            for ($i = 0; $i < $total; $i++) {
                $rgba = imagecolorsforindex($this->image, $i);
                foreach ($sourceColors as $src) {
                    if ($rgba['red'] === $src[0] && $rgba['green'] === $src[1] && $rgba['blue'] === $src[2]) {
                        imagecolorset($this->image, $i, $targetColor[0], $targetColor[1], $targetColor[2]);
                        break;
                    }
                }
            }
            return $this;
        }
        $sourceSet = [];
        foreach ($sourceColors as $src) {
            $sourceSet[($src[0] << 16) | ($src[1] << 8) | $src[2]] = true;
        }
        $targetRgb = ($targetColor[0] << 16) | ($targetColor[1] << 8) | $targetColor[2];
        $width = $this->width();
        $height = $this->height();
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (isset($sourceSet[imagecolorat($this->image, $x, $y) & 0xFFFFFF])) {
                    imagesetpixel($this->image, $x, $y, $targetRgb);
                }
            }
        }
        return $this;
    }

    /**
     * Flood-fill the canvas starting at ($x, $y) with $color.
     */
    public function flood(int $x, int $y, int $color): self
    {
        imagefill($this->image, $x, $y, $color);
        return $this;
    }

    /**
     * Draw a line between two points.
     */
    public function line(int $x1, int $y1, int $x2, int $y2, int $color): self
    {
        imageline($this->image, $x1, $y1, $x2, $y2, $color);
        return $this;
    }

    /**
     * Draw a rectangle outline.
     * $x/$y are the top-left corner; $w/$h are the dimensions.
     */
    public function rect(int $x, int $y, int $w, int $h, int $color): self
    {
        imagerectangle($this->image, $x, $y, $x + $w - 1, $y + $h - 1, $color);
        return $this;
    }

    /**
     * Fill a rectangle.
     * $x/$y are the top-left corner; $w/$h are the dimensions.
     */
    public function fillRect(int $x, int $y, int $w, int $h, int $color): self
    {
        imagefilledrectangle($this->image, $x, $y, $x + $w - 1, $y + $h - 1, $color);
        return $this;
    }

    /**
     * Draw an ellipse outline centred at ($cx, $cy).
     */
    public function ellipse(int $cx, int $cy, int $w, int $h, int $color): self
    {
        imageellipse($this->image, $cx, $cy, $w, $h, $color);
        return $this;
    }

    /**
     * Fill an ellipse centred at ($cx, $cy).
     */
    public function fillEllipse(int $cx, int $cy, int $w, int $h, int $color): self
    {
        imagefilledellipse($this->image, $cx, $cy, $w, $h, $color);
        return $this;
    }

    // ---------------------------------------------------------------
    // Text
    // ---------------------------------------------------------------

    /**
     * Render a TrueType text string. ($x, $y) is the baseline start.
     */
    public function renderText(float $size, float $angle, int $x, int $y, int $color, string $fontFile, string $text): self
    {
        imagettftext($this->image, $size, $angle, $x, $y, $color, $fontFile, $text);
        return $this;
    }

    /**
     * Render a text string using one of GD's built-in bitmap fonts (0–5).
     * Font 0 uses the default font; fonts 1–5 are progressively larger.
     * Intended for simple diagnostic or placeholder images where a TTF
     * font file is not available.
     */
    public function drawString(int $font, int $x, int $y, string $text, int $color): self
    {
        imagestring($this->image, $font, $x, $y, $text, $color);
        return $this;
    }

    /**
     * Measure a TrueType text string without drawing it. Returns the
     * 8-element bounding box from imagettfbbox(), or null on failure.
     */
    public static function measureText(float $size, float $angle, string $fontFile, string $text): ?array
    {
        $bbox = imagettfbbox($size, $angle, $fontFile, $text);
        return $bbox === false ? null : $bbox;
    }

    // ---------------------------------------------------------------
    // Copy / Composite
    // ---------------------------------------------------------------

    /**
     * Copy a rectangular region from $source onto this canvas at ($dstX, $dstY).
     */
    public function copy(self $source, int $dstX, int $dstY, int $srcX, int $srcY, int $w, int $h): self
    {
        imagecopy($this->image, $source->image, $dstX, $dstY, $srcX, $srcY, $w, $h);
        return $this;
    }

    /**
     * Copy a region from $source, scaling it via nearest-neighbour
     * (fast, no interpolation). Use copyResampled() for better quality.
     */
    public function copyResized(
        self $source,
        int $dstX,
        int $dstY,
        int $srcX,
        int $srcY,
        int $dstW,
        int $dstH,
        int $srcW,
        int $srcH,
    ): self {
        imagecopyresized($this->image, $source->image, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
        return $this;
    }

    /**
     * Copy a region from $source, scaling it to the destination
     * dimensions using bicubic resampling (better quality than copyResized).
     */
    public function copyResampled(
        self $source,
        int $dstX,
        int $dstY,
        int $srcX,
        int $srcY,
        int $dstW,
        int $dstH,
        int $srcW,
        int $srcH,
    ): self {
        imagecopyresampled($this->image, $source->image, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
        return $this;
    }

    /**
     * Composite $overlay onto this canvas through $mask. White mask
     * shows the overlay, black keeps the background, grey linearly
     * blends. Clipped to the intersection of all three canvases.
     */
    public function compositeMasked(self $overlay, self $mask): self
    {
        $width = min($this->width(), $overlay->width(), $mask->width());
        $height = min($this->height(), $overlay->height(), $mask->height());

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $maskGray = imagecolorat($mask->image, $x, $y) & 0xff;
                if ($maskGray === 0) {
                    continue;
                }
                $ovColor = imagecolorat($overlay->image, $x, $y);
                if ($maskGray === 255) {
                    imagesetpixel($this->image, $x, $y, $ovColor & 0xffffff);
                    continue;
                }
                $bgColor = imagecolorat($this->image, $x, $y);
                $invMask = 255 - $maskGray;
                $r = intdiv((($bgColor >> 16) & 0xff) * $invMask + (($ovColor >> 16) & 0xff) * $maskGray, 255);
                $g = intdiv((($bgColor >> 8) & 0xff) * $invMask + (($ovColor >> 8) & 0xff) * $maskGray, 255);
                $b = intdiv(($bgColor & 0xff) * $invMask + ($ovColor & 0xff) * $maskGray, 255);
                imagesetpixel($this->image, $x, $y, ($r << 16) | ($g << 8) | $b);
            }
        }
        return $this;
    }

    /**
     * Flatten transparency onto a white background and return a new
     * canvas. Used when saving to JPEG.
     */
    public function flattenToWhite(): self
    {
        $flat = self::create($this->width(), $this->height());
        $flat->enableBlending()->copy($this, 0, 0, 0, 0, $this->width(), $this->height());
        return $flat;
    }

    // ---------------------------------------------------------------
    // Resize / Crop
    // ---------------------------------------------------------------

    /**
     * Resize to $width × $height using bicubic resampling. Alpha preserved.
     */
    public function resizeTo(int $width, int $height): self
    {
        $resized = self::createTransparentImage($width, $height);
        if ($resized === null) {
            return $this;
        }
        imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $width, $height, $this->width(), $this->height());
        $this->image = $resized;
        return $this;
    }

    /**
     * Crop to the rectangle at ($x, $y) with dimensions $width × $height.
     */
    public function crop(int $x, int $y, int $width, int $height): self
    {
        $cropped = imagecrop($this->image, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);
        if ($cropped instanceof \GdImage) {
            $this->image = $cropped;
        }
        return $this;
    }

    // ---------------------------------------------------------------
    // Level / tone adjustments
    // ---------------------------------------------------------------

    /**
     * Stretch the range [$low, $high] to [0, 255] (Photoshop-style
     * Input Levels). Increases contrast; operates per RGB channel.
     */
    public function inputLevels(int $low, int $high): self
    {
        if ($low >= $high) {
            return $this;
        }
        $low = MathUtility::forceIntegerInRange($low, 0, 255);
        $high = MathUtility::forceIntegerInRange($high, 0, 255);
        $delta = $high - $low;
        return $this->remapChannels(static fn(int $value): int => MathUtility::forceIntegerInRange(
            (int)(($value - $low) / $delta * 255),
            0,
            255
        ));
    }

    /**
     * Compress [0, 255] into [$low, $high] (Photoshop-style Output Levels).
     * Reduces contrast; operates per RGB channel.
     */
    public function outputLevels(int $low, int $high): self
    {
        if ($low >= $high) {
            return $this;
        }
        $low = MathUtility::forceIntegerInRange($low, 0, 255);
        $high = MathUtility::forceIntegerInRange($high, 0, 255);
        $delta = $high - $low;
        return $this->remapChannels(static fn(int $value): int => (int)($low + $value / 255 * $delta));
    }

    /**
     * Apply a per-channel mapping to every pixel. The mapping only depends
     * on the channel value, so it is evaluated 256 times into a lookup table
     * rather than three times per pixel.
     *
     * @param \Closure(int): int $map
     */
    private function remapChannels(\Closure $map): self
    {
        $lookup = [];
        for ($value = 0; $value <= 255; $value++) {
            $lookup[$value] = $map($value);
        }
        $width = $this->width();
        $height = $this->height();
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($this->image, $x, $y);
                imagesetpixel($this->image, $x, $y, ($lookup[$color >> 16 & 0xff] << 16)
                    | ($lookup[$color >> 8 & 0xff] << 8)
                    | $lookup[$color & 0xff]);
            }
        }
        return $this;
    }

    /**
     * Stretch the actual luminosity range to the full [0, 255] span,
     * maximising contrast.
     */
    public function autolevels(): self
    {
        $width = $this->width();
        $height = $this->height();
        $min = 255;
        $max = 0;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($this->image, $x, $y);
                $gray = (int)round((($color >> 16 & 0xff) + ($color >> 8 & 0xff) + ($color & 0xff)) / 3);
                $min = min($min, $gray);
                $max = max($max, $gray);
            }
        }
        if ($max > $min) {
            $this->inputLevels($min, $max);
        }
        return $this;
    }

    // ---------------------------------------------------------------
    // Effects
    // ---------------------------------------------------------------

    /**
     * Soften the image. Higher $passes = stronger blur.
     */
    public function blur(int $passes = 1): self
    {
        for ($i = 0; $i < max(1, $passes); $i++) {
            imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
        }
        return $this;
    }

    /**
     * Accentuate edges to make the image look crisper. $amount is 0–100.
     */
    public function sharpen(int $amount): self
    {
        $v = max(1, (int)($amount / 10));
        $matrix = [[-1, -1, -1], [-1, 8 + $v, -1], [-1, -1, -1]];
        imageconvolution($this->image, $matrix, array_sum(array_map('array_sum', $matrix)) ?: 1, 0);
        return $this;
    }

    /**
     * Reduce the image palette to $count colours (posterise). Dithering
     * is enabled so photographic content degrades perceptually; on
     * synthetic high-contrast images GD's median-cut may pick a
     * different palette than IM/GM's K-means.
     */
    public function colors(int $count): self
    {
        $count = MathUtility::forceIntegerInRange($count, 2, 255);
        imagepalettetotruecolor($this->image);
        imagetruecolortopalette($this->image, true, $count);
        // Quantising leaves a palette image behind, on which imagecolorat() returns a
        // palette index instead of a packed RGB value. Promote back to truecolour so
        // chained operations (a following EFFECT keyword, ADJUST, a mask composite)
        // keep reading real colours. The reduced palette is preserved.
        imagepalettetotruecolor($this->image);
        return $this;
    }

    public function grayscale(): self
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    public function invert(): self
    {
        imagefilter($this->image, IMG_FILTER_NEGATE);
        return $this;
    }

    /**
     * Edge detection. Flat regions become black; edges show up as
     * coloured lines preserving the original hue.
     */
    public function edge(): self
    {
        imagepalettetotruecolor($this->image);
        $this->disableAlpha();

        $w = imagesx($this->image);
        $h = imagesy($this->image);

        $sobelX = [
            [-1, 0, 1],
            [-2, 0, 2],
            [-1, 0, 1],
        ];
        $sobelY = [
            [-1, -2, -1],
            [ 0, 0, 0],
            [ 1, 2, 1],
        ];

        $inverted = imagecreatetruecolor($w, $h);
        imagecopy($inverted, $this->image, 0, 0, 0, 0, $w, $h);
        imagefilter($inverted, IMG_FILTER_NEGATE);

        $gxPos = imagecreatetruecolor($w, $h);
        imagecopy($gxPos, $this->image, 0, 0, 0, 0, $w, $h);
        imageconvolution($gxPos, $sobelX, 1, 0);

        $gxNeg = imagecreatetruecolor($w, $h);
        imagecopy($gxNeg, $inverted, 0, 0, 0, 0, $w, $h);
        imageconvolution($gxNeg, $sobelX, 1, 0);

        $gyPos = imagecreatetruecolor($w, $h);
        imagecopy($gyPos, $this->image, 0, 0, 0, 0, $w, $h);
        imageconvolution($gyPos, $sobelY, 1, 0);

        $gyNeg = imagecreatetruecolor($w, $h);
        imagecopy($gyNeg, $inverted, 0, 0, 0, 0, $w, $h);
        imageconvolution($gyNeg, $sobelY, 1, 0);

        $out = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $xPos = imagecolorat($gxPos, $x, $y);
                $xNeg = imagecolorat($gxNeg, $x, $y);
                $yPos = imagecolorat($gyPos, $x, $y);
                $yNeg = imagecolorat($gyNeg, $x, $y);
                $rx = max(($xPos >> 16) & 0xFF, ($xNeg >> 16) & 0xFF);
                $gxCh = max(($xPos >> 8) & 0xFF, ($xNeg >> 8) & 0xFF);
                $bx = max($xPos & 0xFF, $xNeg & 0xFF);
                $ry = max(($yPos >> 16) & 0xFF, ($yNeg >> 16) & 0xFF);
                $gyCh = max(($yPos >> 8) & 0xFF, ($yNeg >> 8) & 0xFF);
                $by = max($yPos & 0xFF, $yNeg & 0xFF);
                $r = (int)max(0, min(255, ($rx + $ry) - 80));
                $g = (int)max(0, min(255, ($gxCh + $gyCh) - 80));
                $b = (int)max(0, min(255, ($bx + $by) - 80));
                imagesetpixel($out, $x, $y, ($r << 16) | ($g << 8) | $b);
            }
        }

        imagefilter($out, IMG_FILTER_BRIGHTNESS, -30);
        imagecopy($this->image, $out, 0, 0, 0, 0, $w, $h);

        return $this;
    }

    /**
     * Charcoal drawing — dark pencil lines on a light background.
     * $radius is 1–5 (1 = sharp sketch, 5 = soft and abstract).
     */
    public function charcoal(int $radius = 1): self
    {
        imagepalettetotruecolor($this->image);
        $this->disableAlpha();
        $radius = MathUtility::forceIntegerInRange($radius, 1, 5);

        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        for ($i = 0; $i < $radius; $i++) {
            imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $scale = $radius * 5;
        imageconvolution(
            $this->image,
            [
                [-$scale, -$scale, -$scale],
                [-$scale, 8 * $scale, -$scale],
                [-$scale, -$scale, -$scale],
            ],
            1,
            0,
        );

        imagefilter($this->image, IMG_FILTER_NEGATE);
        imagegammacorrect($this->image, 1, 0.85);
        imagefilter($this->image, IMG_FILTER_CONTRAST, -10);
        return $this;
    }

    /**
     * Bas-relief / faux-3D effect — edges become highlights and shadows.
     */
    public function emboss(): self
    {
        imagepalettetotruecolor($this->image);
        $this->disableAlpha();
        imageconvolution(
            $this->image,
            [
                [-2, -1, 0],
                [-1, 1, 1],
                [ 0, 1, 2],
            ],
            1,
            32,
        );
        return $this;
    }

    /**
     * Adjust midtone brightness via gamma correction. $outputGamma below
     * 1.0 darkens midtones, above 1.0 brightens them.
     */
    public function gamma(float $inputGamma, float $outputGamma): self
    {
        imagegammacorrect($this->image, $inputGamma, $outputGamma);
        return $this;
    }

    /**
     * Partial-negative / darkroom effect. $percent is 0–99 — channels
     * brighter than that percentage of full intensity are inverted.
     */
    public function solarize(int $percent): self
    {
        $t = (int)round(MathUtility::forceIntegerInRange($percent, 0, 99) / 100 * 255);
        $width = $this->width();
        $height = $this->height();
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($this->image, $x, $y);
                $a = ($color >> 24) & 0x7f;
                $r = ($color >> 16) & 0xff;
                $g = ($color >> 8) & 0xff;
                $b = $color & 0xff;
                // Pack the result directly instead of allocating: on a truecolour
                // canvas the packed value is the colour, and allocation could only
                // ever fail (returning false) on a palette image.
                imagesetpixel($this->image, $x, $y, ($a << 24)
                    | (($r > $t ? 255 - $r : $r) << 16)
                    | (($g > $t ? 255 - $g : $g) << 8)
                    | ($b > $t ? 255 - $b : $b));
            }
        }
        return $this;
    }

    /**
     * Apply an arbitrary 3×3 convolution kernel.
     *
     * @param array<array<float>> $matrix
     */
    public function convolve(array $matrix, float $divisor, float $offset): self
    {
        imageconvolution($this->image, $matrix, $divisor, $offset);
        return $this;
    }

    /**
     * Rotate counter-clockwise by $angle degrees. The canvas grows to
     * fit the rotated image; exposed corners become fully transparent.
     */
    public function rotate(float $angle): self
    {
        imagesavealpha($this->image, true);
        $transparent = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        $rotated = imagerotate($this->image, $angle, $transparent === false ? 0 : $transparent);
        if ($rotated instanceof \GdImage) {
            imagesavealpha($rotated, true);
            $this->image = $rotated;
        }
        return $this;
    }

    /**
     * Apply the EXIF orientation tag from $filePath (JPEG only).
     * Silently skips if the exif extension is unavailable.
     */
    public function autoOrient(string $filePath): self
    {
        if (!function_exists('exif_read_data')) {
            return $this;
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'], true)) {
            return $this;
        }
        $exif = @exif_read_data($filePath);
        if ($exif === false || !isset($exif['Orientation'])) {
            return $this;
        }
        match ((int)$exif['Orientation']) {
            2 => $this->flop(),
            3 => $this->rotate(180),
            4 => $this->flip(),
            5 => $this->rotate(270)->flop(),
            6 => $this->rotate(270),
            7 => $this->rotate(90)->flop(),
            8 => $this->rotate(90),
            default => null,
        };
        return $this;
    }

    public function flip(): self
    {
        imageflip($this->image, IMG_FLIP_VERTICAL);
        return $this;
    }

    public function flop(): self
    {
        imageflip($this->image, IMG_FLIP_HORIZONTAL);
        return $this;
    }

    /**
     * Horizontal shear by $angle degrees. Clamped to ±85 — at ±90 the
     * tangent diverges and the canvas would collapse to zero width.
     */
    public function shear(int $angle): self
    {
        $angle = MathUtility::forceIntegerInRange($angle, -85, 85);
        $srcWidth = $this->width();
        $srcHeight = $this->height();
        $tan = abs(tan(deg2rad($angle)));
        $shiftMax = (int)ceil($srcHeight * $tan);
        $dstWidth = $srcWidth + $shiftMax;

        $output = self::createTransparentImage($dstWidth, $srcHeight);
        if ($output === null) {
            return $this;
        }

        for ($y = 0; $y < $srcHeight; $y++) {
            $xOffset = $angle >= 0
                ? (int)round(($srcHeight - 1 - $y) * $tan)
                : (int)round($y * $tan);
            imagecopy($output, $this->image, $xOffset, $y, 0, $y, $srcWidth, 1);
        }
        $this->image = $output;
        return $this;
    }

    /**
     * Twirl pixels around the centre. $degrees is the rotation at the
     * centre; the falloff is quadratic so the corners stay still.
     */
    public function swirl(int $degrees): self
    {
        $degrees = MathUtility::forceIntegerInRange($degrees, -720, 720);
        if ($degrees === 0) {
            return $this;
        }
        imagepalettetotruecolor($this->image);

        $w = imagesx($this->image);
        $h = imagesy($this->image);
        $cx = ($w - 1) / 2;
        $cy = ($h - 1) / 2;
        $maxRadius = min($cx, $cy);
        // Negate so positive degrees rotate clockwise — same direction as IM's `-swirl`.
        $maxAngle = -deg2rad($degrees);

        $output = self::createTransparentImage($w, $h);
        if ($output === null) {
            return $this;
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $dx = $x - $cx;
                $dy = $y - $cy;
                $distance = sqrt($dx * $dx + $dy * $dy);
                if ($distance >= $maxRadius) {
                    imagesetpixel($output, $x, $y, imagecolorat($this->image, $x, $y));
                    continue;
                }
                $factor = ($maxRadius - $distance) / $maxRadius;
                $angle = $maxAngle * $factor * $factor;
                $cos = cos($angle);
                $sin = sin($angle);
                $sx = (int)round($cos * $dx + $sin * $dy + $cx);
                $sy = (int)round(-$sin * $dx + $cos * $dy + $cy);
                if ($sx < 0 || $sx >= $w || $sy < 0 || $sy >= $h) {
                    imagesetpixel($output, $x, $y, imagecolorat($this->image, $x, $y));
                    continue;
                }
                imagesetpixel($output, $x, $y, imagecolorat($this->image, $sx, $sy));
            }
        }

        $this->image = $output;
        return $this;
    }

    /**
     * Sinusoidal wave distortion. The canvas grows by 2 × $amplitude to
     * avoid clipping the displaced rows.
     */
    public function wave(int $amplitude, int $wavelength = 30): self
    {
        $srcWidth = $this->width();
        $srcHeight = $this->height();
        $dstHeight = $srcHeight + 2 * $amplitude;

        $output = self::createTransparentImage($srcWidth, $dstHeight);
        if ($output === null) {
            return $this;
        }

        for ($x = 0; $x < $srcWidth; $x++) {
            $offset = (int)round($amplitude * sin(2 * M_PI * $x / max(1, $wavelength)));
            imagecopy($output, $this->image, $x, $amplitude + $offset, $x, 0, 1, $srcHeight);
        }
        $this->image = $output;
        return $this;
    }

    // ---------------------------------------------------------------
    // Save / export
    // ---------------------------------------------------------------

    /**
     * Write the canvas to $path. Format is taken from $format, falling
     * back to the file extension. Supports gif/jpg/png/webp/avif.
     * $quality is -1 for the format default. $speed is avif-only.
     * JPEG flattens transparency onto white; GIF quantises to 256 colours.
     */
    public function saveToFile(string $path, string $format = '', int $quality = -1, int $speed = -1): bool
    {
        $ext = $format !== '' ? strtolower($format) : strtolower(PathUtility::pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'webp' || $ext === 'avif') {
            // GD writes the full alpha channel for these two whatever the flag says.
            // The manual asks for it to be set deliberately rather than relied upon,
            // so the behaviour is spelled out instead of inherited from the encoder.
            imagesavealpha($this->image, true);
        }
        if ($ext === 'gif') {
            // GIF needs a palette. This quantises in place — and has to, because a
            // transparent colour set via setTransparentColor() would not survive a
            // copy. The canvas is therefore no longer truecolour after saving as GIF.
            imagetruecolortopalette($this->image, true, 256);
        }
        $result = match ($ext) {
            'gif' => imagegif($this->image, $path),
            'jpg', 'jpeg' => imagejpeg($this->flattenToWhite()->image, $path, $quality),
            'png' => imagepng($this->image, $path),
            'webp' => function_exists('imagewebp') && imagewebp($this->image, $path, $quality),
            'avif' => function_exists('imageavif') && imageavif($this->image, $path, $quality, $speed),
            default => false,
        };
        if ($result) {
            GeneralUtility::fixPermissions($path);
        }
        return $result;
    }

}
