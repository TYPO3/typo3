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

namespace TYPO3\CMS\Core\Cache\Flush;

enum CacheFlushSkipReason: string
{
    /** The table has no TCA, the uid is not positive or the record does not exist */
    case InvalidRecord = 'invalidRecord';
    /** Page TSconfig "TCEMAIN.clearCache_disable" is set */
    case DisabledByPageTsConfig = 'disabledByPageTsConfig';
    /** A workspace version was changed, the live record is untouched */
    case WorkspaceVersion = 'workspaceVersion';
    /** The CacheFlushingContext does not permit flushing this cache group */
    case NotPermitted = 'notPermitted';
}
