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

namespace TYPO3\CMS\Beuser\Event;

use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Listeners are able to manipulating the list of file mounts shown in the backend module
 */
final class AfterFilemountsListIsAssembledEvent
{
    public function __construct(
        public readonly RequestInterface $request,
        public array $filemounts,
    ) {}
}
