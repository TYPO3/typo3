<?php

declare(strict_types=1);
namespace TYPO3\CMS\Core;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(SingletonInterface::class)->addTag('typo3.singleton');
    $containerBuilder->registerForAutoconfiguration(LoggerAwareInterface::class)->addTag('psr.logger_aware');

    // Services, to be read from container-aware dispatchers (on demand), therefore marked 'public'
    $containerBuilder->registerForAutoconfiguration(MiddlewareInterface::class)->addTag('typo3.middleware');
    $containerBuilder->registerForAutoconfiguration(RequestHandlerInterface::class)->addTag('typo3.request_handler');

    $containerBuilder->addCompilerPass(new DependencyInjection\SingletonPass('typo3.singleton'));
    $containerBuilder->addCompilerPass(new DependencyInjection\LoggerAwarePass('psr.logger_aware'));
    $containerBuilder->addCompilerPass(new DependencyInjection\LoggerInterfacePass());
    $containerBuilder->addCompilerPass(new DependencyInjection\MfaProviderPass('mfa.provider'));
    $containerBuilder->addCompilerPass(new DependencyInjection\SoftReferenceParserPass('softreference.parser'));
    $containerBuilder->addCompilerPass(new DependencyInjection\ListenerProviderPass('event.listener'));
    $containerBuilder->addCompilerPass(new DependencyInjection\PublicServicePass('typo3.middleware'));
    $containerBuilder->addCompilerPass(new DependencyInjection\PublicServicePass('typo3.request_handler'));
    $containerBuilder->addCompilerPass(new DependencyInjection\ConsoleCommandPass('console.command'));
    $containerBuilder->addCompilerPass(new DependencyInjection\AutowireInjectMethodsPass());
};
