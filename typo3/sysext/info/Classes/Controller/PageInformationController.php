<?php
namespace TYPO3\CMS\Info\Controller;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\PseudoSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying page information (records, page record properties) in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class PageInformationController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'pObj' => 'Using PageInformationController::$pObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'function_key' => 'Using PageInformationController::$function_key is deprecated, property will be removed in TYPO3 v10.0.',
        'extClassConf' => 'Using PageInformationController::$extClassConf is deprecated, property will be removed in TYPO3 v10.0.',
        'localLangFile' => 'Using PageInformationController::$localLangFile is deprecated, property will be removed in TYPO3 v10.0.',
        'extObj' => 'Using PageInformationController::$extObj is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'modMenu' => 'Using PageInformationController::modMenu() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObjContent' => 'Using PageInformationController::extObjContent() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    protected $fieldConfiguration = [];

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extObj;

    /**
     * Can be hardcoded to the name of a locallang.xlf file (from the same directory as the class file) to use/load
     * and is included / added to $GLOBALS['LOCAL_LANG']
     *
     * @var string
     */
    protected $localLangFile = '';

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extClassConf;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $function_key = '';

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     */
    public function init($pObj)
    {
        $this->pObj = $pObj;
        // Local lang:
        if (!empty($this->localLangFile)) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
            $this->getLanguageService()->includeLLFile($this->localLangFile);
        }
        $this->id = (int)GeneralUtility::_GP('id');
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * Main, called from parent object
     *
     * @return string Output HTML for the module.
     */
    public function main()
    {
        $languageService = $this->getLanguageService();
        $theOutput = '<h1>' . htmlspecialchars($languageService->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:page_title')) . '</h1>';
        $dblist = GeneralUtility::makeInstance(PageLayoutView::class);
        $dblist->descrTable = '_MOD_web_info';
        $dblist->thumbs = 0;
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $dblist->script = (string)$uriBuilder->buildUriFromRoute('web_info');
        $dblist->showIcon = 0;
        $dblist->setLMargin = 0;
        $dblist->agePrefixes = $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $dblist->pI_showUser = true;

        if (isset($this->fieldConfiguration[$this->pObj->MOD_SETTINGS['pages']])) {
            $dblist->fieldArray = $this->fieldConfiguration[$this->pObj->MOD_SETTINGS['pages']]['fields'];
        }

        // PAGES:
        $this->pObj->MOD_SETTINGS['pages_levels'] = $this->pObj->MOD_SETTINGS['depth'];
        // ONLY for the sake of dblist module which uses this value.
        $h_func = BackendUtility::getDropdownMenu($this->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth']);
        $h_func .= BackendUtility::getDropdownMenu($this->id, 'SET[pages]', $this->pObj->MOD_SETTINGS['pages'], $this->pObj->MOD_MENU['pages']);
        $dblist->start($this->id, 'pages', 0);
        $dblist->generateList();

        $theOutput .= '<div class="form-inline form-inline-spaced">'
            . $h_func
            . '<div class="form-group">'
            . BackendUtility::cshItem($dblist->descrTable, 'func_' . $this->pObj->MOD_SETTINGS['pages'], null, '<span class="btn btn-default btn-sm">|</span>')
            . '</div>'
            . '</div>'
            . $dblist->HTMLcode;

        // Additional footer content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'] ?? [] as $hook) {
            // @todo: request object should be submitted here as soon as it is available in TYPO3 v10.0.
            $params = [];
            $theOutput .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        return $theOutput;
    }

    /**
     * Returns the menu array
     *
     * @return array
     */
    protected function modMenu()
    {
        $menu = [
            'pages' => [],
            'depth' => [
                0 => $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $GLOBALS['LANG']->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi')
            ]
        ];

        // Using $GLOBALS['TYPO3_REQUEST'] since $request is not available at this point
        // @todo: Refactor mess and have $request available
        $this->fillFieldConfiguration($this->id, $GLOBALS['TYPO3_REQUEST']);
        foreach ($this->fieldConfiguration as $key => $item) {
            $menu['pages'][$key] = $item['label'];
        }
        return $menu;
    }

    /**
     * Function, which returns all tables to
     * which the user has access. Also a set of standard tables (pages, sys_filemounts, etc...)
     * are filtered out. So what is left is basically all tables which makes sense to list content from.
     *
     * @return string
     */
    protected function cleanTableNames(): string
    {
        // Get all table names:
        $tableNames = array_flip(array_keys($GLOBALS['TCA']));
        // Unset common names:
        unset($tableNames['pages']);
        unset($tableNames['sys_filemounts']);
        unset($tableNames['sys_action']);
        unset($tableNames['sys_workflows']);
        unset($tableNames['be_users']);
        unset($tableNames['be_groups']);
        $allowedTableNames = [];
        // Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
        if (is_array($tableNames)) {
            foreach ($tableNames as $k => $v) {
                if (!$GLOBALS['TCA'][$k]['ctrl']['hideTable'] && $this->getBackendUser()->check('tables_select', $k)) {
                    $allowedTableNames['table_' . $k] = $k;
                }
            }
        }
        return implode(',', array_keys($allowedTableNames));
    }

    /**
     * Generate configuration for field selection
     *
     * @param int $pageId current page id
     * @param ServerRequestInterface $request
     */
    protected function fillFieldConfiguration(int $pageId, ServerRequestInterface $request)
    {
        $modTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_info.']['fieldDefinitions.'] ?? [];
        foreach ($modTSconfig as $key => $item) {
            $fieldList = str_replace('###ALL_TABLES###', $this->cleanTableNames(), $item['fields']);
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            if ((int)$key === 0) {
                // If "Basic settings" is rendered, hide the alias field on trees that have a site configuration
                // and hide the slug field on PseudoSites. On NullSites (pid 0), show both.
                $site = $request->getAttribute('site');
                if ($site instanceof PseudoSite) {
                    $fields = array_diff($fields, ['slug']);
                } elseif ($site instanceof Site && !$site instanceof NullSite) {
                    $fields = array_diff($fields, ['alias']);
                }
            }
            $key = trim($key, '.');
            $this->fieldConfiguration[$key] = [
                'label' => $item['label'] ? $GLOBALS['LANG']->sL($item['label']) : $key,
                'fields' => $fields
            ];
        }
    }

    /**
     * Called from InfoModuleController until deprecation removal in TYPO3 v10.0
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function checkExtObj()
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            $this->extObj->init($this->pObj, $this->extClassConf);
            // Re-write:
            $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), 'web_info');
        }
    }

    /**
     * Calls the main function inside ANOTHER sub-submodule which might exist.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function extObjContent()
    {
        if (is_object($this->extObj)) {
            return $this->extObj->main();
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
