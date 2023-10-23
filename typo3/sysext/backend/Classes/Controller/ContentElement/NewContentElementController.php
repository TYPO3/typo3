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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * New Content element wizard. This is the modal that pops up when clicking "+content" in page module, which
 * will trigger wizardAction() since there is a colPos given. Method positionMapAction() is triggered for
 * instance from the list module "+content" on tt_content table header, and from list module doc-header "+"
 * and then "Click here for wizard".
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class NewContentElementController
{
    protected int $id = 0;
    protected int $uid_pid = 0;
    protected array $pageInfo = [];
    protected int $sys_language = 0;
    protected string $returnUrl = '';

    /**
     * If set, the content is destined for a specific column.
     */
    protected int|null $colPos = null;

    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly DependencyOrderingService $dependencyOrderingService,
    ) {}

    /**
     * Process incoming request and dispatch to the requested action
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
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $colPos = $parsedBody['colPos'] ?? $queryParams['colPos'] ?? null;
        $this->colPos = $colPos === null ? null : (int)$colPos;
        $this->uid_pid = (int)($parsedBody['uid_pid'] ?? $queryParams['uid_pid'] ?? 0);

        // Getting the current page and receiving access information
        $this->pageInfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];

        // Call action and return the response
        return $this->{$action . 'Action'}($request);
    }

    /**
     * Renders the wizard
     */
    protected function wizardAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->id || $this->pageInfo === []) {
            // No pageId or no access.
            return new HtmlResponse('No Access');
        }
        // Whether position selection must be performed (no colPos was yet defined)
        $positionSelection = $this->colPos === null;

        // Get processed and modified wizard items
        $wizardItems = $this->eventDispatcher->dispatch(
            new ModifyNewContentElementWizardItemsEvent(
                $this->getWizards(),
                $this->pageInfo,
                $this->colPos,
                $this->sys_language,
                $this->uid_pid,
            )
        )->getWizardItems();

        $key = 'common';
        $categories = [];
        foreach ($wizardItems as $wizardKey => $wizardItem) {
            // An item is either a header or an item rendered with title/description and icon:
            if (isset($wizardItem['header'])) {
                $key = $wizardKey;
                $categories[$key] = [
                    'identifier' => $key,
                    'label' => $wizardItem['header'] ?: '-',
                    'items' => [],
                ];
            } else {
                // Initialize the view variables for the item
                $item = [
                    'identifier' => $wizardKey,
                    'icon' => $wizardItem['iconIdentifier'] ?? '',
                    'label' => $wizardItem['title'] ?? '',
                    'description' => $wizardItem['description'] ?? '',
                ];

                // Get default values for the wizard item
                $defVals = (array)($wizardItem['tt_content_defValues'] ?? []);
                if (!$positionSelection) {
                    // In case no position has to be selected, we can just add the target
                    if (($wizardItem['saveAndClose'] ?? false)) {
                        // Go to DataHandler directly instead of FormEngine
                        $item['url'] = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                            'data' => [
                                'tt_content' => [
                                    StringUtility::getUniqueId('NEW') => array_replace($defVals, [
                                        'colPos' => $this->colPos,
                                        'pid' => $this->uid_pid,
                                        'sys_language_uid' => $this->sys_language,
                                    ]),
                                ],
                            ],
                            'redirect' => $this->returnUrl,
                        ]);
                    } else {
                        $item['url'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                            'edit' => [
                                'tt_content' => [
                                    $this->uid_pid => 'new',
                                ],
                            ],
                            'returnUrl' => $this->returnUrl,
                            'defVals' => [
                                'tt_content' => array_replace($defVals, [
                                    'colPos' => $this->colPos,
                                    'sys_language_uid' => $this->sys_language,
                                ]),
                            ],
                        ]);
                    }
                } else {
                    $item['url'] = (string)$this->uriBuilder
                        ->buildUriFromRoute(
                            'new_content_element_wizard',
                            [
                                'action' => 'positionMap',
                                'id' => $this->id,
                                'sys_language_uid' => $this->sys_language,
                                'returnUrl' => $this->returnUrl,
                            ]
                        );
                    $item['requestType'] = 'ajax';
                    $item['defaultValues'] = $defVals;
                    $item['saveAndClose'] = (bool)($wizardItem['saveAndClose'] ?? false);
                }
                $categories[$key]['items'][] = $item;
            }
        }

        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'positionSelection' => $positionSelection,
            'categoriesJson' => GeneralUtility::jsonEncodeForHtmlAttribute($categories, false),
        ]);
        return new HtmlResponse($view->render('NewContentElement/Wizard'));
    }

    /**
     * Renders the position map
     */
    protected function positionMapAction(ServerRequestInterface $request): ResponseInterface
    {
        $posMap = GeneralUtility::makeInstance(ContentCreationPagePositionMap::class);
        $posMap->cur_sys_language = $this->sys_language;
        $posMap->defVals = (array)($request->getParsedBody()['defVals'] ?? []);
        $posMap->saveAndClose = (bool)($request->getParsedBody()['saveAndClose'] ?? false);
        $posMap->R_URI = $this->returnUrl;
        $view = $this->backendViewFactory->create($request);
        $view->assign('posMap', $posMap->printContentElementColumns($this->id));
        return new HtmlResponse($view->render('NewContentElement/PositionMap'));
    }

    /**
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     */
    protected function getWizards(): array
    {
        $wizards = BackendUtility::getPagesTSconfig($this->id)['mod.']['wizards.']['newContentElement.']['wizardItems.'] ?? [];
        if (!is_array($wizards) || $wizards === []) {
            return [];
        }
        $wizardItems = [];
        $appendWizards = $this->getAppendWizards((array)($wizards['elements.'] ?? []));
        foreach ($wizards as $groupKey => $wizardGroup) {
            $wizards[$groupKey] = $this->prepareDependencyOrdering($wizards[$groupKey], 'before');
            $wizards[$groupKey] = $this->prepareDependencyOrdering($wizards[$groupKey], 'after');
        }
        foreach ($this->dependencyOrderingService->orderByDependencies($wizards) as $groupKey => $wizardGroup) {
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
                    if ($itemConf !== [] && ($showAll || in_array($itemKey, $showItems))) {
                        $groupItems[$groupKey . '_' . $itemKey] = $this->getWizardItem($itemConf);
                    }
                }
            }
            if (!empty($groupItems)) {
                $wizardItems[$groupKey]['header'] = $this->getLanguageService()->sL($wizardGroup['header'] ?? '');
                $wizardItems = array_merge($wizardItems, $groupItems);
            }
        }

        // Remove elements where preset values are not allowed:
        return $this->removeInvalidWizardItems($wizardItems);
    }

    protected function getAppendWizards(array $wizardElements): array
    {
        $returnElements = [];
        foreach ($wizardElements as $key => $wizardItem) {
            preg_match('/^[a-zA-Z0-9]+_/', $key, $group);
            $wizardGroup = $group[0] ? substr($group[0], 0, -1) . '.' : $key;
            $returnElements[$wizardGroup]['elements.'][substr($key, strlen($wizardGroup)) . '.'] = $wizardItem;
        }
        return $returnElements;
    }

    protected function getWizardItem(array $itemConf): array
    {
        $itemConf['title'] = trim($this->getLanguageService()->sL($itemConf['title'] ?? ''));
        $itemConf['description'] = trim($this->getLanguageService()->sL($itemConf['description'] ?? ''));
        $itemConf['saveAndClose'] = (bool)($itemConf['saveAndClose'] ?? false);
        $itemConf['tt_content_defValues'] = $itemConf['tt_content_defValues.'] ?? [];
        unset($itemConf['tt_content_defValues.']);
        return $itemConf;
    }

    /**
     * Checks the array for elements which might contain invalid default values and will unset them!
     * Looks for the "tt_content_defValues" key in each element and if found it will traverse that
     * array as fieldname / value pairs and check.
     */
    protected function removeInvalidWizardItems(array $wizardItems): array
    {
        $removeItems = [];
        $keepItems = [];
        // Get TCEFORM from TSconfig of current page
        $TCEFORM_TSconfig = BackendUtility::getTCEFORM_TSconfig('tt_content', ['pid' => $this->id]);
        $headersUsed = [];
        // Traverse wizard items:
        foreach ($wizardItems as $key => $cfg) {
            if (!is_array($cfg['tt_content_defValues'] ?? false)) {
                continue;
            }
            // If tt_content_defValues are defined, check access by traversing all fields with default values:
            $backendUser = $this->getBackendUser();
            foreach ($cfg['tt_content_defValues'] as $fieldName => $value) {
                if (!is_array($GLOBALS['TCA']['tt_content']['columns'][$fieldName])) {
                    continue;
                }
                // Get information about if the field value is OK:
                $config = $GLOBALS['TCA']['tt_content']['columns'][$fieldName]['config'] ?? [];
                $userNotAllowedToAccess = ($config['type'] ?? '') === 'select' && ($config['authMode'] ?? false)
                    && !$backendUser->checkAuthMode('tt_content', $fieldName, $value);
                // Check removeItems
                if (!isset($removeItems[$fieldName]) && ($TCEFORM_TSconfig[$fieldName]['removeItems'] ?? false)) {
                    $removeItems[$fieldName] = array_flip(GeneralUtility::trimExplode(
                        ',',
                        $TCEFORM_TSconfig[$fieldName]['removeItems'],
                        true
                    ));
                }
                // Check keepItems
                if (!isset($keepItems[$fieldName]) && ($TCEFORM_TSconfig[$fieldName]['keepItems'] ?? false)) {
                    $keepItems[$fieldName] = array_flip(GeneralUtility::trimExplode(
                        ',',
                        $TCEFORM_TSconfig[$fieldName]['keepItems'],
                        true
                    ));
                }
                $isNotInKeepItems = !empty($keepItems[$fieldName]) && !isset($keepItems[$fieldName][$value]);
                if ($userNotAllowedToAccess || ($fieldName === 'CType' && (isset($removeItems[$fieldName][$value]) || $isNotInKeepItems))) {
                    // Remove element all together:
                    unset($wizardItems[$key]);
                    break;
                }
                // Add the parameter:
                $wizardItems[$key]['tt_content_defValues'][$fieldName] = $this->getLanguageService()->sL($value);
                $tmp = explode('_', $key);
                $headersUsed[$tmp[0]] = $tmp[0];
            }
        }
        // remove headers without elements
        foreach ($wizardItems as $key => $cfg) {
            $tmp = explode('_', $key);
            if (($tmp[0] ?? null) && !($tmp[1] ?? null) && !in_array($tmp[0], $headersUsed, true)) {
                unset($wizardItems[$key]);
            }
        }
        return $wizardItems;
    }

    /**
     * Prepare a wizard tab configuration for sorting.
     */
    protected function prepareDependencyOrdering(array $wizardGroup, string $key): array
    {
        if (isset($wizardGroup[$key])) {
            $wizardGroup[$key] = GeneralUtility::trimExplode(',', $wizardGroup[$key]);
            $wizardGroup[$key] = array_map(static fn($s) => $s . '.', $wizardGroup[$key]);
        }
        return $wizardGroup;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
