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

namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\DependencyInjection\AuthenticatorPass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AuthenticatorPassTest extends UnitTestCase
{
    #[Test]
    public function authenticatorsAreGroupedByRealmAndOrderedByPriority(): void
    {
        $container = $this->createContainer([
            'low' => [['realm' => 'backend', 'priority' => -10]],
            'first' => [['realm' => 'backend']],
            'high' => [['realm' => 'backend', 'priority' => 50], ['realm' => 'api', 'priority' => 0]],
            'second' => [['realm' => 'backend']],
            'frontend' => [['realm' => 'frontend']],
        ]);

        new AuthenticatorPass(AsAuthenticator::TAG_NAME)->process($container);

        self::assertSame(
            [
                'backend' => ['high', 'first', 'second', 'low'],
                'api' => ['high'],
                'frontend' => ['frontend'],
            ],
            $container->getDefinition(AuthenticatorManager::class)->getArgument('$authenticatorIdsByRealm')
        );
    }

    #[Test]
    public function authenticatorWithoutRealmIsRefused(): void
    {
        $container = $this->createContainer(['authenticator' => [[]]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792934546);
        new AuthenticatorPass(AsAuthenticator::TAG_NAME)->process($container);
    }

    #[Test]
    public function invalidRealmIsRefused(): void
    {
        $container = $this->createContainer(['authenticator' => [['realm' => 'Not a realm']]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791491201);
        new AuthenticatorPass(AsAuthenticator::TAG_NAME)->process($container);
    }

    #[Test]
    public function frontendRealmOfSiteIsRefused(): void
    {
        $container = $this->createContainer(['authenticator' => [['realm' => 'frontend:main']]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792942004);
        new AuthenticatorPass(AsAuthenticator::TAG_NAME)->process($container);
    }

    #[Test]
    public function authenticatorRegisteredTwiceForTheSameRealmIsRefused(): void
    {
        $container = $this->createContainer(['authenticator' => [['realm' => 'backend'], ['realm' => 'backend', 'priority' => 10]]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792942005);
        new AuthenticatorPass(AsAuthenticator::TAG_NAME)->process($container);
    }

    #[Test]
    public function serviceNotImplementingTheInterfaceIsRefused(): void
    {
        $container = $this->createContainer([]);
        $container->setDefinition('no-authenticator', new Definition(\stdClass::class)->addTag(AsAuthenticator::TAG_NAME, ['realm' => 'backend']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792934545);
        new AuthenticatorPass(AsAuthenticator::TAG_NAME)->process($container);
    }

    /**
     * @param array<string, list<array<string, mixed>>> $tagsById
     */
    private function createContainer(array $tagsById): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(AuthenticatorManager::class, new Definition(AuthenticatorManager::class));
        $authenticator = new class implements AuthenticatorInterface {
            public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
            {
                return new NotApplicable();
            }
        };
        foreach ($tagsById as $id => $tags) {
            $definition = new Definition($authenticator::class);
            foreach ($tags as $attributes) {
                $definition->addTag(AsAuthenticator::TAG_NAME, $attributes);
            }
            $container->setDefinition($id, $definition);
        }
        return $container;
    }
}
