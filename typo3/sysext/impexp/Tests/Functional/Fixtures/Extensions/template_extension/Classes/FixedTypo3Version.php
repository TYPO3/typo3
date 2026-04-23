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

namespace TYPO3Tests\TemplateExtension;

use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Pinned Typo3Version for impexp functional tests so exported fixtures
 * stay byte-stable across core version bumps. The container of any test
 * loading this fixture extension swaps the real Typo3Version service
 * with this subclass.
 */
final class FixedTypo3Version extends Typo3Version
{
    protected const VERSION = '99.99.99';
    protected const BRANCH = '99.99';
}
