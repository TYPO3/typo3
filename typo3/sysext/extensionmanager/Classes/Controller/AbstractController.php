<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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

/**
 * Abstract action controller.
 */
class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    const TRIGGER_RefreshModuleMenu = 'refreshModuleMenu';

    /**
     * @var array
     */
    protected $triggerArguments = [
        self::TRIGGER_RefreshModuleMenu,
    ];

    /**
     * Translation shortcut
     *
     * @param $key
     * @param NULL|array $arguments
     * @return NULL|string
     */
    protected function translate($key, $arguments = null)
    {
        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'extensionmanager', $arguments);
    }

    /**
     * Handles trigger arguments, e.g. refreshing the module menu
     * widget if an extension with backend modules has been enabled
     * or disabled.
     *
     * @return void
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
