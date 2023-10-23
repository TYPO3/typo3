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

namespace TYPO3\CMS\Backend\LinkHandler;

use TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Base class for core link handlers.
 *
 * @internal This class should only be used internally. Extensions must implement the LinkHandlerInterface.
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
    protected $linkAttributes = ['target', 'title', 'class', 'params', 'rel'];

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

    protected ViewInterface $view;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    public function __construct() {}

    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        $this->linkBrowser = $linkBrowser;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
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

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    public function setView(ViewInterface $view): void
    {
        $this->view = $view;
    }
}
