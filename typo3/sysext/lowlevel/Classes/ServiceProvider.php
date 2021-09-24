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

namespace TYPO3\CMS\Lowlevel;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;

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
            Controller\DatabaseIntegrityController::class => [ static::class, 'getDatabaseIntegrityController' ],
        ];
    }

    public static function getConfigurationController(ContainerInterface $container): Controller\ConfigurationController
    {
        return self::new(
            $container,
            Controller\ConfigurationController::class,
            [
                $container->get(ProviderRegistry::class),
                $container->get(PageRenderer::class),
                $container->get(UriBuilder::class),
                $container->get(ModuleTemplateFactory::class),
            ]
        );
    }

    public static function getDatabaseIntegrityController(ContainerInterface $container): Controller\DatabaseIntegrityController
    {
        return self::new(
            $container,
            Controller\DatabaseIntegrityController::class,
            [
                $container->get(IconFactory::class),
                $container->get(PageRenderer::class),
                $container->get(UriBuilder::class),
                $container->get(ModuleTemplateFactory::class),
            ]
        );
    }
}
