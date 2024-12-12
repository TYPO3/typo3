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
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
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
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\TypoScript\AST\CommentAwareAstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Install\Database\PermissionsCheck;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\SessionService;
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
            Authentication\AuthenticationService::class => self::getAuthenticationService(...),
            Http\Application::class => self::getApplication(...),
            Http\NotFoundRequestHandler::class => self::getNotFoundRequestHandler(...),
            Service\ClearCacheService::class => self::getClearCacheService(...),
            Service\ClearTableService::class => self::getClearTableService(...),
            Service\CoreUpdateService::class => self::getCoreUpdateService(...),
            Service\CoreVersionService::class => self::getCoreVersionService(...),
            Service\LanguagePackService::class => self::getLanguagePackService(...),
            Service\LateBootService::class => self::getLateBootService(...),
            Service\LoadTcaService::class => self::getLoadTcaService(...),
            Service\SilentConfigurationUpgradeService::class => self::getSilentConfigurationUpgradeService(...),
            Service\SilentTemplateFileUpgradeService::class => self::getSilentTemplateFileUpgradeService(...),
            Service\WebServerConfigurationFileService::class => self::getWebServerConfigurationFileService(...),
            Service\DatabaseUpgradeWizardsService::class => self::getDatabaseUpgradeWizardsService(...),
            Service\SessionService::class => self::getSessionService(...),
            Service\SetupService::class => self::getSetupService(...),
            Service\SetupDatabaseService::class => self::getSetupDatabaseService(...),
            Middleware\Installer::class => self::getInstallerMiddleware(...),
            Middleware\Maintenance::class => self::getMaintenanceMiddleware(...),
            Controller\EnvironmentController::class => self::getEnvironmentController(...),
            Controller\IconController::class => self::getIconController(...),
            Controller\InstallerController::class => self::getInstallerController(...),
            Controller\LayoutController::class => self::getLayoutController(...),
            Controller\LoginController::class => self::getLoginController(...),
            Controller\MaintenanceController::class => self::getMaintenanceController(...),
            Controller\SettingsController::class => self::getSettingsController(...),
            Controller\ServerResponseCheckController::class => self::getServerResponseCheckController(...),
            Controller\UpgradeController::class => self::getUpgradeController(...),
            Command\LanguagePackCommand::class => self::getLanguagePackCommand(...),
            Command\UpgradeWizardRunCommand::class => self::getUpgradeWizardRunCommand(...),
            Command\UpgradeWizardListCommand::class => self::getUpgradeWizardListCommand(...),
            Command\UpgradeWizardMarkUndoneCommand::class => self::getUpgradeWizardMarkUndoneCommand(...),
            Command\SetupCommand::class => self::getSetupCommand(...),
            Command\SetupDefaultBackendUserGroupsCommand::class => self::getSetupDefaultBackendUserGroupsCommand(...),
            Database\PermissionsCheck::class => self::getPermissionsCheck(...),
            Updates\DatabaseUpdatedPrerequisite::class => self::getDatabaseUpdatedPrerequisite(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            'backend.routes' => [ static::class, 'configureBackendRoutes' ],
            'backend.modules' => [ static::class, 'configureBackendModules' ],
            'icons' => [ static::class, 'configureIcons' ],
            CommandRegistry::class => self::configureCommands(...),
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

        return self::new($container, Http\Application::class, [
            $dispatcher,
            $container->get(Context::class),
        ]);
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

    public static function getClearTableService(ContainerInterface $container): Service\ClearTableService
    {
        return new Service\ClearTableService(
            $container->get(FailsafePackageManager::class),
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
        return self::new($container, Service\DatabaseUpgradeWizardsService::class, [
            $container->get(SchemaMigrator::class),
        ]);
    }

    public static function getSessionService(ContainerInterface $container): Service\SessionService
    {
        return new Service\SessionService(
            $container->get(HashService::class),
        );
    }

    public static function getSetupService(ContainerInterface $container): Service\SetupService
    {
        return new Service\SetupService(
            $container->get(ConfigurationManager::class),
            $container->get(SiteWriter::class),
            $container->get(YamlFileLoader::class),
            $container->get(FailsafePackageManager::class),
        );
    }

    public static function getSetupDatabaseService(ContainerInterface $container): Service\SetupDatabaseService
    {
        return new Service\SetupDatabaseService(
            $container->get(Service\LateBootService::class),
            $container->get(ConfigurationManager::class),
            $container->get(PermissionsCheck::class),
            $container->get(Registry::class),
            $container->get(SchemaMigrator::class),
        );
    }

    public static function getInstallerMiddleware(ContainerInterface $container): Middleware\Installer
    {
        return new Middleware\Installer(
            $container,
            $container->get(FormProtectionFactory::class),
            $container->get(SessionService::class),
        );
    }

    public static function getMaintenanceMiddleware(ContainerInterface $container): Middleware\Maintenance
    {
        return new Middleware\Maintenance(
            $container->get(FailsafePackageManager::class),
            $container->get(ConfigurationManager::class),
            $container->get(PasswordHashFactory::class),
            $container,
            $container->get(FormProtectionFactory::class),
            $container->get(SessionService::class),
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
            $container->get(ConfigurationManager::class),
            $container->get(FailsafePackageManager::class),
            $container->get(VerifyHostHeader::class),
            $container->get(FormProtectionFactory::class),
            $container->get(SetupService::class),
            $container->get(SetupDatabaseService::class),
            $container->get(HashService::class),
        );
    }

    public static function getLayoutController(ContainerInterface $container): Controller\LayoutController
    {
        return new Controller\LayoutController(
            $container->get(FailsafePackageManager::class),
            $container->get(Service\SilentConfigurationUpgradeService::class),
            $container->get(Service\SilentTemplateFileUpgradeService::class),
            $container->get(BackendEntryPointResolver::class),
            $container->get(HashService::class),
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
            $container->get(Service\ClearTableService::class),
            $container->get(ConfigurationManager::class),
            $container->get(PasswordHashFactory::class),
            $container->get(Locales::class),
            $container->get(LanguageServiceFactory::class),
            $container->get(FormProtectionFactory::class),
            $container->get(SchemaMigrator::class),
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

    public static function getServerResponseCheckController(ContainerInterface $container): Controller\ServerResponseCheckController
    {
        return new Controller\ServerResponseCheckController(
            $container->get(HashService::class),
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
            $container->get(Service\DatabaseUpgradeWizardsService::class),
            $container->get(Service\SilentConfigurationUpgradeService::class)
        );
    }

    public static function getUpgradeWizardListCommand(ContainerInterface $container): Command\UpgradeWizardListCommand
    {
        return new Command\UpgradeWizardListCommand(
            'upgrade:list',
            $container->get(Service\LateBootService::class),
        );
    }

    public static function getUpgradeWizardMarkUndoneCommand(ContainerInterface $container): Command\UpgradeWizardMarkUndoneCommand
    {
        return new Command\UpgradeWizardMarkUndoneCommand(
            'upgrade:mark:undone',
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
        );
    }

    public function getSetupDefaultBackendUserGroupsCommand(ContainerInterface $container): Command\SetupDefaultBackendUserGroupsCommand
    {
        return new Command\SetupDefaultBackendUserGroupsCommand(
            'setup:begroups:default',
            $container->get(Service\SetupService::class),
        );
    }

    public static function getPermissionsCheck(ContainerInterface $container): Database\PermissionsCheck
    {
        return new Database\PermissionsCheck();
    }

    public static function getDatabaseUpdatedPrerequisite(ContainerInterface $container): Updates\DatabaseUpdatedPrerequisite
    {
        return self::new($container, Updates\DatabaseUpdatedPrerequisite::class, [
            $container->get(Service\DatabaseUpgradeWizardsService::class),
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
            'upgrade:mark:undone',
            Command\UpgradeWizardMarkUndoneCommand::class,
            'Mark upgrade wizard as undone.'
        );
        $commandRegistry->addLazyCommand(
            'setup',
            Command\SetupCommand::class,
            'Setup TYPO3 via CLI.'
        );
        $commandRegistry->addLazyCommand(
            'setup:begroups:default',
            Command\SetupDefaultBackendUserGroupsCommand::class,
            'Setup default backend user groups "Editor" and "Advanced Editor".'
        );
        return $commandRegistry;
    }
}
