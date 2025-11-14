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

namespace TYPO3Tests\TestRowUpdater\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterRegistry;

/**
 * Test service getting {@see RowUpdaterRegistry} injected and allow
 * public access to it for testing purpose and the reason this class
 * is flagged as public.
 */
#[Autoconfigure(public: true)]
final readonly class InjectionTestService
{
    public function __construct(
        public RowUpdaterRegistry $registry,
    ) {}
}
