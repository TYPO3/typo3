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

namespace TYPO3\CMS\Backend\View\RecordIdentity;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\SchemaLabelResolver;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Resolves a human-readable type label for a record.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class RecordTypeLabelResolver
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private SchemaLabelResolver $schemaLabelResolver,
    ) {}

    /**
     * @param array<string, mixed> $record
     */
    public function resolve(string $table, array $record): string
    {
        $languageService = $this->getLanguageService();
        $schema = $this->tcaSchemaFactory->has($table)
            ? $this->tcaSchemaFactory->get($table)
            : null;
        $typeLabel = $schema !== null
            ? $schema->getTitle($languageService->sL(...))
            : $table;

        if ($schema !== null && $schema->supportsSubSchema()) {
            $fieldName = $schema->getSubSchemaTypeInformation()->getFieldName();
            $rawTypeValue = $record[$fieldName] ?? '';
            $typeValue = is_array($rawTypeValue) ? (string)($rawTypeValue[0] ?? '') : (string)$rawTypeValue;
            if ($typeValue !== '') {
                $label = $languageService->sL($this->schemaLabelResolver->getLabelForFieldValue($table, $fieldName, $typeValue, $record));
                if ($label === '' && $schema->hasSubSchema($typeValue)) {
                    $label = $schema->getSubSchema($typeValue)->getTitle($languageService->sL(...));
                }
                if ($label !== '') {
                    $typeLabel = $label;
                }
            }
        }

        return $typeLabel;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
