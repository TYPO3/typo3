<?php
declare(strict_types=1);

namespace TYPO3\CMS\Backend\Controller\Wizard;

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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for the New Content element wizard
 */
class NewContentElementWizardController
{
    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

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
    protected $sysLanguage = 0;

    /**
     * Return URL.
     *
     * @var string
     */
    protected $returnUrl = '';

    /**
     * If set, the content is destined for a specific column.
     *
     * @var int|null
     */
    protected $colPos;

    /**
     * @var int
     */
    protected $uidPid;

    /**
     * Module TSconfig.
     *
     * @var array
     */
    protected $modTsConfig = [];

    /**
     * Used to accumulate the content of the module.
     *
     * @var string
     */
    protected $content;

    /**
     * Access boolean.
     *
     * @var bool
     */
    protected $access;

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
     * @var string
     */
    protected $onClickEvent;

    /**
     * @var array
     */
    protected $moduleConfiguration;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var StandaloneView
     */
    protected $menuItemView;

    /**
     * PSR Request Object
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $GLOBALS['SOBE'] = $this;
        $this->view = $this->getFluidTemplateObject();
        $this->menuItemView = $this->getFluidTemplateObject('MenuItem.html');
        $this->init();
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

    /**
     * Constructor, initializing internal variables.
     */
    protected function init()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:lang/Resources/Private/Language/locallang_misc.xlf');
        $originalLocalLanguage = $GLOBALS['LOCAL_LANG'];
        $lang->includeLLFile('EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf');
        ArrayUtility::mergeRecursiveWithOverrule($originalLocalLanguage, $GLOBALS['LOCAL_LANG']);
        $GLOBALS['LOCAL_LANG'] = $originalLocalLanguage;

        // Setting internal vars:
        $this->id = (int)GeneralUtility::_GP('id');
        $this->sysLanguage = (int)GeneralUtility::_GP('sys_language_uid');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        // original variable needs to be kept as is for position map
        $GLOBALS['SOBE']->R_URI = $this->returnUrl;
        $this->colPos = GeneralUtility::_GP('colPos') === null ? null : (int)GeneralUtility::_GP('colPos');
        $this->uidPid = (int)GeneralUtility::_GP('uid_pid');
        $this->moduleConfiguration['name'] = 'xMOD_db_new_content_el';
        $this->modTsConfig = BackendUtility::getModTSconfig($this->id, 'mod.wizards.newContentElement');
        $configuration = BackendUtility::getPagesTSconfig($this->id);
        $this->configuration = $configuration['mod.']['wizards.']['newContentElement.'];
        // Getting the current page and receiving access information (used in main())
        $permissionsClause = $this->getBackendUser()->getPagePermsClause(1);
        $this->pageInfo = BackendUtility::readPageAccess($this->id, $permissionsClause);
        $this->access = is_array($this->pageInfo);
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->main();
        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Creating the module output.
     *
     * @throws \UnexpectedValueException
     */
    protected function main()
    {
        $hasAccess = true;
        if ($this->id && $this->access) {

            // Init position map object:
            $positionMap = GeneralUtility::makeInstance(ContentCreationPagePositionMap::class);
            $positionMap->cur_sys_language = $this->sysLanguage;
            // If a column is pre-set:
            if (isset($this->colPos)) {
                if ($this->uidPid < 0) {
                    $row = [];
                    $row['uid'] = abs($this->uidPid);
                } else {
                    $row = '';
                }
                $this->onClickEvent = $positionMap->onClickInsertRecord(
                    $row,
                    $this->colPos,
                    '',
                    $this->uidPid,
                    $this->sysLanguage
                );
            } else {
                $this->onClickEvent = '';
            }
            // ***************************
            // Creating content
            // ***************************
            // Wizard
            $wizardItems = $this->getWizardItems();
            // Wrapper for wizards
            // Hook for manipulating wizardItems, wrapper, onClickEvent etc.
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof NewContentElementWizardHookInterface) {
                    throw new \UnexpectedValueException(
                        $className . ' must implement interface ' . NewContentElementWizardHookInterface::class,
                        1496496724
                    );
                }
                $hookObject->manipulateWizardItems($wizardItems, $this);
            }

            // Traverse items for the wizard.
            // An item is either a header or an item rendered with a radio button and title/description and icon:
            $counter = ($key = 0);
            $menuItems = [];

            $this->view->assign('onClickEvent', $this->onClickEvent);

            foreach ($wizardItems as $wizardKey => $wizardInformation) {
                $wizardOnClick = '';
                if ($wizardInformation['header']) {
                    $menuItems[] = [
                        'label' => $wizardInformation['header'],
                        'content' => ''
                    ];
                    $key = count($menuItems) - 1;
                } else {
                    if (!$this->onClickEvent) {
                        // Radio button:
                        $wizardOnClick = 'document.editForm.defValues.value=unescape(' . GeneralUtility::quoteJSvalue(rawurlencode($wizardInformation['params'])) . '); window.location.hash=\'#sel2\';';
                        // Onclick action for icon/title:
                        $actionOnClick = 'document.getElementsByName(\'tempB\')[' . $counter . '].checked=1;' . $wizardOnClick . 'return false;';
                    } else {
                        $actionOnClick = 'document.editForm.defValues.value=unescape("' . rawurlencode($wizardInformation['params']) . '");goToalt_doc();' . (!$this->onClickEvent ? 'window.location.hash=\'#sel2\';' : '');
                    }

                    $icon = $this->moduleTemplate->getIconFactory()->getIcon($wizardInformation['iconIdentifier'])->render();

                    $this->menuItemView->assignMultiple(
                        [
                            'onClickEvent' => $this->onClickEvent,
                            'aOnClick' => $actionOnClick,
                            'wizardInformation' => $wizardInformation,
                            'icon' => $icon,
                            'wizardOnClick' => $wizardOnClick,
                            'wizardKey' => $wizardKey
                        ]
                    );
                    $menuItems[$key]['content'] .= $this->menuItemView->render();
                    $counter++;
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
                $this->view->assign(
                    'posMap',
                    $positionMap->printContentElementColumns($this->id, 0, $colPosList, 1, $this->returnUrl)
                );
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
     * Returns the array of elements in the wizard display.
     * For the plugin section there is support for adding elements there from a global variable.
     *
     * @return array
     */
    protected function getWizardItems(): array
    {
        $wizardItems = [];
        if (isset($this->configuration['wizardItems.'])) {
            $wizards = $this->configuration['wizardItems.'];
            if (empty($wizards['elements.'])) {
                $wizards['elements.'] = [];
            }
            $appendWizards = $this->appendWizards($wizards['elements.']);
            if (is_array($wizards)) {
                foreach ($wizards as $groupKey => $wizardGroup) {
                    if (is_array($wizards[$groupKey])) {
                        $this->prepareDependencyOrdering($wizards[$groupKey], 'before');
                        $this->prepareDependencyOrdering($wizards[$groupKey], 'after');
                    }
                }
                $wizards = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($wizards);

                foreach ($wizards as $groupKey => $wizardGroup) {
                    if (is_array($wizardGroup)) {
                        $groupKey = rtrim($groupKey, '.');
                        $showItems = GeneralUtility::trimExplode(',', $wizardGroup['show'], true);
                        $showAll = in_array('*', $showItems, true);
                        $groupItems = [];
                        if (is_array($appendWizards[$groupKey . '.']['elements.'])) {
                            $wizardElements = array_merge(
                                (array)$wizardGroup['elements.'],
                                $appendWizards[$groupKey . '.']['elements.']
                            );
                        } else {
                            $wizardElements = $wizardGroup['elements.'];
                        }
                        if (is_array($wizardElements)) {
                            foreach ($wizardElements as $itemKey => $itemConfiguration) {
                                $itemKey = rtrim($itemKey, '.');
                                if (($showAll || in_array($itemKey, $showItems) && is_array($itemConfiguration))) {
                                    $item = $this->getItem($itemConfiguration);
                                    if ($item) {
                                        $groupItems[$groupKey . '_' . $itemKey] = $item;
                                    }
                                }
                            }
                        }
                        if (!empty($groupItems)) {
                            $wizardItems[$groupKey] = $this->getGroupHeader($wizardGroup);
                            $wizardItems = array_merge($wizardItems, $groupItems);
                        }
                    }
                }
            }
        }
        // Remove elements where preset values are not allowed:
        $this->removeInvalidElements($wizardItems);
        return $wizardItems;
    }

    /**
     * @param array $wizardElements
     * @return array $returnElements
     */
    protected function appendWizards(array $wizardElements): array
    {
        if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'])) {
            foreach ($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] as $class => $path) {
                if (!class_exists($class) && file_exists($path)) {
                    require_once $path;
                }
                $moduleObject = GeneralUtility::makeInstance($class);
                if (method_exists($moduleObject, 'proc')) {
                    $wizardElements = $moduleObject->proc($wizardElements);
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
     * Prepare a wizard tab configuration for sorting.
     *
     * @param array $wizardGroup TypoScript wizard tab configuration
     * @param string $key Which array key should be prepared
     */
    protected function prepareDependencyOrdering(array &$wizardGroup, string $key)
    {
        if (isset($wizardGroup[$key])) {
            $wizardGroup[$key] = GeneralUtility::trimExplode(',', $wizardGroup[$key]);
            $wizardGroup[$key] = array_map(function ($s) {
                return $s . '.';
            }, $wizardGroup[$key]);
        }
    }

    /**
     * @param array $itemConfiguration
     * @return array $itemConfiguration
     */
    protected function getItem(array $itemConfiguration): array
    {
        $itemConfiguration['title'] = $this->getLanguageService()->sL($itemConfiguration['title']);
        $itemConfiguration['description'] = $this->getLanguageService()->sL($itemConfiguration['description']);
        $itemConfiguration['tt_content_defValues'] = $itemConfiguration['tt_content_defValues.'];
        unset($itemConfiguration['tt_content_defValues.']);
        return $itemConfiguration;
    }

    /**
     * @param array $wizardGroup
     * @return array
     */
    protected function getGroupHeader(array $wizardGroup): array
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
    protected function removeInvalidElements(&$wizardItems)
    {
        // Get TCEFORM from TSconfig of current page
        $row = ['pid' => $this->id];
        $tceFormTsConfig = BackendUtility::getTCEFORM_TSconfig('tt_content', $row);
        $headersUsed = [];
        // Traverse wizard items:
        foreach ($wizardItems as $key => $configuration) {
            // Exploding parameter string, if any (old style)
            if ($wizardItems[$key]['params']) {
                // Explode GET vars recursively
                $temporaryGetVariables = GeneralUtility::explodeUrl2Array($wizardItems[$key]['params'], true);
                // If tt_content values are set, merge them into the tt_content_defValues array,
                // unset them from $temporaryGetVariables and re-implode $temporaryGetVariables into the param string
                // (in case remaining parameters are around).
                if (is_array($temporaryGetVariables['defVals']['tt_content'])) {
                    $wizardItems[$key]['tt_content_defValues'] = array_merge(
                        is_array($wizardItems[$key]['tt_content_defValues']) ? $wizardItems[$key]['tt_content_defValues'] : [],
                        $temporaryGetVariables['defVals']['tt_content']
                    );
                    unset($temporaryGetVariables['defVals']['tt_content']);
                    $wizardItems[$key]['params'] = GeneralUtility::implodeArrayForUrl('', $temporaryGetVariables);
                }
            }
            // If tt_content_defValues are defined...:
            if (is_array($wizardItems[$key]['tt_content_defValues'])) {
                $backendUser = $this->getBackendUser();
                // Traverse field values:
                foreach ($wizardItems[$key]['tt_content_defValues'] as $fieldName => $fieldValue) {
                    if (is_array($GLOBALS['TCA']['tt_content']['columns'][$fieldName])) {
                        // Get information about if the field value is OK:
                        $configuration = &$GLOBALS['TCA']['tt_content']['columns'][$fieldName]['config'];
                        $authenticationModeDeny = $configuration['type'] === 'select' && $configuration['authMode']
                            && !$backendUser->checkAuthMode(
                                'tt_content',
                                $fieldName,
                                $fieldValue,
                                $configuration['authMode']
                            );
                        // explode TSconfig keys only as needed
                        if (!isset($removeItems[$fieldName])) {
                            $removeItems[$fieldName] = GeneralUtility::trimExplode(
                                ',',
                                $tceFormTsConfig[$fieldName]['removeItems'],
                                true
                            );
                        }
                        if (!isset($keepItems[$fieldName])) {
                            $keepItems[$fieldName] = GeneralUtility::trimExplode(
                                ',',
                                $tceFormTsConfig[$fieldName]['keepItems'],
                                true
                            );
                        }
                        $isNotInKeepItems = !empty($keepItems[$fieldName]) && !in_array(
                            $fieldValue,
                                $keepItems[$fieldName]
                        );
                        if ($authenticationModeDeny || $fieldName === 'CType' && (in_array(
                            $fieldValue,
                                    $removeItems[$fieldName]
                        ) || $isNotInKeepItems)
                        ) {
                            // Remove element all together:
                            unset($wizardItems[$key]);
                            break;
                        }
                        // Add the parameter:
                        $wizardItems[$key]['params'] .= '&defVals[tt_content][' . $fieldName . ']=' . rawurlencode($this->getLanguageService()->sL($fieldValue));
                        $headerKey = explode('_', $key);
                        $headersUsed[$headerKey[0]] = $headerKey[0];
                    }
                }
            }
        }
        // remove headers without elements
        foreach ($wizardItems as $key => $configuration) {
            $headerKey = explode('_', $key);
            if ($headerKey[0] && !$headerKey[1] && !in_array($headerKey[0], $headersUsed)) {
                unset($wizardItems[$key]);
            }
        }
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->getLanguageService()->getLL('goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-view-go-back',
                    Icon::SIZE_SMALL
                ));
            $buttonBar->addButton($backButton);
        }
        $contextSensitiveHelpButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('new_ce');
        $buttonBar->addButton($contextSensitiveHelpButton);
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
