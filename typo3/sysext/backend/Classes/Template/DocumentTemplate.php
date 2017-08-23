<?php
namespace TYPO3\CMS\Backend\Template;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * TYPO3 Backend Template Class
 *
 * This class contains functions for starting and ending the HTML of backend modules
 * It also contains methods for outputting sections of content.
 * Further there are functions for making icons, links, setting form-field widths etc.
 * Color scheme and stylesheet definitions are also available here.
 * Finally this file includes the language class for TYPO3's backend.
 *
 * After this file $LANG and $TBE_TEMPLATE are global variables / instances of their respective classes.
 *
 * Please refer to Inside TYPO3 for a discussion of how to use this API.
 */
class DocumentTemplate
{
    // Vars you typically might want to/should set from outside after making instance of this class:
    /**
     * This can be set to the HTML-code for a formtag.
     * Useful when you need a form to span the whole page; Inserted exactly after the body-tag.
     *
     * @var string
     */
    public $form = '';

    /**
     * Additional header code (eg. a JavaScript section) could be accommulated in this var. It will be directly outputted in the header.
     *
     * @var string
     */
    public $JScode = '';

    /**
     * Additional header code for ExtJS. It will be included in document header and inserted in a Ext.onReady(function()
     *
     * @var string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use PageRenderers's JS methods to inject JavaScript on a backend page.
     */
    public $extJScode = '';

    /**
     * Similar to $JScode but for use as array with associative keys to prevent double inclusion of JS code. a <script> tag is automatically wrapped around.
     *
     * @var array
     */
    public $JScodeArray = ['jumpToUrl' => '
function jumpToUrl(URL) {
	window.location.href = URL;
	return false;
}
	'];

    /**
     * Additional 'page-end' code could be accumulated in this var. It will be outputted at the end of page before </body> and some other internal page-end code.
     *
     * @var string
     */
    public $postCode = '';

    /**
     * HTML template with markers for module
     *
     * @var string
     */
    public $moduleTemplate = '';

    /**
     * The base file (not overlaid by TBE_STYLES) for the current module, useful for hooks when finding out which modules is rendered currently
     *
     * @var string
     */
    protected $moduleTemplateFilename = '';

    /**
     * Script ID
     *
     * @var string
     */
    public $scriptID = '';

    /**
     * Id which can be set for the body tag. Default value is based on script ID
     *
     * @var string
     */
    public $bodyTagId = '';

    /**
     * You can add additional attributes to the body-tag through this variable.
     *
     * @var string
     */
    public $bodyTagAdditions = '';

    /**
     * Additional CSS styles which will be added to the <style> section in the header
     * used as array with associative keys to prevent double inclusion of CSS code
     *
     * @var array
     */
    public $inDocStylesArray = [];

    /**
     * Compensation for large documents (used in \TYPO3\CMS\Backend\Form\FormEngine)
     *
     * @var float
     */
    public $form_largeComp = 1.33;

    /**
     * Filename of stylesheet (relative to PATH_typo3)
     *
     * @var string
     */
    public $styleSheetFile = '';

    /**
     * Filename of stylesheet #2 - linked to right after the $this->styleSheetFile script (relative to PATH_typo3)
     *
     * @var string
     */
    public $styleSheetFile2 = '';

    /**
     * Filename of a post-stylesheet - included right after all inline styles.
     *
     * @var string
     */
    public $styleSheetFile_post = '';

    /**
     * Whether to use the X-UA-Compatible meta tag
     *
     * @var bool
     */
    protected $useCompatibilityTag = true;

    /**
     * X-Ua-Compatible version output in meta tag
     *
     * @var string
     */
    protected $xUaCompatibilityVersion = 'IE=edge';

    // Skinning
    /**
     * Include these CSS directories from skins by default
     *
     * @var array
     */
    protected $stylesheetsSkins = [
        'structure' => 'Resources/Public/Css/structure/',
        'visual' => 'Resources/Public/Css/visual/'
    ];

    /**
     * JavaScript files loaded for every page in the Backend
     *
     * @var array
     */
    protected $jsFiles = [];

    /**
     * JavaScript files loaded for every page in the Backend, but explicitly excluded from concatenation (useful for libraries etc.)
     *
     * @var array
     */
    protected $jsFilesNoConcatenation = [];

    /**
     * Indicates if a <div>-output section is open
     *
     * @var int
     * @internal will be removed in TYPO3 v9
     */
    public $sectionFlag = 0;

    /**
     * (Default) Class for wrapping <DIV>-tag of page. Is set in class extensions.
     *
     * @var string
     */
    public $divClass = '';

    /**
     * @var string
     */
    public $pageHeaderBlock = '';

    /**
     * @var string
     */
    public $endOfPageJsBlock = '';

    /**
     * @var bool
     */
    public $hasDocheader = true;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer = null;

    /**
     * Alternative template file
     *
     * @var string
     */
    protected $pageHeaderFooterTemplateFile = '';

    /**
     * Whether flashmessages should be rendered or not
     *
     * @var bool $showFlashMessages
     */
    public $showFlashMessages = true;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    const STATUS_ICON_ERROR = 3;
    const STATUS_ICON_WARNING = 2;
    const STATUS_ICON_NOTIFICATION = 1;
    const STATUS_ICON_OK = -1;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initializes the page rendering object:
        $this->initPageRenderer();

        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // initialize Marker Support
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        // Setting default scriptID, trim forward slash from route
        $this->scriptID = GeneralUtility::_GET('M') !== null ? GeneralUtility::_GET('M') : ltrim(GeneralUtility::_GET('route'), '/');
        $this->bodyTagId = preg_replace('/[^A-Za-z0-9-]/', '-', $this->scriptID);
        // Individual configuration per script? If so, make a recursive merge of the arrays:
        if (is_array($GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID])) {
            // Make copy
            $ovr = $GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID];
            // merge styles.
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TBE_STYLES'], $ovr);
            // Have to unset - otherwise the second instantiation will do it again!
            unset($GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID]);
        }
        // Main Stylesheets:
        if ($GLOBALS['TBE_STYLES']['stylesheet']) {
            $this->styleSheetFile = $GLOBALS['TBE_STYLES']['stylesheet'];
        }
        if ($GLOBALS['TBE_STYLES']['stylesheet2']) {
            $this->styleSheetFile2 = $GLOBALS['TBE_STYLES']['stylesheet2'];
        }
        if ($GLOBALS['TBE_STYLES']['styleSheetFile_post']) {
            $this->styleSheetFile_post = $GLOBALS['TBE_STYLES']['styleSheetFile_post'];
        }
        if ($GLOBALS['TBE_STYLES']['inDocStyles_TBEstyle']) {
            $this->inDocStylesArray['TBEstyle'] = $GLOBALS['TBE_STYLES']['inDocStyles_TBEstyle'];
        }
        // include all stylesheets
        foreach ($this->getSkinStylesheetDirectories() as $stylesheetDirectory) {
            $this->addStyleSheetDirectory($stylesheetDirectory);
        }
    }

    /**
     * Initializes the page renderer object
     */
    protected function initPageRenderer()
    {
        if ($this->pageRenderer !== null) {
            return;
        }
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageRenderer->setLanguage($GLOBALS['LANG']->lang);
        $this->pageRenderer->enableConcatenateFiles();
        $this->pageRenderer->enableCompressCss();
        $this->pageRenderer->enableCompressJavascript();
        // Add all JavaScript files defined in $this->jsFiles to the PageRenderer
        foreach ($this->jsFilesNoConcatenation as $file) {
            $this->pageRenderer->addJsFile(
                $file,
                'text/javascript',
                true,
                false,
                '',
                true
            );
        }
        // Add all JavaScript files defined in $this->jsFiles to the PageRenderer
        foreach ($this->jsFiles as $file) {
            $this->pageRenderer->addJsFile($file);
        }
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] === 1) {
            $this->pageRenderer->enableDebugMode();
        }
    }

    /*****************************************
     *
     * EVALUATION FUNCTIONS
     * Various centralized processing
     *
     *****************************************/

    /**
     * Makes link to page $id in frontend (view page)
     * Returns an icon which links to the frontend index.php document for viewing the page with id $id
     * $id must be a page-uid
     * If the BE_USER has access to Web>List then a link to that module is shown as well (with return-url)
     *
     * @param int $id The page id
     * @return string HTML string with linked icon(s)
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function viewPageIcon($id)
    {
        GeneralUtility::logDeprecatedFunction();
        // If access to Web>List for user, then link to that module.
        $str = '<a href="' . htmlspecialchars(BackendUtility::getModuleUrl('web_list', [
            'id' => $id,
            'returnUrl' > GeneralUtility::getIndpEnv('REQUEST_URI')
        ])) . '" title="' . htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showList')) . '">' . $this->iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render() . '</a>';

        // Make link to view page
        $str .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($id, '', BackendUtility::BEgetRootLine($id))) . '" title="' . htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
        return $str;
    }

    /**
     * Makes the header (icon+title) for a page (or other record). Used in most modules under Web>*
     * $table and $row must be a tablename/record from that table
     * $path will be shown as alt-text for the icon.
     * The title will be truncated to 45 chars.
     *
     * @param string $table Table name
     * @param array $row Record row
     * @param string $path Alt text
     * @param bool $noViewPageIcon Set $noViewPageIcon TRUE if you don't want a magnifier-icon for viewing the page in the frontend
     * @param array $tWrap is an array with indexes 0 and 1 each representing HTML-tags (start/end) which will wrap the title
     * @param bool $enableClickMenu If TRUE, render click menu code around icon image
     * @return string HTML content
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getHeader($table, $row, $path, $noViewPageIcon = false, $tWrap = ['', ''], $enableClickMenu = true)
    {
        GeneralUtility::logDeprecatedFunction();
        $viewPage = '';
        if (is_array($row) && $row['uid']) {
            $iconImgTag = '<span title="' . htmlspecialchars($path) . '">' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '</span>';
            $title = strip_tags(BackendUtility::getRecordTitle($table, $row));
            $viewPage = $noViewPageIcon ? '' : $this->viewPageIcon($row['uid']);
        } else {
            $iconImgTag = '<span title="' . htmlspecialchars($path) . '">' . $this->iconFactory->getIcon('apps-pagetree-page-domain', Icon::SIZE_SMALL)->render() . '</span>';
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        }

        if ($enableClickMenu) {
            $iconImgTag = BackendUtility::wrapClickMenuOnIcon($iconImgTag, $table, $row['uid']);
        }

        return '<span class="typo3-moduleHeader">' . $iconImgTag . $viewPage . $tWrap[0] . htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, 45)) . $tWrap[1] . '</span>';
    }

    /**
     * Like ->getHeader() but for files and folders
     * Returns the icon with the path of the file/folder set in the alt/title attribute. Shows the name after the icon.
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceInterface $resource
     * @param array $tWrap is an array with indexes 0 and 1 each representing HTML-tags (start/end) which will wrap the title
     * @param bool $enableClickMenu If TRUE, render click menu code around icon image
     * @return string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getResourceHeader(\TYPO3\CMS\Core\Resource\ResourceInterface $resource, $tWrap = ['', ''], $enableClickMenu = true)
    {
        GeneralUtility::logDeprecatedFunction();
        try {
            $path = $resource->getStorage()->getName() . $resource->getParentFolder()->getIdentifier();
            $iconImgTag = '<span title="' . htmlspecialchars($path) . '">' . $this->iconFactory->getIconForResource($resource, Icon::SIZE_SMALL)->render() . '</span>';
        } catch (\TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException $e) {
            $iconImgTag = '';
        }

        if ($enableClickMenu && ($resource instanceof \TYPO3\CMS\Core\Resource\File)) {
            $metaData = $resource->_getMetaData();
            $iconImgTag = BackendUtility::wrapClickMenuOnIcon($iconImgTag, 'sys_file_metadata', $metaData['uid']);
        }

        return '<span class="typo3-moduleHeader">' . $iconImgTag . $tWrap[0] . htmlspecialchars(GeneralUtility::fixed_lgd_cs($resource->getName(), 45)) . $tWrap[1] . '</span>';
    }

    /**
     * Returns a linked shortcut-icon which will call the shortcut frame and set a shortcut there back to the calling page/module
     *
     * @param string $gvList Is the list of GET variables to store (if any)
     * @param string $setList Is the list of SET[] variables to store (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend modules
     * @param string $modName Module name string
     * @param string|int $motherModName Is used to enter the "parent module name" if the module is a submodule under eg. Web>* or File>*. You can also set this value to 1 in which case the currentLoadedModule is sent to the shortcut script (so - not a fixed value!) - that is used in file_edit and wizard_rte modules where those are really running as a part of another module.
     * @param string $classes
     * @return string HTML content
     */
    public function makeShortcutIcon($gvList, $setList, $modName, $motherModName = '', $classes = '')
    {
        $gvList = 'route,' . $gvList;
        $storeUrl = $this->makeShortcutUrl($gvList, $setList);
        $pathInfo = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
        // Fallback for alt_mod. We still pass in the old xMOD... stuff, but TBE_MODULES only knows about "record_edit".
        // We still need to pass the xMOD name to createShortcut below, since this is used for icons.
        $moduleName = $modName === 'xMOD_alt_doc.php' ? 'record_edit' : $modName;
        // Add the module identifier automatically if typo3/index.php is used:
        if (GeneralUtility::_GET('M') !== null) {
            $storeUrl = '&M=' . $moduleName . $storeUrl;
        }
        if ((int)$motherModName === 1) {
            $motherModule = 'top.currentModuleLoaded';
        } elseif ($motherModName) {
            $motherModule = GeneralUtility::quoteJSvalue($motherModName);
        } else {
            $motherModule = '\'\'';
        }
        $confirmationText = GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark'));

        $shortcutUrl = $pathInfo['path'] . '?' . $storeUrl;
        $shortcutExist = BackendUtility::shortcutExists($shortcutUrl);

        if ($shortcutExist) {
            return '<a class="active ' . htmlspecialchars($classes) . '" title="">' .
            $this->iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL)->render() . '</a>';
        }
        $url = GeneralUtility::quoteJSvalue(rawurlencode($shortcutUrl));
        $onClick = 'top.TYPO3.ShortcutMenu.createShortcut(' . GeneralUtility::quoteJSvalue(rawurlencode($modName)) .
            ', ' . $url . ', ' . $confirmationText . ', ' . $motherModule . ', this);return false;';

        return '<a href="#" class="' . htmlspecialchars($classes) . '" onclick="' . htmlspecialchars($onClick) . '" title="' .
        htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark')) . '">' .
        $this->iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL)->render() . '</a>';
    }

    /**
     * MAKE url for storing
     * Internal func
     *
     * @param string $gvList Is the list of GET variables to store (if any)
     * @param string $setList Is the list of SET[] variables to store (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend modules
     * @return string
     * @access private
     * @see makeShortcutIcon()
     */
    public function makeShortcutUrl($gvList, $setList)
    {
        $GET = GeneralUtility::_GET();
        $storeArray = array_merge(GeneralUtility::compileSelectedGetVarsFromArray($gvList, $GET), ['SET' => GeneralUtility::compileSelectedGetVarsFromArray($setList, (array)$GLOBALS['SOBE']->MOD_SETTINGS)]);
        $storeUrl = GeneralUtility::implodeArrayForUrl('', $storeArray);
        return $storeUrl;
    }

    /**
     * Returns <input> attributes to set the width of an text-type input field.
     * For client browsers with no CSS support the cols/size attribute is returned.
     * For CSS compliant browsers (recommended) a ' style="width: ...px;"' is returned.
     *
     * @param int $size A relative number which multiplied with approx. 10 will lead to the width in pixels
     * @param bool $textarea A flag you can set for textareas - DEPRECATED as there is no difference any more between the two
     * @param string $styleOverride A string which will be returned as attribute-value for style="" instead of the calculated width (if CSS is enabled)
     * @return string Tag attributes for an <input> tag (regarding width)
     */
    public function formWidth($size = 48, $textarea = false, $styleOverride = '')
    {
        return ' style="' . ($styleOverride ?: 'width:' . ceil($size * 9.58) . 'px;') . '"';
    }

    /**
     * Returns JavaScript variables setting the returnUrl and thisScript location for use by JavaScript on the page.
     * Used in fx. db_list.php (Web>List)
     *
     * @param string $thisLocation URL to "this location" / current script
     * @return string Urls are returned as JavaScript variables T3_RETURN_URL and T3_THIS_LOCATION
     * @see typo3/db_list.php
     */
    public function redirectUrls($thisLocation = '')
    {
        $thisLocation = $thisLocation ? $thisLocation : GeneralUtility::linkThisScript([
            'CB' => '',
            'SET' => '',
            'cmd' => '',
            'popViewId' => ''
        ]);
        $out = '
	var T3_RETURN_URL = ' . GeneralUtility::quoteJSvalue(str_replace('%20', '', rawurlencode(GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'))))) . ';
	var T3_THIS_LOCATION = ' . GeneralUtility::quoteJSvalue(str_replace('%20', '', rawurlencode($thisLocation))) . '
		';
        return $out;
    }

    /**
     * Defines whether to use the X-UA-Compatible meta tag.
     *
     * @param bool $useCompatibilityTag Whether to use the tag
     */
    public function useCompatibilityTag($useCompatibilityTag = true)
    {
        $this->useCompatibilityTag = (bool)$useCompatibilityTag;
    }

    /*****************************************
     *
     *	PAGE BUILDING FUNCTIONS.
     *	Use this to build the HTML of your backend modules
     *
     *****************************************/
    /**
     * Returns page start
     * This includes the proper header with charset, title, meta tag and beginning body-tag.
     *
     * @param string $title HTML Page title for the header
     * @return string Returns the whole header section of a HTML-document based on settings in internal variables (like styles, javascript code, charset, generator and docType)
     * @see endPage()
     */
    public function startPage($title)
    {
        // hook pre start page
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'])) {
            $preStartPageHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'];
            if (is_array($preStartPageHook)) {
                $hookParameters = [
                    'title' => &$title
                ];
                foreach ($preStartPageHook as $hookFunction) {
                    GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
                }
            }
        }
        // alternative template for Header and Footer
        if ($this->pageHeaderFooterTemplateFile) {
            $file = GeneralUtility::getFileAbsFileName($this->pageHeaderFooterTemplateFile);
            if ($file) {
                $this->pageRenderer->setTemplateFile($file);
            }
        }

        // Disable rendering of XHTML tags
        $this->pageRenderer->setRenderXhtml(false);

        $languageCode = $this->pageRenderer->getLanguage() === 'default' ? 'en' : $this->pageRenderer->getLanguage();
        $this->pageRenderer->setHtmlTag('<html lang="' . $languageCode . '">');

        $headerStart = '<!DOCTYPE html>';
        $this->pageRenderer->setXmlPrologAndDocType($headerStart);
        $this->pageRenderer->setHeadTag('<head>' . LF . '<!-- TYPO3 Script ID: ' . htmlspecialchars($this->scriptID) . ' -->');
        header('Content-Type:text/html;charset=utf-8');
        $this->pageRenderer->setCharSet('utf-8');
        $this->pageRenderer->addMetaTag($this->generator());
        $this->pageRenderer->addMetaTag('<meta name="robots" content="noindex,follow">');
        $this->pageRenderer->addMetaTag('<meta charset="utf-8">');
        $this->pageRenderer->addMetaTag('<meta name="viewport" content="width=device-width, initial-scale=1">');
        $this->pageRenderer->setFavIcon($this->getBackendFavicon());
        if ($this->useCompatibilityTag) {
            $this->pageRenderer->addMetaTag($this->xUaCompatible($this->xUaCompatibilityVersion));
        }
        $this->pageRenderer->setTitle($title);
        // add docstyles
        $this->docStyle();
        $this->pageRenderer->addHeaderData($this->JScode);
        foreach ($this->JScodeArray as $name => $code) {
            $this->pageRenderer->addJsInlineCode($name, $code, false);
        }

        if ($this->extJScode) {
            GeneralUtility::deprecationLog('The property DocumentTemplate->extJScode to add ExtJS-based onReadyCode is deprecated since TYPO3 v8, and will be removed in TYPO3 v9. Use the page renderer directly instead to add JavaScript code.');
            $this->pageRenderer->addExtOnReadyCode($this->extJScode);
        }

        // Load jquery and twbs JS libraries on every backend request
        $this->pageRenderer->loadJquery();
        // Note: please do not reference "bootstrap" outside of the TYPO3 Core (not in your own extensions)
        // as this is preliminary as long as Twitter bootstrap does not support AMD modules
        // this logic will be changed once Twitter bootstrap 4 is included
        $this->pageRenderer->addJsFile('EXT:core/Resources/Public/JavaScript/Contrib/bootstrap/bootstrap.js');

        // hook for additional headerData
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'])) {
            $preHeaderRenderHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'];
            if (is_array($preHeaderRenderHook)) {
                $hookParameters = [
                    'pageRenderer' => &$this->pageRenderer
                ];
                foreach ($preHeaderRenderHook as $hookFunction) {
                    GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
                }
            }
        }
        // Construct page header.
        $str = $this->pageRenderer->render(PageRenderer::PART_HEADER);
        $this->JScode = ($this->extJScode = '');
        $this->JScodeArray = [];
        $this->endOfPageJsBlock = $this->pageRenderer->render(PageRenderer::PART_FOOTER);
        $str .= $this->docBodyTagBegin() . ($this->divClass ? '

<!-- Wrapping DIV-section for whole page BEGIN -->
<div class="' . $this->divClass . '">
' : '') . trim($this->form);
        return $str;
    }

    /**
     * Returns page end; This includes finishing form, div, body and html tags.
     *
     * @return string The HTML end of a page
     * @see startPage()
     */
    public function endPage()
    {
        $str = $this->postCode . GeneralUtility::wrapJS(BackendUtility::getUpdateSignalCode()) . ($this->form ? '
</form>' : '');
        // If something is in buffer like debug, put it to end of page
        if (ob_get_contents()) {
            $str .= ob_get_clean();
            if (!headers_sent()) {
                header('Content-Encoding: None');
            }
        }
        $str .= ($this->divClass ? '

<!-- Wrapping DIV-section for whole page END -->
</div>' : '') . $this->endOfPageJsBlock;

        // Logging: Can't find better place to put it:
        GeneralUtility::devLog('END of BACKEND session', \TYPO3\CMS\Backend\Template\DocumentTemplate::class, 0, ['_FLUSH' => true]);
        return $str;
    }

    /**
     * Shortcut for render the complete page of a module
     *
     * @param string $title page title
     * @param string $content page content
     * @return string complete page
     */
    public function render($title, $content)
    {
        $pageContent = $this->startPage($title);
        $pageContent .= $content;
        $pageContent .= $this->endPage();
        return $this->insertStylesAndJS($pageContent);
    }

    /**
     * Returns the header-bar in the top of most backend modules
     * Closes section if open.
     *
     * @param string $text The text string for the header
     * @return string HTML content
     * @deprecated since TYPO3 v8, will be removed in TYPO3 9
     */
    public function header($text)
    {
        GeneralUtility::logDeprecatedFunction();
        $str = '

	<!-- MAIN Header in page top -->
	<h1 class="t3js-title-inlineedit">' . htmlspecialchars($text) . '</h1>
';
        return $this->sectionEnd() . $str;
    }

    /**
     * Begins an output section and sets header and content
     *
     * @param string $label The header
     * @param string $text The HTML-content
     * @param bool $nostrtoupper	A flag that will prevent the header from being converted to uppercase
     * @param bool $sH Defines the type of header (if set, "<h3>" rather than the default "h4")
     * @param int $type The number of an icon to show with the header (see the icon-function). -1,1,2,3
     * @param bool $allowHTMLinHeader If set, HTML tags are allowed in $label (otherwise this value is by default htmlspecialchars()'ed)
     * @return string HTML content
     * @see icons(), sectionHeader()
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function section($label, $text, $nostrtoupper = false, $sH = false, $type = 0, $allowHTMLinHeader = false)
    {
        GeneralUtility::logDeprecatedFunction();
        $str = '';
        // Setting header
        if ($label) {
            if (!$allowHTMLinHeader) {
                $label = htmlspecialchars($label);
            }
            $str .= $this->sectionHeader($this->icons($type) . $label, $sH, $nostrtoupper ? '' : ' class="uppercase"');
        }
        // Setting content
        $str .= '

	<!-- Section content -->
' . $text;
        return $this->sectionBegin() . $str;
    }

    /**
     * Inserts a divider image
     * Ends a section (if open) before inserting the image
     *
     * @param int $dist The margin-top/-bottom of the <hr> ruler.
     * @return string HTML content
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function divider($dist)
    {
        GeneralUtility::logDeprecatedFunction();
        $dist = (int)$dist;
        $str = '

	<!-- DIVIDER -->
	<hr style="margin-top: ' . $dist . 'px; margin-bottom: ' . $dist . 'px;" />
';
        return $this->sectionEnd() . $str;
    }

    /**
     * Make a section header.
     * Begins a section if not already open.
     *
     * @param string $label The label between the <h3> or <h4> tags. (Allows HTML)
     * @param bool $sH If set, <h3> is used, otherwise <h4>
     * @param string $addAttrib Additional attributes to h-tag, eg. ' class=""'
     * @return string HTML content
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function sectionHeader($label, $sH = false, $addAttrib = '')
    {
        GeneralUtility::logDeprecatedFunction();
        $tag = $sH ? 'h2' : 'h3';
        if ($addAttrib && $addAttrib[0] !== ' ') {
            $addAttrib = ' ' . $addAttrib;
        }
        $str = '

	<!-- Section header -->
	<' . $tag . $addAttrib . '>' . $label . '</' . $tag . '>
';
        return $this->sectionBegin() . $str;
    }

    /**
     * Begins an output section.
     * Returns the <div>-begin tag AND sets the ->sectionFlag TRUE (if the ->sectionFlag is not already set!)
     * You can call this function even if a section is already begun since the function will only return something if the sectionFlag is not already set!
     *
     * @return string HTML content
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function sectionBegin()
    {
        GeneralUtility::logDeprecatedFunction();
        if (!$this->sectionFlag) {
            $this->sectionFlag = 1;
            $str = '

	<!-- ***********************
	      Begin output section.
	     *********************** -->
	<div>
';
            return $str;
        }
        return '';
    }

    /**
     * Ends and output section
     * Returns the </div>-end tag AND clears the ->sectionFlag (but does so only IF the sectionFlag is set - that is a section is 'open')
     * See sectionBegin() also.
     *
     * @return string HTML content
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function sectionEnd()
    {
        if ($this->sectionFlag) {
            GeneralUtility::logDeprecatedFunction();
            $this->sectionFlag = 0;
            return '
	</div>
	<!-- *********************
	      End output section.
	     ********************* -->
';
        }
        return '';
    }

    /**
     * Creates the bodyTag.
     * You can add to the bodyTag by $this->bodyTagAdditions
     *
     * @return string HTML body tag
     */
    public function docBodyTagBegin()
    {
        return '<body ' . trim($this->bodyTagAdditions . ($this->bodyTagId ? ' id="' . $this->bodyTagId . '"' : '')) . '>';
    }

    /**
     * Outputting document style
     *
     * @return string HTML style section/link tags
     */
    public function docStyle()
    {
        // Implode it all:
        $inDocStyles = implode(LF, $this->inDocStylesArray);

        // Reset styles so they won't be added again in insertStylesAndJS()
        $this->inDocStylesArray = [];

        if ($this->styleSheetFile) {
            $this->pageRenderer->addCssFile($this->styleSheetFile);
        }
        if ($this->styleSheetFile2) {
            $this->pageRenderer->addCssFile($this->styleSheetFile2);
        }

        if ($inDocStyles !== '') {
            $this->pageRenderer->addCssInlineBlock('inDocStyles', $inDocStyles . LF . '/*###POSTCSSMARKER###*/');
        }

        if ($this->styleSheetFile_post) {
            $this->pageRenderer->addCssFile($this->styleSheetFile_post);
        }
    }

    /**
     * Insert additional style sheet link
     *
     * @param string $key some key identifying the style sheet
     * @param string $href uri to the style sheet file
     * @param string $title value for the title attribute of the link element
     * @param string $relation value for the rel attribute of the link element
     */
    public function addStyleSheet($key, $href, $title = '', $relation = 'stylesheet')
    {
        $this->pageRenderer->addCssFile($href, $relation, 'screen', $title);
    }

    /**
     * Add all *.css files of the directory $path to the stylesheets
     *
     * @param string $path directory to add
     */
    public function addStyleSheetDirectory($path)
    {
        $path = GeneralUtility::getFileAbsFileName($path);
        // Read all files in directory and sort them alphabetically
        $cssFiles = GeneralUtility::getFilesInDir($path, 'css');
        foreach ($cssFiles as $cssFile) {
            $this->pageRenderer->addCssFile(PathUtility::getRelativePathTo($path) . $cssFile);
        }
    }

    /**
     * Insert post rendering document style into already rendered content
     * This is needed for extobjbase
     *
     * @param string $content style-content to insert.
     * @return string content with inserted styles
     */
    public function insertStylesAndJS($content)
    {
        $styles = LF . implode(LF, $this->inDocStylesArray);
        $content = str_replace('/*###POSTCSSMARKER###*/', $styles, $content);

        // Insert accumulated JS
        $jscode = $this->JScode . LF . GeneralUtility::wrapJS(implode(LF, $this->JScodeArray));
        $content = str_replace('<!--###POSTJSMARKER###-->', $jscode, $content);
        return $content;
    }

    /**
     * Returns an array of all stylesheet directories belonging to core and skins
     *
     * @return array Stylesheet directories
     */
    public function getSkinStylesheetDirectories()
    {
        $stylesheetDirectories = [];
        // Stylesheets from skins
        // merge default css directories ($this->stylesheetsSkin) with additional ones and include them
        if (is_array($GLOBALS['TBE_STYLES']['skins'])) {
            // loop over all registered skins
            foreach ($GLOBALS['TBE_STYLES']['skins'] as $skinExtKey => $skin) {
                $skinStylesheetDirs = $this->stylesheetsSkins;
                // Skins can add custom stylesheetDirectories using
                // $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']
                if (is_array($skin['stylesheetDirectories'])) {
                    $skinStylesheetDirs = array_merge($skinStylesheetDirs, $skin['stylesheetDirectories']);
                }
                // Add all registered directories
                foreach ($skinStylesheetDirs as $stylesheetDir) {
                    // for EXT:myskin/stylesheets/ syntax
                    if (strpos($stylesheetDir, 'EXT:') === 0) {
                        list($extKey, $path) = explode('/', substr($stylesheetDir, 4), 2);
                        if (!empty($extKey) && ExtensionManagementUtility::isLoaded($extKey) && !empty($path)) {
                            $stylesheetDirectories[] = ExtensionManagementUtility::extPath($extKey) . $path;
                        }
                    } else {
                        // For relative paths
                        $stylesheetDirectories[] = ExtensionManagementUtility::extPath($skinExtKey) . $stylesheetDir;
                    }
                }
            }
        }
        return $stylesheetDirectories;
    }

    /**
     * Returns generator meta tag
     *
     * @return string <meta> tag with name "generator
     */
    public function generator()
    {
        $str = 'TYPO3 CMS, ' . TYPO3_URL_GENERAL . ', &#169; Kasper Sk&#229;rh&#248;j ' . TYPO3_copyright_year . ', extensions are copyright of their respective owners.';
        return '<meta name="generator" content="' . $str . '" />';
    }

    /**
     * Returns X-UA-Compatible meta tag
     *
     * @param string $content Content of the compatible tag (default: IE-8)
     * @return string <meta http-equiv="X-UA-Compatible" content="???" />
     */
    public function xUaCompatible($content = 'IE=8')
    {
        return '<meta http-equiv="X-UA-Compatible" content="' . $content . '" />';
    }

    /*****************************************
     *
     * OTHER ELEMENTS
     * Tables, buttons, formatting dimmed/red strings
     *
     ******************************************/
    /**
     * Returns an image-tag with an 18x16 icon of the following types:
     *
     * $type:
     * -1:	OK icon (Check-mark)
     * 1:	Notice (Speach-bubble)
     * 2:	Warning (Yellow triangle)
     * 3:	Fatal error (Red stop sign)
     *
     * @param int $type See description
     * @param string $styleAttribValue Value for style attribute
     * @return string HTML image tag (if applicable)
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function icons($type, $styleAttribValue = '')
    {
        GeneralUtility::logDeprecatedFunction();
        switch ($type) {
            case self::STATUS_ICON_ERROR:
                $icon = 'status-dialog-error';
                break;
            case self::STATUS_ICON_WARNING:
                $icon = 'status-dialog-warning';
                break;
            case self::STATUS_ICON_NOTIFICATION:
                $icon = 'status-dialog-notification';
                break;
            case self::STATUS_ICON_OK:
                $icon = 'status-dialog-ok';
                break;
            default:
                // Do nothing
        }
        if ($icon) {
            return $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render();
        }
    }

    /**
     * Returns an <input> button with the $onClick action and $label
     *
     * @param string $onClick The value of the onclick attribute of the input tag (submit type)
     * @param string $label The label for the button (which will be htmlspecialchar'ed)
     * @return string A <input> tag of the type "submit
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function t3Button($onClick, $label)
    {
        GeneralUtility::logDeprecatedFunction();
        $button = '<input class="btn btn-default" type="submit" onclick="' . htmlspecialchars($onClick) . '; return false;" value="' . htmlspecialchars($label) . '" />';
        return $button;
    }

    /**
     * Returns string wrapped in CDATA "tags" for XML / XHTML (wrap content of <script> and <style> sections in those!)
     *
     * @param string $string Input string
     * @return string Output string
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function wrapInCData($string)
    {
        GeneralUtility::logDeprecatedFunction();
        $string = '/*<![CDATA[*/' . $string . '/*]]>*/';
        return $string;
    }

    /**
     * Wraps the input string in script tags.
     * Automatic re-identing of the JS code is done by using the first line as ident reference.
     * This is nice for identing JS code with PHP code on the same level.
     *
     * @param string $string Input string
     * @param bool $linebreak Wrap script element in linebreaks? Default is TRUE.
     * @return string Output string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use GeneralUtility::wrapJS()
     */
    public function wrapScriptTags($string, $linebreak = true)
    {
        GeneralUtility::logDeprecatedFunction();
        if (trim($string)) {
            // <script wrapped in nl?
            $cr = $linebreak ? LF : '';
            // Remove nl from the beginning
            $string = ltrim($string, LF);
            // Re-ident to one tab using the first line as reference
            if ($string[0] === TAB) {
                $string = TAB . ltrim($string, TAB);
            }
            $string = $cr . '<script type="text/javascript">
/*<![CDATA[*/
' . $string . '
/*]]>*/
</script>' . $cr;
        }
        return trim($string);
    }

    /**
     * Returns a one-row/two-celled table with $content and $menu side by side.
     * The table is a 100% width table and each cell is aligned left / right
     *
     * @param string $content Content cell content (left)
     * @param string $menu Menu cell content (right)
     * @return string HTML output
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function funcMenu($content, $menu)
    {
        GeneralUtility::logDeprecatedFunction();
        return '
			<table border="0" cellpadding="0" cellspacing="0" width="100%" id="typo3-funcmenu">
				<tr>
					<td valign="top" nowrap="nowrap">' . $content . '</td>
					<td valign="top" align="right" nowrap="nowrap">' . $menu . '</td>
				</tr>
			</table>';
    }

    /**
     * Includes a javascript library that exists in the core /typo3/ directory
     *
     * @param string $lib: Library name. Call it with the full path like "sysext/core/Resources/Public/JavaScript/QueryGenerator.js" to load it
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function loadJavascriptLib($lib)
    {
        GeneralUtility::logDeprecatedFunction();
        $this->pageRenderer->addJsFile($lib);
    }

    /**
     * Includes the necessary Javascript function for the clickmenu (context sensitive menus) in the document
     *
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getContextMenuCode()
    {
        GeneralUtility::logDeprecatedFunction();
        $this->pageRenderer->loadJquery();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
    }

    /**
     * Includes the necessary javascript file for use on pages which have the
     * drag and drop functionality (legacy folder tree)
     *
     * @param string $table indicator of which table the drag and drop function should work on (pages or folders)
     * @param string $additionalJavaScriptCode adds more code to the additional javascript code
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function getDragDropCode($table, $additionalJavaScriptCode = '')
    {
        GeneralUtility::logDeprecatedFunction();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LegacyTree', 'function() {
			DragDrop.table = "' . $table . '";
			' . $additionalJavaScriptCode . '
		}');
    }

    /**
     * Creates a tab menu from an array definition
     *
     * Returns a tab menu for a module
     * Requires the JS function jumpToUrl() to be available
     *
     * @param mixed $mainParams is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $elementName it the form elements name, probably something like "SET[...]
     * @param string $currentValue is the value to be selected currently.
     * @param array $menuItems is an array with the menu items for the selector box
     * @param string $script is the script to send the &id to, if empty it's automatically found
     * @param string $addparams is additional parameters to pass to the script.
     * @return string HTML code for tab menu
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function getTabMenu($mainParams, $elementName, $currentValue, $menuItems, $script = '', $addparams = '')
    {
        GeneralUtility::logDeprecatedFunction();
        $content = '';
        if (is_array($menuItems)) {
            if (!is_array($mainParams)) {
                $mainParams = ['id' => $mainParams];
            }
            $mainParams = GeneralUtility::implodeArrayForUrl('', $mainParams);
            if (!$script) {
                $script = basename(PATH_thisScript);
            }
            $menuDef = [];
            foreach ($menuItems as $value => $label) {
                $menuDef[$value]['isActive'] = (string)$currentValue === (string)$value;
                $menuDef[$value]['label'] = htmlspecialchars($label, ENT_COMPAT, 'UTF-8', false);
                $menuDef[$value]['url'] = $script . '?' . $mainParams . $addparams . '&' . $elementName . '=' . $value;
            }
            $content = $this->getTabMenuRaw($menuDef);
        }
        return $content;
    }

    /**
     * Creates the HTML content for the tab menu
     *
     * @param array $menuItems Menu items for tabs
     * @return string Table HTML
     * @access private
     */
    public function getTabMenuRaw($menuItems)
    {
        if (!is_array($menuItems)) {
            return '';
        }

        $options = '';
        foreach ($menuItems as $id => $def) {
            $class = $def['isActive'] ? 'active' : '';
            $label = $def['label'];
            $url = htmlspecialchars($def['url']);
            $params = $def['addParams'];

            $options .= '<li class="' . $class . '">' .
                '<a href="' . $url . '" ' . $params . '>' . $label . '</a>' .
                '</li>';
        }

        return '<ul class="nav nav-tabs" role="tablist">' .
                $options .
            '</ul>';
    }

    /**
     * Creates the version selector for the page id inputted.
     * Requires the core version management extension, "version" to be loaded.
     *
     * @param int $id Page id to create selector for.
     * @param bool $noAction If set, there will be no button for swapping page.
     * @return string
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function getVersionSelector($id, $noAction = false)
    {
        GeneralUtility::logDeprecatedFunction();
        if (
                ExtensionManagementUtility::isLoaded('version') &&
                ExtensionManagementUtility::isLoaded('compatibility7') &&
                !ExtensionManagementUtility::isLoaded('workspaces')
        ) {
            $versionGuiObj = GeneralUtility::makeInstance(\TYPO3\CMS\Compatibility7\View\VersionView::class);
            return $versionGuiObj->getVersionSelector($id, $noAction);
        }
    }

    /**
     * Function to load a HTML template file with markers.
     * When calling from own extension, use  syntax getHtmlTemplate('EXT:extkey/template.html')
     *
     * @param string $filename tmpl name, usually in the typo3/template/ directory
     * @return string HTML of template
     */
    public function getHtmlTemplate($filename)
    {
        // setting the name of the original HTML template
        $this->moduleTemplateFilename = $filename;
        if ($GLOBALS['TBE_STYLES']['htmlTemplates'][$filename]) {
            $filename = $GLOBALS['TBE_STYLES']['htmlTemplates'][$filename];
        }
        if (GeneralUtility::isFirstPartOfStr($filename, 'EXT:')) {
            $filename = GeneralUtility::getFileAbsFileName($filename);
        } elseif (!GeneralUtility::isAbsPath($filename)) {
            $filename = GeneralUtility::resolveBackPath($filename);
        } elseif (!GeneralUtility::isAllowedAbsPath($filename)) {
            $filename = '';
        }
        $htmlTemplate = '';
        if ($filename !== '') {
            $htmlTemplate = file_get_contents($filename);
        }
        return $htmlTemplate;
    }

    /**
     * Define the template for the module
     *
     * @param string $filename filename
     */
    public function setModuleTemplate($filename)
    {
        $this->moduleTemplate = $this->getHtmlTemplate($filename);
    }

    /**
     * Put together the various elements for the module <body> using a static HTML
     * template
     *
     * @param array $pageRecord Record of the current page, used for page path and info
     * @param array $buttons HTML for all buttons
     * @param array $markerArray HTML for all other markers
     * @param array $subpartArray HTML for the subparts
     * @return string Composite HTML
     */
    public function moduleBody($pageRecord = [], $buttons = [], $markerArray = [], $subpartArray = [])
    {
        // Get the HTML template for the module
        $moduleBody = $this->templateService->getSubpart($this->moduleTemplate, '###FULLDOC###');
        // Add CSS
        $this->inDocStylesArray[] = 'html { overflow: hidden; }';
        // Get the page path for the docheader
        $markerArray['PAGEPATH'] = $this->getPagePath($pageRecord);
        // Get the page info for the docheader
        $markerArray['PAGEINFO'] = $this->getPageInfo($pageRecord);
        // Get all the buttons for the docheader
        $docHeaderButtons = $this->getDocHeaderButtons($buttons);
        // Merge docheader buttons with the marker array
        $markerArray = array_merge($markerArray, $docHeaderButtons);
        // replacing subparts
        foreach ($subpartArray as $marker => $content) {
            $moduleBody = $this->templateService->substituteSubpart($moduleBody, $marker, $content);
        }
        // adding flash messages
        if ($this->showFlashMessages) {
            $flashMessages = $this->getFlashMessages();
            if (!empty($flashMessages)) {
                $markerArray['FLASHMESSAGES'] = $flashMessages;
                // If there is no dedicated marker for the messages present
                // then force them to appear before the content
                if (strpos($moduleBody, '###FLASHMESSAGES###') === false) {
                    $moduleBody = str_replace('###CONTENT###', '###FLASHMESSAGES######CONTENT###', $moduleBody);
                }
            }
        }
        // Hook for adding more markers/content to the page, like the version selector
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['moduleBodyPostProcess'])) {
            $params = [
                'moduleTemplateFilename' => &$this->moduleTemplateFilename,
                'moduleTemplate' => &$this->moduleTemplate,
                'moduleBody' => &$moduleBody,
                'markers' => &$markerArray,
                'parentObject' => &$this
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['moduleBodyPostProcess'] as $funcRef) {
                GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        // Replacing all markers with the finished markers and return the HTML content
        return $this->templateService->substituteMarkerArray($moduleBody, $markerArray, '###|###');
    }

    /**
     * Get the default rendered FlashMessages from queue
     *
     * @return string
     */
    public function getFlashMessages()
    {
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        return $defaultFlashMessageQueue->renderFlashMessages();
    }

    /**
     * Renders the FlashMessages from queue and returns them as AJAX.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function renderQueuedFlashMessages(ServerRequestInterface $request, ResponseInterface $response)
    {
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $flashMessages = $defaultFlashMessageQueue->getAllMessagesAndFlush();

        $messages = [];
        foreach ($flashMessages as $flashMessage) {
            $messages[] = [
                'title' => $flashMessage->getTitle(),
                'message' => $flashMessage->getMessage(),
                'severity' => $flashMessage->getSeverity()
            ];
        }

        $response->getBody()->write(json_encode($messages));
        return $response;
    }

    /**
     * Fill the button lists with the defined HTML
     *
     * @param array $buttons HTML for all buttons
     * @return array Containing HTML for both buttonlists
     */
    protected function getDocHeaderButtons($buttons)
    {
        $markers = [];
        // Fill buttons for left and right float
        $floats = ['left', 'right'];
        foreach ($floats as $key) {
            // Get the template for each float
            $buttonTemplate = $this->templateService->getSubpart($this->moduleTemplate, '###BUTTON_GROUPS_' . strtoupper($key) . '###');
            // Fill the button markers in this float
            $buttonTemplate = $this->templateService->substituteMarkerArray($buttonTemplate, $buttons, '###|###', true);
            // getting the wrap for each group
            $buttonWrap = $this->templateService->getSubpart($this->moduleTemplate, '###BUTTON_GROUP_WRAP###');
            // looping through the groups (max 6) and remove the empty groups
            for ($groupNumber = 1; $groupNumber < 6; $groupNumber++) {
                $buttonMarker = '###BUTTON_GROUP' . $groupNumber . '###';
                $buttonGroup = $this->templateService->getSubpart($buttonTemplate, $buttonMarker);
                if (trim($buttonGroup)) {
                    if ($buttonWrap) {
                        $buttonGroup = $this->templateService->substituteMarker($buttonWrap, '###BUTTONS###', $buttonGroup);
                    }
                    $buttonTemplate = $this->templateService->substituteSubpart($buttonTemplate, $buttonMarker, trim($buttonGroup));
                }
            }
            // Replace the marker with the template and remove all line breaks (for IE compat)
            $markers['BUTTONLIST_' . strtoupper($key)] = str_replace(LF, '', $buttonTemplate);
        }
        // Hook for manipulating docHeaderButtons
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook'])) {
            $params = [
                'buttons' => $buttons,
                'markers' => &$markers,
                'pObj' => &$this
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook'] as $funcRef) {
                GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        return $markers;
    }

    /**
     * Generate the page path for docheader
     *
     * @param array $pageRecord Current page
     * @return string Page path
     */
    protected function getPagePath($pageRecord)
    {
        // Is this a real page
        if (is_array($pageRecord) && $pageRecord['uid']) {
            $title = substr($pageRecord['_thePathFull'], 0, -1);
            // Remove current page title
            $pos = strrpos($title, $pageRecord['title']);
            if ($pos !== false) {
                $title = substr($title, 0, $pos);
            }
        } else {
            $title = '';
        }
        // Setting the path of the page
        $pagePath = htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.path')) . ': <span class="typo3-docheader-pagePath">';
        // crop the title to title limit (or 50, if not defined)
        $cropLength = empty($GLOBALS['BE_USER']->uc['titleLen']) ? 50 : $GLOBALS['BE_USER']->uc['titleLen'];
        $croppedTitle = GeneralUtility::fixed_lgd_cs($title, -$cropLength);
        if ($croppedTitle !== $title) {
            $pagePath .= '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
        } else {
            $pagePath .= htmlspecialchars($title);
        }
        $pagePath .= '</span>';
        return $pagePath;
    }

    /**
     * Setting page icon with context menu + uid for docheader
     *
     * @param array $pageRecord Current page
     * @return string Page info
     */
    protected function getPageInfo($pageRecord)
    {
        // Add icon with context menu, etc:
        // If there IS a real page
        if (is_array($pageRecord) && $pageRecord['uid']) {
            $alttext = BackendUtility::getRecordIconAltText($pageRecord, 'pages');
            $iconImg = '<span title="' . htmlspecialchars($alttext) . '">' . $this->iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render() . '</span>';
            // Make Icon:
            $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'pages', $pageRecord['uid']);
            $uid = $pageRecord['uid'];
            $title = BackendUtility::getRecordTitle('pages', $pageRecord);
        } else {
            // On root-level of page tree
            // Make Icon
            $iconImg = '<span title="' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '">' . $this->iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render() . '</span>';
            if ($GLOBALS['BE_USER']->user['admin']) {
                $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'pages', 0);
            } else {
                $theIcon = $iconImg;
            }
            $uid = '0';
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        }
        // Setting icon with context menu + uid
        $pageInfo = $theIcon . '<strong>' . htmlspecialchars($title) . '&nbsp;[' . $uid . ']</strong>';
        return $pageInfo;
    }

    /**
    * Retrieves configured favicon for backend (with fallback)
    *
    * @return string
    */
    protected function getBackendFavicon()
    {
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['backend'], ['allowed_classes' => false]);

        if (!empty($extConf['backendFavicon'])) {
            $path =  $this->getUriForFileName($extConf['backendFavicon']);
        } else {
            $path = ExtensionManagementUtility::extPath('backend') . 'Resources/Public/Icons/favicon.ico';
        }
        return PathUtility::getAbsoluteWebPath($path);
    }

    /**
     * Returns the uri of a relative reference, resolves the "EXT:" prefix
     * (way of referring to files inside extensions) and checks that the file is inside
     * the PATH_site of the TYPO3 installation
     *
     * @param string $filename The input filename/filepath to evaluate
     * @return string Returns the filename of $filename if valid, otherwise blank string.
     */
    protected function getUriForFileName($filename)
    {
        if (strpos($filename, '://')) {
            return $filename;
        }
        $urlPrefix = '';
        if (strpos($filename, 'EXT:') === 0) {
            $absoluteFilename = GeneralUtility::getFileAbsFileName($filename);
            $filename = '';
            if ($absoluteFilename !== '') {
                $filename = PathUtility::getAbsoluteWebPath($absoluteFilename);
            }
        } elseif (strpos($filename, '/') !== 0) {
            $urlPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        }
        return $urlPrefix . $filename;
    }
}
