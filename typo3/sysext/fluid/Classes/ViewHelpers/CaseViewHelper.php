<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\DefaultCaseViewHelper;

/**
 * Case view helper that is only usable within the SwitchViewHelper.
 *
 * @see \TYPO3Fluid\Fluid\ViewHelpers\SwitchViewHelper
 *
 * @api
 */
class CaseViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\CaseViewHelper
{
    /**
     * Overrides the "value" argument definition, making it optional.
     *
     * @return void
     */
    public function initializeArguments() {
        parent::initializeArguments();
        $this->overrideArgument('value', 'mixed', 'Value to match in this case', false);
    }

    /**
     * @param array $arguments
     * @return void
     */
    public function handleAdditionalArguments(array $arguments) {
        if (isset($arguments['default'])) {
            GeneralUtility::deprecationLog('Argument "default" on f:case is deprecated - use f:defaultCase instead');
            if ((bool)$arguments['default']) {
                // Patch the ViewHelperNode (parse-time only) and change it's class name
                $attribute = new \ReflectionProperty($this->viewHelperNode, 'viewHelperClassName');
                $attribute->setAccessible(true);
                $attribute->setValue($this->viewHelperNode, DefaultCaseViewHelper::class);
            }
        }
    }

    /**
     * @param array $arguments
     * @return void
     */
    public function validateAdditionalArguments(array $arguments) {
        // Unset the "default" argument and let everything else through to be validated
        unset($arguments['default']);
        parent::validateAdditionalArguments($arguments);
    }
}
