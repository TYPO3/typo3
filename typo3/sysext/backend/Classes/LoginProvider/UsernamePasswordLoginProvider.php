<?php
namespace TYPO3\CMS\Backend\LoginProvider;

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

use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class UsernamePasswordLoginProvider
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class UsernamePasswordLoginProvider implements LoginProviderInterface
{
    const SIGNAL_getPageRenderer = 'getPageRenderer';

    /**
     * @param StandaloneView $view
     * @param PageRenderer $pageRenderer
     * @param LoginController $loginController
     * @throws \UnexpectedValueException
     */
    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        GeneralUtility::makeInstance(ObjectManager::class)->get(Dispatcher::class)->dispatch(__CLASS__, self::SIGNAL_getPageRenderer, [$pageRenderer]);

        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/UserPassLogin');

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/UserPassLoginForm.html'));
        if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            $view->assign('presetUsername', GeneralUtility::_GP('u'));
            $view->assign('presetPassword', GeneralUtility::_GP('p'));
        }
    }
}
