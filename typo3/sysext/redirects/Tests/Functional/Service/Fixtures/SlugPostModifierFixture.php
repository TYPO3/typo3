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

namespace TYPO3\CMS\Redirects\Tests\Functional\Service\Fixtures;

use TYPO3\CMS\Core\DataHandling\SlugHelper;

/**
 * Registered as "postModifiers" of the pages slug field to verify slug modifiers
 * are taken into account when sub page slugs are rebuilt.
 */
final class SlugPostModifierFixture
{
    public function modify(array $parameters, SlugHelper $slugHelper): string
    {
        return $parameters['slug'] . '-modified';
    }
}
