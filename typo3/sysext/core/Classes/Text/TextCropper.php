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

namespace TYPO3\CMS\Core\Text;

class TextCropper
{
    /**
     * Implements "cropHTML" which is a modified "substr" function allowing to limit a string length to a certain number
     * of chars (from either start or end of string) and having a pre/postfix applied if the string really was cropped.
     *
     * Note:  Crop is done without properly respecting html tags and entities.
     *
     * @param string $content The string to perform the operation on
     * @param int $numberOfChars Max number of chars of the string. Negative value means cropping from end of string.
     * @param string $replacementForEllipsis  The pre/postfix string to apply if cropping occurs.
     * @param bool $cropToSpace If true then crop will be applied at nearest space.
     * @return string The processed input value.
     */
    public function crop(string $content, int $numberOfChars, string $replacementForEllipsis, bool $cropToSpace): string
    {
        if (!$numberOfChars || !(mb_strlen($content, 'utf-8') > abs($numberOfChars))) {
            return $content;
        }

        if ($numberOfChars < 0) {
            // cropping from the right side of the content, prepanding replacementForEllipsis
            $content = mb_substr($content, $numberOfChars, null, 'utf-8');
            $truncatePosition = $cropToSpace ? mb_strpos($content, ' ', 0, 'utf-8') : false;
            return $truncatePosition > 0
                ? $replacementForEllipsis . mb_substr($content, $truncatePosition, null, 'utf-8')
                : $replacementForEllipsis . $content;
        }

        // cropping from the left side of content, appending replacementForEllipsis
        $content = mb_substr($content, 0, $numberOfChars, 'utf-8');
        $truncatePosition = $cropToSpace ? mb_strrpos($content, ' ', 0, 'utf-8') : false;
        return $truncatePosition > 0
            ? mb_substr($content, 0, $truncatePosition, 'utf-8') . $replacementForEllipsis
            : $content . $replacementForEllipsis;
    }
}
