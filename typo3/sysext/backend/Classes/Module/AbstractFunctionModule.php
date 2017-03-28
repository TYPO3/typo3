<?php
namespace TYPO3\CMS\Backend\Module;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Parent class for 'Extension Objects' in backend modules.
 *
 * Used for 'submodules' to other modules. Also called 'Function menu modules'
 * in \TYPO3\CMS\Core\Utility\ExtensionManagementUtility. And now its even called
 * 'Extension Objects'. Or 'Module functions'. Wish we had just one name. Or a
 * name at all...(?) Thank God its not so advanced when it works...
 *
 * In other words this class is used for backend modules which is not true
 * backend modules appearing in the menu but rather adds themselves as a new
 * entry in the function menu which typically exists for a backend
 * module (like Web>Functions, Web>Info or Tools etc...)
 * The magic that binds this together is stored in the global variable
 * $TBE_MODULES_EXT where extensions wanting to connect a module based on
 * this class to an existing backend module store configuration which consists
 * of the classname, script-path and a label (title/name).
 *
 * For more information about this, please see the large example comment for the
 * class \TYPO3\CMS\Backend\Module\BaseScriptClass. This will show the principle of a
 * 'level-1' connection. The more advanced example - having two layers as it is done
 * by the 'func_wizards' extension with the 'web_info' module - can be seen in the
 * comment above.
 *
 * EXAMPLE: One level.
 * This can be seen in the extension 'frontend' where the info module have a
 * function added. In 'ext_tables.php' this is done by this function call:
 *
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
 * 'web_info',
 * \TYPO3\CMS\Frontend\Controller\PageInformationController::class,
 * NULL,
 * 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page'
 * );
 *
 * EXAMPLE: Two levels.
 * This is the advanced example. You can see it with the extension 'func_wizards'
 * which is the first layer but then providing another layer for extensions to connect by.
 * The key used in TBE_MODULES_EXT is normally 'function' (for the 'function menu')
 * but the 'func_wizards' extension uses an alternative key for its configuration: 'wiz'.
 * In the 'ext_tables.php' file of an extension ('wizard_crpages') which uses the
 * framework provided by 'func_wizards' this looks like this:
 *
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
 * 'web_func',
 * \TYPO3\CMS\WizardCrpages\Controller\CreatePagesWizardModuleFunctionController::class
 * NULL,
 * 'LLL:EXT:wizard_crpages/locallang.xlf:wiz_crMany',
 * 'wiz'
 * );
 *
 * But for this two-level thing to work it also requires that the parent
 * module (the real backend module) supports it.
 * This is the case for the modules web_func and web_info since they have two
 * times inclusion sections in their index.php scripts. For example (from web_func):
 *
 * Make instance:
 * $GLOBALS['SOBE'] = GeneralUtility::makeInstance(\TYPO3\CMS\Func\Controller\PageFunctionsController::class);
 * $GLOBALS['SOBE']->init();
 *
 * Anyways, the final interesting thing is to see what the framework
 * "func_wizard" actually does:
 *
 * class WebFunctionWizardsBaseController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {
 * var $localLangFile = "locallang.xlf";
 * var $function_key = "wiz";
 * function init(&$pObj, $conf) {
 * OK, handles ordinary init. This includes setting up the
 * menu array with ->modMenu
 * parent::init($pObj,$conf);
 * $this->handleExternalFunctionValue();
 * }
 * }
 *
 * Notice that the handleExternalFunctionValue of this class
 * is called and that the ->function_key internal var is set!
 *
 * The two level-2 sub-module "wizard_crpages" and "wizard_sortpages"
 * are totally normal "submodules".
 * @see \TYPO3\CMS\Backend\Module\BaseScriptClass
 * @see \TYPO3\CMS\FuncWizards\Controller\WebFunctionWizardsBaseController
 * @see \TYPO3\CMS\WizardSortpages\View\SortPagesWizardModuleFunction
 */
abstract class AbstractFunctionModule
{
    /**
     * Contains a reference to the parent (calling) object (which is probably an instance of
     * an extension class to \TYPO3\CMS\Backend\Module\BaseScriptClass
     *
     * @var BaseScriptClass
     * @see init()
     */
    public $pObj;

    /**
     * @var BaseScriptClass
     */
    public $extObj = null;

    /**
     * Set to the directory name of this class file.
     *
     * @see init()
     * @var string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public $thisPath = '';

    /**
     * Can be hardcoded to the name of a locallang.xlf file (from the same directory as the class file) to use/load
     * and is included / added to $GLOBALS['LOCAL_LANG']
     *
     * @see init()
     * @var string
     */
    public $localLangFile = '';

    /**
     * Contains module configuration parts from TBE_MODULES_EXT if found
     *
     * @see handleExternalFunctionValue()
     * @var array
     */
    public $extClassConf;

    /**
     * If this value is set it points to a key in the TBE_MODULES_EXT array (not on the top level..) where another classname/filepath/title can be defined for sub-subfunctions.
     * This is a little hard to explain, so see it in action; it used in the extension 'func_wizards' in order to provide yet a layer of interfacing with the backend module.
     * The extension 'func_wizards' has this description: 'Adds the 'Wizards' item to the function menu in Web>Func. This is just a framework for wizard extensions.' - so as you can see it is designed to allow further connectivity - 'level 2'
     *
     * @see handleExternalFunctionValue(), \TYPO3\CMS\FuncWizards\Controller\WebFunctionWizardsBaseController
     * @var string
     */
    public $function_key = '';

    /**
     * @var PageRenderer
     */
    protected $pageRenderer = null;

    /**
     * Initialize the object
     *
     * @param BaseScriptClass $pObj A reference to the parent (calling) object
     * @param array $conf The configuration set for this module - from global array TBE_MODULES_EXT
     * @throws \RuntimeException
     * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
     */
    public function init(&$pObj, $conf)
    {
        $this->pObj = $pObj;
        // Path of this script:
        $reflector = new \ReflectionObject($this);
        $this->thisPath = dirname($reflector->getFileName());
        if (!@is_dir($this->thisPath)) {
            throw new \RuntimeException('TYPO3 Fatal Error: Could not find path for class ' . get_class($this), 1381164687);
        }
        // Local lang:
        if (!empty($this->localLangFile)) {
            $this->getLanguageService()->includeLLFile($this->localLangFile);
        }
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * If $this->function_key is set (which means there are two levels of object connectivity) then
     * $this->extClassConf is loaded with the TBE_MODULES_EXT configuration for that sub-sub-module
     *
     * @see $function_key, \TYPO3\CMS\FuncWizards\Controller\WebFunctionWizardsBaseController::init()
     */
    public function handleExternalFunctionValue()
    {
        // Must clean first to make sure the correct key is set...
        $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
        if ($this->function_key) {
            $this->extClassConf = $this->pObj->getExternalItemConfig($this->pObj->MCONF['name'], $this->function_key, $this->pObj->MOD_SETTINGS[$this->function_key]);
        }
    }

    /**
     * Including any locallang file configured and merging its content over
     * the current global LOCAL_LANG array (which is EXPECTED to exist!!!)
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function incLocalLang()
    {
        GeneralUtility::logDeprecatedFunction();
        if (
            $this->localLangFile
            && (
                @is_file(($this->thisPath . '/' . $this->localLangFile))
                || @is_file(($this->thisPath . '/' . substr($this->localLangFile, 0, -4) . '.xml'))
            )
        ) {
            $LOCAL_LANG = $this->getLanguageService()->includeLLFile($this->thisPath . '/' . $this->localLangFile, false);
            if (is_array($LOCAL_LANG)) {
                $GLOBALS['LOCAL_LANG'] = (array)$GLOBALS['LOCAL_LANG'];
                ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['LOCAL_LANG'], $LOCAL_LANG);
            }
        }
    }

    /**
     * Same as \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
     *
     * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
     */
    public function checkExtObj()
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            $this->extObj->init($this->pObj, $this->extClassConf);
            // Re-write:
            $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
        }
    }

    /**
     * Calls the main function inside ANOTHER sub-submodule which might exist.
     */
    public function extObjContent()
    {
        if (is_object($this->extObj)) {
            return $this->extObj->main();
        }
    }

    /**
     * Dummy function - but is used to set up additional menu items for this submodule.
     *
     * @return array A MOD_MENU array which will be merged together with the one from the parent object
     * @see init(), \TYPO3\CMS\Frontend\Controller\PageInformationController::modMenu()
     */
    public function modMenu()
    {
        return [];
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
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }

    /**
     * @return string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    protected function getBackPath()
    {
        GeneralUtility::logDeprecatedFunction();
        return '';
    }

    /**
     * @return DatabaseConnection
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use the Doctrine DBAL layer via the ConnectionPool class
     */
    protected function getDatabaseConnection()
    {
        GeneralUtility::logDeprecatedFunction();
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }
}
