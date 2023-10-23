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

namespace TYPO3\CMS\SysNote\Provider;

use TYPO3\CMS\Backend\Controller\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;
use TYPO3\CMS\SysNote\Renderer\NoteRenderer;

/**
 * Render existing notes within list module.
 *
 * @internal
 */
class RecordListProvider
{
    public function __construct(protected readonly NoteRenderer $noteRenderer) {}

    public function __invoke(RenderAdditionalContentToRecordListEvent $event): void
    {
        $request = $event->getRequest();
        $pid = (int)($event->getRequest()->getParsedBody()['id'] ?? $event->getRequest()->getQueryParams()['id'] ?? 0);
        $returnUrl = $request->getAttribute('normalizedParams')->getRequestUri();
        $event->addContentAbove($this->noteRenderer->renderList($request, $pid, SysNoteRepository::SYS_NOTE_POSITION_TOP, $returnUrl));
        $event->addContentBelow($this->noteRenderer->renderList($request, $pid, SysNoteRepository::SYS_NOTE_POSITION_BOTTOM, $returnUrl));
    }
}
