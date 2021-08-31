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

namespace TYPO3\CMS\Form\EventListener;

use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * Event listener to disable certain actions when checking for form.yaml files.
 * @internal
 */
class ProcessFileListActionsEventListener
{
    protected const DISABLED_ACTIONS = ['edit', 'view', 'replace', 'rename', 'download'];

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        if (!$event->isFile()) {
            return;
        }
        $fullIdentifier = $event->getResource()->getCombinedIdentifier();
        if (!str_ends_with($fullIdentifier, FormPersistenceManager::FORM_DEFINITION_FILE_EXTENSION)) {
            return;
        }

        $actionItems = $event->getActionItems();
        foreach (self::DISABLED_ACTIONS as $disableIconName) {
            if (!empty($actionItems[$disableIconName])) {
                $actionItems[$disableIconName] = null;
            }
        }
        $event->setActionItems($actionItems);
    }
}
