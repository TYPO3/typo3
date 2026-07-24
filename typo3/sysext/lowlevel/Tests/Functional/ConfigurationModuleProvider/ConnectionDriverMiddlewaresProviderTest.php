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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\ConfigurationModuleProvider;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ConnectionDriverMiddlewaresProvider;
use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConnectionDriverMiddlewaresProviderTest extends FunctionalTestCase
{
    #[Test]
    public function connectionOptionsAreBlindedForAdditionalConnections(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Secondary'] = [
            'driver' => 'mysqli',
            'dbname' => 'secondary-database',
            'host' => 'secondary-host',
            'password' => 'secondary-password',
            'port' => 3307,
            'user' => 'secondary-user',
            'unix_socket' => 'secondary-socket',
        ];

        $connections = $this->getSubject()->getConfiguration()['Raw']['Connections'];

        self::assertSame(
            [
                'dbname' => '******',
                'host' => '******',
                'password' => '******',
                'port' => '******',
                'unix_socket' => '******',
                'user' => '******',
            ],
            array_intersect_key($connections['Secondary'], array_flip([
                'dbname', 'host', 'password', 'port', 'unix_socket', 'user',
            ]))
        );
        // Non-sensitive options stay readable
        self::assertSame('mysqli', $connections['Secondary']['driver']);
    }

    #[Test]
    public function connectionOptionsDerivedFromDsnUrlAreBlinded(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Dsn'] = [
            'url' => 'mysqli://dsn-user:dsn-password@dsn-host:3308/dsn-database',
        ];

        $connections = $this->getSubject()->getConfiguration()['Raw']['Connections'];

        // ConnectionPool::getConnectionParams() replaces "url" with the parsed parameters,
        // hence the credentials must be blinded under their Doctrine DBAL names.
        self::assertSame('******', $connections['Dsn']['dbname']);
        self::assertSame('******', $connections['Dsn']['host']);
        self::assertSame('******', $connections['Dsn']['password']);
        self::assertSame('******', $connections['Dsn']['port']);
        self::assertSame('******', $connections['Dsn']['user']);
        self::assertStringNotContainsString('dsn-password', json_encode($connections, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('dsn-user', json_encode($connections, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function blindedConnectionOptionsAddedForASingleConnectionAreKept(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Secondary'] = [
            'driver' => 'mysqli',
            'dbname' => 'secondary-database',
            'password' => 'secondary-password',
            'custom' => 'secondary-custom',
        ];

        $this->addBlindedConfigurationOptionsListener(static function (ModifyBlindedConfigurationOptionsEvent $event): void {
            $blindedConfigurationOptions = $event->getBlindedConfigurationOptions();
            $blindedConfigurationOptions['doctrine-dbal-driver-middlewares']['Raw']['Connections']['Secondary']['custom'] = '###';
            $event->setBlindedConfigurationOptions($blindedConfigurationOptions);
        });

        $connections = $this->getSubject()->getConfiguration()['Raw']['Connections'];

        // The connection specific option is applied ...
        self::assertSame('###', $connections['Secondary']['custom']);
        // ... without losing the options declared for "Default"
        self::assertSame('******', $connections['Secondary']['dbname']);
        self::assertSame('******', $connections['Secondary']['password']);
    }

    #[Test]
    public function blindedConfigurationOptionsRemovedByListenerAreNotApplied(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Secondary'] = [
            'driver' => 'mysqli',
            'dbname' => 'secondary-database',
        ];

        $this->addBlindedConfigurationOptionsListener(static function (ModifyBlindedConfigurationOptionsEvent $event): void {
            $event->setBlindedConfigurationOptions([]);
        });

        $connections = $this->getSubject()->getConfiguration()['Raw']['Connections'];

        self::assertSame('secondary-database', $connections['Secondary']['dbname']);
    }

    private function getSubject(): ConnectionDriverMiddlewaresProvider
    {
        $subject = new ConnectionDriverMiddlewaresProvider(
            $this->get(EventDispatcherInterface::class),
            $this->get(ConnectionPool::class),
        );
        $subject([
            'identifier' => 'doctrineDbalDriverMiddlewares',
            'label' => 'doctrineDbalDriverMiddlewares',
        ]);
        return $subject;
    }

    private function addBlindedConfigurationOptionsListener(\Closure $listener): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('modify-blinded-configuration-options', $listener);
        $container->get(ListenerProvider::class)->addListener(
            ModifyBlindedConfigurationOptionsEvent::class,
            'modify-blinded-configuration-options'
        );
    }
}
