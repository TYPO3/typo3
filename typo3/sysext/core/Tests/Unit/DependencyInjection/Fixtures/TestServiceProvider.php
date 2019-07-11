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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;

function myFunctionFactory()
{
    return 42;
}

class TestServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            'serviceA' => function (ContainerInterface $container): \stdClass {
                $instance = new \stdClass();
                $instance->serviceB = $container->get('serviceB');

                return $instance;
            },
            'serviceB' => [ TestServiceProvider::class, 'createServiceB' ],
            'serviceC' => function (ContainerInterface $container): \stdClass {
                return new \stdClass();
            },
            'serviceD' => new class {
                public function __invoke(ContainerInterface $container): \stdClass
                {
                    return new \stdClass();
                }
            },
            'function' => 'TYPO3\\CMS\\Core\\Tests\\Unit\\DependencyInjection\\Fixtures\\myFunctionFactory'
        ];
    }

    public static function createServiceB(ContainerInterface $container): \stdClass
    {
        $instance = new \stdClass();
        $instance->parameter = 'localhost';
        return $instance;
    }

    public function getExtensions()
    {
        return [];
    }
}
