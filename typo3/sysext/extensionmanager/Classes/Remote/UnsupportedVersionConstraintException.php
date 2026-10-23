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

namespace TYPO3\CMS\Extensionmanager\Remote;

use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * A remote sent a version constraint the ext_emconf.php notation cannot
 * express, such as "^13.4" or alternatives joined by "||".
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class UnsupportedVersionConstraintException extends ExtensionManagerException {}
