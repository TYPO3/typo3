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

namespace TYPO3\CMS\Tstemplate\ViewHelpers\If;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * True if a given object is an instance of given class-string.
 *
 * @internal This is not part of TYPO3 Core API.
 */
final class IsInstanceOfViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'object', 'Object to check', true);
        $this->registerArgument('class', 'string', 'Class string to check against', true);
    }

    protected static function evaluateCondition($arguments = null)
    {
        return $arguments['value'] instanceof $arguments['class'];
    }
}
