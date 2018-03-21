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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * A class taking care of the "outer" HTML of a module, especially
 * the doc header and other related parts.
 *
 * @internal This API is not yet carved in stone and may be adapted later.
 */
class ModuleTemplate
{
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
     * @var bool
     */
    protected $uiBlock = false;

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
     * Body Tag
     *
     * @var string
     */
    protected $bodyTag = '<body>';

    /**
     * Flash message queue
     *
     * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue
     */
    protected $flashMessageQueue;

    /**
     * Returns the current body tag
     *
     * @return string
     */
    public function getBodyTag()
    {
        return $this->bodyTag;
    }

    /**
     * Sets the body tag
     *
     * @param string $bodyTag
     */
    public function setBodyTag($bodyTag)
    {
        $this->bodyTag = $bodyTag;
    }

    /**
     * Gets the standalone view.
     *
     * @return StandaloneView
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set content
     *
     * @param string $content Content of the module
     */
    public function setContent($content)
    {
        $this->view->assign('content', $content);
    }

    /**
     * Set title tag
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the IconFactory
     *
     * @return IconFactory
     */
    public function getIconFactory()
    {
        return $this->iconFactory;
    }

    /**
     * Class constructor
     * Sets up view and property objects
     *
     * @throws InvalidTemplateResourceException In case a template is invalid
     */
    public function __construct()
    {
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
     */
    protected function loadJavaScripts()
    {
        $this->pageRenderer->loadJquery();
        $this->pageRenderer->loadRequireJsModule('bootstrap');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextHelp');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DocumentHeader');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/SplitButtons');
    }

    /**
     * Loads all necessary stylesheets
     */
    protected function loadStylesheets()
    {
        if (!empty($GLOBALS['TBE_STYLES']['stylesheet'])) {
            $this->pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheet']);
        }
        if (!empty($GLOBALS['TBE_STYLES']['stylesheet2'])) {
            $this->pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheet2']);
        }
    }

    /**
     * Sets mandatory parameters for the view (pageRenderer)
     */
    protected function setupPage()
    {
        // Yes, hardcoded on purpose
        $this->pageRenderer->setXmlPrologAndDocType('<!DOCTYPE html>');
        $this->pageRenderer->setCharSet('utf-8');
        $this->pageRenderer->setLanguage($GLOBALS['LANG']->lang);
        $this->pageRenderer->addMetaTag('<meta name="viewport" content="width=device-width, initial-scale=1">');
    }

    /**
     * Wrapper function for adding JS inline blocks
     */
    protected function setJavaScriptCodeArray()
    {
        foreach ($this->javascriptCodeArray as $name => $code) {
            $this->pageRenderer->addJsInlineCode($name, $code, false);
        }
    }

    /**
     * Adds JS inline blocks of code to the internal registry
     *
     * @param string $name Javascript code block name
     * @param string $code Inline Javascript
     */
    public function addJavaScriptCode($name = '', $code = '')
    {
        $this->javascriptCodeArray[$name] = $code;
    }

    /**
     * Get the DocHeader
     *
     * @return DocHeaderComponent
     */
    public function getDocHeaderComponent()
    {
        return $this->docHeaderComponent;
    }

    /**
     * Returns the fully rendered view
     *
     * @return string
     */
    public function renderContent()
    {
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
        $this->view->assign('uiBlock', $this->uiBlock);
        $this->view->assign('flashMessageQueueIdentifier', $this->getFlashMessageQueue()->getIdentifier());
        $renderedPage = $this->pageRenderer->render(PageRenderer::PART_HEADER);
        $renderedPage .= $this->bodyTag;
        $renderedPage .= $this->view->render();
        $this->pageRenderer->addJsFooterInlineCode('updateSignals', BackendUtility::getUpdateSignalCode());
        $renderedPage .= $this->pageRenderer->render(PageRenderer::PART_FOOTER);

        return $renderedPage;
    }

    /**
     * Get PageRenderer
     *
     * @return PageRenderer
     */
    public function getPageRenderer()
    {
        return $this->pageRenderer;
    }

    /**
     * Set form tag
     *
     * @param string $formTag Form tag to add
     */
    public function setForm($formTag = '')
    {
        $this->view->assign('formTag', $formTag);
    }

    /**
     * Sets the ModuleId
     *
     * @param string $moduleId ID of the module
     */
    public function setModuleId($moduleId)
    {
        $this->moduleId = $moduleId;
        $this->registerModuleMenu($moduleId);
    }

    /**
     * Sets the ModuleName
     *
     * @param string $moduleName Name of the module
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Generates the Menu for things like Web->Info
     *
     * @param $moduleMenuIdentifier
     */
    public function registerModuleMenu($moduleMenuIdentifier)
    {
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

    /**
     * Creates a tab menu where the tabs or collapsible are rendered with bootstrap markup
     *
     * @param array $menuItems Tab elements, each element is an array with "label" and "content"
     * @param string $domId DOM id attribute, will be appended with an iteration number per tab.
     * @param int $defaultTabIndex Default tab to open (for toggle <=0). Value corresponds to integer-array index + 1
     *                             (index zero is "1", index "1" is 2 etc.). A value of zero (or something non-existing
     *                             will result in no default tab open.
     * @param bool $collapsible If set, the tabs are rendered as headers instead over each sheet. Effectively this means
     *                          there is no tab menu, but rather a foldout/fold-in menu.
     * @param bool $wrapContent If set, the content is wrapped in div structure which provides a padding and border
     *                          style. Set this FALSE to get unstyled content pane with fullsize content area.
     * @param bool $storeLastActiveTab If set, the last open tab is stored in local storage and will be re-open again.
     *                                 If you don't need this feature, e.g. for wizards like import/export you can
     *                                 disable this behaviour.
     * @return string
     */
    public function getDynamicTabMenu(array $menuItems, $domId, $defaultTabIndex = 1, $collapsible = false, $wrapContent = true, $storeLastActiveTab = true)
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tabs');
        $templatePath = ExtensionManagementUtility::extPath('backend')
            . 'Resources/Private/Templates/DocumentTemplate/';
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($templatePath . ($collapsible ? 'Collapse.html' : 'Tabs.html'));
        $view->setPartialRootPaths([$templatePath . 'Partials']);
        $view->assignMultiple([
            'id' => 'DTM-' . GeneralUtility::shortMD5($domId),
            'items' => $menuItems,
            'defaultTabIndex' => $defaultTabIndex,
            'wrapContent' => $wrapContent,
            'storeLastActiveTab' => $storeLastActiveTab,
        ]);
        return $view->render();
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
     * Includes a javascript library that exists in the core /typo3/ directory
     *
     * @param string $lib Library name. Call it with the full path like
     * "sysext/core/Resources/Public/JavaScript/QueryGenerator.js" to load it
     *
     * @internal
     */
    public function loadJavascriptLib($lib)
    {
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
     * @param string $displayName When given this name is used instead of the
     * module name.
     * @param string $classes Additional CSS classes for the link around the icon
     *
     * @return string HTML content
     * @todo Make this thing return a button object
     * @internal
     */
    public function makeShortcutIcon($gvList, $setList, $modName, $motherModName = '', $displayName = '', $classes = 'btn btn-default btn-sm')
    {
        $gvList = 'route,' . $gvList;
        $storeUrl = $this->makeShortcutUrl($gvList, $setList);
        $pathInfo = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
        // Fallback for alt_mod. We still pass in the old xMOD... stuff,
        // but TBE_MODULES only knows about "record_edit".
        // We still need to pass the xMOD name to createShortcut below,
        // since this is used for icons.
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
        $confirmationText = GeneralUtility::quoteJSvalue(
            $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark')
        );

        $shortcutUrl = $pathInfo['path'] . '?' . $storeUrl;
        $shortcutExist = BackendUtility::shortcutExists($shortcutUrl);

        if ($shortcutExist) {
            return '<a class="active ' . htmlspecialchars($classes) . '" title="">' .
            $this->iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL)->render() . '</a>';
        }

        $url = GeneralUtility::quoteJSvalue(rawurlencode($shortcutUrl));
        $onClick = 'top.TYPO3.ShortcutMenu.createShortcut(' . GeneralUtility::quoteJSvalue(rawurlencode($modName)) .
            ', ' . $url . ', ' . $confirmationText . ', ' . $motherModule . ', this, ' . GeneralUtility::quoteJSvalue($displayName) . ');return false;';

        return '<a href="#" class="' . htmlspecialchars($classes) . '" onclick="' . htmlspecialchars($onClick) . '" title="' .
        htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark')) . '">' .
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
    public function makeShortcutUrl($gvList, $setList)
    {
        $getParams = GeneralUtility::_GET();
        $storeArray = array_merge(
            GeneralUtility::compileSelectedGetVarsFromArray($gvList, $getParams),
            ['SET' => GeneralUtility::compileSelectedGetVarsFromArray($setList, (array)$GLOBALS['SOBE']->MOD_SETTINGS)]
        );
        return GeneralUtility::implodeArrayForUrl('', $storeArray);
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
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getVersionSelector($id, $noAction = false)
    {
        if (ExtensionManagementUtility::isLoaded('version')
            && ExtensionManagementUtility::isLoaded('compatibility7')
            && !ExtensionManagementUtility::isLoaded('workspaces')
        ) {
            /**
             * For Code Completion
             *
             * @var $versionGuiObj \TYPO3\CMS\Compatibility7\View\VersionView
             */
            $versionGuiObj = GeneralUtility::makeInstance(\TYPO3\CMS\Compatibility7\View\VersionView::class);
            return $versionGuiObj->getVersionSelector($id, $noAction);
        }
        return '';
    }

    /**
     * Returns the BE USER Object
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
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
    public function icons($type)
    {
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

    /**
     * Returns JavaScript variables setting the returnUrl and thisScript location for use by JavaScript on the page.
     * Used in fx. db_list.php (Web>List)
     *
     * @param string $thisLocation URL to "this location" / current script
     * @return string Urls are returned as JavaScript variables T3_RETURN_URL and T3_THIS_LOCATION
     * @see typo3/db_list.php
     * @internal
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
     * Returns the header-bar in the top of most backend modules
     * Closes section if open.
     *
     * @param string $text The text string for the header
     * @return string HTML content
     * @internal
     */
    public function header($text)
    {
        return '

	<!-- MAIN Header in page top -->
	<h1 class="t3js-title-inlineedit">' . htmlspecialchars($text) . '</h1>
';
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default)
     * @throws \InvalidArgumentException if the message body is no string
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1446483133);
        }
        /* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\FlashMessage::class,
            $messageBody,
            $messageTitle,
            $severity,
            $storeInSession
        );
        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    /**
     * @param \TYPO3\CMS\Core\Messaging\FlashMessageQueue $flashMessageQueue
     */
    public function setFlashMessageQueue($flashMessageQueue)
    {
        $this->flashMessageQueue = $flashMessageQueue;
    }

    /**
     * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue
     */
    protected function getFlashMessageQueue()
    {
        if (!isset($this->flashMessageQueue)) {
            /** @var FlashMessageService $service */
            $service = GeneralUtility::makeInstance(FlashMessageService::class);
            $this->flashMessageQueue = $service->getMessageQueueByIdentifier();
        }
        return $this->flashMessageQueue;
    }

    /**
     * @return bool
     */
    public function isUiBlock(): bool
    {
        return $this->uiBlock;
    }

    /**
     * @param bool $uiBlock
     */
    public function setUiBlock(bool $uiBlock)
    {
        $this->uiBlock = $uiBlock;
    }
}
