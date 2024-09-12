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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\Tree\View\ContentMovingPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The "move tt_content element" wizard. Reachable via list module "Re-position content element" on tt_content records.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class MoveElementController
{
    use PageRendererBackendSetupTrait;

    public function __construct(
        private PageRenderer $pageRenderer,
        private BackendViewFactory $backendViewFactory,
        private LanguageServiceFactory $languageServiceFactory,
        private ExtensionConfiguration $extensionConfiguration
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->setUpBasicPageRendererForBackend(
            $this->pageRenderer,
            $this->extensionConfiguration,
            $request,
            $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser())
        );
        $view = $this->backendViewFactory->create($request);
        $queryParams = $request->getQueryParams();
        $contentOnly = $queryParams['contentOnly'] ?? false;
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/page-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/viewport/resizable-navigation.js');
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/wizard/move-content-element.js', 'MoveContentElement')->instance()
        );
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/Wizards/move_content_elements.xlf');

        $view->assignMultiple(array_merge($this->getContentVariables($request), [
            'contentOnly' => $contentOnly,
        ]));

        $content = $view->render('ContentElement/MoveElement');
        if ($contentOnly) {
            return new HtmlResponse($content);
        }
        $this->pageRenderer->setBodyContent('<body>' . $content);
        return new HtmlResponse($this->pageRenderer->render());
    }

    private function getContentVariables(ServerRequestInterface $request): array
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $contentElementUid = (int)($parsedBody['uid'] ?? $queryParams['uid'] ?? 0);
        $pageId = (int)($parsedBody['expandPage'] ?? $queryParams['expandPage'] ?? 0);
        $sysLanguage = (int)($parsedBody['sys_language'] ?? $queryParams['sys_language'] ?? 0);
        $makeCopy = (bool)($parsedBody['makeCopy'] ?? $queryParams['makeCopy'] ?? 0);
        $permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        if (!$contentElementUid) {
            return [];
        }

        $contentElement = BackendUtility::getRecordWSOL('tt_content', $contentElementUid);
        $pageInfo = BackendUtility::readPageAccess($pageId, $permsClause);

        $assigns = [
            'record' => $contentElement,
            'makeCopyChecked' => $makeCopy,
            'pageInfo' => $pageInfo,
            'recordTitle' => BackendUtility::getRecordTitle('tt_content', $contentElement, true),
        ];
        if (is_array($pageInfo) && $this->getBackendUser()->isInWebMount($pageInfo['uid'], $permsClause)) {
            // Initialize the content position map:
            $contentPositionMap = GeneralUtility::makeInstance(ContentMovingPagePositionMap::class);
            $contentPositionMap->copyMode = $makeCopy ? 'copy' : 'move';
            $contentPositionMap->moveUid = $contentElementUid;
            $contentPositionMap->cur_sys_language = $sysLanguage;

            $assigns['pageRecord']['recordTooltip'] = BackendUtility::getRecordIconAltText($pageInfo, 'pages', false);
            $assigns['pageRecord']['recordTitle'] = BackendUtility::getRecordTitle('pages', $pageInfo, true);
            $assigns['contentElementColumns'] = $contentPositionMap->printContentElementColumns($pageId, $pageInfo, $request);
        }
        return $assigns;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
