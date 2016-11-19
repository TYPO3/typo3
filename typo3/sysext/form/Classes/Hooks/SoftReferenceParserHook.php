<?php
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\Database\SoftReferenceIndex;

/**
 * Register new referenced formDefinitions within a plugin as a soft reference.
 *
 * This is used in BE to track how often a specific form is used in a content
 * element. The number is shown in the form module "Manage forms".
 *
 * Scope: backend
 * @internal
 */
class SoftReferenceParserHook extends SoftReferenceIndex
{
    /**
     * Main function through which all processing happens
     *
     * @param string $table Database table name
     * @param string $field Field name for which processing occurs
     * @param int $uid UID of the record
     * @param string $content The content/value of the field
     * @param string $spKey The softlink parser key. This is only interesting if more than one parser is grouped in the same class. That is the case with this parser.
     * @param array $spParams Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
     * @param string $structurePath If running from inside a FlexForm structure, this is the path of the tag.
     * @return array Result array on positive matches, see description above. Otherwise FALSE
     */
    public function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '')
    {
        $this->tokenID_basePrefix = $table . ':' . $uid . ':' . $field . ':' . $structurePath . ':' . $spKey;
        $tokenId = $this->makeTokenID($content);
        return [
            'content' => '{softref:' . $tokenId . '}',
            'elements' => [
                $tokenId => [
                    'matchString' => $content,
                    'subst' => [
                        'type' => 'string',
                        'tokenID' => $tokenId,
                        'tokenValue' => $content
                    ],
                ]
            ]
        ];
    }
}
