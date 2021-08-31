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

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\AbstractRecursivePass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;

/**
 * Looks for constructor arguments that request LoggerInterface and registers a lookup to the LogManager
 */
class LoggerInterfacePass extends AbstractRecursivePass
{
    /**
     * @param mixed $value
     * @param bool $isRoot
     * @return mixed
     */
    protected function processValue($value, $isRoot = false)
    {
        $value = parent::processValue($value, $isRoot);

        if (!$value instanceof Definition || !$value->isAutowired() || $value->isAbstract() || !$value->getClass()) {
            return $value;
        }
        if (!$reflectionClass = $this->container->getReflectionClass($value->getClass(), false)) {
            return $value;
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return $value;
        }

        $arguments = $value->getArguments();
        foreach ($reflectionClass->getConstructor()->getParameters() as $index => $parameter) {
            $name = '$' . $parameter->getName();

            if (isset($arguments[$name]) || isset($arguments[$index])) {
                continue;
            }

            if (!$parameter->hasType()) {
                continue;
            }

            $type = $parameter->getType();
            if (!($type instanceof \ReflectionNamedType && $type->getName() === LoggerInterface::class)) {
                continue;
            }

            $channel = $this->getParameterChannelName($parameter) ?? $this->getClassChannelName($reflectionClass) ?? $value->getClass();

            $logger = new Definition(Logger::class);
            $logger->setFactory([new Reference(LogManager::class), 'getLogger']);
            $logger->setArguments([$channel]);
            $logger->setShared(false);

            $value->setArgument($name, $logger);
        }

        return $value;
    }

    protected function getParameterChannelName(\ReflectionParameter $parameter): ?string
    {
        // Attribute channel definition is only supported on PHP 8 and later.
        if (class_exists('\ReflectionAttribute', false)) {
            $attributes = $parameter->getAttributes(Channel::class, \ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $channel) {
                return $channel->newInstance()->name;
            }
        }

        return null;
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
