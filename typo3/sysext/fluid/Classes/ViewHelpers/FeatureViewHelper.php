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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This ViewHelper checks if a feature flag is enabled
 *
 * Examples
 * ========
 *
 * Basic usage
 * -----------
 *
 * ::
 *
 *    <f:feature name="myFeatureFlag">
 *       This is being shown if the flag is enabled
 *    </f:feature>
 *
 * feature / then / else
 * ---------------------
 *
 * ::
 *
 *    <f:feature name="myFeatureFlag">
 *       <f:then>
 *          Flag is enabled
 *       </f:then>
 *       <f:else>
 *          Flag is undefined or not enabled
 *       </f:else>
 *    </f:feature>
 */
final class FeatureViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'name of the feature flag that should be checked', true);
    }

    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        return GeneralUtility::makeInstance(Features::class)->isFeatureEnabled($arguments['name']);
    }
}
