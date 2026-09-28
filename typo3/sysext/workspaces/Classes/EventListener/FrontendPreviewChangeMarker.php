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

namespace TYPO3\CMS\Workspaces\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterRecordIsRenderedEvent;
use TYPO3\CMS\Workspaces\Middleware\WorkspacePreview;

/**
 * Wraps rendered records in HTML comment markers, so the frontend preview can locate
 * them (see @typo3/workspaces/frontend-change-highlighter.js):
 *
 * - In a workspace preview, records having a version in the current workspace are
 *   marked with their change state.
 * - In the live version requested by the preview for comparison, all records are
 *   marked, so the preview can find the live counterpart of each change.
 *
 * HTML comments are used on purpose: they do not affect CSS selectors or the layout.
 *
 * @internal
 */
final readonly class FrontendPreviewChangeMarker
{
    public function __construct(private Context $context) {}

    #[AsEventListener('typo3-workspaces/frontend-preview-change-marker')]
    public function __invoke(ModifyRenderedRecordEvent|AfterRecordIsRenderedEvent $event): void
    {
        $event->setRenderedRecord($this->mark($event->getRenderedRecord(), $event->getRecord(), $event->getRequest()));
    }

    private function mark(string $content, RecordInterface $record, ServerRequestInterface $request): string
    {
        if (!ApplicationType::fromRequest($request)->isFrontend()) {
            return $content;
        }
        $marker = [
            'table' => $record->getMainType(),
            'uid' => $record->getUid(),
        ];
        if ($request->getAttribute(WorkspacePreview::LIVE_COMPARISON_ATTRIBUTE) !== true) {
            $workspaceId = (int)$this->context->getPropertyFromAspect('workspace', 'id', 0);
            // Read from the raw row, a RawRecord provided by AfterRecordIsRenderedEvent has no version info
            $row = $record->getRawRecord()?->toArray() ?? [];
            if ($workspaceId === 0 || (int)($row['t3ver_wsid'] ?? 0) !== $workspaceId || !$this->isPreviewNotificationEnabled($request)) {
                return $content;
            }
            // Deleted records are not rendered in the preview, so DELETE_PLACEHOLDER cannot occur here
            $marker['state'] = match (VersionState::tryFrom((int)($row['t3ver_state'] ?? 0))) {
                VersionState::NEW_PLACEHOLDER => 'new',
                VersionState::MOVE_POINTER => 'moved',
                default => 'modified',
            };
        }
        return '<!--typo3-workspace-record ' . json_encode($marker, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . '-->'
            . $content
            . '<!--/typo3-workspace-record-->';
    }

    /**
     * The highlighting is part of the preview notification, markers are useless without it
     */
    private function isPreviewNotificationEnabled(ServerRequestInterface $request): bool
    {
        $frontendTypoScript = $request->getAttribute('frontend.typoscript');
        return $frontendTypoScript instanceof FrontendTypoScript
            && (int)($frontendTypoScript->getConfigArray()['disablePreviewNotification'] ?? 0) !== 1;
    }
}
