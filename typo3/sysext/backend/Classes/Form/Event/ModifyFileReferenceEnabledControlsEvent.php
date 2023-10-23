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
 * Listeners to this Event will be able to modify the state (enabled or disabled) for controls of a file reference
 */
final class ModifyFileReferenceEnabledControlsEvent
{
    /**
     * @var array<string, bool>
     */
    private array $controlsState;

    public function __construct(
        private readonly array $data,
        private readonly array $record,
    ) {
        $this->controlsState = (array)($data['inlineParentConfig']['appearance']['enabledControls'] ?? []);
    }

    /**
     * Enable a control, if it exists
     *
     * @return bool Whether the control could be enabled
     */
    public function enableControl(string $identifier): bool
    {
        if (!$this->hasControl($identifier)) {
            return false;
        }

        $this->controlsState[$identifier] = true;
        return true;
    }

    /**
     * Disable a control, if it exists
     *
     * @return bool Whether the control could be disabled
     */
    public function disableControl(string $identifier): bool
    {
        if (!$this->hasControl($identifier)) {
            return false;
        }

        $this->controlsState[$identifier] = false;
        return true;
    }

    /**
     * Returns whether a control exists for the given identifier
     */
    public function hasControl(string $identifier): bool
    {
        return isset($this->controlsState[$identifier]);
    }

    /**
     * Returns whether the control is enabled.
     * Note: Will also return FALSE in case no control exists for the requested identifier
     */
    public function isControlEnabled(string $identifier): bool
    {
        return (bool)($this->controlsState[$identifier] ?? false);
    }

    /**
     * Returns all controls with their state (enabled or disabled)
     */
    public function getControlsState(): array
    {
        return $this->controlsState;
    }

    /**
     * Returns all enabled controls
     */
    public function getEnabledControls(): array
    {
        return array_filter($this->controlsState, static fn($control) => (bool)$control === true);
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
     * Returns the TCA configuration of the TCA type=file field
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
