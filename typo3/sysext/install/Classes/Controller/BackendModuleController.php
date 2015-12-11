<?php
namespace TYPO3\CMS\Install\Controller;

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
 * Backend module controller
 *
 * Embeds in backend an only shows the 'enable install tool button' or redirects
 * to step installer if install tool is enabled.
 *
 * This is a classic extbase module that does not interfere with the other code
 * within the install tool.
 */
class BackendModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var \TYPO3\CMS\Install\Service\EnableFileService
     */
    protected $enableFileService;

    /**
     * @var \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
     */
    protected $formProtection;

    /**
     * @param \TYPO3\CMS\Install\Service\EnableFileService $enableFileService
     */
    public function injectEnableFileService(\TYPO3\CMS\Install\Service\EnableFileService $enableFileService)
    {
        $this->enableFileService = $enableFileService;
    }

    /**
     * Set formprotection property
     */
    public function initializeAction()
    {
        $this->formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
    }

    /**
     * Index action shows install tool / step installer or redirect to action to enable install tool
     *
     * @return void
     */
    public function indexAction()
    {
        if ($this->enableFileService->checkInstallToolEnableFile()) {
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect('sysext/install/Start/Install.php?install[context]=backend');
        } else {
            $this->forward('showEnableInstallToolButton');
        }
    }

    /**
     * Show enable install tool
     *
     * @return void
     */
    public function showEnableInstallToolButtonAction()
    {
        $token = $this->formProtection->generateToken('installTool');
        $this->view->assign('installToolEnableToken', $token);
    }

    /**
     * Enable the install tool
     *
     * @param string $installToolEnableToken
     * @throws \RuntimeException
     */
    public function enableInstallToolAction($installToolEnableToken)
    {
        if (!$this->formProtection->validateToken($installToolEnableToken, 'installTool')) {
            throw new \RuntimeException('Given form token was not valid', 1369161225);
        }
        $this->enableFileService->createInstallToolEnableFile();
        $this->forward('index');
    }

    /**
     * Redirect to specified URI
     *
     * @param string $uri
     * @param string $_1 Dummy parameter to ensure compatible method signature
     * @param string $_2 Dummy parameter to ensure compatible method signature
     * @param array $_3 Dummy parameter to ensure compatible method signature
     * @param int $_4 Dummy parameter to ensure compatible method signature
     * @param int $_5 Dummy parameter to ensure compatible method signature
     * @param int $_6 Dummy parameter to ensure compatible method signature
     */
    protected function redirect(
        $uri,
        $_1 = null,
        $_2 = null,
        array $_3 = null,
        $_4 = null,
        $_5 = 0,
        $_6 = 303
    ) {
        \TYPO3\CMS\Core\Utility\HttpUtility::redirect($uri);
    }
}
