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

namespace TYPO3\CMS\Reactions\Reaction;

/**
 * What keeps the endpoint from creating any record for a reaction, whatever the payload.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
enum DryRunObstacle: string
{
    /**
     * The reaction is disabled, and the endpoint does not find it.
     */
    case DISABLED = 'disabled';

    /**
     * The start date of the reaction lies ahead, and the endpoint does not find it yet.
     */
    case SCHEDULED = 'scheduled';

    /**
     * The end date of the reaction has passed, and the endpoint no longer finds it.
     */
    case EXPIRED = 'expired';

    /**
     * The reaction impersonates no user, or one that is disabled or deleted.
     */
    case NO_USER = 'noUser';

    /**
     * The impersonated user may not modify records of the target table.
     */
    case TABLE_NOT_PERMITTED = 'tableNotPermitted';

    /**
     * The storage page does not exist.
     */
    case PAGE_MISSING = 'pageMissing';

    /**
     * The impersonated user may not create records on the storage page.
     */
    case PAGE_NOT_PERMITTED = 'pageNotPermitted';
}
