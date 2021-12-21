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

namespace TYPO3\CMS\Recordlist\Browser;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for element browsers
 *
 * NOTE: This class should only be used internally. Extensions must implement the ElementBrowserInterface.
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
abstract class AbstractElementBrowser
{
    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

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

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Main initialization
     */
    protected function initialize()
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->getRequest());
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $this->moduleTemplate->getView()->setTemplate('ElementBrowser');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/ElementBrowser');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Viewport/ResizableNavigation');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->determineScriptUrl();
        $this->initVariables();
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

    /**
     * Initialize the body tag for the module
     */
    protected function setBodyTagParameters()
    {
        $bodyDataAttributes = array_merge(
            $this->getBParamDataAttributes(),
            $this->getBodyTagAttributes()
        );
        $bodyTag = $this->moduleTemplate->getBodyTag();
        $bodyTag = str_replace('>', ' ' . GeneralUtility::implodeAttributes($bodyDataAttributes, true, true) . '>', $bodyTag);
        $this->moduleTemplate->setBodyTag($bodyTag);
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
        $fieldRef = $params[0];
        $rteParams = $params[1];
        $rteConfig = $params[2];
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

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
