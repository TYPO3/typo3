<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Install\Controller\Action;

/**
 * Welcome page
 */
class LoadExtensions extends Action\AbstractAction
{
    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $extensionCompatibilityTesterFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3temp/assets/ExtensionCompatibilityTester.txt';
        $this->view
            ->assign('extensionCompatibilityTesterProtocolFile', $extensionCompatibilityTesterFile)
            ->assign('extensionCompatibilityTesterMessages', $this->getExtensionCompatibilityTesterMessages());

        return $this->view->render();
    }
}
