<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers\Be;

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

use TYPO3\CMS\Extensionmanager\Controller\AbstractController;

/**
 * Special ViewHelper for the BE module of the Extension Manager. Loads JS code for triggering
 * refresh events.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <em:be.container triggers="{triggers}" />
 * </code>
 * <output>
 * Writes some JS inline code
 * </output>
 *
 * @internal
 */
class TriggerViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper
{
    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('triggers', 'array', 'Defined triggers to be forwarded to client (e.g. refreshing backend widgets)', false, []);
    }

    /**
     * Loads some JS inline code based on a list of triggers. This is used to reload the main
     * menu when modules are loaded/unloaded.
     *
     * @return string This ViewHelper does not return any content
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
     * @see \TYPO3\CMS\Core\Page\PageRenderer
     */
    public function render()
    {
        $pageRenderer = $this->getPageRenderer();
        // Handle triggers
        if (!empty($this->arguments['triggers'][AbstractController::TRIGGER_RefreshModuleMenu])) {
            $pageRenderer->addJsInlineCode(
                AbstractController::TRIGGER_RefreshModuleMenu,
                'if (top && top.TYPO3.ModuleMenu.App) { top.TYPO3.ModuleMenu.App.refreshMenu(); }'
            );
        }

        if (!empty($this->arguments['triggers'][AbstractController::TRIGGER_RefreshTopbar])) {
            $pageRenderer->addJsInlineCode(
                AbstractController::TRIGGER_RefreshTopbar,
                'if (top && top.TYPO3.Backend && top.TYPO3.Backend.Topbar) { top.TYPO3.Backend.Topbar.refresh(); }'
            );
        }
        return '';
    }
}
