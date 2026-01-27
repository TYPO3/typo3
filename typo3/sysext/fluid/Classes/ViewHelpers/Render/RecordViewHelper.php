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
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * ViewHelper to render a record object using its TypoScript definition.
 * The most common use case is to render a content element, which is
 * available as a record object in a Fluid template.
 *
 *  ```html
 *    <f:render.record record="{record}" />
 *  ```
 *
 *  or:
 *
 *  ```html
 *    {record -> f:render.record()}
 *  ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-render-record
 */
final class RecordViewHelper extends AbstractViewHelper
{
    /**
     * @var bool use content as-is
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConfigurationManagerInterface $configurationManager,
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('record', RecordInterface::class, 'The record to be rendered', true);
    }

    public function getContentArgumentName(): string
    {
        return 'record';
    }

    public function render(): string
    {
        $record = $this->renderChildren();
        if (!$record instanceof RecordInterface) {
            throw new \InvalidArgumentException('The "record" argument must be an instance of ' . RecordInterface::class, 1770215699);
        }

        $request = $this->getRequest();
        $result = $this->renderRecord($record, $request);

        $event = $this->eventDispatcher->dispatch(
            new ModifyRenderedRecordEvent(
                renderedRecord: $result,
                record: $record,
                request: $request,
            ),
        );
        return $event->getRenderedRecord();
    }

    private function renderRecord(RecordInterface $record, ServerRequestInterface $request): string
    {
        $table = $record->getMainType();
        $data = $record->getRawRecord()?->toArray(true) ?? $record->toArray();

        $contentObjectRenderer = $this->getContentObjectRenderer($request);
        $contentObjectRenderer->start($data, $table);

        $setup = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        if (!isset($setup[$table])) {
            throw new Exception(
                'No Content Object definition found at TypoScript object path "' . $table . '"',
                1769184455
            );
        }

        $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        if ($timeTracker->LR) {
            $timeTracker->push('/f:render.record/', '<' . $table);
        }
        $timeTracker->incStackPointer();
        $content = $contentObjectRenderer->cObjGetSingle($setup[$table], $setup[$table . '.'] ?? [], $table);
        $timeTracker->decStackPointer();
        if ($timeTracker->LR) {
            $timeTracker->pull($content);
        }
        return $content;
    }

    private function getContentObjectRenderer(ServerRequestInterface $request): ContentObjectRenderer
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $parent = $request->getAttribute('currentContentObject');
        if ($parent instanceof ContentObjectRenderer) {
            $contentObjectRenderer->setParent($parent->data, $parent->currentRecord);
        }
        $contentObjectRenderer->setRequest($request);
        return $contentObjectRenderer;
    }

    private function getRequest(): ServerRequestInterface
    {
        if (!$this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            throw new \RuntimeException('Required request not found in RenderingContext', 1769508877);
        }
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }
}
