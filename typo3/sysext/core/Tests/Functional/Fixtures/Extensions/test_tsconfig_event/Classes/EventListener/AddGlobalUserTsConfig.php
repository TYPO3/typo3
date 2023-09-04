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

namespace TYPO3Tests\TestTsconfigEvent\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent;

#[AsEventListener(identifier: 'typo3tests/test-tsconfig-event/add-global-user-tsconfig')]
final class AddGlobalUserTsConfig
{
    public function __invoke(BeforeLoadedUserTsConfigEvent $event): void
    {
        $event->addTsConfig('number = two');
    }
}
