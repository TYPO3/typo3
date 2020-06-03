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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers\Be;

use TYPO3\CMS\Extensionmanager\Controller\AbstractController;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

/**
 * Special ViewHelper for the BE module of the Extension Manager. Loads JS code for triggering
 * refresh events.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <em:be.trigger triggers="TYPO3.ModuleMenu.App.refreshMenu" />
 * </code>
 * <output>
 * Writes custom HTML instruction tags
 * </output>
 *
 * @internal
 */
class TriggerViewHelper extends AbstractBackendViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

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
     */
    public function render()
    {
        $html = '';
        // Handle triggers
        $triggers = $this->arguments['triggers'] ?? [];
        if (!empty($triggers[AbstractController::TRIGGER_RefreshModuleMenu])) {
            $html .= $this->buildInstructionDataTag('TYPO3.ModuleMenu.App.refreshMenu');
        }
        if (!empty($triggers[AbstractController::TRIGGER_RefreshTopbar])) {
            $html .= $this->buildInstructionDataTag('TYPO3.Backend.Topbar.refresh');
        }
        return $html;
    }

    protected function buildInstructionDataTag(string $dispatchAction): string
    {
        return sprintf(
            '<typo3-immediate-action action="%s"></typo3-immediate-action>' . "\n",
            htmlspecialchars($dispatchAction)
        );
    }
}
