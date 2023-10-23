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

namespace TYPO3\CMS\Core\Configuration\Event;

/**
 * Listeners to this event are able to modify or enhance the data structure identifier,
 * which is used for a given TCA flex field.
 *
 * This event can be used to add additional data to an identifier. Be careful here, especially if
 * stuff from the source record like uid or pid is added! This may easily lead to issues with
 * data handler details like copy or move records, localization and version overlays.
 * Test this very well! Multiple listeners may add information to the same identifier here -
 * take care to namespace array keys. Information added here can be later used in the
 * data structure related PSR-14 Events (BeforeFlexFormDataStructureParsedEvent and
 * AfterFlexFormDataStructureParsedEvent) again.
 *
 * See the note on FlexFormTools regarding the schema of $dataStructure.
 */
final class AfterFlexFormDataStructureIdentifierInitializedEvent
{
    /**
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @param array $identifier The data structure identifier (set by event listener of the default)
     */
    public function __construct(
        private readonly array $fieldTca,
        private readonly string $tableName,
        private readonly string $fieldName,
        private readonly array $row,
        private array $identifier,
    ) {}

    /**
     * Returns the full TCA of the currently handled field, having
     * `type=flex` set.
     */
    public function getFieldTca(): array
    {
        return $this->fieldTca;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Returns the whole database row of the current record.
     */
    public function getRow(): array
    {
        return $this->row;
    }

    /**
     * Allows to modify or completely replace the initialized data
     * structure identifier.
     */
    public function setIdentifier(array $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * Returns the initialized data structure identifier, which has
     * either been defined by an event listener or set to the default
     * by the `FlexFormTools` component.
     */
    public function getIdentifier(): array
    {
        return $this->identifier;
    }
}
