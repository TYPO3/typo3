<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Core\IO;

/**
 * Inspired by https://csv.thephpleague.com/9.0/interoperability/enclose-field/
 *
 * A unique sequence is added to relevant CSV field values in order to trigger enclosure in fputcsv.
 * This stream filter is taking care of removing that sequence again when actually writing to stream.
 */
class CsvStreamFilter extends \php_user_filter
{
    protected const NAME = 'csv.typo3';

    /**
     * @var array contains 'sequence' key for stream filter
     * @private
     * @internal
     */
    public $params = [];

    /**
     * Implicitly handles stream filter when writing CSV data - example:
     *
     * @example
     * $resource = fopen('file.csv', 'w');
     * $modifier = CsvUtility::applyStreamFilter($resource);
     * fputcsv($resource, $modifier($fieldValues));
     * fclose($resource);
     *
     * @param resource $in
     * @param resource $out
     * @param int $consumed
     * @param bool $closing
     * @return int
     */
    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            // removes sequence boundary indicator
            $bucket->data = str_replace(
                $this->params['sequence'],
                '',
                $bucket->data
            );
            if ($this->params['LF'] === false) {
                // remove line-feed added by `fputcsv` per default
                $bucket->data = preg_replace('#\r?\n$#', '', $bucket->data);
            }
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }

    /**
     * @param resource $stream
     * @param bool $LF whether to apply line-feed
     * @return \Closure
     */
    public static function applyStreamFilter($stream, bool $LF = true): \Closure
    {
        self::registerStreamFilter();
        // must contain a spacing character to enforce enclosure
        $sequence = "\t\x1d\x1e\x1f";
        stream_filter_append(
            $stream,
            self::NAME,
            STREAM_FILTER_WRITE,
            ['sequence' => $sequence, 'LF' => $LF]
        );
        return self::buildStreamFilterModifier($sequence);
    }

    /**
     * Registers stream filter
     */
    protected static function registerStreamFilter()
    {
        if (in_array(self::NAME, stream_get_filters(), true)) {
            return;
        }
        stream_filter_register(
            self::NAME,
            static::class
        );
    }

    /**
     * @param string $sequence
     * @return \Closure
     */
    protected static function buildStreamFilterModifier(string $sequence): \Closure
    {
        return function ($element) use ($sequence) {
            foreach ($element as &$value) {
                if (is_numeric($value) || $value === '') {
                    continue;
                }
                $value = $sequence . $value;
            }
            unset($value); // de-reference
            return $element;
        };
    }
}
