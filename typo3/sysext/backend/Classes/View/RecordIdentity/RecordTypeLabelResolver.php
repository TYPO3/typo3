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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\SchemaLabelResolver;
use TYPO3\CMS\Core\Schema\SchemaTypeInformation;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\MathUtility;

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
            $typeInformation = $schema->getSubSchemaTypeInformation();
            $fieldName = $typeInformation->getFieldName();
            if ($typeInformation->isPointerToForeignFieldInForeignSchema()) {
                $typeValue = $this->resolveTypeValueFromForeignRecord($typeInformation, $record[$fieldName] ?? '');
                $label = '';
            } else {
                $rawTypeValue = $record[$fieldName] ?? '';
                $typeValue = is_array($rawTypeValue) ? (string)($rawTypeValue[0] ?? '') : (string)$rawTypeValue;
                $label = $typeValue !== ''
                    ? $languageService->sL($this->schemaLabelResolver->getLabelForFieldValue($table, $fieldName, $typeValue, $record))
                    : '';
            }
            if ($typeValue !== '') {
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

    /**
     * The pointer field holds the uid of the related record: a plain value in a database
     * row, a list of uids for a select field and a list of relation items for a group
     * field in a row processed by FormEngine.
     */
    private function resolveTypeValueFromForeignRecord(SchemaTypeInformation $typeInformation, mixed $pointerValue): string
    {
        if (is_array($pointerValue)) {
            $pointerValue = $pointerValue[0] ?? '';
            if (is_array($pointerValue)) {
                $pointerValue = $pointerValue['uid'] ?? '';
            }
        }
        if (!MathUtility::canBeInterpretedAsInteger($pointerValue) || (int)$pointerValue <= 0) {
            return '';
        }
        $foreignTable = (string)$typeInformation->getForeignSchemaName();
        $foreignField = (string)$typeInformation->getForeignFieldName();
        $foreignRecord = BackendUtility::getRecord($foreignTable, (int)$pointerValue, $foreignField);
        return (string)($foreignRecord[$foreignField] ?? '');
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
