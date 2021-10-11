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

namespace TYPO3\CMS\Core\DataHandling\SoftReference;

/**
 * Finding URLs in content
 */
class UrlSoftReferenceParser extends AbstractSoftReferenceParser
{
    protected const REGEXP = '/([^[:alnum:]"\']+)((https?|ftp):\\/\\/(?:[!#$&-;=?-\[\]_a-z~]+|%[0-9a-fA-F]{2})+)([[:space:]])?/';

    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        $elements = [];
        // URLs
        $parts = preg_split(self::REGEXP, ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $idx => $value) {
            if ($idx % 5 === 3) {
                unset($parts[$idx]);
            }
            if ($idx % 5 === 2) {
                $tokenID = $this->makeTokenID((string)$idx);
                $elements[$idx] = [];
                $elements[$idx]['matchString'] = $value;
                if (in_array('subst', $this->parameters, true)) {
                    $parts[$idx] = '{softref:' . $tokenID . '}';
                    $elements[$idx]['subst'] = [
                        'type' => 'string',
                        'tokenID' => $tokenID,
                        'tokenValue' => $value,
                    ];
                }
            }
        }

        return SoftReferenceParserResult::create(
            substr(implode('', $parts), 1, -1),
            $elements
        );
    }
}
