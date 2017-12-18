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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
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
class DocumentTemplate implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'hasDocheader' => 'Using $hasDocheader of class DocumentTemplate is discouraged. The property is not evaluated in the TYPO3 core anymore and will be removed in TYPO3 v10.0.'
    ];

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
     * Filename of stylesheet
     *
     * @var string
     */
    public $styleSheetFile = '';

    /**
     * Filename of stylesheet #2 - linked to right after the $this->styleSheetFile script
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
    protected $useCompatibilityTag = false;

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
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     */
    protected $hasDocheader = true;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

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
        $this->scriptID = ltrim(GeneralUtility::_GET('route'), '/');
        $this->bodyTagId = preg_replace('/[^A-Za-z0-9-]/', '-', $this->scriptID);
        // Individual configuration per script? If so, make a recursive merge of the arrays:
        if (is_array($GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID] ?? false)) {
            // Make copy
            $ovr = $GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID];
            // merge styles.
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TBE_STYLES'], $ovr);
            // Have to unset - otherwise the second instantiation will do it again!
            unset($GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID]);
        }
        // Main Stylesheets:
        if (!empty($GLOBALS['TBE_STYLES']['stylesheet'])) {
            $this->styleSheetFile = $GLOBALS['TBE_STYLES']['stylesheet'];
        }
        if (!empty($GLOBALS['TBE_STYLES']['stylesheet2'])) {
            $this->styleSheetFile2 = $GLOBALS['TBE_STYLES']['stylesheet2'];
        }
        if (!empty($GLOBALS['TBE_STYLES']['styleSheetFile_post'])) {
            $this->styleSheetFile_post = $GLOBALS['TBE_STYLES']['styleSheetFile_post'];
        }
        if (!empty($GLOBALS['TBE_STYLES']['inDocStyles_TBEstyle'])) {
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
        $this->pageRenderer->enableConcatenateCss();
        $this->pageRenderer->enableConcatenateJavascript();
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
        $confirmationText = GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark'));

        $shortcutUrl = $pathInfo['path'] . '?' . $storeUrl;
        $shortcutRepository = GeneralUtility::makeInstance(ShortcutRepository::class);
        $shortcutExist = $shortcutRepository->shortcutExists($shortcutUrl);

        if ($shortcutExist) {
            return '<a class="active ' . htmlspecialchars($classes) . '" title="">' .
            $this->iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL)->render() . '</a>';
        }
        $url = GeneralUtility::quoteJSvalue(rawurlencode($shortcutUrl));
        $onClick = 'top.TYPO3.ShortcutMenu.createShortcut(' . GeneralUtility::quoteJSvalue(rawurlencode($modName)) .
            ', ' . $url . ', ' . $confirmationText . ', ' . $motherModule . ', this);return false;';

        return '<a href="#" class="' . htmlspecialchars($classes) . '" onclick="' . htmlspecialchars($onClick) . '" title="' .
        htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark')) . '">' .
        $this->iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL)->render() . '</a>';
    }

    /**
     * MAKE url for storing
     * Internal func
     *
     * @param string $gvList Is the list of GET variables to store (if any)
     * @param string $setList Is the list of SET[] variables to store (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend modules
     * @return string
     * @internal
     * @see makeShortcutIcon()
     */
    public function makeShortcutUrl($gvList, $setList)
    {
        $GET = GeneralUtility::_GET();
        $storeArray = array_merge(
            GeneralUtility::compileSelectedGetVarsFromArray($gvList, $GET),
            ['SET' => GeneralUtility::compileSelectedGetVarsFromArray($setList, (array)$GLOBALS['SOBE']->MOD_SETTINGS)]
        );
        return HttpUtility::buildQueryString($storeArray, '&');
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function formWidth($size = 48, $textarea = false, $styleOverride = '')
    {
        trigger_error('DocumentTemplate->formWidth() will be removed in TYPO3 10.0 - use responsive code or direct inline styles to format your input fields instead.', E_USER_DEPRECATED);
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'] ?? [] as $hookFunction) {
            $hookParameters = [
                'title' => &$title
            ];
            GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
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
        $this->pageRenderer->setMetaTag('name', 'generator', $this->generator());
        $this->pageRenderer->setMetaTag('name', 'robots', 'noindex,follow');
        $this->pageRenderer->setMetaTag('name', 'viewport', 'width=device-width, initial-scale=1');
        $this->pageRenderer->setFavIcon($this->getBackendFavicon());
        if ($this->useCompatibilityTag) {
            $this->pageRenderer->setMetaTag('http-equiv', 'X-UA-Compatible', $this->xUaCompatibilityVersion);
        }
        $this->pageRenderer->setTitle($title);
        // add docstyles
        $this->docStyle();
        $this->pageRenderer->addHeaderData($this->JScode);
        foreach ($this->JScodeArray as $name => $code) {
            $this->pageRenderer->addJsInlineCode($name, $code, false);
        }

        // Note: please do not reference "bootstrap" outside of the TYPO3 Core (not in your own extensions)
        // as this is preliminary as long as Twitter bootstrap does not support AMD modules
        // this logic will be changed once Twitter bootstrap 4 is included
        // @todo
        $this->pageRenderer->addJsFile('EXT:core/Resources/Public/JavaScript/Contrib/bootstrap/bootstrap.js');

        // csh manual require js module & moduleUrl
        if (TYPO3_MODE === 'BE' && $this->getBackendUser() && !empty($this->getBackendUser()->user)) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextHelp');
            $this->pageRenderer->addInlineSetting(
                'ContextHelp',
                'moduleUrl',
                (string)$uriBuilder->buildUriFromRoute('help_cshmanual', ['action' => 'detail'])
            );
        }

        // hook for additional headerData
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'] ?? [] as $hookFunction) {
            $hookParameters = [
                'pageRenderer' => &$this->pageRenderer
            ];
            GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
        }
        // Construct page header.
        $str = $this->pageRenderer->render(PageRenderer::PART_HEADER);
        $this->JScode = '';
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
        $this->logger->debug('END of BACKEND session', ['_FLUSH' => true]);
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
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     * @see PageRenderer::addCssFile()
     */
    public function addStyleSheet($key, $href, $title = '', $relation = 'stylesheet')
    {
        trigger_error('DocumentTemplate->->addStyleSheet() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
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
        return 'TYPO3 CMS, ' . TYPO3_URL_GENERAL . ', &#169; Kasper Sk&#229;rh&#248;j ' . TYPO3_copyright_year . ', extensions are copyright of their respective owners.';
    }

    /**
     * Returns X-UA-Compatible meta tag
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     *
     * @param string $content Content of the compatible tag (default: IE-8)
     * @return string <meta http-equiv="X-UA-Compatible" content="???" />
     */
    public function xUaCompatible($content = 'IE=8')
    {
        trigger_error('DocumentTemplate->xUaCompatible() will be removed with TYPO3 v10.0. Use PageRenderer->setMetaTag() instead.', E_USER_DEPRECATED);
        return '<meta http-equiv="X-UA-Compatible" content="' . $content . '" />';
    }

    /*****************************************
     *
     * OTHER ELEMENTS
     * Tables, buttons, formatting dimmed/red strings
     *
     ******************************************/

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
        $filename = GeneralUtility::getFileAbsFileName($filename);
        return $filename !== '' ? file_get_contents($filename) : '';
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['moduleBodyPostProcess'] ?? [] as $funcRef) {
            $params = [
                'moduleTemplateFilename' => &$this->moduleTemplateFilename,
                'moduleTemplate' => &$this->moduleTemplate,
                'moduleBody' => &$moduleBody,
                'markers' => &$markerArray,
                'parentObject' => &$this
            ];
            GeneralUtility::callUserFunction($funcRef, $params, $this);
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
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        return $defaultFlashMessageQueue->renderFlashMessages();
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook'] ?? [] as $funcRef) {
            $params = [
                'buttons' => $buttons,
                'markers' => &$markers,
                'pObj' => &$this
            ];
            GeneralUtility::callUserFunction($funcRef, $params, $this);
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
        $pagePath = htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.path')) . ': <span class="typo3-docheader-pagePath">';
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
            if ($GLOBALS['BE_USER']->isAdmin()) {
                $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'pages');
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
        $backendFavicon = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('backend', 'backendFavicon');
        if (!empty($backendFavicon)) {
            $path = $this->getUriForFileName($backendFavicon);
        } else {
            $path = ExtensionManagementUtility::extPath('backend') . 'Resources/Public/Icons/favicon.ico';
        }
        return PathUtility::getAbsoluteWebPath($path);
    }

    /**
     * Returns the uri of a relative reference, resolves the "EXT:" prefix
     * (way of referring to files inside extensions) and checks that the file is inside
     * the project root of the TYPO3 installation
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

    /**
     * @return BackendUserAuthentication|null
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
