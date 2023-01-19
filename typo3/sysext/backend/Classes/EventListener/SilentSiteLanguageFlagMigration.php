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

namespace TYPO3\CMS\Backend\EventListener;

use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;

/**
 * @deprecated Silent site language flag migration will be removed with v13.
 */
final class SilentSiteLanguageFlagMigration
{
    private const FLAGICONMAP = [
        'an' => 'nl', // Netherlands Antilles -> Netherlands
        'mi' => 'nz', // Māori -> New Zealand
        'kl' => 'gl', // Greenlandig -> Greenland
        'cs' => 'rs', // State Union of Serbia and Montenegro -> Republic of Serbia
        'qc' => 'ca-qc', // Canada, Quebec
        'catalonia' => 'es-ct', // Spain, Catalonia
    ];

    public function __invoke(SiteConfigurationLoadedEvent $event): void
    {
        $configuration = $event->getConfiguration();

        // Migrate Languages
        if (is_array($configuration['languages'] ?? null)) {
            $configuration['languages'] = array_map(static function ($language) {
                if (is_string($language['flag'] ?? null) && isset(self::FLAGICONMAP[$language['flag']])) {
                    $replacement = self::FLAGICONMAP[$language['flag']];
                    trigger_error(
                        sprintf(
                            'The flag icon "%s" has been replaced with "%s" as it is deprecated and will be removed in TYPO3 v13.0. Please adjust your site configuration.',
                            $language['flag'],
                            $replacement
                        ),
                        E_USER_DEPRECATED
                    );
                    $language['flag'] = $replacement;
                }

                return $language;
            }, $configuration['languages']);
        }

        $event->setConfiguration($configuration);
    }
}
