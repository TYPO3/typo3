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

use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Listeners to this Event will be able to modify the data of a single form element,
 * for example the human-readable "processedValue", before it is handed over to Fluid.
 */
final class BeforeFormValueIsRenderedEvent
{
    /**
     * @param array{element: FormElementInterface, isSection?: bool, value?: mixed, processedValue?: mixed, isMultiValue?: bool} $data
     */
    public function __construct(
        public array $data,
        public readonly FormElementInterface $element,
        public readonly FormRuntime $formRuntime,
    ) {}
}
