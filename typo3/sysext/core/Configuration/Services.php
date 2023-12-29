<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TYPO3\CMS\Core\Attribute\AsEventListener;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(SingletonInterface::class)->addTag('typo3.singleton');
    $containerBuilder->registerForAutoconfiguration(LoggerAwareInterface::class)->addTag('psr.logger_aware');

    // Services, to be read from container-aware dispatchers (on demand), therefore marked 'public'
    $containerBuilder->registerForAutoconfiguration(MiddlewareInterface::class)->addTag('typo3.middleware');
    $containerBuilder->registerForAutoconfiguration(RequestHandlerInterface::class)->addTag('typo3.request_handler');

    $containerBuilder->registerAttributeForAutoconfiguration(
        AsEventListener::class,
        static function (ChildDefinition $definition, AsEventListener $attribute, \Reflector $reflector): void {
            $definition->addTag(
                AsEventListener::TAG_NAME,
                [
                    'identifier' => $attribute->identifier,
                    'event' => $attribute->event,
                    'method' => $attribute->method ?? ($reflector instanceof \ReflectionMethod ? $reflector->getName() : null),
                    'before' => $attribute->before,
                    'after' => $attribute->after,
                ]
            );
        }
    );

    $containerBuilder->registerAttributeForAutoconfiguration(
        AsMessageHandler::class,
        static function (ChildDefinition $definition, AsMessageHandler $attribute, \Reflector $reflector): void {
            $definition->addTag(
                'messenger.message_handler',
                [
                    'bus' => $attribute->bus,
                    'fromTransport' => $attribute->fromTransport,
                    'handles' => $attribute->handles,
                    'method' => $attribute->method ?? ($reflector instanceof \ReflectionMethod ? $reflector->getName() : null),
                    'priority' => $attribute->priority,
                ]
            );
        }
    );

    $containerBuilder->registerAttributeForAutoconfiguration(
        AsCommand::class,
        static function (ChildDefinition $definition, AsCommand $attribute): void {
            $commands = explode('|', $attribute->name);
            $hidden = false;
            $name = array_shift($commands);

            if ($name === '') {
                // Symfony AsCommand attribute encodes hidden flag as an empty command name
                $hidden = true;
                $name = array_shift($commands);
            }

            if ($name === null) {
                // This happens in case no name and no aliases are given
                // @todo Throw exception
                return;
            }

            $definition->addTag(
                'console.command',
                [
                    'command' => $name,
                    'description' => $attribute->description,
                    'hidden' => $hidden,
                    // The `schedulable` flag is not configurable via symfony attribute parameters, use sane defaults
                    'schedulable' => true,
                ]
            );

            foreach ($commands as $name) {
                $definition->addTag(
                    'console.command',
                    [
                        'command' => $name,
                        'hidden' => $hidden,
                        'alias' => true,
                    ]
                );
            }
        }
    );

    $containerBuilder->addCompilerPass(new DependencyInjection\SingletonPass('typo3.singleton'));
    $containerBuilder->addCompilerPass(new DependencyInjection\LoggerAwarePass('psr.logger_aware'));
    $containerBuilder->addCompilerPass(new DependencyInjection\LoggerInterfacePass());
    $containerBuilder->addCompilerPass(new DependencyInjection\MfaProviderPass('mfa.provider'));
    $containerBuilder->addCompilerPass(new DependencyInjection\SoftReferenceParserPass('softreference.parser'));
    $containerBuilder->addCompilerPass(new DependencyInjection\ListenerProviderPass('event.listener'));
    $containerBuilder->addCompilerPass(new DependencyInjection\PublicServicePass('typo3.middleware'));
    $containerBuilder->addCompilerPass(new DependencyInjection\PublicServicePass('typo3.request_handler'));
    $containerBuilder->addCompilerPass(new DependencyInjection\ConsoleCommandPass('console.command'));
    $containerBuilder->addCompilerPass(new DependencyInjection\MessageHandlerPass('messenger.message_handler'));
    $containerBuilder->addCompilerPass(new DependencyInjection\MessengerMiddlewarePass('messenger.middleware'));
    $containerBuilder->addCompilerPass(new DependencyInjection\AutowireInjectMethodsPass());
};
