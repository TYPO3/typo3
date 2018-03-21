<?php
namespace TYPO3\CMS\Backend\Controller\ContentElement;

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
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for the New Content element wizard
 */
class NewContentElementController extends AbstractModule
{
    /**
     * Page id
     *
     * @var int
     */
    public $id;

    /**
     * Sys language
     *
     * @var int
     */
    public $sys_language = 0;

    /**
     * Return URL.
     *
     * @var string
     */
    public $R_URI = '';

    /**
     * If set, the content is destined for a specific column.
     *
     * @var int|null
     */
    public $colPos;

    /**
     * @var int
     */
    public $uid_pid;

    /**
     * Module TSconfig.
     *
     * @var array
     */
    public $modTSconfig = [];

    /**
     * Internal backend template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * Used to accumulate the content of the module.
     *
     * @var string
     */
    public $content;

    /**
     * Access boolean.
     *
     * @var bool
     */
    public $access;

    /**
     * config of the wizard
     *
     * @var array
     */
    public $config;

    /**
     * @var array
     */
    protected $pageInfo;

    /**
     * @var string
     */
    protected $onClickEvent;

    /**
     * @var array
     */
    protected $MCONF;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var StandaloneView
     */
    protected $menuItemView;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $GLOBALS['SOBE'] = $this;
        $this->view = $this->getFluidTemplateObject();
        $this->menuItemView = $this->getFluidTemplateObject('MenuItem.html');
        $this->init();
    }

    /**
     * Constructor, initializing internal variables.
     */
    public function init()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:lang/Resources/Private/Language/locallang_misc.xlf');
        $LOCAL_LANG_orig = $GLOBALS['LOCAL_LANG'];
        $lang->includeLLFile('EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf');
        ArrayUtility::mergeRecursiveWithOverrule($LOCAL_LANG_orig, $GLOBALS['LOCAL_LANG']);
        $GLOBALS['LOCAL_LANG'] = $LOCAL_LANG_orig;

        // Setting internal vars:
        $this->id = (int)GeneralUtility::_GP('id');
        $this->sys_language = (int)GeneralUtility::_GP('sys_language_uid');
        $this->R_URI = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->colPos = GeneralUtility::_GP('colPos') === null ? null : (int)GeneralUtility::_GP('colPos');
        $this->uid_pid = (int)GeneralUtility::_GP('uid_pid');
        $this->MCONF['name'] = 'xMOD_db_new_content_el';
        $this->modTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.wizards.newContentElement');
        $config = BackendUtility::getPagesTSconfig($this->id);
        $this->config = $config['mod.']['wizards.']['newContentElement.'];
        // Starting the document template object:
        // We keep this here in case somebody relies on it in a hook or alike
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        // Setting up the context sensitive menu:
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        // Getting the current page and receiving access information (used in main())
        $perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->pageInfo = BackendUtility::readPageAccess($this->id, $perms_clause);
        $this->access = is_array($this->pageInfo) ? 1 : 0;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();
        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Creating the module output.
     *
     * @throws \UnexpectedValueException
     */
    public function main()
    {
        $hasAccess = true;
        if ($this->id && $this->access) {

            // Init position map object:
            $posMap = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap::class);
            $posMap->cur_sys_language = $this->sys_language;
            // If a column is pre-set:
            if (isset($this->colPos)) {
                if ($this->uid_pid < 0) {
                    $row = [];
                    $row['uid'] = abs($this->uid_pid);
                } else {
                    $row = '';
                }
                $this->onClickEvent = $posMap->onClickInsertRecord(
                    $row,
                    $this->colPos,
                    '',
                    $this->uid_pid,
                    $this->sys_language
                );
            } else {
                $this->onClickEvent = '';
            }
            // ***************************
            // Creating content
            // ***************************
            // Wizard
            $wizardItems = $this->wizardArray();
            // Wrapper for wizards
            // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] as $classData) {
                    $hookObject = GeneralUtility::getUserObj($classData);
                    if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                        throw new \UnexpectedValueException(
                            $classData . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                            1227834741
                        );
                    }
                    $hookObject->manipulateWizardItems($wizardItems, $this);
                }
            }
            // Add document inline javascript
            $this->moduleTemplate->addJavaScriptCode(
                'NewContentElementWizardInlineJavascript',
                '
				function goToalt_doc() {
					' . $this->onClickEvent . '
				}'
            );

            // Traverse items for the wizard.
            // An item is either a header or an item rendered with a radio button and title/description and icon:
            $cc = ($key = 0);
            $menuItems = [];

            $this->view->assign('onClickEvent', $this->onClickEvent);

            foreach ($wizardItems as $wizardKey => $wInfo) {
                $wizardOnClick = '';
                if (isset($wInfo['header'])) {
                    $menuItems[] = [
                        'label' => $wInfo['header'] ?: '-',
                        'content' => ''
                    ];
                    $key = count($menuItems) - 1;
                } else {
                    if (!$this->onClickEvent) {
                        // Radio button:
                        $wizardOnClick = 'document.editForm.defValues.value=unescape(' . GeneralUtility::quoteJSvalue(rawurlencode($wInfo['params'])) . ');goToalt_doc();' . (!$this->onClickEvent ? 'window.location.hash=\'#sel2\';' : '');
                        // Onclick action for icon/title:
                        $aOnClick = 'document.getElementsByName(\'tempB\')[' . $cc . '].checked=1;' . $wizardOnClick . 'return false;';
                    } else {
                        $aOnClick = "document.editForm.defValues.value=unescape('" . rawurlencode($wInfo['params']) . "');goToalt_doc();" . (!$this->onClickEvent?"window.location.hash='#sel2';":'');
                    }

                    $icon = $this->moduleTemplate->getIconFactory()->getIcon($wInfo['iconIdentifier'])->render();

                    $this->menuItemView->assignMultiple([
                        'onClickEvent' => $this->onClickEvent,
                        'aOnClick' => $aOnClick,
                        'wizardInformation' => $wInfo,
                        'icon' => $icon,
                        'wizardOnClick' => $wizardOnClick,
                        'wizardKey' => $wizardKey
                    ]);
                    $menuItems[$key]['content'] .= $this->menuItemView->render();
                    $cc++;
                }
            }

            $this->view->assign('renderedTabs', $this->moduleTemplate->getDynamicTabMenu(
                $menuItems,
                'new-content-element-wizard'
            ));

            // If the user must also select a column:
            if (!$this->onClickEvent) {

                // Load SHARED page-TSconfig settings and retrieve column list from there, if applicable:
                $colPosArray = GeneralUtility::callUserFunction(
                    BackendLayoutView::class . '->getColPosListItemsParsed',
                    $this->id,
                    $this
                );
                $colPosIds = array_column($colPosArray, 1);
                // Removing duplicates, if any
                $colPosList = implode(',', array_unique(array_map('intval', $colPosIds)));
                // Finally, add the content of the column selector to the content:
                $this->view->assign('posMap', $posMap->printContentElementColumns($this->id, 0, $colPosList, 1, $this->R_URI));
            }
        } else {
            // In case of no access:
            $hasAccess = false;
        }
        $this->view->assign('hasAccess', $hasAccess);

        $this->content = $this->view->render();
        // Setting up the buttons and markers for docheader
        $this->getButtons();
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->R_URI) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->R_URI)
                ->setTitle($this->getLanguageService()->getLL('goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-view-go-back',
                    Icon::SIZE_SMALL
                ));
            $buttonBar->addButton($backButton);
        }
        $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('new_ce');
        $buttonBar->addButton($cshButton);
    }

    /***************************
     *
     * OTHER FUNCTIONS:
     *
     ***************************/

    /**
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     *
     * @return array
     */
    public function wizardArray()
    {
        $wizardItems = [];
        if (is_array($this->config)) {
            $wizards = $this->config['wizardItems.'];
            $appendWizards = $this->wizard_appendWizards($wizards['elements.']);
            if (is_array($wizards)) {
                foreach ($wizards as $groupKey => $wizardGroup) {
                    $this->prepareDependencyOrdering($wizards[$groupKey], 'before');
                    $this->prepareDependencyOrdering($wizards[$groupKey], 'after');
                }
                $wizards = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($wizards);

                foreach ($wizards as $groupKey => $wizardGroup) {
                    $groupKey = rtrim($groupKey, '.');
                    $showItems = GeneralUtility::trimExplode(',', $wizardGroup['show'], true);
                    $showAll = in_array('*', $showItems, true);
                    $groupItems = [];
                    if (is_array($appendWizards[$groupKey . '.']['elements.'])) {
                        $wizardElements = array_merge((array)$wizardGroup['elements.'], $appendWizards[$groupKey . '.']['elements.']);
                    } else {
                        $wizardElements = $wizardGroup['elements.'];
                    }
                    if (is_array($wizardElements)) {
                        foreach ($wizardElements as $itemKey => $itemConf) {
                            $itemKey = rtrim($itemKey, '.');
                            if ($showAll || in_array($itemKey, $showItems)) {
                                $tmpItem = $this->wizard_getItem($groupKey, $itemKey, $itemConf);
                                if ($tmpItem) {
                                    $groupItems[$groupKey . '_' . $itemKey] = $tmpItem;
                                }
                            }
                        }
                    }
                    if (!empty($groupItems)) {
                        $wizardItems[$groupKey] = $this->wizard_getGroupHeader($groupKey, $wizardGroup);
                        $wizardItems = array_merge($wizardItems, $groupItems);
                    }
                }
            }
        }
        // Remove elements where preset values are not allowed:
        $this->removeInvalidElements($wizardItems);
        return $wizardItems;
    }

    /**
     * @param mixed $wizardElements
     * @return array
     */
    public function wizard_appendWizards($wizardElements)
    {
        if (!is_array($wizardElements)) {
            $wizardElements = [];
        }
        if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'])) {
            foreach ($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] as $class => $path) {
                require_once $path;
                $modObj = GeneralUtility::makeInstance($class);
                $wizardElements = $modObj->proc($wizardElements);
            }
        }
        $returnElements = [];
        foreach ($wizardElements as $key => $wizardItem) {
            preg_match('/^[a-zA-Z0-9]+_/', $key, $group);
            $wizardGroup = $group[0] ? substr($group[0], 0, -1) . '.' : $key;
            $returnElements[$wizardGroup]['elements.'][substr($key, strlen($wizardGroup)) . '.'] = $wizardItem;
        }
        return $returnElements;
    }

    /**
     * @param string $groupKey Not used
     * @param string $itemKey Not used
     * @param array $itemConf
     * @return array
     */
    public function wizard_getItem($groupKey, $itemKey, $itemConf)
    {
        $itemConf['title'] = $this->getLanguageService()->sL($itemConf['title']);
        $itemConf['description'] = $this->getLanguageService()->sL($itemConf['description']);
        $itemConf['tt_content_defValues'] = $itemConf['tt_content_defValues.'];
        unset($itemConf['tt_content_defValues.']);
        return $itemConf;
    }

    /**
     * @param string $groupKey Not used
     * @param array $wizardGroup
     * @return array
     */
    public function wizard_getGroupHeader($groupKey, $wizardGroup)
    {
        return [
            'header' => $this->getLanguageService()->sL($wizardGroup['header'])
        ];
    }

    /**
     * Checks the array for elements which might contain unallowed default values and will unset them!
     * Looks for the "tt_content_defValues" key in each element and if found it will traverse that array as fieldname /
     * value pairs and check.
     * The values will be added to the "params" key of the array (which should probably be unset or empty by default).
     *
     * @param array $wizardItems Wizard items, passed by reference
     */
    public function removeInvalidElements(&$wizardItems)
    {
        // Get TCEFORM from TSconfig of current page
        $row = ['pid' => $this->id];
        $TCEFORM_TSconfig = BackendUtility::getTCEFORM_TSconfig('tt_content', $row);
        $headersUsed = [];
        // Traverse wizard items:
        foreach ($wizardItems as $key => $cfg) {
            // Exploding parameter string, if any (old style)
            if ($wizardItems[$key]['params']) {
                // Explode GET vars recursively
                $tempGetVars = GeneralUtility::explodeUrl2Array($wizardItems[$key]['params'], true);
                // If tt_content values are set, merge them into the tt_content_defValues array,
                // unset them from $tempGetVars and re-implode $tempGetVars into the param string
                // (in case remaining parameters are around).
                if (is_array($tempGetVars['defVals']['tt_content'])) {
                    $wizardItems[$key]['tt_content_defValues'] = array_merge(
                        is_array($wizardItems[$key]['tt_content_defValues']) ? $wizardItems[$key]['tt_content_defValues'] : [],
                        $tempGetVars['defVals']['tt_content']
                    );
                    unset($tempGetVars['defVals']['tt_content']);
                    $wizardItems[$key]['params'] = GeneralUtility::implodeArrayForUrl('', $tempGetVars);
                }
            }
            // If tt_content_defValues are defined...:
            if (is_array($wizardItems[$key]['tt_content_defValues'])) {
                $backendUser = $this->getBackendUser();
                // Traverse field values:
                foreach ($wizardItems[$key]['tt_content_defValues'] as $fN => $fV) {
                    if (is_array($GLOBALS['TCA']['tt_content']['columns'][$fN])) {
                        // Get information about if the field value is OK:
                        $config = &$GLOBALS['TCA']['tt_content']['columns'][$fN]['config'];
                        $authModeDeny = $config['type'] === 'select' && $config['authMode']
                            && !$backendUser->checkAuthMode('tt_content', $fN, $fV, $config['authMode']);
                        // explode TSconfig keys only as needed
                        if (!isset($removeItems[$fN]) && isset($TCEFORM_TSconfig[$fN]['removeItems']) && $TCEFORM_TSconfig[$fN]['removeItems'] !== '') {
                            $removeItems[$fN] = array_flip(GeneralUtility::trimExplode(
                                ',',
                                $TCEFORM_TSconfig[$fN]['removeItems'],
                                true
                            ));
                        }
                        if (!isset($keepItems[$fN]) && isset($TCEFORM_TSconfig[$fN]['keepItems']) && $TCEFORM_TSconfig[$fN]['keepItems'] !== '') {
                            $keepItems[$fN] = array_flip(GeneralUtility::trimExplode(
                                ',',
                                $TCEFORM_TSconfig[$fN]['keepItems'],
                                true
                            ));
                        }
                        $isNotInKeepItems = !empty($keepItems[$fN]) && !isset($keepItems[$fN][$fV]);
                        if ($authModeDeny || ($fN === 'CType' && (isset($removeItems[$fN][$fV]) || $isNotInKeepItems))) {
                            // Remove element all together:
                            unset($wizardItems[$key]);
                            break;
                        }
                        // Add the parameter:
                        $wizardItems[$key]['params'] .= '&defVals[tt_content][' . $fN . ']=' . rawurlencode($this->getLanguageService()->sL($fV));
                        $tmp = explode('_', $key);
                        $headersUsed[$tmp[0]] = $tmp[0];
                    }
                }
            }
        }
        // remove headers without elements
        foreach ($wizardItems as $key => $cfg) {
            $tmp = explode('_', $key);
            if ($tmp[0] && !$tmp[1] && !in_array($tmp[0], $headersUsed)) {
                unset($wizardItems[$key]);
            }
        }
    }

    /**
     * Prepare a wizard tab configuration for sorting.
     *
     * @param array  $wizardGroup TypoScript wizard tab configuration
     * @param string $key         Which array key should be prepared
     */
    protected function prepareDependencyOrdering(&$wizardGroup, $key)
    {
        if (isset($wizardGroup[$key])) {
            $wizardGroup[$key] = GeneralUtility::trimExplode(',', $wizardGroup[$key]);
            $wizardGroup[$key] = array_map(function ($s) {
                return $s . '.';
            }, $wizardGroup[$key]);
        }
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @param string $filename
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename = 'Main.html'): StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/NewContentElement/' . $filename));
        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
