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

namespace TYPO3\CMS\Frontend\EventListener;

use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * This event listener removes the Content Element "General Plugin" (CType: list) if
 * there are no `list_type` plugins registered.
 *
 * @deprecated Remove event listener in v14, when the whole sub types feature is removed
 */
class RemoveListTypePluginFromNewContentElementWizard
{
    #[AsEventListener(identifier: 'remove-list-type-plugin')]
    public function __invoke(ModifyNewContentElementWizardItemsEvent $event): void
    {
        $identifier = 'plugins_list';
        if (!$event->hasWizardItem($identifier)) {
            return;
        }
        $subtypeValueField = $GLOBALS['TCA']['tt_content']['types']['list']['subtype_value_field'] ?? '';
        $listTypePlugins = $GLOBALS['TCA']['tt_content']['columns'][$subtypeValueField]['config']['items'] ?? [];
        if (!is_array($listTypePlugins) || $listTypePlugins === []) {
            $event->removeWizardItem($identifier);
        }
    }
}
