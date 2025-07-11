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

namespace TYPO3\CMS\Form\Domain\Model\FormElements;

use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Interface for form elements capable of resolving their submitted value
 * to a display representation (e.g. translating option keys to labels).
 *
 * @internal
 */
interface ProcessableValueFormElementInterface
{
    public function processElementValue(mixed $value, FormRuntime $formRuntime): mixed;
}
