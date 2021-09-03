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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;

/**
 * @internal
 */
final class LoggerAwarePass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $tagName;

    /**
     * @param string $tagName
     */
    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            if (!$definition->isAutowired() || $definition->isAbstract()) {
                continue;
            }

            $channel = $id;
            if ($definition->getClass()) {
                $reflectionClass = $container->getReflectionClass($definition->getClass(), false);
                if ($reflectionClass) {
                    $channel = $this->getClassChannelName($reflectionClass) ?? $channel;
                }
            }

            $logger = new Definition(Logger::class);
            $logger->setFactory([new Reference(LogManager::class), 'getLogger']);
            $logger->setArguments([$channel]);
            $logger->setShared(false);

            $definition->addMethodCall('setLogger', [$logger]);
        }
    }

    protected function getClassChannelName(\ReflectionClass $class): ?string
    {
        // Attribute channel definition is only supported on PHP 8 and later.
        if (class_exists('\ReflectionAttribute', false)) {
            $attributes = $class->getAttributes(Channel::class, \ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $channel) {
                return $channel->newInstance()->name;
            }
        }

        if ($class->getParentClass() !== false) {
            return $this->getClassChannelName($class->getParentClass());
        }

        return null;
    }
}
