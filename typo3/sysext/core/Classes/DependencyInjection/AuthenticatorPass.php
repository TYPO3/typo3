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

namespace TYPO3\CMS\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorManager;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;

/**
 * Hands the authenticators of each realm to the AuthenticatorManager,
 * ordered by priority, so that an authenticator is only ever asked for
 * the realms it is registered for.
 *
 * @internal
 */
final readonly class AuthenticatorPass implements CompilerPassInterface
{
    public function __construct(
        private string $tagName,
    ) {}

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(AuthenticatorManager::class)) {
            return;
        }
        $registrations = [];
        $references = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }
            $className = $definition->getClass() ?? $id;
            if (!is_a($className, AuthenticatorInterface::class, true)) {
                throw new \InvalidArgumentException(
                    'Service "' . $id . '" is tagged as "' . $this->tagName . '", but its class "' . $className . '" does not implement ' . AuthenticatorInterface::class . '.',
                    1792934545
                );
            }
            foreach ($tags as $attributes) {
                $realm = (string)($attributes['realm'] ?? '');
                if ($realm === '') {
                    throw new \InvalidArgumentException(
                        'Authenticator "' . $id . '" names no realm, register it with the AsAuthenticator attribute.',
                        1792934546
                    );
                }
                $realm = Realm::fromRegistration($realm)->identifier;
                if (in_array($id, array_column($registrations[$realm] ?? [], 'id'), true)) {
                    throw new \InvalidArgumentException(
                        'Authenticator "' . $id . '" is registered more than once for the realm "' . $realm . '".',
                        1792942005
                    );
                }
                $registrations[$realm][] = ['id' => $id, 'priority' => (int)($attributes['priority'] ?? 0)];
                $references[$id] = new Reference($id);
            }
        }
        $authenticatorIdsByRealm = [];
        foreach ($registrations as $realm => $authenticators) {
            // Stable sort: authenticators of the same priority keep their registration order
            usort($authenticators, static fn(array $a, array $b): int => $b['priority'] <=> $a['priority']);
            $authenticatorIdsByRealm[$realm] = array_column($authenticators, 'id');
        }
        $container->findDefinition(AuthenticatorManager::class)
            ->setArgument('$authenticators', ServiceLocatorTagPass::register($container, $references))
            ->setArgument('$authenticatorIdsByRealm', $authenticatorIdsByRealm);
    }
}
