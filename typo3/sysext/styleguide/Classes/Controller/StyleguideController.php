<?php
namespace TYPO3\CMS\Styleguide\Controller;

/**
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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;

/**
 * Backend module for Styleguide
 */
class StyleguideController extends ActionController
{
    /**
     * Buttons
     */
    public function buttonsAction()
    {
    }

    /**
     * Index
     */
    public function indexAction()
    {
    }

    /**
     * Typography
     */
    public function typographyAction()
    {
    }

    /**
     * Forms
     */
    public function formsAction()
    {
    }

    /**
     * Trees
     */
    public function treesAction()
    {
    }

    /**
     * Tables
     */
    public function tablesAction()
    {
    }

    /**
     * TCA
     */
    public function tcaAction()
    {
    }

    /**
     * Debug
     */
    public function debugAction()
    {
    }

    /**
     * Icons
     */
    public function iconsAction()
    {
        $this->view->assign('icons', $GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable']);
    }

    /**
     * FlashMessages
     */
    public function flashMessagesAction()
    {
        $loremIpsum = $this->objectManager->get(KauderwelschService::class)->getLoremIpsum();
        $this->addFlashMessage($loremIpsum, 'Info - Title for Info message', FlashMessage::INFO, true);
        $this->addFlashMessage($loremIpsum, 'Notice - Title for Notice message', FlashMessage::NOTICE, true);
        $this->addFlashMessage($loremIpsum, 'Error - Title for Error message', FlashMessage::ERROR, true);
        $this->addFlashMessage($loremIpsum, 'Ok - Title for OK message', FlashMessage::OK, true);
        $this->addFlashMessage($loremIpsum, 'Warning - Title for Warning message', FlashMessage::WARNING, true);
    }

    /**
     * Callouts
     */
    public function calloutAction()
    {
    }

    /**
     * Helpers
     */
    public function helpersAction()
    {
    }

    /**
     * Avatar
     */
    public function avatarAction()
    {
        $this->view->assign(
            'backendUser',
            $GLOBALS['BE_USER']->user
        );
    }

    /**
     * Tabs
     */
    public function tabAction()
    {
        /** @var \TYPO3\CMS\Backend\Template\ModuleTemplate */
        $module = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);

        $menuItems = array(
            0 => array(
                'label' => 'First label',
                'content' => 'First content'
            ),
            1 => array(
                'label' => 'Second label',
                'content' => 'Second content'
            ),
            2 => array(
                'label' => 'Third label',
                'content' => 'Third content'
            )
        );
        $tabs = $module->getDynamicTabMenu($menuItems, 'ident');
        $this->view->assign('tabs', $tabs);
    }
}
