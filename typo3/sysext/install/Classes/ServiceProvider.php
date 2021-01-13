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

namespace TYPO3\CMS\Install;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Middleware\NormalizedParamsAttribute as NormalizedParamsMiddleware;
use TYPO3\CMS\Core\Middleware\ResponsePropagation as ResponsePropagationMiddleware;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser;
use TYPO3\CMS\Install\Database\PermissionsCheck;
use TYPO3\CMS\Install\Service\WebServerConfigurationFileService;

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
            Http\Application::class => [ static::class, 'getApplication' ],
            Http\NotFoundRequestHandler::class => [ static::class, 'getNotFoundRequestHandler' ],
            Service\ClearCacheService::class => [ static::class, 'getClearCacheService' ],
            Service\CoreUpdateService::class => [ static::class, 'getCoreUpdateService' ],
            Service\CoreVersionService::class => [ static::class, 'getCoreVersionService' ],
            Service\ExtensionConfigurationService::class => [ static::class, 'getExtensionConfigurationService' ],
            Service\LanguagePackService::class => [ static::class, 'getLanguagePackService' ],
            Service\LateBootService::class => [ static::class, 'getLateBootService' ],
            Service\LoadTcaService::class => [ static::class, 'getLoadTcaService' ],
            Service\SilentConfigurationUpgradeService::class => [ static::class, 'getSilentConfigurationUpgradeService' ],
            Service\SilentTemplateFileUpgradeService::class => [ static::class, 'getSilentTemplateFileUpgradeService' ],
            Service\WebServerConfigurationFileService::class => [ static::class, 'getWebServerConfigurationFileService' ],
            Service\UpgradeWizardsService::class => [ static::class, 'getUpgradeWizardsService' ],
            Middleware\Installer::class => [ static::class, 'getInstallerMiddleware' ],
            Middleware\Maintenance::class => [ static::class, 'getMaintenanceMiddleware' ],
            Controller\EnvironmentController::class => [ static::class, 'getEnvironmentController' ],
            Controller\IconController::class => [ static::class, 'getIconController' ],
            Controller\InstallerController::class => [ static::class, 'getInstallerController' ],
            Controller\LayoutController::class => [ static::class, 'getLayoutController' ],
            Controller\LoginController::class => [ static::class, 'getLoginController' ],
            Controller\MaintenanceController::class => [ static::class, 'getMaintenanceController' ],
            Controller\SettingsController::class => [ static::class, 'getSettingsController' ],
            Controller\UpgradeController::class => [ static::class, 'getUpgradeController' ],
            Command\LanguagePackCommand::class => [ static::class, 'getLanguagePackCommand' ],
            Command\UpgradeWizardRunCommand::class => [ static::class, 'getUpgradeWizardRunCommand' ],
            Command\UpgradeWizardListCommand::class => [ static::class, 'getUpgradeWizardListCommand' ],
            Database\PermissionsCheck::class => [ static::class, 'getPermissionsCheck' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            'backend.routes' => [ static::class, 'configureBackendRoutes' ],
            'icons' => [ static::class, 'configureIcons' ],
            CommandRegistry::class => [ static::class, 'configureCommands' ],
        ];
    }

    public static function getApplication(ContainerInterface $container): Http\Application
    {
        $requestHandler = $container->get(Http\NotFoundRequestHandler::class);
        $dispatcher = new MiddlewareDispatcher($requestHandler, [], $container);

        // Stack of middlewares, executed LIFO
        $dispatcher->lazy(ResponsePropagationMiddleware::class);
        $dispatcher->lazy(Middleware\Installer::class);
        $dispatcher->add($container->get(Middleware\Maintenance::class));
        $dispatcher->lazy(NormalizedParamsMiddleware::class);

        return new Http\Application($dispatcher, $container->get(Context::class));
    }

    public static function getNotFoundRequestHandler(ContainerInterface $container): Http\NotFoundRequestHandler
    {
        return new Http\NotFoundRequestHandler();
    }

    public static function getClearCacheService(ContainerInterface $container): Service\ClearCacheService
    {
        return new Service\ClearCacheService(
            $container->get(Service\LateBootService::class),
            $container->get('cache.di')
        );
    }

    public static function getCoreUpdateService(ContainerInterface $container): Service\CoreUpdateService
    {
        return new Service\CoreUpdateService(
            $container->get(Service\CoreVersionService::class)
        );
    }

    public static function getCoreVersionService(ContainerInterface $container): Service\CoreVersionService
    {
        return new Service\CoreVersionService();
    }

    public static function getExtensionConfigurationService(ContainerInterface $container): Service\ExtensionConfigurationService
    {
        return new Service\ExtensionConfigurationService(
            $container->get(PackageManager::class),
            $container->get(ConstantConfigurationParser::class)
        );
    }

    public static function getLanguagePackService(ContainerInterface $container): Service\LanguagePackService
    {
        return new Service\LanguagePackService(
            $container->get(EventDispatcherInterface::class),
            $container->get(RequestFactory::class),
            $container->get(LogManager::class)->getLogger(Service\LanguagePackService::class)
        );
    }

    public static function getLateBootService(ContainerInterface $container): Service\LateBootService
    {
        return new Service\LateBootService(
            $container->get(ContainerBuilder::class),
            $container
        );
    }

    public static function getLoadTcaService(ContainerInterface $container): Service\LoadTcaService
    {
        return new Service\LoadTcaService(
            $container->get(Service\LateBootService::class)
        );
    }

    public static function getSilentConfigurationUpgradeService(ContainerInterface $container): Service\SilentConfigurationUpgradeService
    {
        return new Service\SilentConfigurationUpgradeService(
            $container->get(ConfigurationManager::class)
        );
    }

    public static function getSilentTemplateFileUpgradeService(ContainerInterface $container): Service\SilentTemplateFileUpgradeService
    {
        return new Service\SilentTemplateFileUpgradeService(
            $container->get(WebServerConfigurationFileService::class)
        );
    }

    public static function getWebServerConfigurationFileService(ContainerInterface $container): Service\WebServerConfigurationFileService
    {
        return self::new($container, Service\WebServerConfigurationFileService::class);
    }

    public static function getUpgradeWizardsService(ContainerInterface $container): Service\UpgradeWizardsService
    {
        return new Service\UpgradeWizardsService();
    }

    public static function getInstallerMiddleware(ContainerInterface $container): Middleware\Installer
    {
        return new Middleware\Installer($container);
    }

    public static function getMaintenanceMiddleware(ContainerInterface $container): Middleware\Maintenance
    {
        return new Middleware\Maintenance(
            $container->get(FailsafePackageManager::class),
            $container->get(ConfigurationManager::class),
            $container->get(PasswordHashFactory::class),
            $container
        );
    }

    public static function getEnvironmentController(ContainerInterface $container): Controller\EnvironmentController
    {
        return new Controller\EnvironmentController(
            $container->get(Service\LateBootService::class)
        );
    }

    public static function getIconController(ContainerInterface $container): Controller\IconController
    {
        return new Controller\IconController(
            $container->get(IconRegistry::class),
            $container->get(IconFactory::class)
        );
    }

    public static function getInstallerController(ContainerInterface $container): Controller\InstallerController
    {
        return new Controller\InstallerController(
            $container->get(Service\LateBootService::class),
            $container->get(Service\SilentConfigurationUpgradeService::class),
            $container->get(Service\SilentTemplateFileUpgradeService::class),
            $container->get(ConfigurationManager::class),
            $container->get(SiteConfiguration::class),
            $container->get(Registry::class),
            $container->get(FailsafePackageManager::class),
            $container->get(VerifyHostHeader::class),
            $container->get(PermissionsCheck::class)
        );
    }

    public static function getLayoutController(ContainerInterface $container): Controller\LayoutController
    {
        return new Controller\LayoutController(
            $container->get(Service\SilentConfigurationUpgradeService::class),
            $container->get(Service\SilentTemplateFileUpgradeService::class)
        );
    }

    public static function getLoginController(ContainerInterface $container): Controller\LoginController
    {
        return new Controller\LoginController();
    }

    public static function getMaintenanceController(ContainerInterface $container): Controller\MaintenanceController
    {
        return new Controller\MaintenanceController(
            $container->get(Service\LateBootService::class),
            $container->get(Service\ClearCacheService::class),
            $container->get(ConfigurationManager::class),
            $container->get(PasswordHashFactory::class),
            $container->get(Locales::class)
        );
    }

    public static function getSettingsController(ContainerInterface $container): Controller\SettingsController
    {
        return new Controller\SettingsController(
            $container->get(PackageManager::class),
            $container->get(Service\ExtensionConfigurationService::class),
            $container->get(LanguageServiceFactory::class)
        );
    }

    public static function getUpgradeController(ContainerInterface $container): Controller\UpgradeController
    {
        return new Controller\UpgradeController(
            $container->get(PackageManager::class),
            $container->get(Service\LateBootService::class),
            $container->get(Service\UpgradeWizardsService::class)
        );
    }

    public static function getLanguagePackCommand(ContainerInterface $container): Command\LanguagePackCommand
    {
        return new Command\LanguagePackCommand(
            'language:update',
            $container->get(Service\LateBootService::class)
        );
    }

    public static function getUpgradeWizardRunCommand(ContainerInterface $container): Command\UpgradeWizardRunCommand
    {
        return new Command\UpgradeWizardRunCommand(
            'upgrade:run',
            $container->get(Service\LateBootService::class),
            $container->get(Service\UpgradeWizardsService::class)
        );
    }

    public static function getUpgradeWizardListCommand(ContainerInterface $container): Command\UpgradeWizardListCommand
    {
        return new Command\UpgradeWizardListCommand(
            'upgrade:list',
            $container->get(Service\LateBootService::class),
            $container->get(Service\UpgradeWizardsService::class)
        );
    }

    public static function getPermissionsCheck(ContainerInterface $container): Database\PermissionsCheck
    {
        return new Database\PermissionsCheck();
    }

    public static function configureCommands(ContainerInterface $container, CommandRegistry $commandRegistry): CommandRegistry
    {
        $commandRegistry->addLazyCommand(
            'language:update',
            Command\LanguagePackCommand::class,
            'Update the language files of all activated extensions',
            false,
            true
        );
        $commandRegistry->addLazyCommand(
            'upgrade:run',
            Command\UpgradeWizardRunCommand::class,
            'Run upgrade wizard. Without arguments all available wizards will be run.'
        );
        $commandRegistry->addLazyCommand(
            'upgrade:list',
            Command\UpgradeWizardListCommand::class,
            'List available upgrade wizards.'
        );
        return $commandRegistry;
    }
}
