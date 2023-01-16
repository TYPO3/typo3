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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TsConfigTreeBuilder;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
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

            $tsConfigTreeBuilder = GeneralUtility::makeInstance(TsConfigTreeBuilder::class);
            $pageTsConfigTree = $tsConfigTreeBuilder->getPagesTsConfigTree($rootLine, new LosslessTokenizer());
            // @todo: This is a bit dusty. It would be better to render the full tree similar to
            //        tstemplate Template Analyzer. For now, we simply create an array with the
            //        to-string'ed content and render this.
            $TSparts = [];
            foreach ($pageTsConfigTree->getNextChild() as $child) {
                $lineStream = $child->getLineStream();
                if ($lineStream instanceof LineStream) {
                    $TSparts[$child->getName()] = (string)$lineStream;
                }
            }

            $lines = [];
            $pUids = [];

            foreach ($TSparts as $key => $value) {
                $title = $key;
                $line = [
                    'title' => $key,
                ];
                if (str_starts_with($key, 'pagesTsConfig-page-')) {
                    $pageId = (int)explode('-', $key)[2];
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
                    $this->pageRenderer->loadJavaScriptModule('@typo3/t3editor/element/code-mirror-element.js');
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

            if ($moduleData->get('tsconf_alphaSort')) {
                $pageTsConfig = ArrayUtility::sortByKeyRecursive($pageTsConfig);
            }

            $this->view->assignMultiple([
                'pageTsConfig' => $pageTsConfig,
            ]);
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
            'mode' => GeneralUtility::jsonEncodeForHtmlAttribute(JavaScriptModuleInstruction::create('@typo3/t3editor/language/typoscript.js', 'typoscript')->invoke(), false),
            'autoheight' => 'true',
            'nolazyload' => 'true',
            'readonly' => 'true',
        ];
        $textareaAttributes = [
            'rows' => (string)count(explode(LF, $content)),
            'class' => 'form-control',
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
}
