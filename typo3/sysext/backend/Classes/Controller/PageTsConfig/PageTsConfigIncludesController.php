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

namespace TYPO3\CMS\Backend\Controller\PageTsConfig;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TsConfigTreeBuilder;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * PageTsConfig > Included PageTsConfig
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[Controller]
class PageTsConfigIncludesController
{
    protected ModuleInterface $currentModule;
    protected ?ModuleTemplate $view;
    public array $pageinfo = [];

    /**
     * Value of the GET/POST var 'id' = the current page ID
     */
    protected int $id;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        if ($this->id === 0) {
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('pagetsconfig_records'));
        }
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->currentModule = $request->getAttribute('module');
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $this->view->setTitle(
            $this->getLanguageService()->sL($this->currentModule->getTitle()),
            $this->id !== 0 && isset($this->pageinfo['title']) ? $this->pageinfo['title'] : ''
        );
        // The page will show only if there is a valid page and if this page may be viewed by the user
        if ($this->pageinfo !== []) {
            $this->view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        $accessContent = false;
        $backendUser = $this->getBackendUser();
        if ($this->pageinfo !== [] || $backendUser->isAdmin()) {
            $accessContent = true;
            if ($backendUser->isAdmin()) {
                // @todo This reset to stripped down dataset with hardcoded values seems to be clumsy. Consider if
                //       this should be extended or solved in another way. See `getButtons()` where a `doktype` check
                //       is done based in this `pageinf` array.
                $this->pageinfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
            }
            $this->view->assign('id', $this->id);
            // Setting up the buttons and the module menu for the doc header
            $this->getButtons();
            $this->view->makeDocHeaderModuleMenu(['id' => $this->id]);
        }
        $this->view->assign('accessContent', $accessContent);

        $moduleData = $request->getAttribute('moduleData');
        if ($moduleData->cleanUp([])) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }

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
        } else {
            $editIcon = '';
        }

        $this->view->assign('lines', $lines);
        $this->view->assign('editIcon', $editIcon);
        $this->view->assign('pageUid', $this->id);
        return $this->view->renderResponse('PageTsConfig/Includes');
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

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();

        if ($this->id) {
            // View
            $pagesTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid']);
            if (isset($pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
                $excludeDokTypes = GeneralUtility::intExplode(
                    ',',
                    (string)$pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'],
                    true
                );
            } else {
                // exclude sysfolders and recycler by default
                $excludeDokTypes = [
                    PageRepository::DOKTYPE_RECYCLER,
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_SPACER,
                ];
            }
            // @todo Recheck if resetting property `pageinfo` for admins in `handleRequest()` should contain more
            //       default values. Guard it here for now with null coalescing operator.
            if (!in_array((int)($this->pageinfo['doktype'] ?? 0), $excludeDokTypes, true)) {
                // View page
                $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageinfo['uid'])
                    ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
                    ->buildDispatcherDataAttributes();
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setShowLabelText(true)
                    ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT);
            }
        }

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($this->currentModule->getIdentifier())
            ->setDisplayName($this->currentModule->getTitle())
            ->setArguments(['id' => $this->id]);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
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
