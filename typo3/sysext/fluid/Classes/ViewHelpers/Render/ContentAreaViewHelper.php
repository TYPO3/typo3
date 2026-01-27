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

namespace TYPO3\CMS\Fluid\ViewHelpers\Render;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\ContentArea;
use TYPO3\CMS\Fluid\Event\ModifyRenderedContentAreaEvent;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to render a content area as provided by the page-content processor.
 * The most common use case is to render all content elements within column from a
 * backend layout.
 *
 *  ```typoscript
 *    page = PAGE
 *    page.10 = PAGEVIEW
 *    page.10.paths.10 = EXT:my_site_package/Resources/Private/Templates/
 *  ```
 *
 *  ```html
 *    <f:render.contentArea contentArea="{content.main}" />
 *  ```
 *
 * or:
 *
 *  ```html
 *    {content.main -> f:render.contentArea()}
 *  ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-render-contentarea
 */
final class ContentAreaViewHelper extends AbstractViewHelper
{
    /**
     * @var bool use content as-is
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('contentArea', ContentArea::class, 'A content area from the page-content processor', true);
    }

    public function getContentArgumentName(): string
    {
        return 'contentArea';
    }

    public function render(): string
    {
        $contentArea = $this->renderChildren();
        if (!$contentArea instanceof ContentArea) {
            throw new \InvalidArgumentException('The "contentArea" argument must be an instance of ' . ContentArea::class, 1770212183);
        }

        $result = '';
        foreach ($contentArea->getRecords() as $record) {
            $result .= $this->renderingContext->getViewHelperInvoker()->invoke(
                RecordViewHelper::class,
                [
                    'record' => $record,
                ],
                $this->renderingContext,
            );
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyRenderedContentAreaEvent(
                renderedContentArea: $result,
                contentArea: $contentArea,
                request: $this->getRequest(),
            ),
        );
        return $event->getRenderedContentArea();
    }

    private function getRequest(): ServerRequestInterface
    {
        if (!$this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            throw new \RuntimeException('Required request not found in RenderingContext', 1769183896);
        }
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }
}
