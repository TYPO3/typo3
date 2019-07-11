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

class TestServiceProviderOverride2 implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [];
    }

    public static function overrideServiceA(ContainerInterface $container, $serviceA = null): \stdClass
    {
        $serviceA->newProperty2 = 'bar';

        return $serviceA;
    }

    public function getExtensions()
    {
        return [
            'serviceA' => [self::class, 'overrideServiceA'],
            'serviceC' => function (ContainerInterface $container, \stdClass $instance): \stdClass {
                $instance->serviceB = $container->get('serviceB');

                return $instance;
            },
        ];
    }
}
