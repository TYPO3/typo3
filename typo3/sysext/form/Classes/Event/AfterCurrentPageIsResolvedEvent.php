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

namespace TYPO3\CMS\Form\Event;

use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Listeners to this Event will be able to modify the current page after it has been resolved.
 */
final class AfterCurrentPageIsResolvedEvent
{
    public function __construct(
        public ?Page $currentPage,
        public readonly FormRuntime $formRuntime,
        public readonly ?Page $lastDisplayedPage,
        public readonly RequestInterface $request,
    ) {}
}
