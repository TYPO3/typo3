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

namespace TYPO3\CMS\Core\Error\PageErrorHandler;

use TYPO3\CMS\Core\Error\Exception;

/**
 * Is typically used, when a site configuration has no page-error handler configured
 * for a specific HTTP Status type that is requested.
 */
class PageErrorHandlerNotConfiguredException extends Exception
{
}
