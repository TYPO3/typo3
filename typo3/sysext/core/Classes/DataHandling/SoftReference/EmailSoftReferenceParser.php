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
 * Finding email addresses in content and making them substitutable.
 */
class EmailSoftReferenceParser extends AbstractSoftReferenceParser
{
    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        $this->setTokenIdBasePrefix($table, (string)$uid, $field, $structurePath);
        $elements = [];
        // Email:
        $parts = preg_split('/([\s\'":<>]+)([A-Za-z0-9._-]+[^-][@][A-Za-z0-9._-]+[.].[A-Za-z0-9]+)/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $idx => $value) {
            if ($idx % 3 === 2) {
                // Ignore invalid emails, which haven't been filtered out by regex.
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
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
