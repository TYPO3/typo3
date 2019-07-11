<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures;

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

use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;

class TestRegistryServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            'serviceA' => function () {
                return new \stdClass();
            },
            'param' => function () {
                return 42;
            },
        ];
    }

    public function getExtensions()
    {
        return [
            'serviceB' => function () {
                return new \stdClass();
            },
        ];
    }
}
