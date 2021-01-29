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

namespace TYPO3\CMS\Backend\ViewHelpers\Mfa;

use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * Check if the given provider for the current user has the requested state set
 *
 * @internal
 */
class IfHasStateViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('state', 'string', 'The state to check for (e.g. active or locked)', true);
        $this->registerArgument('provider', MfaProviderManifestInterface::class, 'The provider in question', true);
    }

    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        $stateMethod = 'is' . ucfirst($arguments['state']);
        $provider = $arguments['provider'];

        $propertyManager = MfaProviderPropertyManager::create($provider, $GLOBALS['BE_USER']);
        return is_callable([$provider, $stateMethod]) && $provider->{$stateMethod}($propertyManager);
    }
}
