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

namespace TYPO3\CMS\Dashboard\Widgets;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for widgets that need the ServerRequestInterface Request.
 * The setter is called immediately after class instantiation.
 * Useful for Widgets that depend on a request, for instance when dealing
 * with views based on BackendViewFactory.
 */
interface RequestAwareWidgetInterface
{
    public function setRequest(ServerRequestInterface $request): void;
}
