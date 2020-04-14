<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Controller\ContentElement;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for the New Content element wizard
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class NewContentElementController
{
    /**
     * Page id
     *
     * @var int
     */
    protected $id;

    /**
     * Sys language
     *
     * @var int
     */
    protected $sys_language = 0;

    /**
     * Return URL.
     *
     * @var string
     */
    protected $R_URI = '';

    /**
     * If set, the content is destined for a specific column.
     *
     * @var int|null
     */
    protected $colPos;

    /**
     * @var int
     */
    protected $uid_pid;

    /**
     * config of the wizard
     *
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $pageInfo;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var StandaloneView
     */
    protected $menuItemView;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $GLOBALS['SOBE'] = $this;
        $this->view = $this->getFluidTemplateObject();
        $this->menuItemView = $this->getFluidTemplateObject('MenuItem.html');
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * Constructor, initializing internal variables.
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request)
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $lang->includeLLFile('EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf');

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Setting internal vars:
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $this->sys_language = (int)($parsedBody['sys_language_uid'] ?? $queryParams['sys_language_uid'] ?? 0);
        $this->R_URI = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $colPos = $parsedBody['colPos'] ?? $queryParams['colPos'] ?? null;
        $this->colPos = $colPos === null ? null : (int)$colPos;
        $this->uid_pid = (int)($parsedBody['uid_pid'] ?? $queryParams['uid_pid'] ?? 0);
        $this->config = BackendUtility::getPagesTSconfig($this->id)['mod.']['wizards.']['newContentElement.']['wizardItems.'] ?? [];
        // Setting up the context sensitive menu:
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        // Getting the current page and receiving access information (used in main())
        $this->pageInfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $this->prepareContent('window');
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function wizardAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $this->prepareContent('list_frame');
        return new HtmlResponse($this->view->render());
    }

    /**
     * Create on-click event value.
     *
     * @param string $clientContext
     * @return string
     */
    protected function onClickInsertRecord(string $clientContext): string
    {
        // $this->uid_pid can be negative (= pointing to tt_content record) or positive (= "page ID")
        $location = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit[tt_content][' . $this->uid_pid . ']' => 'new',
            'defVals[tt_content][colPos]' => $this->colPos,
            'defVals[tt_content][sys_language_uid]' => $this->sys_language,
            'returnUrl' => GeneralUtility::_GP('returnUrl')
        ]);
        return $clientContext . '.location.href=' . GeneralUtility::quoteJSvalue($location) . '+document.editForm.defValues.value; return false;';
    }

    /**
     * Creating the module output.
     *
     * @param string $clientContext JavaScript client context to be used
     *        + 'window', legacy if rendered in current document
     *        + 'list_frame', in case rendered in global modal
     * @throws \UnexpectedValueException
     */
    protected function prepareContent(string $clientContext): void
    {
        // Setting up the buttons for docheader
        $this->getButtons();
        $hasAccess = $this->id && is_array($this->pageInfo);
        if ($hasAccess) {
            // If a column is pre-set
            if (isset($this->colPos)) {
                $onClickEvent = $this->onClickInsertRecord($clientContext);
            } else {
                $onClickEvent = '';
            }
            // ***************************
            // Creating content
            // ***************************
            // Wizard
            $wizardItems = $this->getWizards();
            // Wrapper for wizards
            // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                    throw new \UnexpectedValueException(
                        $className . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                        1227834741
                    );
                }
                $hookObject->manipulateWizardItems($wizardItems, $this);
            }

            // Traverse items for the wizard.
            // An item is either a header or an item rendered with a radio button and title/description and icon:
            $cc = ($key = 0);
            $menuItems = [];

            $this->view->assignMultiple([
                'hasClickEvent' => $onClickEvent !== '',
                'onClickEvent' => 'function goToalt_doc() { ' . $onClickEvent . '}',
            ]);

            foreach ($wizardItems as $wizardKey => $wInfo) {
                $wizardOnClick = '';
                if (isset($wInfo['header'])) {
                    $menuItems[] = [
                        'label' => $wInfo['header'] ?: '-',
                        'content' => ''
                    ];
                    $key = count($menuItems) - 1;
                } else {
                    if (!$onClickEvent) {
                        // Radio button:
                        $wizardOnClick = 'document.editForm.defValues.value=unescape(' . GeneralUtility::quoteJSvalue(rawurlencode($wInfo['params'])) . '); window.location.hash=\'#sel2\';';
                        // Onclick action for icon/title:
                        $aOnClick = 'document.getElementsByName(\'tempB\')[' . $cc . '].checked=1;' . $wizardOnClick . 'return false;';
                    } else {
                        $aOnClick = "document.editForm.defValues.value=unescape('" . rawurlencode($wInfo['params']) . "');goToalt_doc();";
                    }

                    // Go to DataHandler directly instead of FormEngine
                    if ($wInfo['saveAndClose'] ?? false) {
                        $urlParams = [];
                        $id = uniqid('NEW');
                        parse_str($wInfo['params'], $urlParams);
                        $urlParams['data']['tt_content'][$id] = $urlParams['defVals']['tt_content'] ?? [];
                        $urlParams['data']['tt_content'][$id]['colPos'] = $this->colPos;
                        $urlParams['data']['tt_content'][$id]['pid'] = $this->uid_pid;
                        $urlParams['data']['tt_content'][$id]['sys_language_uid'] = $this->sys_language;
                        $urlParams['redirect'] = GeneralUtility::_GP('returnUrl');
                        unset($urlParams['defVals']);
                        $url = $this->uriBuilder->buildUriFromRoute('tce_db', $urlParams);
                        $aOnClick = 'list_frame.location.href=' . GeneralUtility::quoteJSvalue((string)$url) . '; return false';
                    }
                    $icon = $this->moduleTemplate->getIconFactory()->getIcon($wInfo['iconIdentifier'])->render();

                    $this->menuItemView->assignMultiple([
                        'onClickEvent' => $onClickEvent,
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
            if (!$onClickEvent) {
                $this->definePositionMapEntries($clientContext);
            }
        }
        $this->view->assign('hasAccess', $hasAccess);
    }

    /**
     * User must select a column as well (when in "main mode"), so the position map is initialized and assigned to
     * the view.
     *
     * @param string $clientContext
     */
    protected function definePositionMapEntries(string $clientContext): void
    {
        // Load SHARED page-TSconfig settings and retrieve column list from there, if applicable:
        $colPosArray = GeneralUtility::makeInstance(BackendLayoutView::class)->getColPosListItemsParsed((int)$this->id);
        $colPosIds = array_column($colPosArray, 1);
        // Removing duplicates, if any
        $colPosList = implode(',', array_unique(array_map('intval', $colPosIds)));
        // Finally, add the content of the column selector to the content:
        // Init position map object
        $posMap = GeneralUtility::makeInstance(
            ContentCreationPagePositionMap::class,
            null,
            $clientContext
        );
        $posMap->cur_sys_language = $this->sys_language;
        $this->view->assign('posMap', $posMap->printContentElementColumns($this->id, 0, $colPosList, 1, $this->R_URI));
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

    /**
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     *
     * @return array
     */
    protected function getWizards(): array
    {
        $wizardItems = [];
        $wizards = $this->config;
        $appendWizards = $this->getAppendWizards($wizards['elements.'] ?? []);
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
                            $tmpItem = $this->getWizardItem($itemConf);
                            if ($tmpItem) {
                                $groupItems[$groupKey . '_' . $itemKey] = $tmpItem;
                            }
                        }
                    }
                }
                if (!empty($groupItems)) {
                    $wizardItems[$groupKey] = $this->getWizardGroupHeader($wizardGroup);
                    $wizardItems = array_merge($wizardItems, $groupItems);
                }
            }
        }
        // Remove elements where preset values are not allowed:
        $this->removeInvalidWizardItems($wizardItems);
        return $wizardItems;
    }

    /**
     * @param array $wizardElements
     * @return array
     */
    protected function getAppendWizards(array $wizardElements): array
    {
        if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'])) {
            foreach ($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] as $class => $path) {
                if (!class_exists($class) && file_exists($path)) {
                    require_once $path;
                }
                $modObj = GeneralUtility::makeInstance($class);
                if (method_exists($modObj, 'proc')) {
                    $wizardElements = $modObj->proc($wizardElements);
                }
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
     * @param array $itemConf
     * @return array
     */
    protected function getWizardItem(array $itemConf): array
    {
        $itemConf['title'] = $this->getLanguageService()->sL($itemConf['title']);
        $itemConf['description'] = $this->getLanguageService()->sL($itemConf['description']);
        $itemConf['saveAndClose'] = (bool)$itemConf['saveAndClose'];
        $itemConf['tt_content_defValues'] = $itemConf['tt_content_defValues.'];
        unset($itemConf['tt_content_defValues.']);
        return $itemConf;
    }

    /**
     * @param array $wizardGroup
     * @return array
     */
    protected function getWizardGroupHeader(array $wizardGroup): array
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
    protected function removeInvalidWizardItems(array &$wizardItems): void
    {
        $removeItems = [];
        $keepItems = [];
        // Get TCEFORM from TSconfig of current page
        $TCEFORM_TSconfig = BackendUtility::getTCEFORM_TSconfig('tt_content', ['pid' => $this->id]);
        $headersUsed = [];
        // Traverse wizard items:
        foreach ($wizardItems as $key => $cfg) {
            // Exploding parameter string, if any (old style)
            if ($wizardItems[$key]['params']) {
                // Explode GET vars recursively
                $tempGetVars = [];
                parse_str($wizardItems[$key]['params'], $tempGetVars);
                // If tt_content values are set, merge them into the tt_content_defValues array,
                // unset them from $tempGetVars and re-implode $tempGetVars into the param string
                // (in case remaining parameters are around).
                if (is_array($tempGetVars['defVals']['tt_content'])) {
                    $wizardItems[$key]['tt_content_defValues'] = array_merge(
                        is_array($wizardItems[$key]['tt_content_defValues']) ? $wizardItems[$key]['tt_content_defValues'] : [],
                        $tempGetVars['defVals']['tt_content']
                    );
                    unset($tempGetVars['defVals']['tt_content']);
                    $wizardItems[$key]['params'] = HttpUtility::buildQueryString($tempGetVars, '&');
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
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @param string $filename
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    protected function getFluidTemplateObject(string $filename = 'Main.html'): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/NewContentElement/' . $filename));
        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }

    /**
     * Provide information about the current page making use of the wizard
     *
     * @return array
     */
    public function getPageInfo(): array
    {
        return $this->pageInfo;
    }

    /**
     * Provide information about the column position of the button that triggered the wizard
     *
     * @return int|null
     */
    public function getColPos(): ?int
    {
        return $this->colPos;
    }

    /**
     * Provide information about the language used while triggering the wizard
     *
     * @return int
     */
    public function getSysLanguage(): int
    {
        return $this->sys_language;
    }

    /**
     * Provide information about the element to position the new element after (uid) or into (pid)
     *
     * @return int
     */
    public function getUidPid(): int
    {
        return $this->uid_pid;
    }
}
