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

namespace TYPO3\CMS\Backend\Domain\Model\Element;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Model for creating an immediate action element on a module
 *
 * @internal not part of TYPO3 Core API due to possible refactorings in this place.
 */
class ImmediateActionElement
{
    public const MODULE_NAME = 'TYPO3/CMS/Backend/Storage/ModuleStateStorage';

    protected string $action;
    protected ?array $args = null;

    public static function forAction(string $action): self
    {
        return new self($action, null);
    }

    public static function moduleStateUpdate(string $module, $identifier, bool $select = null): self
    {
        return new self(
            'TYPO3.Backend.Storage.ModuleStateStorage.update',
            [$module, $identifier, $select]
        );
    }

    public static function moduleStateUpdateWithCurrentMount(string $module, $identifier, bool $select = null): self
    {
        return new self(
            'TYPO3.Backend.Storage.ModuleStateStorage.updateWithCurrentMount',
            [$module, $identifier, $select]
        );
    }

    public static function dispatchCustomEvent(string $name, array $details = null, bool $useTop = false): self
    {
        return new self(
            'TYPO3.Backend.Event.EventDispatcher.dispatchCustomEvent',
            [$name, $details, $useTop]
        );
    }

    private function __construct(string $action, ?array $args)
    {
        $this->action = $action;
        $this->args = $args;
    }

    public function __toString(): string
    {
        $attributes = ['action' => $this->action];
        if ($this->args !== null) {
            $attributes['args'] = GeneralUtility::jsonEncodeForHtmlAttribute($this->args);
        }
        return sprintf(
            '<typo3-immediate-action %s></typo3-immediate-action>',
            GeneralUtility::implodeAttributes($attributes, true)
        );
    }
}
