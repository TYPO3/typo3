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
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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
     * @var array
     */
    protected $pageInfo;

    /**
     * @var StandaloneView
     */
    protected $view;

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
     * Process incoming request and dispatch to the requested action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $action = (string)($parsedBody['action'] ?? $queryParams['action'] ?? 'wizard');
        if (!in_array($action, ['wizard', 'positionMap'], true)) {
            return new HtmlResponse('Action not allowed', 400);
        }

        // Setting internal vars:
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $this->sys_language = (int)($parsedBody['sys_language_uid'] ?? $queryParams['sys_language_uid'] ?? 0);
        $this->R_URI = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $colPos = $parsedBody['colPos'] ?? $queryParams['colPos'] ?? null;
        $this->colPos = $colPos === null ? null : (int)$colPos;
        $this->uid_pid = (int)($parsedBody['uid_pid'] ?? $queryParams['uid_pid'] ?? 0);

        // Getting the current page and receiving access information
        $this->pageInfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];

        // Initializing the view by forwarding the requested action as template name
        $this->view = $this->getFluidTemplateObject(ucfirst($action));

        // Call action and return the response
        return $this->{$action . 'Action'}($request);
    }

    /**
     * Renders the wizard
     */
    public function wizardAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->prepareWizardContent($request);
        return new HtmlResponse($this->view->render());
    }

    /**
     * Renders the position map
     */
    public function positionMapAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->preparePositionMap($request);
        return new HtmlResponse($this->view->render());
    }

    /**
     * Creating the module output.
     *
     * @throws \UnexpectedValueException
     */
    protected function prepareWizardContent(ServerRequestInterface $request): void
    {
        $hasAccess = $this->id && $this->pageInfo !== [];
        $this->view->assign('hasAccess', $hasAccess);
        if (!$hasAccess) {
            return;
        }
        // Whether position selection must be performed (no colPos was yet defined)
        $positionSelection = !isset($this->colPos);
        $this->view->assign('positionSelection', $positionSelection);

        // Get processed wizard items from configuration
        $wizardItems = $this->getWizards();

        // Call hooks for manipulating the wizard items
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

        $key = 0;
        $menuItems = [];
        // Traverse items for the wizard.
        // An item is either a header or an item rendered with title/description and icon:
        foreach ($wizardItems as $wizardKey => $wInfo) {
            if (isset($wInfo['header'])) {
                $menuItems[] = [
                    'label' => $wInfo['header'] ?: '-',
                    'content' => '',
                ];
                $key = count($menuItems) - 1;
            } else {
                // Initialize the view variables for the item
                $viewVariables = [
                    'wizardInformation' => $wInfo,
                    'wizardKey' => $wizardKey,
                    'icon' => $this->iconFactory->getIcon(($wInfo['iconIdentifier'] ?? ''), Icon::SIZE_DEFAULT, ($wInfo['iconOverlay'] ?? ''))->render(),
                ];

                // Check wizardItem for defVals
                $itemParams = [];
                parse_str($wInfo['params'] ?? '', $itemParams);
                $defVals = $itemParams['defVals']['tt_content'] ?? [];

                // In case no position has to be selected, we can just add the target
                if (!$positionSelection) {
                    // Go to DataHandler directly instead of FormEngine
                    if (($wInfo['saveAndClose'] ?? false)) {
                        $viewVariables['target'] = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                            'data' => [
                                'tt_content' => [
                                    StringUtility::getUniqueId('NEW') => array_replace($defVals, [
                                        'colPos' => $this->colPos,
                                        'pid' => $this->uid_pid,
                                        'sys_language_uid' => $this->sys_language,
                                    ]),
                                ],
                            ],
                            'redirect' => $this->R_URI,
                        ]);
                    } else {
                        $viewVariables['target'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                            'edit' => [
                                'tt_content' => [
                                    $this->uid_pid => 'new',
                                ],
                            ],
                            'returnUrl' => $this->R_URI,
                            'defVals' => [
                                'tt_content' => array_replace($defVals, [
                                    'colPos' => $this->colPos,
                                    'sys_language_uid' => $this->sys_language,
                                ]),
                            ],
                        ]);
                    }
                } else {
                    $viewVariables['positionMapArguments'] = GeneralUtility::jsonEncodeForHtmlAttribute([
                        'url' => (string)$this->uriBuilder->buildUriFromRoute('new_content_element_wizard', [
                            'action' => 'positionMap',
                            'id' => $this->id,
                            'sys_language_uid' => $this->sys_language,
                            'returnUrl' => $this->R_URI,
                        ]),
                        'defVals' => $defVals,
                        'saveAndClose' => (bool)($wInfo['saveAndClose'] ?? false),
                    ], true);
                }

                $menuItems[$key]['content'] .= $this->getFluidTemplateObject('MenuItem')->assignMultiple($viewVariables)->render();
            }
        }

        $this->view->assign('renderedTabs', $this->moduleTemplateFactory->create($request)->getDynamicTabMenu(
            $menuItems,
            'new-content-element-wizard'
        ));
    }

    /**
     * Creating the position map, necessary in case the position of the element has to be defined
     */
    protected function preparePositionMap(ServerRequestInterface $request): void
    {
        // Init position map object
        $posMap = GeneralUtility::makeInstance(ContentCreationPagePositionMap::class);
        $posMap->cur_sys_language = $this->sys_language;
        $posMap->defVals = (array)($request->getParsedBody()['defVals'] ?? []);
        $posMap->saveAndClose = (bool)($request->getParsedBody()['saveAndClose'] ?? false);
        $posMap->R_URI =  $this->R_URI;
        $this->view->assign('posMap', $posMap->printContentElementColumns($this->id));
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
        $wizards = BackendUtility::getPagesTSconfig($this->id)['mod.']['wizards.']['newContentElement.']['wizardItems.'] ?? [];
        $appendWizards = $this->getAppendWizards($wizards['elements.'] ?? []);
        if (is_array($wizards)) {
            foreach ($wizards as $groupKey => $wizardGroup) {
                $this->prepareDependencyOrdering($wizards[$groupKey], 'before');
                $this->prepareDependencyOrdering($wizards[$groupKey], 'after');
            }
            $wizards = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($wizards);

            foreach ($wizards as $groupKey => $wizardGroup) {
                $groupKey = rtrim($groupKey, '.');
                $showItems = GeneralUtility::trimExplode(',', $wizardGroup['show'] ?? '', true);
                $showAll = in_array('*', $showItems, true);
                $groupItems = [];
                $appendWizardElements = $appendWizards[$groupKey . '.']['elements.'] ?? null;
                if (is_array($appendWizardElements)) {
                    $wizardElements = array_merge((array)($wizardGroup['elements.'] ?? []), $appendWizardElements);
                } else {
                    $wizardElements = $wizardGroup['elements.'] ?? [];
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
        // @deprecated will be removed in TYPO3 v12.0.
        $classes = $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] ?? [];
        if (is_array($classes)) {
            if (!empty($classes)) {
                trigger_error('The hook $TBE_MODULES_EXT[xMOD_db_new_content_el][addElClasses] will be removed in TYPO3 v12.0. Use $GLOBALS[TYPO3_CONF_VARS][SC_OPTIONS][cms][db_new_content_el][wizardItemsHook] instead.', E_USER_DEPRECATED);
            }
            foreach ($classes as $class => $path) {
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
        $itemConf['saveAndClose'] = (bool)($itemConf['saveAndClose'] ?? false);
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
            'header' => $this->getLanguageService()->sL($wizardGroup['header'] ?? ''),
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
            if ($wizardItems[$key]['params'] ?? false) {
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
            if (is_array($wizardItems[$key]['tt_content_defValues'] ?? false)) {
                $backendUser = $this->getBackendUser();
                // Traverse field values:
                $wizardItems[$key]['params'] ??= '';
                foreach ($wizardItems[$key]['tt_content_defValues'] as $fN => $fV) {
                    if (is_array($GLOBALS['TCA']['tt_content']['columns'][$fN])) {
                        // Get information about if the field value is OK:
                        $config = &$GLOBALS['TCA']['tt_content']['columns'][$fN]['config'];
                        $authModeDeny = $config['type'] === 'select' && ($config['authMode'] ?? false)
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
            if (($tmp[0] ?? null) && !($tmp[1] ?? null) && !in_array($tmp[0], $headersUsed)) {
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
            $wizardGroup[$key] = array_map(static function ($s) {
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
     * @param string $templateName
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $templateName): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/NewContentElement/' . $templateName . '.html'));
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
