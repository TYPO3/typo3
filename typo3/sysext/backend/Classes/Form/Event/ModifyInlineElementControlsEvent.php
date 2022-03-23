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

namespace TYPO3\CMS\Backend\Form\Event;

/**
 * Listeners to this Event will be able to modify the controls of an inline element
 */
final class ModifyInlineElementControlsEvent
{
    public function __construct(
        private array $controls,
        private readonly array $data,
        private readonly array $record,
    ) {
    }

    /**
     * Returns all controls with their markup
     */
    public function getControls(): array
    {
        return $this->controls;
    }

    /**
     * Overwrite the controls
     */
    public function setControls(array $controls): void
    {
        $this->controls = $controls;
    }

    /**
     * Returns the markup for the requested control
     */
    public function getControl(string $identifier): string
    {
        return $this->controls[$identifier] ?? '';
    }

    /**
     * Set a control with the given identifier and markup
     * IMPORTANT: Overwrites an existing control with the same identifier
     */
    public function setControl(string $identifier, string $markup): void
    {
        $this->controls[$identifier] = $markup;
    }

    /**
     * Returns whether a control exists for the given identifier
     */
    public function hasControl(string $identifier): bool
    {
        return isset($this->controls[$identifier]);
    }

    /**
     * Removes a control from the inline element, if it exists
     *
     * @return bool Whether the control could be removed
     */
    public function removeControl(string $identifier): bool
    {
        if (!$this->hasControl($identifier)) {
            return false;
        }

        unset($this->controls[$identifier]);
        return true;
    }

    /**
     * Returns the whole element data
     */
    public function getElementData(): array
    {
        return $this->data;
    }

    /**
     * Returns the current record of the controls are created for
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * Returns the uid of the parent (embedding) record (uid or NEW...)
     */
    public function getParentUid(): string
    {
        return (string)($this->data['inlineParentUid'] ?? '');
    }

    /**
     * Returns the table (foreign_table) the controls are created for
     */
    public function getForeignTable(): string
    {
        return (string)($this->getFieldConfiguration()['foreign_table'] ?? '');
    }

    /**
     * Returns the TCA configuration of the inline record field
     */
    public function getFieldConfiguration(): array
    {
        return (array)($this->data['inlineParentConfig'] ?? []);
    }

    /**
     * Returns whether the current records is only virtually shown and not physically part of the parent record
     */
    public function isVirtual(): bool
    {
        return (bool)($this->data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false);
    }
}
