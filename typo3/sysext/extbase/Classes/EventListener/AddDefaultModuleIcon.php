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

namespace TYPO3\CMS\Extbase\EventListener;

use TYPO3\CMS\Backend\Module\BeforeModuleCreationEvent;

/**
 * Set default extbase icon for extbase modules
 */
final class AddDefaultModuleIcon
{
    public function __invoke(BeforeModuleCreationEvent $event)
    {
        if (!$event->hasConfigurationValue('controllerActions')
            || $event->getConfigurationValue('icon')
            || $event->getConfigurationValue('iconIdentifier')
        ) {
            // Either no extbase module or icon / iconIdentifier is already set
            return;
        }

        $event->setConfigurationValue('icon', 'EXT:extbase/Resources/Public/Icons/Extension.svg');
    }
}
