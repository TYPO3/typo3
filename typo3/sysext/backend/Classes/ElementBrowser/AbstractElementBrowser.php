<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\ElementBrowser;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Base class for element browsers
 * This class should only be used internally. Extensions must implement the ElementBrowserInterface.
 *
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
abstract class AbstractElementBrowser
{
    use PageRendererBackendSetupTrait;

    /**
     * The element browsers unique identifier
     */
    protected string $identifier = '';

    /**
     * Typed DTO containing all browser parameters.
     */
    protected ElementBrowserParameters $browserParameters;

    protected ?ServerRequestInterface $request = null;
    protected ViewInterface $view;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly ComponentFactory $componentFactory,
    ) {}

    /**
     * Main initialization
     */
    protected function initialize(ServerRequestInterface $request)
    {
        $this->setUpBasicPageRendererForBackend($this->pageRenderer, $this->extensionConfiguration, $this->getRequest(), $this->getLanguageService());
        $view = $this->backendViewFactory->create($request);
        $this->view = $view;
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/hotkeys.js');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->initVariables($request);
    }

    /**
     * Returns the identifier for the browser
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    protected function initVariables(ServerRequestInterface $request)
    {
        $this->browserParameters = ElementBrowserParameters::fromRequest($request);
    }

    protected function getBodyTagParameters(): string
    {
        $bodyDataAttributes = array_merge(
            $this->getBParamDataAttributes(),
            $this->getBodyTagAttributes()
        );
        return GeneralUtility::implodeAttributes($bodyDataAttributes, true, true);
    }

    /**
     * @return array<string, string> Array of body-tag attributes
     */
    protected function getBodyTagAttributes()
    {
        return [];
    }

    /**
     * Returns data attributes for the body tag, used by the Javascript.
     *
     * @return array<string, string|null> Data attributes for Javascript
     */
    protected function getBParamDataAttributes()
    {
        return $this->browserParameters->toDataAttributes();
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
        // initialize here, this is a dirty hack as long as the interface does not support setting a request object properly
        // see ElementBrowserController.php for the process on how the program code flow is used
        $this->initialize($request);
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $this->request ?? $GLOBALS['TYPO3_REQUEST'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
