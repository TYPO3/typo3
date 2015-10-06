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

use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Version\View\VersionView;

/**
 * A class taking care of the "outer" HTML of a module, especially
 * the doc header and other related parts.
 *
 * @internal This API is not yet carved in stone and may be adapted later.
 */
class ModuleTemplate {

	/**
	 * Error Icon Constant
	 *
	 * @internal
	 */
	const STATUS_ICON_ERROR = 3;

	/**
	 * Warning Icon Constant
	 *
	 * @internal
	 */
	const STATUS_ICON_WARNING = 2;

	/**
	 * Notification Icon Constant
	 *
	 * @internal
	 */
	const STATUS_ICON_NOTIFICATION = 1;

	/**
	 * OK Icon Constant
	 *
	 * @internal
	 */
	const STATUS_ICON_OK = -1;

	/**
	 * DocHeaderComponent
	 *
	 * @var DocHeaderComponent
	 */
	protected $docHeaderComponent;

	/**
	 * Javascript Code Array
	 * Used for inline JS
	 *
	 * @var array
	 */
	protected $javascriptCodeArray = [];

	/**
	 * Expose the pageRenderer
	 *
	 * @var PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * TemplateRootPath
	 *
	 * @var string[]
	 */
	protected $templateRootPaths = ['EXT:backend/Resources/Private/Templates'];

	/**
	 * PartialRootPath
	 *
	 * @var string[]
	 */
	protected $partialRootPaths = ['EXT:backend/Resources/Private/Partials'];

	/**
	 * LayoutRootPath
	 *
	 * @var string[]
	 */
	protected $layoutRootPaths = ['EXT:backend/Resources/Private/Layouts'];

	/**
	 * Template name
	 *
	 * @var string
	 */
	protected $templateFile = 'Module.html';

	/**
	 * Fluid Standalone View
	 *
	 * @var StandaloneView
	 */
	protected $view;

	/**
	 * Content String
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Defines whether a section has been opened before
	 *
	 * @var int
	 */
	protected $sectionFlag = 0;

	/**
	 * IconFactory Member
	 *
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Module ID
	 *
	 * @var string
	 */
	protected $moduleId = '';

	/**
	 * Module Name
	 *
	 * @var string
	 */
	protected $moduleName = '';

	/**
	 * Title Tag
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Gets the standalone view.
	 *
	 * @return StandaloneView
	 */
	public function getView() {
		return $this->view;
	}

	/**
	 * Set content
	 *
	 * @param string $content Content of the module
	 * @return void
	 */
	public function setContent($content) {
		$this->view->assign('content', $content);
	}

	/**
	 * Set title tag
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the IconFactory
	 *
	 * @return IconFactory
	 */
	public function getIconFactory() {
		return $this->iconFactory;
	}

	/**
	 * Class constructor
	 * Sets up view and property objects
	 *
	 * @throws InvalidTemplateResourceException In case a template is invalid
	 */
	public function __construct() {
		$this->view = GeneralUtility::makeInstance(StandaloneView::class);
		$this->view->setPartialRootPaths($this->partialRootPaths);
		$this->view->setTemplateRootPaths($this->templateRootPaths);
		$this->view->setLayoutRootPaths($this->layoutRootPaths);
		$this->view->setTemplate($this->templateFile);
		$this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$this->docHeaderComponent = GeneralUtility::makeInstance(DocHeaderComponent::class);
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
	}

	/**
	 * Loads all necessary Javascript Files
	 *
	 * @return void
	 */
	protected function loadJavaScripts() {
		$this->pageRenderer->loadJquery();
		$this->pageRenderer->loadRequireJsModule('bootstrap');
		$this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextHelp');
		$this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DocumentHeader');
	}

	/**
	 * Loads all necessary stylesheets
	 *
	 * @return void
	 */
	protected function loadStylesheets() {
		if ($GLOBALS['TBE_STYLES']['stylesheet']) {
			$this->pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheet']);
		}
		if ($GLOBALS['TBE_STYLES']['stylesheet2']) {
			$this->pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheet2']);
		}
	}

	/**
	 * Sets mandatory parameters for the view (pageRenderer)
	 *
	 * @return void
	 */
	protected function setupPage() {
		// Yes, hardcoded on purpose
		$this->pageRenderer->setXmlPrologAndDocType('<!DOCTYPE html>');
		$this->pageRenderer->setCharSet('utf-8');
		$this->pageRenderer->setLanguage('default');
		$this->pageRenderer->addMetaTag('<meta name="viewport" content="width=device-width, initial-scale=1">');
	}

	/**
	 * Wrapper function for adding JS inline blocks
	 *
	 * @return void
	 */
	protected function setJavaScriptCodeArray() {
		foreach ($this->javascriptCodeArray as $name => $code) {
			$this->pageRenderer->addJsInlineCode($name, $code, FALSE);
		}
	}

	/**
	 * Adds JS inline blocks of code to the internal registry
	 *
	 * @param string $name Javascript code block name
	 * @param string $code Inline Javascript
	 *
	 * @return void
	 */
	public function addJavaScriptCode($name = '', $code = '') {
		$this->javascriptCodeArray[$name] = $code;
	}

	/**
	 * Get the DocHeader
	 *
	 * @return DocHeaderComponent
	 */
	public function getDocHeaderComponent() {
		return $this->docHeaderComponent;
	}

	/**
	 * Returns the fully rendered view
	 *
	 * @return string
	 */
	public function renderContent() {
		$this->setupPage();
		$this->pageRenderer->setTitle($this->title);
		$this->loadJavaScripts();
		$this->setJavaScriptCodeArray();
		$this->loadStylesheets();

		$this->view->assign('docHeader', $this->docHeaderComponent->docHeaderContent());
		if ($this->moduleId) {
			$this->view->assign('moduleId', $this->moduleId);
		}
		if ($this->moduleName) {
			$this->view->assign('moduleName', $this->moduleName);
		}

		$renderedPage = $this->pageRenderer->render(PageRenderer::PART_HEADER);
		$renderedPage .= $this->view->render();
		$renderedPage .= $this->pageRenderer->render(PageRenderer::PART_FOOTER);

		return $renderedPage;
	}

	/**
	 * Get PageRenderer
	 *
	 * @return PageRenderer
	 */
	public function getPageRenderer() {
		return $this->pageRenderer;
	}

	/**
	 * Set form tag
	 *
	 * @param string $formTag Form tag to add
	 *
	 * @return void
	 */
	public function setForm($formTag = '') {
		$this->view->assign('formTag', $formTag);
	}

	/**
	 * Sets the ModuleId
	 *
	 * @param string $moduleId ID of the module
	 *
	 * @return void
	 */
	public function setModuleId($moduleId) {
		$this->moduleId = $moduleId;
		$this->registerModuleMenu($moduleId);
	}

	/**
	 * Sets the ModuleName
	 *
	 * @param string $moduleName Name of the module
	 *
	 * @return void
	 */
	public function setModuleName($moduleName) {
		$this->moduleName = $moduleName;
	}

	/**
	 * Generates the Menu for things like Web->Info
	 *
	 * @param $moduleMenuIdentifier
	 */
	public function registerModuleMenu($moduleMenuIdentifier) {
		if (isset($GLOBALS['TBE_MODULES_EXT'][$moduleMenuIdentifier])) {
			$menuEntries =
				$GLOBALS['TBE_MODULES_EXT'][$moduleMenuIdentifier]['MOD_MENU']['function'];
			$menu = $this->getDocHeaderComponent()->getMenuRegistry()->makeMenu()->setIdentifier('MOD_FUNC');
			foreach ($menuEntries as $menuEntry) {
				$menuItem = $menu->makeMenuItem()
					->setTitle($menuEntry['title'])
					->setHref('#');
				$menu->addMenuItem($menuItem);
			}
			$this->docHeaderComponent->getMenuRegistry()->addMenu($menu);
		}
	}



	/*******************************************
	 * THE FOLLOWING METHODS ARE SUBJECT TO BE DEPRECATED / DROPPED!
	 *
	 * These methods have been copied over from DocumentTemplate and enables
	 * core modules to drop the dependency to DocumentTemplate altogether without
	 * rewriting these modules now.
	 * The methods below are marked as internal and will be removed
	 * one-by-one with further refactoring of modules.
	 *
	 * Do not use these methods within own extensions if possible or
	 * be prepared to change this later again.
	 *******************************************/

	/**
	 * Makes click menu link (context sensitive menu)
	 * Returns $str (possibly an <|img> tag/icon) wrapped in a link which will
	 * activate the context sensitive menu for the record ($table/$uid) or
	 * file ($table = file)
	 * The link will load the top frame with the parameter "&item" which is
	 * the table,uid and listFr arguments imploded
	 * by "|": rawurlencode($table.'|'.$uid.'|'.$listFr)
	 *
	 * @param string $content String to be wrapped in link, typ. image tag.
	 * @param string $table Table name/File path. If the icon is for a database
	 * record, enter the tablename from $GLOBALS['TCA']. If a file then enter
	 * the absolute filepath
	 * @param int $uid If icon is for database record this is the UID for the
	 * record from $table
	 * @param bool $listFr Tells the top frame script that the link is coming
	 * from a "list" frame which means a frame from within the backend content frame.
	 * @param string $addParams Additional GET parameters for the link to the
	 * ClickMenu AJAX request
	 * @param string $enDisItems Enable / Disable click menu items.
	 * Example: "+new,view" will display ONLY these two items (and any spacers
	 * in between), "new,view" will display all BUT these two items.
	 * @param bool $returnTagParameters If set, will return only the onclick
	 * JavaScript, not the whole link.
	 *
	 * @return string The link-wrapped input string.
	 * @internal
	 */
	public function wrapClickMenuOnIcon(
		$content,
		$table,
		$uid = 0,
		$listFr = TRUE,
		$addParams = '',
		$enDisItems = '',
		$returnTagParameters = FALSE
	) {
		$tagParameters = array(
			'class'           => 't3-js-clickmenutrigger',
			'data-table'      => $table,
			'data-uid'        => (int)$uid !== 0 ? (int)$uid : '',
			'data-listframe'  => $listFr,
			'data-iteminfo'   => str_replace('+', '%2B', $enDisItems),
			'data-parameters' => $addParams,
		);

		if ($returnTagParameters) {
			return $tagParameters;
		}
		return '<a href="#" ' . GeneralUtility::implodeAttributes($tagParameters, TRUE) . '>' . $content . '</a>';
	}

	/**
	 * Includes a javascript library that exists in the core /typo3/ directory
	 *
	 * @param string $lib Library name. Call it with the full path like
	 * "sysext/core/Resources/Public/JavaScript/QueryGenerator.js" to load it
	 *
	 * @return void
	 * @internal
	 */
	public function loadJavascriptLib($lib) {
		// @todo: maybe we can remove this one as well
		$this->pageRenderer->addJsFile($lib);
	}

	/**
	 * Returns a linked shortcut-icon which will call the shortcut frame and set a
	 * shortcut there back to the calling page/module
	 *
	 * @param string $gvList Is the list of GET variables to store (if any)
	 * @param string $setList Is the list of SET[] variables to store
	 * (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS
	 * for backend modules
	 * @param string $modName Module name string
	 * @param string|int $motherModName Is used to enter the "parent module
	 * name" if the module is a submodule under eg. Web>* or File>*. You
	 * can also set this value to 1 in which case the currentLoadedModule
	 * is sent to the shortcut script (so - not a fixed value!) - that is used
	 * in file_edit and wizard_rte modules where those are really running as
	 * a part of another module.
	 *
	 * @return string HTML content
	 * @todo Make this thing return a button object
	 * @internal
	 */
	public function makeShortcutIcon($gvList, $setList, $modName, $motherModName = '') {
		$storeUrl = $this->makeShortcutUrl($gvList, $setList);
		$pathInfo = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
		// Fallback for alt_mod. We still pass in the old xMOD... stuff,
		// but TBE_MODULES only knows about "record_edit".
		// We still need to pass the xMOD name to createShortcut below,
		// since this is used for icons.
		$moduleName = $modName === 'xMOD_alt_doc.php' ? 'record_edit' : $modName;
		// Add the module identifier automatically if typo3/index.php is used:
		if (GeneralUtility::_GET('M') !== NULL && isset($GLOBALS['TBE_MODULES']['_PATHS'][$moduleName])) {
			$storeUrl = '&M=' . $moduleName . $storeUrl;
		}
		if ((int)$motherModName === 1) {
			$motherModule = 'top.currentModuleLoaded';
		} elseif ($motherModName) {
			$motherModule = GeneralUtility::quoteJSvalue($motherModName);
		} else {
			$motherModule = '\'\'';
		}
		$confirmationText = GeneralUtility::quoteJSvalue(
			$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.makeBookmark')
		);

		$shortcutUrl = $pathInfo['path'] . '?' . $storeUrl;
		$shortcutExist = BackendUtility::shortcutExists($shortcutUrl);

		if ($shortcutExist) {
			return '<a class="active" title="">' .
			$this->iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL)->render() . '</a>';
		}

		$url = GeneralUtility::quoteJSvalue(rawurlencode($shortcutUrl));
		$onClick = 'top.TYPO3.ShortcutMenu.createShortcut(' . GeneralUtility::quoteJSvalue(rawurlencode($modName)) .
			', ' . $url . ', ' . $confirmationText . ', ' . $motherModule . ', this);return false;';

		return '<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' .
		$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.makeBookmark', TRUE) . '">' .
		$this->iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL)->render() . '</a>';
	}

	/**
	 * MAKE url for storing
	 * Internal func
	 *
	 * @param string $gvList Is the list of GET variables to store (if any)
	 * @param string $setList Is the list of SET[] variables to store (if any)
	 * - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend
	 * modules
	 *
	 * @return string
	 * @internal
	 */
	public function makeShortcutUrl($gvList, $setList) {
		$getParams = GeneralUtility::_GET();
		$storeArray = array_merge(
			GeneralUtility::compileSelectedGetVarsFromArray($gvList, $getParams),
			array('SET' => GeneralUtility::compileSelectedGetVarsFromArray($setList, (array)$GLOBALS['SOBE']->MOD_SETTINGS))
		);
		return GeneralUtility::implodeArrayForUrl('', $storeArray);
	}

	/**
	 * Returns a URL with a command to TYPO3 Core Engine (tce_db.php)
	 * See description of the API elsewhere.
	 *
	 * @param string $params Is a set of GET params to send to tce_db.php.
	 * Example: "&cmd[tt_content][123][move]=456" or
	 * "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World
	 * @param string|int $redirectUrl Redirect URL, default is to use
	 * GeneralUtility::getIndpEnv('REQUEST_URI'), -1 means to generate
	 * an URL for JavaScript using T3_THIS_LOCATION
	 *
	 * @return string URL to BackendUtility::getModuleUrl('tce_db') + parameters
	 * @internal
	 */
	public function issueCommand($params, $redirectUrl = '') {
		$urlParameters = [
			'prErr' => 1,
			'uPT' => 1,
			'vC' => $this->getBackendUserAuthentication()->veriCode()
		];
		$url = BackendUtility::getModuleUrl('tce_db', $urlParameters) . $params . '&redirect=';
		if ((int)$redirectUrl === -1) {
			$url = GeneralUtility::quoteJSvalue($url) . '+T3_THIS_LOCATION';
		} else {
			$url .= rawurlencode($redirectUrl ?: GeneralUtility::getIndpEnv('REQUEST_URI'));
		}
		return $url;
	}

	/**
	 * Creates the version selector for the page id inputted.
	 * Requires the core version management extension, "version" to be loaded.
	 *
	 * @param int $id Page id to create selector for.
	 * @param bool $noAction If set, there will be no button for swapping page.
	 *
	 * @return string
	 * @internal
	 */
	public function getVersionSelector($id, $noAction = FALSE) {
		if (
			ExtensionManagementUtility::isLoaded('version') &&
			!ExtensionManagementUtility::isLoaded('workspaces')
		) {
			/**
			 * For Code Completion
			 *
			 * @var $versionGuiObj VersionView
			 */
			$versionGuiObj = GeneralUtility::makeInstance(VersionView::class);
			return $versionGuiObj->getVersionSelector($id, $noAction);
		}
		return '';
	}

	/**
	 * Begins an output section and sets header and content
	 *
	 * @param string $label The header
	 * @param string $text The HTML-content
	 * @param bool $noStrToUpper A flag that will prevent the header from
	 * being converted to uppercase
	 * @param bool $sH Defines the type of header (if set, "<h3>" rather
	 * than the default "h4")
	 * @param int $type The number of an icon to show with the header
	 * (see the icon-function). -1,1,2,3
	 * @param bool $allowHtmlInHeader If set, HTML tags are allowed in
	 * $label (otherwise this value is by default htmlspecialchars()'ed)
	 *
	 * @return string HTML content
	 * @internal
	 */
	public function section($label, $text, $noStrToUpper = FALSE, $sH = FALSE, $type = 0, $allowHtmlInHeader = FALSE) {
		$str = '';
		// Setting header
		if ($label) {
			if (!$allowHtmlInHeader) {
				$label = htmlspecialchars($label);
			}
			$str .= $this->sectionHeader($this->icons($type) . $label, $sH, $noStrToUpper ? '' : ' class="uppercase"');
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
	 *
	 * @return string HTML content
	 * @internal
	 */
	public function divider($dist) {
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
	 * @param int $dist Padding-top for the div-section
	 *
	 * @return string HTML content
	 * @internal
	 */
	public function spacer($dist) {
		if ($dist > 0) {
			return '

	<!-- Spacer element -->
	<div style="padding-top: ' . (int)$dist . 'px;"></div>
';
		}
		return '';
	}

	/**
	 * Make a section header.
	 * Begins a section if not already open.
	 *
	 * @param string $label The label between the <h3> or <h4> tags. (Allows HTML)
	 * @param bool $sH If set, <h3> is used, otherwise <h4>
	 * @param string $addAttribute Additional attributes to h-tag, eg. ' class=""'
	 *
	 * @return string HTML content
	 * @internal
	 */
	public function sectionHeader($label, $sH = FALSE, $addAttribute = '') {
		$tag = $sH ? 'h2' : 'h3';
		if ($addAttribute && $addAttribute[0] !== ' ') {
			$addAttribute = ' ' . $addAttribute;
		}
		$str = '

	<!-- Section header -->
	<' . $tag . $addAttribute . '>' . $label . '</' . $tag . '>
';
		return $this->sectionBegin() . $str;
	}

	/**
	 * Begins an output section.
	 * Returns the <div>-begin tag AND sets the ->sectionFlag TRUE
	 * (if the ->sectionFlag is not already set!)
	 * You can call this function even if a section is already begun
	 * since the function will only return something if the sectionFlag
	 * is not already set!
	 *
	 * @return string HTML content
	 * @internal
	 */
	public function sectionBegin() {
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
	 * Returns the </div>-end tag AND clears the ->sectionFlag
	 * (but does so only IF the sectionFlag is set - that is a section is 'open')
	 * See sectionBegin() also.
	 *
	 * @return string HTML content
	 * @internal
	 */
	public function sectionEnd() {
		if ($this->sectionFlag) {
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
	 * Returns the BE USER Object
	 *
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns the LanguageService
	 *
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns an image-tag with an 18x16 icon of the following types:
	 *
	 * $type:
	 * -1:»   OK icon (Check-mark)
	 * 1:»   Notice (Speach-bubble)
	 * 2:»   Warning (Yellow triangle)
	 * 3:»   Fatal error (Red stop sign)
	 *
	 * @param int $type See description
	 *
	 * @return string HTML image tag (if applicable)
	 * @internal
	 */
	public function icons($type) {
		$icon = '';
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
		if ($icon != '') {
			return $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render();
		}
		return '';
	}

}