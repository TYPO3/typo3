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

namespace TYPO3\CMS\Lowlevel\HookProvider;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Controller\ConfigurationController;
use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

/**
 * This class provides a replacement for the "modifyBlindedConfigurationOptions" hook, which is deprecated
 * and will be removed in v13. Extension authors have to use the PSR-14 ModifyBlindedConfigurationOptionsEvent.
 *
 * @internal
 * @deprecated remove in v13
 */
final class ModifyBlindedConfigurationOptionsHookProvider
{
    public function __invoke(ModifyBlindedConfigurationOptionsEvent $event): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][ConfigurationController::class]['modifyBlindedConfigurationOptions'])
            && count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][ConfigurationController::class]['modifyBlindedConfigurationOptions']) > 0
        ) {
            trigger_error(
                'The hook "modifyBlindedConfigurationOptions" has been marked as deprecated and will be removed with TYPO3 v13. Use the PSR-14 ModifyBlindedConfigurationOptionsEvent instead.',
                E_USER_DEPRECATED
            );

            $blindedConfigurationOptions = $event->getBlindedConfigurationOptions();
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][ConfigurationController::class]['modifyBlindedConfigurationOptions'] ?? [] as $classReference) {
                $processingObject = GeneralUtility::makeInstance($classReference);
                $blindedConfigurationOptions = $processingObject->modifyBlindedConfigurationOptions($blindedConfigurationOptions);
            }
            $event->setBlindedConfigurationOptions($blindedConfigurationOptions);
        }
    }
}
