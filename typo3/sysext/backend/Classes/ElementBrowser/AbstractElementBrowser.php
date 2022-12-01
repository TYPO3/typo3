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

namespace TYPO3\CMS\Backend\ElementBrowser;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
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
     * URL of current request
     *
     * @var string
     */
    protected $thisScript = '';

    /**
     * Active with TYPO3 Element Browser: Contains the name of the form field for which this window
     * opens - thus allows us to make references back to the main window in which the form is.
     * Example value: "data[pages][39][bodytext]|||tt_content|"
     * or "data[tt_content][NEW3fba56fde763d][image]|||gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai|"
     * Values:
     * 0: form field name reference, eg. "data[tt_content][123][image]"
     * 1: htmlArea RTE parameters: editorNo:contentTypo3Language
     * 2: RTE config parameters: RTEtsConfigParams
     * 3: allowed types. Eg. "tt_content" or "gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai"
     * 4: IRRE uniqueness: target level object-id to perform actions/checks on, eg. "data-4-pages-4-nav_icon-sys_file_reference" ("data-<uid>-<table>-<pid>-<field>-<foreign_table>")
     *
     * $pArr = explode('|', $this->bparams);
     * $formFieldName = $pArr[0];
     * $allowedTablesOrFileTypes = $pArr[3];
     *
     * @var string
     */
    protected $bparams = '';

    protected ?ServerRequestInterface $request = null;
    protected ViewInterface $view;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly BackendViewFactory $backendViewFactory,
    ) {
    }

    /**
     * Main initialization
     */
    protected function initialize()
    {
        $this->setUpBasicPageRendererForBackend($this->pageRenderer, $this->extensionConfiguration, $this->getRequest(), $this->getLanguageService());
        $view = $this->backendViewFactory->create($this->request);
        $this->view = $view;
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/viewport/resizable-navigation.js');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->determineScriptUrl();
        $this->initVariables();
    }

    /**
     * Returns the identifier for the browser
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Sets the script url depending on being a module or script request
     */
    protected function determineScriptUrl()
    {
        $this->thisScript = (string)$this->uriBuilder->buildUriFromRoute(
            $this->getRequest()->getAttribute('route')->getOption('_identifier')
        );
    }

    protected function initVariables()
    {
        $this->bparams = $this->getRequest()->getParsedBody()['bparams'] ?? $this->getRequest()->getQueryParams()['bparams'] ?? '';
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
     * Splits parts of $this->bparams and returns needed data attributes for the Javascript
     *
     * @return array<string, string> Data attributes for Javascript
     */
    protected function getBParamDataAttributes()
    {
        $params = explode('|', $this->bparams);
        $fieldRef = $params[0] ?? null;
        $rteParams = $params[1] ?? null;
        $rteConfig = $params[2] ?? null;
        $irreObjectId = $params[4] ?? null;

        return [
            'data-form-field-name' => 'data[' . $fieldRef . '][' . $rteParams . '][' . $rteConfig . ']',
            'data-field-reference' => $fieldRef,
            'data-rte-parameters' => $rteParams,
            'data-rte-configuration' => $rteConfig,
            'data-irre-object-id' => $irreObjectId,
        ];
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
        // initialize here, this is a dirty hack as long as the interface does not support setting a request object properly
        // see ElementBrowserController.php for the process on how the program code flow is used
        $this->initialize();
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
