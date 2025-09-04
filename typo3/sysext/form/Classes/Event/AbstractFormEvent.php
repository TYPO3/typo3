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

namespace TYPO3\CMS\Form\Event;

/**
 * Listeners to those Events will be able to modify the form definition and
 * persistence identifier before a form is processed, e.g. created or saved.
 */
abstract class AbstractFormEvent
{
    /**
     * @param array{type: string, label: string, identifier: string, prototypeName: string, renderables?: array} $form
     *        The form definition as array. The array contains at least the following keys: type, label, identifier,
     *        prototypeName. Optional the array may contain a key "renderables" with predefined renderables of the form.
     */
    public function __construct(
        public string $formPersistenceIdentifier,
        public array $form
    ) {}
}
