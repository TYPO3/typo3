<?php
namespace TYPO3\CMS\Recordlist\Browser;

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

use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Base class for element browsers
 *
 * NOTE: This class should only be used internally. Extensions must implement the ElementBrowserInterface.
 */
abstract class AbstractElementBrowser
{
    /**
    * @var DocumentTemplate
    */
    protected $doc;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer = null;

    /**
     * URL of current request
     *
     * @var string
     */
    protected $thisScript = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Active with TYPO3 Element Browser: Contains the name of the form field for which this window
     * opens - thus allows us to make references back to the main window in which the form is.
     * Example value: "data[pages][39][bodytext]|||tt_content|"
     * or "data[tt_content][NEW3fba56fde763d][image]|||gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai|"
     *
     * Values:
     * 0: form field name reference, eg. "data[tt_content][123][image]"
     * 1: htmlArea RTE parameters: editorNo:contentTypo3Language
     * 2: RTE config parameters: RTEtsConfigParams
     * 3: allowed types. Eg. "tt_content" or "gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai"
     * 4: IRRE uniqueness: target level object-id to perform actions/checks on, eg. "data[79][tt_address][1][<field>][<foreign_table>]"
     * 5: IRRE uniqueness: name of function in opener window that checks if element is already used, eg. "inline.checkUniqueElement"
     * 6: IRRE uniqueness: name of function in opener window that performs some additional(!) action, eg. "inline.setUniqueElement"
     * 7: IRRE uniqueness: name of function in opener window that performs action instead of using addElement/insertElement, eg. "inline.importElement"
     *
     * $pArr = explode('|', $this->bparams);
     * $formFieldName = $pArr[0];
     * $allowedTablesOrFileTypes = $pArr[3];
     *
     * @var string
     */
    protected $bparams;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageRenderer->loadJquery();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/ElementBrowser');

        $this->initialize();
    }

    /**
     * Main initialization
     */
    protected function initialize()
    {
        $this->determineScriptUrl();
        $this->initVariables();
    }

    /**
     * Sets the script url depending on being a module or script request
     */
    protected function determineScriptUrl()
    {
        if ($routePath = GeneralUtility::_GP('route')) {
            $router = GeneralUtility::makeInstance(Router::class);
            $route = $router->match($routePath);
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->thisScript = (string)$uriBuilder->buildUriFromRoute($route->getOption('_identifier'));
        } elseif ($moduleName = GeneralUtility::_GP('M')) {
            $this->thisScript = BackendUtility::getModuleUrl($moduleName);
        } else {
            $this->thisScript = GeneralUtility::getIndpEnv('SCRIPT_NAME');
        }
    }

    /**
     */
    protected function initVariables()
    {
        $this->bparams = GeneralUtility::_GP('bparams');
        if ($this->bparams === null) {
            $this->bparams = '';
        }
    }

    /**
     * Initialize document template object
     */
    protected function initDocumentTemplate()
    {
        $bodyDataAttributes = array_merge(
            $this->getBParamDataAttributes(),
            $this->getBodyTagAttributes()
        );
        foreach ($bodyDataAttributes as $attributeName => $value) {
            $this->doc->bodyTagAdditions .= ' ' . $attributeName . '="' . htmlspecialchars($value) . '"';
        }

        // unset the default jumpToUrl() function as we ship our own
        unset($this->doc->JScodeArray['jumpToUrl']);
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    abstract protected function getBodyTagAttributes();

    /**
     * Splits parts of $this->bparams and returns needed data attributes for the Javascript
     *
     * @return string[] Data attributes for Javascript
     */
    protected function getBParamDataAttributes()
    {
        list($fieldRef, $rteParams, $rteConfig, , $irreObjectId, $irreCheckUniqueAction, $irreAddAction, $irreInsertAction) = explode('|', $this->bparams);

        return [
            'data-this-script-url' => strpos($this->thisScript, '?') === false ? $this->thisScript . '?' : $this->thisScript . '&',
            'data-form-field-name' => 'data[' . $fieldRef . '][' . $rteParams . '][' . $rteConfig . ']',
            'data-field-reference' => $fieldRef,
            'data-field-reference-slashed' => addslashes($fieldRef),
            'data-rte-parameters' => $rteParams,
            'data-rte-configuration' => $rteConfig,
            'data-irre-object-id' => $irreObjectId,
            'data-irre-check-unique-action' => $irreCheckUniqueAction,
            'data-irre-add-action' => $irreAddAction,
            'data-irre-insert-action' => $irreInsertAction,
        ];
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
