<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Lowlevel;

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
use TYPO3\CMS\Core\Package\AbstractServiceProvider;

/**
 * @internal
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    public function getFactories(): array
    {
        return [
            Controller\ConfigurationController::class => [ static::class, 'getConfigurationController' ],
        ];
    }

    public static function getConfigurationController(ContainerInterface $container): Controller\ConfigurationController
    {
        return self::new($container, Controller\ConfigurationController::class, [$container]);
    }
}
