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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
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
#[AsController]
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
    protected ?int $colPos = null;

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
                $defaultValues = (array)($wizardItem['defaultValues'] ?? []);
                if (!$positionSelection) {
                    // In case no position has to be selected, we can just add the target
                    if (($wizardItem['saveAndClose'] ?? false)) {
                        // Go to DataHandler directly instead of FormEngine
                        $item['url'] = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                            'data' => [
                                'tt_content' => [
                                    StringUtility::getUniqueId('NEW') => array_replace($defaultValues, [
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
                                'tt_content' => array_replace($defaultValues, [
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
                    $item['defaultValues'] = $defaultValues;
                    $item['saveAndClose'] = (bool)($wizardItem['saveAndClose'] ?? false);
                }
                $categories[$key]['items'][] = $item;
            }
        }

        // Unset empty categories
        foreach ($categories as $key => $category) {
            if ($category['items'] === []) {
                unset($categories[$key]);
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
        $pageInfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));

        $posMap = GeneralUtility::makeInstance(ContentCreationPagePositionMap::class);
        $posMap->cur_sys_language = $this->sys_language;
        $posMap->defVals = (array)($request->getParsedBody()['defVals'] ?? []);
        $posMap->saveAndClose = (bool)($request->getParsedBody()['saveAndClose'] ?? false);
        $posMap->R_URI = $this->returnUrl;
        $view = $this->backendViewFactory->create($request);
        $view->assign('posMap', $posMap->printContentElementColumns($this->id, $pageInfo, $request));
        return new HtmlResponse($view->render('NewContentElement/PositionMap'));
    }

    /**
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     */
    protected function getWizards(): array
    {
        $wizards = $this->loadAvailableWizards();
        $newContentElementWizardTsConfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['wizards.']['newContentElement.'] ?? [];
        $wizardsFromPageTSConfig = $this->migrateCommonGroupToDefault($newContentElementWizardTsConfig['wizardItems.'] ?? []);
        $wizardsFromPageTSConfig = $this->migratePositionalCommonGroupToDefault($wizardsFromPageTSConfig);
        $wizards = array_replace_recursive($wizards, $wizardsFromPageTSConfig);
        $wizards = $this->removeWizardsByPageTs($wizards, $newContentElementWizardTsConfig);
        if ($wizards === []) {
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
                    if ($itemConf !== []) {
                        $groupItems[$groupKey . '_' . $itemKey] = $this->prepareWizardItem($itemConf);
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

    protected function loadAvailableWizards(): array
    {
        $typeField = (string)($GLOBALS['TCA']['tt_content']['ctrl']['type'] ?? '');
        $fieldConfig = $GLOBALS['TCA']['tt_content']['columns'][$typeField] ?? [];
        $items = $fieldConfig['config']['items'] ?? [];
        $groupedWizardItems = [];
        foreach ($items as $item) {
            $selectItem = SelectItem::fromTcaItemArray($item);
            if ($selectItem->isDivider()) {
                continue;
            }
            $recordType = $selectItem->getValue();
            $groupIdentifier = $selectItem->getGroup();
            if (!is_array($groupedWizardItems[$groupIdentifier . '.'] ?? null)) {
                $groupedWizardItems[$groupIdentifier . '.'] = [
                    'elements.' => [],
                    'header' => $fieldConfig['config']['itemGroups'][$groupIdentifier] ?? $groupIdentifier,
                ];
            }
            $itemDescription = $selectItem->getDescription();
            $wizardEntry = [
                'iconIdentifier' => $selectItem->getIcon(),
                'title' => $selectItem->getLabel(),
                'description' => $itemDescription['description'] ?? ($itemDescription ?? ''),
                'defaultValues' => [
                    'CType' => $recordType,
                ],
            ];
            $wizardEntry = array_replace_recursive($wizardEntry, $GLOBALS['TCA']['tt_content']['types'][$recordType]['creationOptions'] ?? []);
            $groupedWizardItems[$groupIdentifier . '.']['elements.'][$recordType . '.'] = $wizardEntry;
        }
        return $groupedWizardItems;
    }

    /**
     * This method returns the wizard items, defined in Page TSconfig for b/w
     * compatibility.
     *
     * Additionally, it migrates previously defined wizard items in the
     * `common` group to the new `default` group, which is defined in TCA.
     *
     * @param array<string, array> $wizardsFromPageTs
     * @return array<string, array>
     */
    protected function migrateCommonGroupToDefault(array $wizardsFromPageTs): array
    {
        if (!array_key_exists('common.', $wizardsFromPageTs)) {
            // In case "common." is not defined, just return the wizards, which are still defined via Page TSconfig
            return $wizardsFromPageTs;
        }

        // Prepare "removeItems" to be merged
        if ($wizardsFromPageTs['default.']['elements.']['removeItems'] ?? false) {
            $wizardsFromPageTs['default.']['removeItems'] = GeneralUtility::trimExplode(',', $wizardsFromPageTs['default.']['elements.']['removeItems'] ?? '', true);
        } elseif ($wizardsFromPageTs['default.']['removeItems'] ?? false) {
            $wizardsFromPageTs['default.']['removeItems'] = GeneralUtility::trimExplode(',', $wizardsFromPageTs['default.']['removeItems'], true);
        }

        if ($wizardsFromPageTs['common.']['elements.']['removeItems'] ?? false) {
            $wizardsFromPageTs['common.']['removeItems'] = GeneralUtility::trimExplode(',', $wizardsFromPageTs['common.']['elements.']['removeItems'] ?? '', true);
        } elseif ($wizardsFromPageTs['common.']['removeItems'] ?? false) {
            $wizardsFromPageTs['common.']['removeItems'] = GeneralUtility::trimExplode(',', $wizardsFromPageTs['common.']['removeItems'], true);
        }

        $defaultItems = array_merge_recursive($wizardsFromPageTs['default.'] ?? [], $wizardsFromPageTs['common.']);
        unset($wizardsFromPageTs['common.']);

        if ($defaultItems !== []) {
            $wizardsFromPageTs['default.'] = $defaultItems;
        }

        return $wizardsFromPageTs;
    }

    protected function migratePositionalCommonGroupToDefault(array $wizards): array
    {
        foreach ($wizards as $group => $wizard) {
            if (($wizard['before'] ?? '') === 'common') {
                $wizards[$group]['before'] = 'default';
            }
            if (($wizard['after'] ?? '') === 'common') {
                $wizards[$group]['after'] = 'default';
            }
        }
        return $wizards;
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

    protected function prepareWizardItem(array $itemConf): array
    {
        // Just replace the "known" keys of $itemConf. This way extensions are able to set custom keys, which are not
        // used by the controller, but might be evaluated by listeners of the ModifyNewContentElementWizardItemsEvent.
        $itemConf = array_replace_recursive(
            $itemConf,
            [
                'title' => trim($this->getLanguageService()->sL($itemConf['title'] ?? '')),
                'description' => trim($this->getLanguageService()->sL($itemConf['description'] ?? '')),
                'iconIdentifier' => $itemConf['iconIdentifier'] ?? null,
                'saveAndClose' => (bool)($itemConf['saveAndClose'] ?? false),
                'defaultValues' => array_replace_recursive(
                    $itemConf['tt_content_defValues'] ?? [],
                    $itemConf['tt_content_defValues.'] ?? [],
                    $itemConf['defaultValues'] ?? []
                ),
            ]
        );
        unset($itemConf['tt_content_defValues'], $itemConf['tt_content_defValues.']);
        return $itemConf;
    }

    protected function removeWizardsByPageTs(array $wizards, mixed $wizardsItemsPageTs): array
    {
        $removeWizardItems = $wizardsItemsPageTs['wizardItems.']['removeItems'] ?? [];
        if (is_string($removeWizardItems)) {
            $removeWizardItems = GeneralUtility::trimExplode(',', $removeWizardItems, true);
        }

        foreach ($wizards as $key => &$wizard) {
            // Leave out removeItems etc.
            if (is_string($wizard)) {
                unset($wizards[$key]);
                continue;
            }
            if (in_array(rtrim((string)$key, '.'), $removeWizardItems, true)) {
                unset($wizards[$key]);
                continue;
            }
            $removeWizardElements = $wizardsItemsPageTs['wizardItems.'][$key]['removeItems'] ?? [];
            if (is_string($removeWizardElements)) {
                $removeWizardElements = GeneralUtility::trimExplode(',', $removeWizardElements, true);
            }
            foreach ($wizard['elements.'] ?? [] as $identifier => $element) {
                if (in_array(rtrim((string)$identifier, '.'), $removeWizardElements, true)) {
                    unset($wizard['elements.'][$identifier]);
                }
            }
        }

        return $wizards;
    }

    /**
     * Checks the array for elements which might contain invalid default values and will unset them!
     * Looks for the "defaultValues" key in each element and if found it will traverse that array
     * as fieldname / value pairs and check.
     */
    protected function removeInvalidWizardItems(array $wizardItems): array
    {
        $removeItems = [];
        $keepItems = [];
        // Get TCEFORM from TSconfig of current page
        $TCEFORM_TSconfig = BackendUtility::getTCEFORM_TSconfig('tt_content', ['pid' => $this->id]);
        // Traverse wizard items:
        foreach ($wizardItems as $key => $cfg) {
            if (!is_array($cfg['defaultValues'] ?? false)) {
                continue;
            }
            // If defaultValues are defined, check access by traversing all fields with default values:
            $backendUser = $this->getBackendUser();
            foreach ($cfg['defaultValues'] as $fieldName => $value) {
                if (!is_array($GLOBALS['TCA']['tt_content']['columns'][$fieldName] ?? false)) {
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
                $wizardItems[$key]['defaultValues'][$fieldName] = $this->getLanguageService()->sL($value);
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
            $wizardGroup[$key] = array_map(static fn(string|int $s): string => $s . '.', $wizardGroup[$key]);
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
