<?php

namespace Tests\Support;

/**
 * Builds real PNG bytes without the GD extension.
 *
 * UploadedFile::fake()->image() draws with GD, which is not enabled in every
 * environment (it is commented out in XAMPP's default php.ini). The app
 * itself never needs GD — Laravel's `dimensions` rule reads getimagesize(),
 * which is core PHP — so only test fixtures were affected.
 *
 * These are genuine PNGs, so `image`, `mimes` and `dimensions` all validate
 * against them exactly as they would against a real upload.
 */
class FakeImage
{
    /**
     * @param  int  $padBytes  Extra bytes in a trailing text chunk, for
     *                         exercising file-size limits.
     */
    public static function png(int $width, int $height, int $padBytes = 0): string
    {
        $scanlines = '';

        for ($y = 0; $y < $height; $y++) {
            // Each row is prefixed with its filter type; 0 means "none".
            $scanlines .= chr(0).str_repeat(chr(190).chr(120).chr(60), $width);
        }

        $ihdr = pack('N', $width)
            .pack('N', $height)
            .chr(8)   // bit depth
            .chr(2)   // colour type: truecolour RGB
            .chr(0)   // compression: deflate
            .chr(0)   // filter method
            .chr(0);  // no interlacing

        $png = "\x89PNG\r\n\x1a\n"
            .self::chunk('IHDR', $ihdr)
            .self::chunk('IDAT', gzcompress($scanlines));

        if ($padBytes > 0) {
            // tEXt is ignored by decoders, so padding keeps the file valid.
            $png .= self::chunk('tEXt', 'Comment'.chr(0).str_repeat('x', $padBytes));
        }

        return $png.self::chunk('IEND', '');
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            .$type
            .$data
            .pack('N', crc32($type.$data));
    }
}
