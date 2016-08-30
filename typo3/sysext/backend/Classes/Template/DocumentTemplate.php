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
use TYPO3\CMS\Fluid\View\StandaloneView;

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
     * Similar to $JScode (see below) but used as an associative array to prevent double inclusion of JS code.
     * This is used to include certain external Javascript libraries before the inline JS code.
     * <script>-Tags are not wrapped around automatically
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use pageRenderer directly
     */
    public $JScodeLibArray = [];

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
     * Doc-type used in the header. Default is xhtml_trans. You can also set it to 'html_3', 'xhtml_strict' or 'xhtml_frames'.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, as it is HTML5
     */
    public $docType = '';

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
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use the pageRenderer property for adding CSS styles
     */
    public $inDocStyles = '';

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
     * If set, then a JavaScript section will be outputted in the bottom of page which will try and update the top.busy session expiry object.
     *
     * @var int
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $endJS = 1;

    // TYPO3 Colorscheme.
    // If you want to change this, please do so through a skin using the global var $GLOBALS['TBE_STYLES']

    /**
     * Light background color
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $bgColor = '#F7F3EF';

    /**
     * Steel-blue
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $bgColor2 = '#9BA1A8';

    /**
     * dok.color
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $bgColor3 = '#F6F2E6';

    /**
     * light tablerow background, brownish
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $bgColor4 = '#D9D5C9';

    /**
     * light tablerow background, greenish
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $bgColor5 = '#ABBBB4';

    /**
     * light tablerow background, yellowish, for section headers. Light.
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $bgColor6 = '#E7DBA8';

    /**
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $hoverColor = '#254D7B';

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
     * Background image of page (relative to PATH_typo3)
     *
     * @var string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use a stylesheet instead
     */
    public $backGroundImage = '';

    /**
     * Inline css styling set from TBE_STYLES array
     *
     * @var string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use inDocStylesArray['TBEstyle']
     */
    public $inDocStyles_TBEstyle = '';

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
     * stylesheets from core
     *
     * @var array
     */
    protected $stylesheetsCore = [
        'generatedSprites' => '../typo3temp/sprites/'
    ];

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
     * Will output the parsetime of the scripts in milliseconds (for admin-users).
     * Set this to FALSE when releasing TYPO3. Only for dev.
     *
     * @var bool
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $parseTimeFlag = false;

    /**
     * internal character set, nowadays utf-8 for everything
     *
     * @var string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, as it is always utf-8
     */
    protected $charset = 'utf-8';

    /**
     * Indicates if a <div>-output section is open
     *
     * @var int
     * @internal
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
     * @var bool
     */
    protected $extDirectStateProvider = false;

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

        // load Legacy CSS Support
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LegacyCssClasses');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // initialize Marker Support
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        // Setting default scriptID:
        if (($temp_M = (string)GeneralUtility::_GET('M')) && $GLOBALS['TBE_MODULES']['_PATHS'][$temp_M]) {
            $this->scriptID = preg_replace('/^.*\\/(sysext|ext)\\//', 'ext/', $GLOBALS['TBE_MODULES']['_PATHS'][$temp_M] . 'index.php');
        } else {
            $this->scriptID = preg_replace('/^.*\\/(sysext|ext)\\//', 'ext/', \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(PATH_thisScript));
        }
        if (TYPO3_mainDir != 'typo3/' && substr($this->scriptID, 0, strlen(TYPO3_mainDir)) == TYPO3_mainDir) {
            // This fixes if TYPO3_mainDir has been changed so the script ids are STILL "typo3/..."
            $this->scriptID = 'typo3/' . substr($this->scriptID, strlen(TYPO3_mainDir));
        }
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
            $this->addStylesheetDirectory($stylesheetDirectory);
        }
        // Background image
        if ($GLOBALS['TBE_STYLES']['background']) {
            GeneralUtility::deprecationLog('Usage of $TBE_STYLES["background"] is deprecated. Please use stylesheets directly.');
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
                $GLOBALS['BACK_PATH'] . $file,
                'text/javascript',
                true,
                false,
                '',
                true
            );
        }
        // Add all JavaScript files defined in $this->jsFiles to the PageRenderer
        foreach ($this->jsFiles as $file) {
            $this->pageRenderer->addJsFile($GLOBALS['BACK_PATH'] . $file);
        }
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] === 1) {
            $this->pageRenderer->enableDebugMode();
        }
    }

    /**
     * Gets instance of PageRenderer configured with the current language, file references and debug settings
     *
     * @return PageRenderer
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8.
     */
    public function getPageRenderer()
    {
        GeneralUtility::logDeprecatedFunction();
        $this->initPageRenderer();

        return $this->pageRenderer;
    }

    /**
     * Sets inclusion of StateProvider
     *
     * @return void
     */
    public function setExtDirectStateProvider()
    {
        $this->extDirectStateProvider = true;
    }

    /*****************************************
     *
     * EVALUATION FUNCTIONS
     * Various centralized processing
     *
     *****************************************/
    /**
     * Makes click menu link (context sensitive menu)
     * Returns $str (possibly an <|img> tag/icon) wrapped in a link which will activate the context sensitive menu for the record ($table/$uid) or file ($table = file)
     * The link will load the top frame with the parameter "&item" which is the table,uid and listFr arguments imploded by "|": rawurlencode($table.'|'.$uid.'|'.$listFr)
     *
     * @param string $str String to be wrapped in link, typ. image tag.
     * @param string $table Table name/File path. If the icon is for a database record, enter the tablename from $GLOBALS['TCA']. If a file then enter the absolute filepath
     * @param int $uid If icon is for database record this is the UID for the record from $table
     * @param bool $listFr Tells the top frame script that the link is coming from a "list" frame which means a frame from within the backend content frame.
     * @param string $addParams Additional GET parameters for the link to the ClickMenu AJAX request
     * @param string $enDisItems Enable / Disable click menu items. Example: "+new,view" will display ONLY these two items (and any spacers in between), "new,view" will display all BUT these two items.
     * @param bool $returnTagParameters If set, will return only the onclick JavaScript, not the whole link.
     * @return string The link-wrapped input string.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use BackendUtility::wrapClickMenuOnIcon() instead
     */
    public function wrapClickMenuOnIcon($content, $table, $uid = 0, $listFr = true, $addParams = '', $enDisItems = '', $returnTagParameters = false)
    {
        GeneralUtility::logDeprecatedFunction();
        return BackendUtility::wrapClickMenuOnIcon($content, $table, $uid, $listFr, $addParams, $enDisItems, $returnTagParameters);
    }

    /**
     * Makes link to page $id in frontend (view page)
     * Returns an icon which links to the frontend index.php document for viewing the page with id $id
     * $id must be a page-uid
     * If the BE_USER has access to Web>List then a link to that module is shown as well (with return-url)
     *
     * @param int $id The page id
     * @param string $_ @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @return string HTML string with linked icon(s)
     */
    public function viewPageIcon($id, $_ = '')
    {
        // If access to Web>List for user, then link to that module.
        $str = BackendUtility::getListViewLink([
            'id' => $id,
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ], $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showList'));
        // Make link to view page
        $str .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($id, '', BackendUtility::BEgetRootLine($id))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', true) . '">' . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
        return $str;
    }

    /**
     * Returns a URL with a command to TYPO3 Core Engine (tce_db.php)
     * See description of the API elsewhere.
     *
     * @param string $params is a set of GET params to send to tce_db.php. Example: "&cmd[tt_content][123][move]=456" or "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World
     * @param string|int $redirectUrl Redirect URL, default is to use GeneralUtility::getIndpEnv('REQUEST_URI'), -1 means to generate an URL for JavaScript using T3_THIS_LOCATION
     * @return string URL to BackendUtility::getModuleUrl('tce_db') + parameters
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick()
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use BackendUtility::getLinkToDataHandlerAction() instead
     */
    public function issueCommand($params, $redirectUrl = '')
    {
        GeneralUtility::logDeprecatedFunction();
        return BackendUtility::getLinkToDataHandlerAction($params, $redirectUrl);
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
     */
    public function getHeader($table, $row, $path, $noViewPageIcon = false, $tWrap = ['', ''], $enableClickMenu = true)
    {
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
     */
    public function getResourceHeader(\TYPO3\CMS\Core\Resource\ResourceInterface $resource, $tWrap = ['', ''], $enableClickMenu = true)
    {
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
        if (GeneralUtility::_GET('M') !== null && isset($GLOBALS['TBE_MODULES']['_PATHS'][$moduleName])) {
            $storeUrl = '&M=' . $moduleName . $storeUrl;
        }
        if ((int)$motherModName === 1) {
            $motherModule = 'top.currentModuleLoaded';
        } elseif ($motherModName) {
            $motherModule = GeneralUtility::quoteJSvalue($motherModName);
        } else {
            $motherModule = '\'\'';
        }
        $confirmationText = GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.makeBookmark'));

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
        $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.makeBookmark', true) . '">' .
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
     * Returns a formatted string of $tstamp
     * Uses $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] and $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] to format date and time
     *
     * @param int $tstamp UNIX timestamp, seconds since 1970
     * @param int $type How much data to show: $type = 1: hhmm, $type = 10:	ddmmmyy
     * @return string Formatted timestamp
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use the corresponding methods in BackendUtility
     */
    public function formatTime($tstamp, $type)
    {
        GeneralUtility::logDeprecatedFunction();
        $dateStr = '';
        switch ($type) {
            case 1:
                $dateStr = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $tstamp);
                break;
            case 10:
                $dateStr = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $tstamp);
                break;
        }
        return $dateStr;
    }

    /**
     * Returns script parsetime IF ->parseTimeFlag is set and user is "admin"
     * Automatically outputted in page end
     *
     * @return string HTML formated with <p>-tags
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function parseTime()
    {
        GeneralUtility::logDeprecatedFunction();
        if ($this->parseTimeFlag && $GLOBALS['BE_USER']->isAdmin()) {
            return '<p>(ParseTime: ' . (GeneralUtility::milliseconds() - $GLOBALS['PARSETIME_START']) . ' ms</p>
					<p>REQUEST_URI-length: ' . strlen(GeneralUtility::getIndpEnv('REQUEST_URI')) . ')</p>';
        }
    }

    /**
     * Defines whether to use the X-UA-Compatible meta tag.
     *
     * @param bool $useCompatibilityTag Whether to use the tag
     * @return void
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
            $file = GeneralUtility::getFileAbsFileName($this->pageHeaderFooterTemplateFile, true);
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
        if ($this->extDirectStateProvider) {
            $this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/JavaScript/ExtDirect.StateProvider.js');
        }
        $this->pageRenderer->addHeaderData($this->JScode);
        foreach ($this->JScodeArray as $name => $code) {
            $this->pageRenderer->addJsInlineCode($name, $code, false);
        }
        if (!empty($this->JScodeLibArray)) {
            GeneralUtility::deprecationLog('DocumentTemplate->JScodeLibArray is deprecated since TYPO3 CMS 7. Use the functionality within pageRenderer directly');
            foreach ($this->JScodeLibArray as $library) {
                $this->pageRenderer->addHeaderData($library);
            }
        }
        if ($this->extJScode) {
            $this->pageRenderer->addExtOnReadyCode($this->extJScode);
        }

        // Load jquery and twbs JS libraries on every backend request
        $this->pageRenderer->loadJquery();
        // Note: please do not reference "bootstrap" outside of the TYPO3 Core (not in your own extensions)
        // as this is preliminary as long as Twitter bootstrap does not support AMD modules
        // this logic will be changed once Twitter bootstrap 4 is included
        $this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('core') . 'Resources/Public/JavaScript/Contrib/bootstrap/bootstrap.js');

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
        $str = $this->sectionEnd() . $this->postCode . $this->wrapScriptTags(BackendUtility::getUpdateSignalCode()) . ($this->form ? '
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
        if (TYPO3_DLOG) {
            GeneralUtility::devLog('END of BACKEND session', \TYPO3\CMS\Backend\Template\DocumentTemplate::class, 0, ['_FLUSH' => true]);
        }
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
     */
    public function header($text)
    {
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
     */
    public function section($label, $text, $nostrtoupper = false, $sH = false, $type = 0, $allowHTMLinHeader = false)
    {
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
     */
    public function divider($dist)
    {
        $dist = (int)$dist;
        $str = '

	<!-- DIVIDER -->
	<hr style="margin-top: ' . $dist . 'px; margin-bottom: ' . $dist . 'px;" />
';
        return $this->sectionEnd() . $str;
    }

    /**
     * Returns a blank <div>-section with a height
     *
     * @param int $dist Padding-top for the div-section (should be margin-top but konqueror (3.1) doesn't like it :-(
     * @return string HTML content
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function spacer($dist)
    {
        GeneralUtility::logDeprecatedFunction();
        if ($dist > 0) {
            return '

	<!-- Spacer element -->
	<div style="padding-top: ' . (int)$dist . 'px;"></div>
';
        }
    }

    /**
     * Make a section header.
     * Begins a section if not already open.
     *
     * @param string $label The label between the <h3> or <h4> tags. (Allows HTML)
     * @param bool $sH If set, <h3> is used, otherwise <h4>
     * @param string $addAttrib Additional attributes to h-tag, eg. ' class=""'
     * @return string HTML content
     */
    public function sectionHeader($label, $sH = false, $addAttrib = '')
    {
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
     */
    public function sectionBegin()
    {
        if (!$this->sectionFlag) {
            $this->sectionFlag = 1;
            $str = '

	<!-- ***********************
	      Begin output section.
	     *********************** -->
	<div>
';
            return $str;
        } else {
            return '';
        }
    }

    /**
     * Ends and output section
     * Returns the </div>-end tag AND clears the ->sectionFlag (but does so only IF the sectionFlag is set - that is a section is 'open')
     * See sectionBegin() also.
     *
     * @return string HTML content
     */
    public function sectionEnd()
    {
        if ($this->sectionFlag) {
            $this->sectionFlag = 0;
            return '
	</div>
	<!-- *********************
	      End output section.
	     ********************* -->
';
        } else {
            return '';
        }
    }

    /**
     * If a form-tag is defined in ->form then and end-tag for that <form> element is outputted
     * Further a JavaScript section is outputted which will update the top.busy session-expiry object (unless $this->endJS is set to FALSE)
     *
     * @return string HTML content (<script> tag section)
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, nothing there to output anymore
     */
    public function endPageJS()
    {
        GeneralUtility::logDeprecatedFunction();
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
     * @return void
     */
    public function addStyleSheet($key, $href, $title = '', $relation = 'stylesheet')
    {
        $this->pageRenderer->addCssFile($href, $relation, 'screen', $title);
    }

    /**
     * Add all *.css files of the directory $path to the stylesheets
     *
     * @param string $path directory to add
     * @return void
     */
    public function addStyleSheetDirectory($path)
    {
        // Calculation needed, when TYPO3 source is used via a symlink
        // absolute path to the stylesheets
        $filePath = dirname(GeneralUtility::getIndpEnv('SCRIPT_FILENAME')) . '/' . $GLOBALS['BACK_PATH'] . $path;
        // Clean the path
        $resolvedPath = GeneralUtility::resolveBackPath($filePath);
        // Read all files in directory and sort them alphabetically
        $files = GeneralUtility::getFilesInDir($resolvedPath, 'css', false, 1);
        foreach ($files as $file) {
            $this->pageRenderer->addCssFile($GLOBALS['BACK_PATH'] . $path . $file, 'stylesheet', 'all');
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
        $jscode = $this->JScode . LF . $this->wrapScriptTags(implode(LF, $this->JScodeArray));
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
        // Add default core stylesheets
        foreach ($this->stylesheetsCore as $stylesheetDir) {
            $stylesheetDirectories[] = $stylesheetDir;
        }
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
                    if (substr($stylesheetDir, 0, 4) === 'EXT:') {
                        list($extKey, $path) = explode('/', substr($stylesheetDir, 4), 2);
                        if (!empty($extKey) && ExtensionManagementUtility::isLoaded($extKey) && !empty($path)) {
                            $stylesheetDirectories[] = ExtensionManagementUtility::extRelPath($extKey) . $path;
                        }
                    } else {
                        // For relative paths
                        $stylesheetDirectories[] = ExtensionManagementUtility::extRelPath($skinExtKey) . $stylesheetDir;
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
     */
    public function icons($type, $styleAttribValue = '')
    {
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
     */
    public function t3Button($onClick, $label)
    {
        $button = '<input class="btn btn-default" type="submit" onclick="' . htmlspecialchars($onClick) . '; return false;" value="' . htmlspecialchars($label) . '" />';
        return $button;
    }

    /**
     * Dimmed-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be gray/dimmed
     *
     * @param string $string Input string
     * @return string Output string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use proper HTML directly
     */
    public function dfw($string)
    {
        GeneralUtility::logDeprecatedFunction();
        return '<span class="text-muted">' . $string . '</span>';
    }

    /**
     * red-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be red
     *
     * @param string $string Input string
     * @return string Output string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use proper HTML directly
     */
    public function rfw($string)
    {
        GeneralUtility::logDeprecatedFunction();
        return '<span class="text-danger">' . $string . '</span>';
    }

    /**
     * Returns string wrapped in CDATA "tags" for XML / XHTML (wrap content of <script> and <style> sections in those!)
     *
     * @param string $string Input string
     * @return string Output string
     */
    public function wrapInCData($string)
    {
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
     */
    public function wrapScriptTags($string, $linebreak = true)
    {
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

    // These vars defines the layout for the table produced by the table() function.
    // You can override these values from outside if you like.
    public $tableLayout = [
        'defRow' => [
            'defCol' => ['<td valign="top">', '</td>']
        ]
    ];

    public $table_TR = '<tr>';

    public $table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist" id="typo3-tmpltable">';

    /**
     * Returns a table based on the input $data
     *
     * @param array $data Multidim array with first levels = rows, second levels = cells
     * @param array $layout If set, then this provides an alternative layout array instead of $this->tableLayout
     * @return string The HTML table.
     * @internal
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function table($data, $layout = null)
    {
        GeneralUtility::logDeprecatedFunction();
        $result = '';
        if (is_array($data)) {
            $tableLayout = is_array($layout) ? $layout : $this->tableLayout;
            $rowCount = 0;
            foreach ($data as $tableRow) {
                if ($rowCount % 2) {
                    $layout = is_array($tableLayout['defRowOdd']) ? $tableLayout['defRowOdd'] : $tableLayout['defRow'];
                } else {
                    $layout = is_array($tableLayout['defRowEven']) ? $tableLayout['defRowEven'] : $tableLayout['defRow'];
                }
                $rowLayout = is_array($tableLayout[$rowCount]) ? $tableLayout[$rowCount] : $layout;
                $rowResult = '';
                if (is_array($tableRow)) {
                    $cellCount = 0;
                    foreach ($tableRow as $tableCell) {
                        $cellWrap = is_array($layout[$cellCount]) ? $layout[$cellCount] : $layout['defCol'];
                        $cellWrap = is_array($rowLayout['defCol']) ? $rowLayout['defCol'] : $cellWrap;
                        $cellWrap = is_array($rowLayout[$cellCount]) ? $rowLayout[$cellCount] : $cellWrap;
                        $rowResult .= $cellWrap[0] . $tableCell . $cellWrap[1];
                        $cellCount++;
                    }
                }
                $rowWrap = is_array($layout['tr']) ? $layout['tr'] : [$this->table_TR, '</tr>'];
                $rowWrap = is_array($rowLayout['tr']) ? $rowLayout['tr'] : $rowWrap;
                $result .= $rowWrap[0] . $rowResult . $rowWrap[1];
                $rowCount++;
            }
            $tableWrap = is_array($tableLayout['table']) ? $tableLayout['table'] : [$this->table_TABLE, '</table>'];
            $result = $tableWrap[0] . $result . $tableWrap[1];
        }
        return $result;
    }

    /**
     * Constructs a table with content from the $arr1, $arr2 and $arr3.
     *
     * @param array $arr1 Menu elements on first level
     * @param array $arr2 Secondary items
     * @param array $arr3 Third-level items
     * @return string HTML content, <table>...</table>
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function menuTable($arr1, $arr2 = [], $arr3 = [])
    {
        GeneralUtility::logDeprecatedFunction();
        $rows = max([count($arr1), count($arr2), count($arr3)]);
        $menu = '
		<table border="0" cellpadding="0" cellspacing="0" id="typo3-tablemenu">';
        for ($a = 0; $a < $rows; $a++) {
            $menu .= '<tr>';
            $cls = [];
            $valign = 'middle';
            $cls[] = '<td valign="' . $valign . '">' . $arr1[$a][0] . '</td><td>' . $arr1[$a][1] . '</td>';
            if (!empty($arr2)) {
                $cls[] = '<td valign="' . $valign . '">' . $arr2[$a][0] . '</td><td>' . $arr2[$a][1] . '</td>';
                if (!empty($arr3)) {
                    $cls[] = '<td valign="' . $valign . '">' . $arr3[$a][0] . '</td><td>' . $arr3[$a][1] . '</td>';
                }
            }
            $menu .= implode($cls, '<td>&nbsp;&nbsp;</td>');
            $menu .= '</tr>';
        }
        $menu .= '
		</table>
		';
        return $menu;
    }

    /**
     * Returns a one-row/two-celled table with $content and $menu side by side.
     * The table is a 100% width table and each cell is aligned left / right
     *
     * @param string $content Content cell content (left)
     * @param string $menu Menu cell content (right)
     * @return string HTML output
     */
    public function funcMenu($content, $menu)
    {
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
     * @return void
     */
    public function loadJavascriptLib($lib)
    {
        // @todo: maybe we can remove this one as well
        $this->pageRenderer->addJsFile($lib);
    }

    /**
     * Includes the necessary Javascript function for the clickmenu (context sensitive menus) in the document
     *
     * @return void
     */
    public function getContextMenuCode()
    {
        $this->pageRenderer->loadJquery();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
    }

    /**
     * Includes the necessary javascript file for use on pages which have the
     * drag and drop functionality (legacy folder tree)
     *
     * @param string $table indicator of which table the drag and drop function should work on (pages or folders)
     * @param string $additionalJavaScriptCode adds more code to the additional javascript code
     * @return void
     */
    public function getDragDropCode($table, $additionalJavaScriptCode = '')
    {
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
     */
    public function getTabMenu($mainParams, $elementName, $currentValue, $menuItems, $script = '', $addparams = '')
    {
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
     * Creates a DYNAMIC tab-menu where the tabs or collapseable are rendered with bootstrap markup
     *
     * @param array $menuItems Numeric array where each entry is an array in itself with associative keys: "label" contains the label for the TAB, "content" contains the HTML content that goes into the div-layer of the tabs content. "description" contains description text to be shown in the layer. "linkTitle" is short text for the title attribute of the tab-menu link (mouse-over text of tab). "stateIcon" indicates a standard status icon (see ->icon(), values: -1, 1, 2, 3). "icon" is an image tag placed before the text.
     * @param string $identString Identification string. This should be unique for every instance of a dynamic menu!
     * @param int $defaultTabIndex Default tab to open (for toggle <=0). Value corresponds to integer-array index + 1 (index zero is "1", index "1" is 2 etc.). A value of zero (or something non-existing) will result in no default tab open.
     * @param bool $collapseable If set, the tabs are rendered as headers instead over each sheet. Effectively this means there is no tab menu, but rather a foldout/foldin menu.
     * @param bool $wrapContent If set, the content is wrapped in div structure which provides a padding and border style. Set this FALSE to get unstyled content pane with fullsize content area.
     * @param bool $storeLastActiveTab If set, the last open tab is stored in local storage and will be re-open again. If you don't need this feature, e.g. for wizards like import/export you can disable this behaviour.
     * @return string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use getDynamicTabMenu() from ModuleTemplate instead.
     */
    public function getDynamicTabMenu(array $menuItems, $identString, $defaultTabIndex = 1, $collapseable = false, $wrapContent = true, $storeLastActiveTab = true)
    {
        GeneralUtility::logDeprecatedFunction();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tabs');
        $templatePathAndFileName = 'EXT:backend/Resources/Private/Templates/DocumentTemplate/' . ($collapseable ? 'Collapse.html' : 'Tabs.html');
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFileName));
        $view->assignMultiple([
            'id' => 'DTM-' . GeneralUtility::shortMD5($identString),
            'items' => $menuItems,
            'defaultTabIndex' => $defaultTabIndex,
            'wrapContent' => $wrapContent,
            'storeLastActiveTab' => $storeLastActiveTab,
            'BACK_PATH' => $GLOBALS['BACK_PATH']
        ]);
        return $view->render();
    }

    /**
     * Creates a DYNAMIC tab-menu where the tabs are switched between with DHTML.
     * Should work in MSIE, Mozilla, Opera and Konqueror. On Konqueror I did find a serious problem: <textarea> fields loose their content when you switch tabs!
     *
     * @param array $menuItems Numeric array where each entry is an array in itself with associative keys: "label" contains the label for the TAB, "content" contains the HTML content that goes into the div-layer of the tabs content. "description" contains description text to be shown in the layer. "linkTitle" is short text for the title attribute of the tab-menu link (mouse-over text of tab). "stateIcon" indicates a standard status icon (see ->icon(), values: -1, 1, 2, 3). "icon" is an image tag placed before the text.
     * @param string $identString Identification string. This should be unique for every instance of a dynamic menu!
     * @param int $toggle If "1", then enabling one tab does not hide the others - they simply toggles each sheet on/off. This makes most sense together with the $foldout option. If "-1" then it acts normally where only one tab can be active at a time BUT you can click a tab and it will close so you have no active tabs.
     * @param bool $foldout If set, the tabs are rendered as headers instead over each sheet. Effectively this means there is no tab menu, but rather a foldout/foldin menu. Make sure to set $toggle as well for this option.
     * @param bool $noWrap Deprecated - delivered by CSS
     * @param bool $fullWidth If set, the tabs will span the full width of their position
     * @param int $defaultTabIndex Default tab to open (for toggle <=0). Value corresponds to integer-array index + 1 (index zero is "1", index "1" is 2 etc.). A value of zero (or something non-existing) will result in no default tab open.
     * @param int $tabBehaviour If set to '1' empty tabs will be remove, If set to '2' empty tabs will be disabled. setting this option to '2' is deprecated since TYPO3 CMS 7, and will be removed iwth CMS 8
     * @return string JavaScript section for the HTML header.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function getDynTabMenu($menuItems, $identString, $toggle = 0, $foldout = false, $noWrap = true, $fullWidth = false, $defaultTabIndex = 1, $tabBehaviour = 1)
    {
        GeneralUtility::logDeprecatedFunction();
        return $this->getDynamicTabMenu($menuItems, $identString, $defaultTabIndex, $foldout, $noWrap);
    }

    /**
     * Creates the id for dynTabMenus.
     *
     * @param string $identString Identification string. This should be unique for every instance of a dynamic menu!
     * @return string The id with a short MD5 of $identString and prefixed "DTM-", like "DTM-2e8791854a
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function getDynTabMenuId($identString)
    {
        GeneralUtility::logDeprecatedFunction();
        $id = 'DTM-' . GeneralUtility::shortMD5($identString);
        return $id;
    }

    /**
     * Creates the version selector for the page id inputted.
     * Requires the core version management extension, "version" to be loaded.
     *
     * @param int $id Page id to create selector for.
     * @param bool $noAction If set, there will be no button for swapping page.
     * @return string
     */
    public function getVersionSelector($id, $noAction = false)
    {
        if (
                ExtensionManagementUtility::isLoaded('version') &&
                !ExtensionManagementUtility::isLoaded('workspaces')
        ) {
            $versionGuiObj = GeneralUtility::makeInstance(\TYPO3\CMS\Version\View\VersionView::class);
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
            $filename = GeneralUtility::getFileAbsFileName($filename, true, true);
        } elseif (!GeneralUtility::isAbsPath($filename)) {
            $filename = GeneralUtility::resolveBackPath($filename);
        } elseif (!GeneralUtility::isAllowedAbsPath($filename)) {
            $filename = '';
        }
        $htmlTemplate = '';
        if ($filename !== '') {
            $htmlTemplate = GeneralUtility::getUrl($filename);
        }
        return $htmlTemplate;
    }

    /**
     * Define the template for the module
     *
     * @param string $filename filename
     * @return void
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
        $pagePath = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.path', true) . ': <span class="typo3-docheader-pagePath">';
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
     * Setting page icon with clickmenu + uid for docheader
     *
     * @param array $pageRecord Current page
     * @return string Page info
     */
    protected function getPageInfo($pageRecord)
    {
        // Add icon with clickmenu, etc:
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
        // Setting icon with clickmenu + uid
        $pageInfo = $theIcon . '<strong>' . htmlspecialchars($title) . '&nbsp;[' . $uid . ']</strong>';
        return $pageInfo;
    }

    /**
     * Makes a collapseable section. See reports module for an example
     *
     * @param string $title
     * @param string $html
     * @param string $id
     * @param string $saveStatePointer
     * @return string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use HTML bootstrap classes, localStorage etc.
     */
    public function collapseableSection($title, $html, $id, $saveStatePointer = '')
    {
        GeneralUtility::logDeprecatedFunction();
        $hasSave = (bool)$saveStatePointer;
        $collapsedStyle = ($collapsedClass = '');
        if ($hasSave) {
            /** @var $userSettingsController \TYPO3\CMS\Backend\Controller\UserSettingsController */
            $userSettingsController = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\UserSettingsController::class);
            $value = $userSettingsController->process('get', $saveStatePointer . '.' . $id);
            if ($value) {
                $collapsedStyle = ' style="display: none"';
                $collapsedClass = ' collapsed';
            } else {
                $collapsedStyle = '';
                $collapsedClass = ' expanded';
            }
        }
        $this->pageRenderer->loadExtJS();
        $this->pageRenderer->addExtOnReadyCode('
			Ext.select("h2.section-header").each(function(element){
				element.on("click", function(event, tag) {
					var state = 0,
						el = Ext.fly(tag),
						div = el.next("div"),
						saveKey = el.getAttribute("rel");
					if (el.hasClass("collapsed")) {
						el.removeClass("collapsed").addClass("expanded");
						div.slideIn("t", {
							easing: "easeIn",
							duration: .5
						});
					} else {
						el.removeClass("expanded").addClass("collapsed");
						div.slideOut("t", {
							easing: "easeOut",
							duration: .5,
							remove: false,
							useDisplay: true
						});
						state = 1;
					}
					if (saveKey) {
						try {
							top.TYPO3.Storage.Persistent.set(saveKey + "." + tag.id, state);
						} catch(e) {}
					}
				});
			});
		');
        return '
		  <h2 id="' . $id . '" class="section-header' . $collapsedClass . '" rel="' . $saveStatePointer . '"> ' . $title . '</h2>
		  <div' . $collapsedStyle . '>' . $html . '</div>
		';
    }

    /**
    * Retrieves configured favicon for backend (with fallback)
    *
    * @return string
    */
    protected function getBackendFavicon()
    {
        return $GLOBALS['TBE_STYLES']['favicon'] ?: ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/Icons/favicon.ico';
    }
}
