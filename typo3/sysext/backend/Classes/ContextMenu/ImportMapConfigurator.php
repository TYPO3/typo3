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

namespace TYPO3\CMS\Backend\ContextMenu;

use TYPO3\CMS\Core\Page\Event\ResolveJavaScriptImportEvent;

class ImportMapConfigurator
{
    public function __invoke(ResolveJavaScriptImportEvent $event): void
    {
        if ($event->specifier === '@typo3/backend/context-menu.js') {
            $event->importMap->includeTaggedImports('backend.contextmenu');
        }
    }
}
