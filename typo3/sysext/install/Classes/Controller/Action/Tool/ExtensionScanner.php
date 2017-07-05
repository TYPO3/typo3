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

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Install\Controller\Action\AbstractAction;

/**
 * Run code analysis based on changelog documentation
 */
class ExtensionScanner extends AbstractAction
{

    /**
     * Executes the action upon click in the Install Tool Menu
     *
     * @return string Rendered content
     * @throws \InvalidArgumentException
     */
    protected function executeAction()
    {
        $finder = new Finder();
        $extensionsInTypo3conf = $finder->directories()->in(PATH_site . 'typo3conf/ext')->depth('== 0')->sortByName();
        $this->view->assign('extensionsInTypo3conf', $extensionsInTypo3conf);

        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $this->view->assign('extensionScannerFilesToken', $formProtection->generateToken('installTool', 'extensionScannerFiles'));
        $this->view->assign('extensionScannerScanFileToken', $formProtection->generateToken('installTool', 'extensionScannerScanFile'));
        $this->view->assign('extensionScannerMarkFullyScannedRestFilesToken', $formProtection->generateToken('installTool', 'extensionScannerMarkFullyScannedRestFiles'));

        return $this->view->render();
    }
}
