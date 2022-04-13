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

namespace TYPO3\CMS\Info\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Page TSconfig viewer in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class InfoPageTyposcriptConfigController extends InfoModuleController
{
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->getLanguageService()->includeLLFile('EXT:info/Resources/Private/Language/InfoPageTsConfig.xlf');
        $this->init($request);

        $backendUser = $this->getBackendUser();
        $moduleData = $request->getAttribute('moduleData');
        $allowedModuleOptions = $this->getAllowedModuleOptions();
        if ($moduleData->cleanUp($allowedModuleOptions)) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }

        if ($this->id === 0) {
            $this->view->assign('pageZero', true);
            $pagesUsingTSConfig = $this->getOverviewOfPagesUsingTSConfig();
            if (count($pagesUsingTSConfig) > 0) {
                $this->view->assign('overviewOfPagesUsingTSConfig', $pagesUsingTSConfig);
            }
            return $this->view->renderResponse('PageTsConfig');
        }

        if ((int)$moduleData->get('tsconf_parts') === 99) {
            $rootLine = BackendUtility::BEgetRootLine($this->id, '', true);
            /** @var array<string, string> $TSparts */
            $TSparts = GeneralUtility::makeInstance(PageTsConfigLoader::class)->collect($rootLine);
            $lines = [];
            $pUids = [];

            foreach ($TSparts as $key => $value) {
                $line = [];
                if ($key === 'global') {
                    $title = $this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/InfoPageTsConfig.xlf:editTSconfig_global');
                    $line['title'] = $title;
                } elseif ($key === 'default') {
                    $title = $this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/InfoPageTsConfig.xlf:editTSconfig_default');
                    $line['title'] = $title;
                } else {
                    // Remove the "page_" prefix
                    [, $pageId] = explode('_', $key, 3);
                    $pageId = (int)$pageId;
                    $pUids[] = $pageId;
                    $row = BackendUtility::getRecordWSOL('pages', $pageId);
                    $icon = $this->iconFactory->getIconForRecord('pages', $row, Icon::SIZE_SMALL);
                    $urlParameters = [
                        'edit' => [
                            'pages' => [
                                $pageId => 'edit',
                            ],
                        ],
                        'columnsOnly' => 'TSconfig,tsconfig_includes',
                        'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                    ];
                    $line['editIcon'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    $line['editTitle'] = 'editTSconfig';
                    $title = BackendUtility::getRecordTitle('pages', $row);
                    $line['title'] = BackendUtility::wrapClickMenuOnIcon((string)$icon, 'pages', $row['uid']) . ' ' . htmlspecialchars($title);
                }

                if (ExtensionManagementUtility::isLoaded('t3editor')) {
                    // @todo: Let EXT:t3editor add the deps via events in the render-loops above
                    $line['content'] = $this->getCodeMirrorHtml($title, trim($value));
                    $this->pageRenderer->addCssFile('EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/lib/codemirror.css');
                    $this->pageRenderer->addCssFile('EXT:t3editor/Resources/Public/Css/t3editor.css');
                    $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/T3editor/Element/CodeMirrorElement');
                } else {
                    $line['content'] = $this->getTextareaMarkup(trim($value));
                }

                $lines[] = $line;
            }

            if (!empty($pUids)) {
                $urlParameters = [
                    'edit' => [
                        'pages' => [
                            implode(',', $pUids) => 'edit',
                        ],
                    ],
                    'columnsOnly' => 'TSconfig,tsconfig_includes',
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $editIcon = htmlspecialchars($url);
                $editTitle = 'editTSconfig_all';
            } else {
                $editIcon = '';
                $editTitle = '';
            }

            $this->view->assign('tsconfParts99', true);
            $this->view->assign('lines', $lines);
            $this->view->assign('editIcon', $editIcon);
            $this->view->assign('editTitle', $editTitle);
        } else {
            $this->view->assign('tsconfParts99', false);
            $pageTsConfig = BackendUtility::getPagesTSconfig($this->id);
            switch ((string)$moduleData->get('tsconf_parts')) {
                case '1':
                    $pageTsConfig = $pageTsConfig['mod.'] ?? [];
                    break;
                case '1a':
                    $pageTsConfig = $pageTsConfig['mod.']['web_layout.'] ?? [];
                    break;
                case '1b':
                    $pageTsConfig = $pageTsConfig['mod.']['web_view.'] ?? [];
                    break;
                case '1d':
                    $pageTsConfig = $pageTsConfig['mod.']['web_list.'] ?? [];
                    break;
                case '1e':
                    $pageTsConfig = $pageTsConfig['mod.']['web_info.'] ?? [];
                    break;
                case '1g':
                    $pageTsConfig = $pageTsConfig['mod.']['web_ts.'] ?? [];
                    break;
                case '2':
                    $pageTsConfig = $pageTsConfig['RTE.'] ?? [];
                    break;
                case '5':
                    $pageTsConfig = $pageTsConfig['TCEFORM.'] ?? [];
                    break;
                case '6':
                    $pageTsConfig = $pageTsConfig['TCEMAIN.'] ?? [];
                    break;
                case '7':
                    $pageTsConfig = $pageTsConfig['TCAdefaults.'] ?? [];
                    break;
                case '4':
                    $pageTsConfig = $pageTsConfig['user.'] ?? [];
                    break;
                default:
                // Entire array
                }
            $this->view->assign('tree', $this->renderTree($pageTsConfig, '', '', (bool)$moduleData->get('tsconf_alphaSort')));
        }
        $this->view->assign('alphaSort', BackendUtility::getFuncCheck($this->id, 'tsconf_alphaSort', (bool)$moduleData->get('tsconf_alphaSort'), '', '', 'id="checkTsconf_alphaSort"'));
        $this->view->assign('dropdownMenu', BackendUtility::getDropdownMenu($this->id, 'tsconf_parts', $moduleData->get('tsconf_parts'), $allowedModuleOptions['tsconf_parts'], '', '', ['id' => 'tsconf_parts']));
        return $this->view->renderResponse('PageTsConfig');
    }

    protected function getAllowedModuleOptions(): array
    {
        $lang = $this->getLanguageService();
        $allowedModuleOptions = [
            'tsconf_parts' => [
                0 => $lang->getLL('tsconf_parts_0'),
                1 => $lang->getLL('tsconf_parts_1') . ' [mod.]',
                '1a' => $lang->getLL('tsconf_parts_1a') . ' [mod.web_layout.]',
                '1b' => $lang->getLL('tsconf_parts_1b') . ' [mod.web_view.]',
                '1d' => $lang->getLL('tsconf_parts_1d') . ' [mod.web_list.]',
                '1e' => $lang->getLL('tsconf_parts_1e') . ' [mod.web_info.]',
                '1g' => $lang->getLL('tsconf_parts_1g') . ' [mod.web_ts.]',
                2 => '[RTE.]',
                7 => '[TCAdefaults.]',
                5 => '[TCEFORM.]',
                6 => '[TCEMAIN.]',
                4 => '[user.]',
                99 => $lang->getLL('tsconf_configFields'),
            ],
        ];
        if (!$this->getBackendUser()->isAdmin()) {
            unset($allowedModuleOptions['tsconf_parts'][99]);
        }
        return $allowedModuleOptions;
    }

    /**
     * Renders table rows of all pages containing TSConfig together with its rootline
     *
     * @return array
     */
    protected function getOverviewOfPagesUsingTSConfig(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));

        $res = $queryBuilder
            ->select('uid', 'TSconfig')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->neq(
                    'TSconfig',
                    $queryBuilder->createNamedParameter('')
                )
            )
            ->executeQuery();

        $pageArray = [];

        while ($row = $res->fetchAssociative()) {
            $this->setInPageArray($pageArray, BackendUtility::BEgetRootLine($row['uid'], 'AND 1=1'), $row);
        }
        return $this->getList($pageArray);
    }

    /**
     * Set page in array
     * This function is called recursively and builds a multi-dimensional array that reflects the page
     * hierarchy.
     *
     * @param array $hierarchicArray The hierarchic array (passed by reference)
     * @param array $rootlineArray The rootline array
     * @param array $row The row from the database containing the uid and TSConfig fields
     */
    protected function setInPageArray(array &$hierarchicArray, array $rootlineArray, array $row): void
    {
        ksort($rootlineArray);
        reset($rootlineArray);
        if (!($rootlineArray[0]['uid'] ?? false)) {
            array_shift($rootlineArray);
        }
        $currentElement = current($rootlineArray);
        $hierarchicArray[$currentElement['uid']] = htmlspecialchars($currentElement['title']);
        array_shift($rootlineArray);
        if (!empty($rootlineArray)) {
            if (!is_array($hierarchicArray[$currentElement['uid'] . '.'] ?? null)) {
                $hierarchicArray[$currentElement['uid'] . '.'] = [];
            }
            $this->setInPageArray($hierarchicArray[$currentElement['uid'] . '.'], $rootlineArray, $row);
        } else {
            $hierarchicArray[$currentElement['uid'] . '_'] = $this->extractLinesFromTSConfig($row);
        }
    }

    /**
     * Extract the lines of TSConfig from a given pages row
     *
     * @param array $row The row from the database containing the uid and TSConfig fields
     * @return array
     */
    protected function extractLinesFromTSConfig(array $row): array
    {
        $out = [];
        $includeLines = 0;
        $out['uid'] = $row['uid'];
        $lines = GeneralUtility::trimExplode("\r\n", $row['TSconfig']);
        foreach ($lines as $line) {
            if (str_contains($line, '<INCLUDE_TYPOSCRIPT:')) {
                $includeLines++;
            }
        }
        $out['includeLines'] = $includeLines;
        $out['writtenLines'] = (count($lines) - $includeLines);
        return $out;
    }

    /**
     * Get the list of pages to show.
     * This function is called recursively
     *
     * @param array $pageArray The Page Array
     * @param array $lines Lines that have been processed up to this point
     * @param int $pageDepth The level of the current $pageArray being processed
     * @return array
     */
    protected function getList(array $pageArray, array $lines = [], int $pageDepth = 0): array
    {
        if ($pageArray === []) {
            return $lines;
        }

        foreach ($pageArray as $identifier => $title) {
            if (!MathUtility::canBeInterpretedAsInteger($identifier)) {
                continue;
            }
            $line = [];
            $line['padding'] = ($pageDepth * 20) + 10;
            if (isset($pageArray[$identifier . '_'])) {
                $line['link'] = $this->uriBuilder->buildUriFromRoute($this->currentModule->getIdentifier(), ['id' => $identifier]);
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render();
                $line['title'] = 'ID: ' . $identifier;
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($title, 30);
                $line['includedFiles'] = ($pageArray[$identifier . '_']['includeLines'] === 0 ? '' : $pageArray[$identifier . '_']['includeLines']);
                $line['lines'] = ($pageArray[$identifier . '_']['writtenLines'] === 0 ? '' : $pageArray[$identifier . '_']['writtenLines']);
            } else {
                $line['link'] = '';
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render();
                $line['title'] = '';
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($title, 30);
                $line['includedFiles'] = '';
                $line['lines'] = '';
            }
            $lines[] = $line;
            $lines = $this->getList($pageArray[$identifier . '.'] ?? [], $lines, $pageDepth + 1);
        }
        return $lines;
    }

    protected function getCodeMirrorHtml(string $label, string $content): string
    {
        $codeMirrorConfig = [
            'label' => $label,
            'panel' => 'top',
            'mode' => 'TYPO3/CMS/T3editor/Mode/typoscript/typoscript',
            'autoheight' => 'true',
            'nolazyload' => 'true',
            'options' => GeneralUtility::jsonEncodeForHtmlAttribute([
                'readOnly' => true,
                'format' => 'typoscript',
                'rows' => 'auto',
            ], false),
        ];
        $textareaAttributes = [
            'rows' => (string)count(explode(LF, $content)),
            'readonly' => 'readonly',
        ];

        $code = '<typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>';
        $code .= '<textarea ' . GeneralUtility::implodeAttributes($textareaAttributes, true) . '>' . htmlspecialchars($content) . '</textarea>';
        $code .= '</typo3-t3editor-codemirror>';

        return $code;
    }

    protected function getTextareaMarkup(string $content): string
    {
        return '<textarea class="form-control" rows="' . count(explode(LF, $content)) . '" disabled>'
            . htmlspecialchars($content)
            . '</textarea>';
    }

    /**
     * Render object tree
     */
    private function renderTree($arr, $depth_in, $depthData, bool $alphaSort = false): string
    {
        $HTML = '';
        if ($alphaSort) {
            ksort($arr);
        }
        $keyArr_num = [];
        $keyArr_alpha = [];
        foreach ($arr as $key => $value) {
            // Don't do anything with comments / linenumber registrations...
            if (substr((string)$key, -2) !== '..') {
                $key = preg_replace('/\\.$/', '', (string)$key) ?? '';
                if (substr($key, -1) !== '.') {
                    if (MathUtility::canBeInterpretedAsInteger($key)) {
                        $keyArr_num[$key] = $arr[$key] ?? '';
                    } else {
                        $keyArr_alpha[$key] = $arr[$key] ?? '';
                    }
                }
            }
        }
        ksort($keyArr_num);
        $keyArr = $keyArr_num + $keyArr_alpha;
        if ($depth_in) {
            $depth_in = $depth_in . '.';
        }
        foreach ($keyArr as $key => $value) {
            $depth = $depth_in . $key;
            // This excludes all constants starting with '_' from being shown.
            if ($depth[0] !== '_') {
                $deeper = is_array($arr[$key . '.'] ?? null);
                $HTML .= $depthData . '<li><span class="list-tree-group">';
                $label = $key;
                $HTML .= '<span class="list-tree-label" title="' . htmlspecialchars($depth_in . $key) . '">[' . $label . ']</span>';
                if (isset($arr[$key])) {
                    $theValue = $arr[$key];
                    $HTML .= ' = <span class="list-tree-value">' . htmlspecialchars($theValue) . '</span>';
                }
                $HTML .= '</span>';
                if ($deeper) {
                    $HTML .= $this->renderTree($arr[$key . '.'] ?? [], $depth, $depthData, $alphaSort);
                }
            }
        }
        if ($HTML !== '') {
            $HTML = '<ul class="list-tree text-monospace">' . $HTML . '</ul>';
        }

        return $HTML;
    }
}
