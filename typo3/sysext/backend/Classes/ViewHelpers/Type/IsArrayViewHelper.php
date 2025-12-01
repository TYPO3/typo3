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

namespace TYPO3\CMS\Backend\ViewHelpers\Type;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to check whether the given value is an array.
 *
 * ```
 *    <f:if condition="{be:type.isArray(value: myVariable)}">
 *        <f:then>myVariable is an array</f:then>
 *        <f:else>myVariable is not an array</f:else>
 *    </f:if>
 * ```
 *
 * @internal This experimental ViewHelper is not part of TYPO3 Core API and may change or vanish any time.
 */
final class IsArrayViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'mixed', 'The variable being checked', true);
    }

    public function render(): bool
    {
        return is_array($this->arguments['value']);
    }
}
