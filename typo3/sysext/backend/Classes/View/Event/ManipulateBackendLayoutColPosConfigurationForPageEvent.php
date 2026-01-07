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

namespace TYPO3\CMS\Backend\View\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;

/**
 * Event to change backend layout configuration based on colPos and pageUid of records. This is designed
 * for extensions like ext:container to update allowed & disallowed restrictions if needed.
 *
 * @internal TYPO3 core v14 needs to emit *some* event at this point to enable extensions to hook in. However,
 *           this event is dispatched from within BackendLayoutView which is involved in a quite convoluted
 *           system around current backend layout handling. The backend layout handling should in general see
 *           more refactorings to model things more straight forward, and the method dispatching the event is not
 *           called as systematically as it should be and is declared internal as well. As such, this event is
 *           for now declared as "may change, use at your own risk" since it exposes the ugly internal structures
 *           of the backend layout implementation.
 */
final class ManipulateBackendLayoutColPosConfigurationForPageEvent
{
    public function __construct(
        public array $configuration,
        public readonly BackendLayout $backendLayout,
        public readonly int $colPos,
        public readonly int $pageUid,
        public readonly ?ServerRequestInterface $request = null,
    ) {}
}
