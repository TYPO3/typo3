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

namespace TYPO3\CMS\Frontend\Event;

use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;

/**
 * This event is dispatched after the FrontendTypoScript object has been calculated,
 * just before it is attached to the request.
 *
 * The event is designed to enable listeners to act on specific TypoScript conditions.
 * Listeners *must not* modify TypoScript at this point, the core will try to actively
 * prevent this.
 *
 * This event is especially useful when "upper" middlewares that do not have the
 * determined TypoScript need to behave differently depending on TypoScript 'config' that
 * is only created after them.
 * The core uses this in the TimeTrackInitialization and the WorkspacePreview middlewares,
 * to determine debugging and preview details.
 *
 * Note both 'settings' ("constants") and 'config' are *always* set within the
 * FrontendTypoScript at this point, even in 'fully cached page' scenarios. 'setup'
 * and (@internal) 'page' may not be set.
 */
final readonly class AfterTypoScriptDeterminedEvent
{
    public function __construct(
        private FrontendTypoScript $frontendTypoScript,
    ) {}

    public function getFrontendTypoScript(): FrontendTypoScript
    {
        return $this->frontendTypoScript;
    }
}
