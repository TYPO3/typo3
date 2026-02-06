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

namespace TYPO3\CMS\Form\ViewHelpers\Be;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Backend\View\PageViewMode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Used by the form editor.
 * Render a content element preview like the page module
 *
 * Scope: backend
 * @internal
 */
#[Autoconfigure(public: true)]
final class RenderContentElementPreviewViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly PageContextFactory $pageContextFactory,
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('contentElementUid', 'int', 'The uid of a content element');
        $this->registerArgument('formPersistenceIdentifier', 'string', 'The form persistence identifier for return URL', false, '');
    }

    public function render(): string
    {
        $content = '';
        $contentElementUid = $this->arguments['contentElementUid'];
        $contentRecord = BackendUtility::getRecord('tt_content', $contentElementUid);
        $request = null;
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        if (!empty($contentRecord) && $request !== null) {
            $backendLayout = GeneralUtility::makeInstance(BackendLayout::class, 'dummy', 'dummy', []);
            $pageId = (int)$contentRecord['pid'];
            $pageContext = $request->getAttribute('pageContext');
            if (!$pageContext instanceof PageContext) {
                try {
                    $pageContext = $this->pageContextFactory->createFromRequest($request, $pageId, $this->getBackendUser());
                } catch (\Exception $e) {
                    return '';
                }
            }

            $manipulatedRequest = $this->getManipulatedRequestToFormEditor($request, $contentRecord);

            $pageLayoutContext = GeneralUtility::makeInstance(
                PageLayoutContext::class,
                $pageContext,
                $backendLayout,
                DrawingConfiguration::create($backendLayout, BackendUtility::getPagesTSconfig($pageId), PageViewMode::LayoutView),
                $manipulatedRequest
            );
            $gridColumn = GeneralUtility::makeInstance(GridColumn::class, $pageLayoutContext, []);
            $contentRecord = GeneralUtility::makeInstance(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tt_content', $contentRecord, null, $pageLayoutContext->getRecordIdentityMap());
            $columnItem = GeneralUtility::makeInstance(GridColumnItem::class, $pageLayoutContext, $gridColumn, $contentRecord);
            return $columnItem->getPreview();
        }
        return $content;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Create a manipulated request with custom NormalizedParams to override the return URL.
     * This allows customizing the return URL used by PageLayoutContext->getReturnUrl()
     * without modifying the original request or the PageLayoutContext logic.
     */
    private function getManipulatedRequestToFormEditor(ServerRequestInterface $request, array $contentRecord): ServerRequestInterface
    {
        $serverParams = $request->getServerParams();
        $serverParams['REQUEST_URI'] = $this->buildCustomReturnUrl($request, $contentRecord);

        $customNormalizedParams = NormalizedParams::createFromServerParams($serverParams);

        return $request->withAttribute('normalizedParams', $customNormalizedParams);
    }

    /**
     * Build the custom return URL for the form editor.
     * Generates the URL to FormEditor->index action with the formPersistenceIdentifier parameter.
     */
    private function buildCustomReturnUrl(ServerRequestInterface $request, array $contentRecord): string
    {
        $formPersistenceIdentifier = $this->arguments['formPersistenceIdentifier'] ?? '';

        if (empty($formPersistenceIdentifier)) {
            return $request->getAttribute('normalizedParams')->getRequestUri();
        }

        $uri = $this->uriBuilder->buildUriFromRoute(
            'web_FormFormbuilder.FormEditor_index',
            ['formPersistenceIdentifier' => $formPersistenceIdentifier]
        );
        return (string)$uri;
    }
}
