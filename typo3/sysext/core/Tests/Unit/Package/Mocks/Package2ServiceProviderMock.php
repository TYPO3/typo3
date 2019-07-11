<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Package\Mocks;

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

use TYPO3\CMS\Core\Package\AbstractServiceProvider;

class Package2ServiceProviderMock extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../../Http/Fixtures/Package2/';
    }

    public function getFactories(): array
    {
        return [];
    }
}
