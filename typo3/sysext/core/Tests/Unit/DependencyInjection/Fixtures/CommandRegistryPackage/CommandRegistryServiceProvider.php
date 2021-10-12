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

namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\CommandRegistryPackage;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;

class CommandRegistryServiceProvider implements ServiceProviderInterface
{
    public function getFactories(): array
    {
        return [
            CommandRegistry::class => [ static::class, 'getConsoleCommandRegistry' ],
        ];
    }

    public function getExtensions(): array
    {
        return [];
    }

    public static function getConsoleCommandRegistry(ContainerInterface $container): CommandRegistry
    {
        return new CommandRegistry($container);
    }
}
