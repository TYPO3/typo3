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

namespace TYPO3Tests\TestTcaEvent\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

#[AsEventListener(identifier: 'typo3tests/test-tca-event/after-tca-compilation')]
final class AddTcaAfterTcaCompilation
{
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $tca['fruit']['ctrl']['title'] = 'Vegetable';
        $event->setTca($tca);
    }
}
