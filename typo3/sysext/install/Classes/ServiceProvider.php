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
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Middleware\NormalizedParamsAttribute as NormalizedParamsMiddleware;
use TYPO3\CMS\Core\Middleware\ResponsePropagation as ResponsePropagationMiddleware;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\TypoScript\AST\CommentAwareAstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Install\Database\PermissionsCheck;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\SetupDatabaseService;
use TYPO3\CMS\Install\Service\SetupService;
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

    protected static function getPackageName(): string
    {
        return 'typo3/cms-install';
    }

    public function getFactories(): array
    {
        return [
            Authentication\AuthenticationService::class => [ static::class, 'getAuthenticationService' ],
            Http\Application::class => [ static::class, 'getApplication' ],
            Http\NotFoundRequestHandler::class => [ static::class, 'getNotFoundRequestHandler' ],
            Service\ClearCacheService::class => [ static::class, 'getClearCacheService' ],
            Service\CoreUpdateService::class => [ static::class, 'getCoreUpdateService' ],
            Service\CoreVersionService::class => [ static::class, 'getCoreVersionService' ],
            Service\LanguagePackService::class => [ static::class, 'getLanguagePackService' ],
            Service\LateBootService::class => [ static::class, 'getLateBootService' ],
            Service\LoadTcaService::class => [ static::class, 'getLoadTcaService' ],
            Service\SilentConfigurationUpgradeService::class => [ static::class, 'getSilentConfigurationUpgradeService' ],
            Service\SilentTemplateFileUpgradeService::class => [ static::class, 'getSilentTemplateFileUpgradeService' ],
            Service\WebServerConfigurationFileService::class => [ static::class, 'getWebServerConfigurationFileService' ],
            Service\DatabaseUpgradeWizardsService::class => [ static::class, 'getDatabaseUpgradeWizardsService' ],
            Service\SetupService::class => [ static::class, 'getSetupService' ],
            Service\SetupDatabaseService::class => [ static::class, 'getSetupDatabaseService' ],
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
            Command\SetupCommand::class => [ static::class, 'getSetupCommand' ],
            Database\PermissionsCheck::class => [ static::class, 'getPermissionsCheck' ],
            Mailer::class => [ static::class, 'getMailer' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            'backend.routes' => [ static::class, 'configureBackendRoutes' ],
            'backend.modules' => [ static::class, 'configureBackendModules' ],
            'icons' => [ static::class, 'configureIcons' ],
            CommandRegistry::class => [ static::class, 'configureCommands' ],
        ];
    }

    public static function getAuthenticationService(ContainerInterface $container): Authentication\AuthenticationService
    {
        return new Authentication\AuthenticationService(
            $container->get(Mailer::class)
        );
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

    public static function getDatabaseUpgradeWizardsService(ContainerInterface $container): Service\DatabaseUpgradeWizardsService
    {
        return new Service\DatabaseUpgradeWizardsService();
    }

    public static function getSetupService(ContainerInterface $container): Service\SetupService
    {
        return new Service\SetupService(
            $container->get(ConfigurationManager::class),
            $container->get(SiteConfiguration::class),
        );
    }

    public static function getSetupDatabaseService(ContainerInterface $container): Service\SetupDatabaseService
    {
        return new Service\SetupDatabaseService(
            $container->get(Service\LateBootService::class),
            $container->get(ConfigurationManager::class),
            $container->get(PermissionsCheck::class),
            $container->get(Registry::class),
        );
    }

    public static function getInstallerMiddleware(ContainerInterface $container): Middleware\Installer
    {
        return new Middleware\Installer(
            $container,
            $container->get(FormProtectionFactory::class)
        );
    }

    public static function getMaintenanceMiddleware(ContainerInterface $container): Middleware\Maintenance
    {
        return new Middleware\Maintenance(
            $container->get(FailsafePackageManager::class),
            $container->get(ConfigurationManager::class),
            $container->get(PasswordHashFactory::class),
            $container,
            $container->get(FormProtectionFactory::class)
        );
    }

    public static function getEnvironmentController(ContainerInterface $container): Controller\EnvironmentController
    {
        return new Controller\EnvironmentController(
            $container->get(Service\LateBootService::class),
            $container->get(FormProtectionFactory::class),
            $container->get(Mailer::class)
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
            $container->get(FailsafePackageManager::class),
            $container->get(VerifyHostHeader::class),
            $container->get(FormProtectionFactory::class),
            $container->get(SetupService::class),
            $container->get(SetupDatabaseService::class),
        );
    }

    public static function getLayoutController(ContainerInterface $container): Controller\LayoutController
    {
        return new Controller\LayoutController(
            $container->get(FailsafePackageManager::class),
            $container->get(Service\SilentConfigurationUpgradeService::class),
            $container->get(Service\SilentTemplateFileUpgradeService::class)
        );
    }

    public static function getLoginController(ContainerInterface $container): Controller\LoginController
    {
        return new Controller\LoginController(
            $container->get(FormProtectionFactory::class),
            $container->get(ConfigurationManager::class),
        );
    }

    public static function getMaintenanceController(ContainerInterface $container): Controller\MaintenanceController
    {
        return new Controller\MaintenanceController(
            $container->get(Service\LateBootService::class),
            $container->get(Service\ClearCacheService::class),
            $container->get(ConfigurationManager::class),
            $container->get(PasswordHashFactory::class),
            $container->get(Locales::class),
            $container->get(LanguageServiceFactory::class),
            $container->get(FormProtectionFactory::class)
        );
    }

    public static function getSettingsController(ContainerInterface $container): Controller\SettingsController
    {
        return new Controller\SettingsController(
            $container->get(PackageManager::class),
            $container->get(LanguageServiceFactory::class),
            $container->get(CommentAwareAstBuilder::class),
            $container->get(LosslessTokenizer::class),
            $container->get(AstTraverser::class),
            $container->get(FormProtectionFactory::class),
            $container->get(ConfigurationManager::class),
        );
    }

    public static function getUpgradeController(ContainerInterface $container): Controller\UpgradeController
    {
        return new Controller\UpgradeController(
            $container->get(PackageManager::class),
            $container->get(Service\LateBootService::class),
            $container->get(Service\DatabaseUpgradeWizardsService::class),
            $container->get(FormProtectionFactory::class)
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
            $container->get(Service\DatabaseUpgradeWizardsService::class)
        );
    }

    public static function getUpgradeWizardListCommand(ContainerInterface $container): Command\UpgradeWizardListCommand
    {
        return new Command\UpgradeWizardListCommand(
            'upgrade:list',
            $container->get(Service\LateBootService::class),
        );
    }

    public static function getSetupCommand(ContainerInterface $container): Command\SetupCommand
    {
        return new Command\SetupCommand(
            'setup',
            $container->get(Service\SetupDatabaseService::class),
            $container->get(Service\SetupService::class),
            $container->get(ConfigurationManager::class),
            $container->get(LateBootService::class),
            $container->get(FailsafePackageManager::class),
        );
    }

    public static function getPermissionsCheck(ContainerInterface $container): Database\PermissionsCheck
    {
        return new Database\PermissionsCheck();
    }

    public static function getMailer(ContainerInterface $container): Mailer
    {
        return self::new($container, Mailer::class, [
            null,
            $container->get(EventDispatcherInterface::class),
        ]);
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
        $commandRegistry->addLazyCommand(
            'setup',
            Command\SetupCommand::class,
            'Setup TYPO3 via CLI.'
        );
        return $commandRegistry;
    }
}
