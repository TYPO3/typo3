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
 * A full field value targeted for manual substitution (for import /export features)
 */
class SubstituteSoftReferenceParser extends AbstractSoftReferenceParser
{
    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        $this->setTokenIdBasePrefix($table, (string)$uid, $field, $structurePath);
        $tokenID = $this->makeTokenID();

        return SoftReferenceParserResult::create(
            '{softref:' . $tokenID . '}',
            [
                [
                    'matchString' => $content,
                    'subst' => [
                        'type' => 'string',
                        'tokenID' => $tokenID,
                        'tokenValue' => $content,
                    ],
                ],
            ]
        );
    }
}
