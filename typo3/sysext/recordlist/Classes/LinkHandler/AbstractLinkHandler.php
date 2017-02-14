<?php
namespace TYPO3\CMS\Recordlist\LinkHandler;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;

/**
 * Base class for link handlers
 *
 * NOTE: This class should only be used internally. Extensions must implement the LinkHandlerInterface.
 */
abstract class AbstractLinkHandler
{
    /**
     * Available additional link attributes
     *
     * 'rel' only works in RTE, still we have to declare support for it.
     *
     * @var string[]
     */
    protected $linkAttributes = [ 'target', 'title', 'class', 'params', 'rel' ];

    /**
     * @var bool
     */
    protected $updateSupported = true;

    /**
     * @var AbstractLinkBrowserController
     */
    protected $linkBrowser;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Initialize the handler
     *
     * @param AbstractLinkBrowserController $linkBrowser
     * @param string $identifier
     * @param array $configuration Page TSconfig
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        $this->linkBrowser = $linkBrowser;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('recordlist');
        $this->view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:recordlist/Resources/Private/Templates/LinkBrowser')]);
        $this->view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:recordlist/Resources/Private/Partials/LinkBrowser')]);
        $this->view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:recordlist/Resources/Private/Layouts/LinkBrowser')]);
    }

    /**
     * @return array
     */
    public function getLinkAttributes()
    {
        return $this->linkAttributes;
    }

    /**
     * @param string[] $fieldDefinitions Array of link attribute field definitions
     * @return string[]
     */
    public function modifyLinkAttributes(array $fieldDefinitions)
    {
        return $fieldDefinitions;
    }

    /**
     * Return TRUE if the handler supports to update a link.
     *
     * This is useful for e.g. file or page links, when only attributes are changed.
     *
     * @return bool
     */
    public function isUpdateSupported()
    {
        return $this->updateSupported;
    }

    /**
     * Sets a DB mount and stores it in the currently defined backend user in her/his uc
     */
    protected function setTemporaryDbMounts()
    {
        $backendUser = $this->getBackendUser();

        // Clear temporary DB mounts
        $tmpMount = GeneralUtility::_GET('setTempDBmount');
        if (isset($tmpMount)) {
            $backendUser->setAndSaveSessionData('pageTree_temporaryMountPoint', (int)$tmpMount);
        }
        // Set temporary DB mounts
        $alternativeWebmountPoint = (int)$backendUser->getSessionData('pageTree_temporaryMountPoint');
        if ($alternativeWebmountPoint) {
            $alternativeWebmountPoint = GeneralUtility::intExplode(',', $alternativeWebmountPoint);
            $backendUser->setWebmounts($alternativeWebmountPoint);
        } else {
            // Setting alternative browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
            $alternativeWebmountPoints = trim($backendUser->getTSConfigVal('options.pageTree.altElementBrowserMountPoints'));
            $appendAlternativeWebmountPoints = $backendUser->getTSConfigVal('options.pageTree.altElementBrowserMountPoints.append');
            if ($alternativeWebmountPoints) {
                $alternativeWebmountPoints = GeneralUtility::intExplode(',', $alternativeWebmountPoints);
                $this->getBackendUser()->setWebmounts($alternativeWebmountPoints, $appendAlternativeWebmountPoints);
            }
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
