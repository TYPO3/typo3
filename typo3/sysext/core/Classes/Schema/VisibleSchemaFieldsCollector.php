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

namespace TYPO3\CMS\Core\Schema;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;

/**
 * Class that provides record type dependant fields, visible for the current user, taking language context into account
 */
readonly class VisibleSchemaFieldsCollector
{
    public function __construct(
        private TcaSchemaFactory $schemaFactory,
        private RecordFactory $recordFactory,
    ) {}

    public function getFields(string $schemaName, array $row, array $exlcudeFieldNames = []): FieldCollection
    {
        if (!$this->schemaFactory->has($schemaName)) {
            return new FieldCollection();
        }

        $backendUser = $this->getBackendUser();
        $schema = $this->schemaFactory->get($schemaName);
        $fields = $schema->getFields();
        $record = $this->recordFactory->createRawRecord($schemaName, $row);

        if ($schema->hasSubSchema($record->getRecordType() ?? '')) {
            $subSchema = $schema->getSubSchema($record->getRecordType());
            // @todo Support of "subtypes" will most likely be deprecated in upcoming versions
            if ($subSchema->getSubTypeDivisorField() !== null
                && $record->has($subSchema->getSubTypeDivisorField()->getName())
                && isset($subSchema->getSubSchemata()[$record->get($subSchema->getSubTypeDivisorField()->getName())])
            ) {
                $subSchema = $subSchema->getSubSchema($record->get($subSchema->getSubTypeDivisorField()->getName()));
            }
            $fields = $subSchema->getFields();
        }

        // FieldCollection is immutable - to remove fields we transform it to an array
        $fields = iterator_to_array($fields);

        foreach ($exlcudeFieldNames as $fieldName) {
            unset($fields[$fieldName]);
        }

        $isOverlay = false;
        if ($schema->hasCapability(TcaSchemaCapability::Language)) {
            $isOverlay = (int)($record->toArray()[$schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName()] ?? 0) > 0;
        }

        foreach ($fields as $field) {
            if (($field->supportsAccessControl() && !$backendUser->check('non_exclude_fields', $schemaName . ':' . $field->getName()))
                || ($isOverlay && empty($field->getConfiguration()['l10n_display']) && ($field->getConfiguration()['l10n_mode'] ?? '') === 'exclude')
            ) {
                unset($fields[$field->getName()]);
            }
        }

        return new FieldCollection($fields);
    }

    /**
     * @return string[]
     */
    public function getFieldNames(string $schemaName, array $row, array $excludeFieldNames = []): array
    {
        return array_map(static fn(FieldTypeInterface $field): string => $field->getName(), iterator_to_array($this->getFields($schemaName, $row, $excludeFieldNames)));
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
