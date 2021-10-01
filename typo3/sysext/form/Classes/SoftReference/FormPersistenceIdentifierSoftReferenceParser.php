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

namespace TYPO3\CMS\Form\SoftReference;

use TYPO3\CMS\Core\DataHandling\SoftReference\AbstractSoftReferenceParser;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserResult;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Register new referenced formDefinitions within a plugin as a soft reference.
 *
 * This is used in BE to track how often a specific form is used in a content
 * element. The number is shown in the form module "Manage forms".
 *
 * Scope: backend
 * @internal
 */
class FormPersistenceIdentifierSoftReferenceParser extends AbstractSoftReferenceParser
{
    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        $this->setTokenIdBasePrefix($table, (string)$uid, $field, $structurePath);
        $tokenId = $this->makeTokenID($content);

        if (PathUtility::isExtensionPath($content)) {
            return $this->createResultForExtensionReference($content, $tokenId);
        }
        try {
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $file = $resourceFactory->retrieveFileOrFolderObject($content);
        } catch (\Exception $e) {
            // Top level catch to ensure useful following exception handling, because FAL throws top level exceptions.
            // TYPO3\CMS\Core\Database\ReferenceIndex::getRelations() will check the return value of this hook with is_array()
            // so we return null to tell getRelations() to do nothing.
            return SoftReferenceParserResult::createWithoutMatches();
        }

        if ($file === null) {
            return SoftReferenceParserResult::createWithoutMatches();
        }

        return SoftReferenceParserResult::create('{softref:' . $tokenId . '}', [
            $tokenId => [
                'matchString' => $content,
                'subst' => [
                    'type' => 'db',
                    'recordRef' => 'sys_file:' . $file->getUid(),
                    'tokenID' => $tokenId,
                    'tokenValue' => $content,
                ],
            ],
        ]);
    }

    private function createResultForExtensionReference(string $extensionReference, string $tokenId): SoftReferenceParserResult
    {
        return SoftReferenceParserResult::create('{softref:' . $tokenId . '}', [
            $tokenId => [
                'matchString' => $extensionReference,
                'subst' => [
                    'type' => 'string',
                    'tokenID' => $tokenId,
                    'tokenValue' => $extensionReference,
                ],
            ],
        ]);
    }
}
