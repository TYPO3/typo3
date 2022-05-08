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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * Dummy controller for 2nd level module. It only exists since the 2nd level
 * menu entry 'web_ts' needs a _default route in routes section in Configuration/Modules.php.
 *
 * Note the Template module is admin-only, we are sure there is always at least one 3rd
 * level entry. They're all delivered by ext:tstemplate, too.
 *
 * @todo: Find a better way.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class DummyController
{
    public function handleRequest(): ResponseInterface
    {
        throw new \RuntimeException(
            'This controller should never be called. It exists just to happify Configuration/Modules.php.',
            1651996049
        );
    }
}
