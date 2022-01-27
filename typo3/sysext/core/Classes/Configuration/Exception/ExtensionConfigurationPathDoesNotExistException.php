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

namespace TYPO3\CMS\Core\Configuration\Exception;

use TYPO3\CMS\Core\Exception;

/**
 * An exception thrown if ExtensionConfiguration->get() is called with
 * a path that does not exist within the extension configuration.
 */
class ExtensionConfigurationPathDoesNotExistException extends Exception
{
}
