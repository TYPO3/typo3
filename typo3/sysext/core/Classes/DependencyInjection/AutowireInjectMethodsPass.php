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
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;

/**
 * Looks for definitions with autowiring enabled and registers their corresponding "inject*" methods as setters.
 */
class AutowireInjectMethodsPass extends AbstractRecursivePass
{
    /**
     * @param bool $isRoot
     */
    protected function processValue($value, $isRoot = false): mixed
    {
        $value = parent::processValue($value, $isRoot);

        if (!$value instanceof Definition || !$value->isAutowired() || $value->isAbstract() || !$value->getClass()) {
            return $value;
        }
        if (!$reflectionClass = $this->container->getReflectionClass($value->getClass(), false)) {
            return $value;
        }

        $alreadyCalledMethods = [];

        foreach ($value->getMethodCalls() as [$method]) {
            $alreadyCalledMethods[strtolower($method)] = true;
        }

        $addInitCall = false;

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $r = $reflectionMethod;

            if ($r->isConstructor() || isset($alreadyCalledMethods[strtolower($r->name)])) {
                continue;
            }

            if ($reflectionMethod->isPublic() && str_starts_with($reflectionMethod->name, 'inject')) {
                $this->addInjectMethodCall($value, $reflectionMethod);
            }

            if ($reflectionMethod->name === 'initializeObject' && $reflectionMethod->isPublic()) {
                $addInitCall = true;
            }
        }

        if ($addInitCall) {
            // Add call to initializeObject() which is required by classes that need to perform
            // constructions tasks after the inject* method based injection of dependencies.
            $value->addMethodCall('initializeObject');
        }

        return $value;
    }

    private function addInjectMethodCall(Definition $definition, \ReflectionMethod $reflectionMethod): void
    {
        $definition->addMethodCall(
            $reflectionMethod->name,
            $this->getRequiredInjectMethodArguments($definition, $reflectionMethod)
        );
    }

    /**
     * @return array<string, Definition>
     */
    private function getRequiredInjectMethodArguments(Definition $definition, \ReflectionMethod $reflectionMethod): array
    {
        $channelExtractor = new LogChannelExtractor();
        $arguments = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {
            if (!$parameter->hasType()) {
                continue;
            }

            $type = $parameter->getType();
            if (!$type instanceof \ReflectionNamedType || $type->getName() !== LoggerInterface::class) {
                continue;
            }

            $channel = $channelExtractor->getParameterChannelName($parameter) ?? $channelExtractor->getClassChannelName($this->container->getReflectionClass($definition->getClass(), false)) ?? $definition->getClass();

            $logger = new Definition(Logger::class);
            $logger->setFactory([new Reference(LogManager::class), 'getLogger']);
            $logger->setArguments([$channel]);
            $logger->setShared(false);

            $name = '$' . $parameter->getName();
            $arguments[$name] = $logger;
        }
        return $arguments;
    }
}
