<?php

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

namespace TYPO3\CMS\Extensionmanager\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Abstract action controller.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class AbstractController extends ActionController
{
    const TRIGGER_RefreshModuleMenu = 'refreshModuleMenu';
    const TRIGGER_RefreshTopbar = 'refreshTopbar';

    /**
     * @var array
     */
    protected $triggerArguments = [
        self::TRIGGER_RefreshModuleMenu,
        self::TRIGGER_RefreshTopbar,
    ];

    /**
     * Translation shortcut
     *
     * @param string $key
     * @param array|null $arguments
     * @return string
     */
    protected function translate($key, $arguments = null)
    {
        return LocalizationUtility::translate($key, 'extensionmanager', $arguments) ?? '';
    }

    /**
     * Handles trigger arguments, e.g. refreshing the module menu
     * widget if an extension with backend modules has been enabled
     * or disabled.
     */
    protected function handleTriggerArguments()
    {
        $triggers = [];
        foreach ($this->triggerArguments as $triggerArgument) {
            if ($this->request->hasArgument($triggerArgument)) {
                $triggers[$triggerArgument] = $this->request->getArgument($triggerArgument);
            }
        }
        $this->view->assign('triggers', $triggers);
    }
}
