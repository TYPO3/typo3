<?php

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

namespace TYPO3\CMS\Core\Resource\Exception;

use TYPO3\CMS\Core\Resource\Exception;

/**
 * An exception when something is wrong with fetching the permissions for a file or a folder.
 *
 * Extending \RuntimeException for backwards compatibility.
 */
class ResourcePermissionsUnavailableException extends Exception
{
}
