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

use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;
use TYPO3\CMS\SysNote\Renderer\NoteRenderer;

/**
 * Event listener to render notes in the page module.
 *
 * @internal This is a specific listener implementation and is not considered part of the Public TYPO3 API.
 */
final class PageModuleProvider
{
    public function __construct(protected readonly NoteRenderer $noteRenderer) {}

    /**
     * Add sys_notes as additional content to the header and footer of the page module
     */
    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $request = $event->getRequest();
        $id = (int)($request->getQueryParams()['id'] ?? 0);
        $returnUrl = $request->getAttribute('normalizedParams')->getRequestUri();
        $event->addHeaderContent($this->noteRenderer->renderList($request, $id, SysNoteRepository::SYS_NOTE_POSITION_TOP, $returnUrl));
        $event->addFooterContent($this->noteRenderer->renderList($request, $id, SysNoteRepository::SYS_NOTE_POSITION_BOTTOM, $returnUrl));
    }
}
