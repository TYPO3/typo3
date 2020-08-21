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

use TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\SysNote\Controller\NoteController;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;

/**
 * Class RecordListProvider
 * @internal
 */
class RecordListProvider
{
    protected $noteController;

    public function __construct(NoteController $noteController)
    {
        $this->noteController = $noteController;
    }

    public function __invoke(RenderAdditionalContentToRecordListEvent $event): void
    {
        $id = (int)($event->getRequest()->getParsedBody()['id'] ?? $event->getRequest()->getQueryParams()['id'] ?? 0);
        $event->addContentAbove($this->noteController->listAction($id, SysNoteRepository::SYS_NOTE_POSITION_TOP));
        $event->addContentBelow($this->noteController->listAction($id, SysNoteRepository::SYS_NOTE_POSITION_BOTTOM));
    }
}
