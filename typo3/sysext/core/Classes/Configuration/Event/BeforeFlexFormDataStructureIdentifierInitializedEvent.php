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

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Listeners to this event are able to specify the data structure identifier,
 * used for a given TCA flex field.
 *
 * Listeners should call ->setIdentifier() to set the identifier or ignore the
 * event to allow other listeners to set it. Do not set an empty string as this
 * will immediately stop event propagation!
 *
 * The identifier SHOULD include the keys specified in the Identifier definition
 * on FlexFormTools, and nothing else. Adding other keys may or may not work,
 * depending on other code that is enabled, and they are not guaranteed nor
 * covered by BC guarantees.
 *
 * Warning: If adding source record details like the uid or pid here, this may turn out to be fragile.
 * Be sure to test scenarios like workspaces and data handler copy/move well, additionally, this may
 * break in between different core versions.
 * It is probably a good idea to return at least something like [ 'type' => 'myExtension', ... ], see
 * the core internal 'tca' and 'record' return values below
 *
 * See the note on FlexFormTools regarding the schema of $dataStructure.
 */
final class BeforeFlexFormDataStructureIdentifierInitializedEvent implements StoppableEventInterface
{
    private ?array $identifier = null;

    /**
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     */
    public function __construct(
        private readonly array $fieldTca,
        private readonly string $tableName,
        private readonly string $fieldName,
        private readonly array $row,
    ) {
    }

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

    public function getRow(): array
    {
        return $this->row;
    }

    public function setIdentifier(array $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): ?array
    {
        return $this->identifier ?? null;
    }

    public function isPropagationStopped(): bool
    {
        return isset($this->identifier);
    }
}
