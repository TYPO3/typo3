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

namespace TYPO3\CMS\PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<CallLike>
 * @see \TYPO3\CMS\PHPStan\Tests\Rules\Classes\NamedArgumentUsageRule\NamedArgumentUsageRuleTest
 */
final readonly class NamedArgumentUsageRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private array $allowedNamespaces = [],
    ) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ((!$node instanceof MethodCall && !$node instanceof StaticCall)
            || !$this->hasNamedArgument($node)
            || $this->isInAllowedNamespace($scope)
        ) {
            return [];
        }

        // Resolve the method definition: Calculate which default parameters are implicitly skipped
        $methodReflection = null;
        $calledClassName = null;
        if ($node instanceof MethodCall) {
            $varType = $scope->getType($node->var);
            $methodReflection = $scope->getMethodReflection($varType, $node->name->toString());
            $calledClassName = $methodReflection?->getDeclaringClass()->getName();
        } elseif ($node->class instanceof Name) {
            $className = $node->class->toString();
            if ($this->reflectionProvider->hasClass($className)) {
                $classReflection = $this->reflectionProvider->getClass($className);
                if ($classReflection->hasMethod($node->name->toString())) {
                    $methodReflection = $classReflection->getMethod($node->name->toString(), $scope);
                    $calledClassName = $methodReflection->getDeclaringClass()->getName();
                }
            }
        }

        if ($methodReflection === null || $calledClassName === null) {
            return [];
        }

        $methodName = $node->name->toString();
        $parameters = $methodReflection->getVariants()[0]->getParameters();
        $args = $node->getArgs();
        $skippedParameters = [];

        // Map provided arguments
        $argsByName = [];
        $argsByPosition = [];

        foreach ($args as $index => $arg) {
            if ($arg->name instanceof Identifier) {
                $argsByName[$arg->name->toString()] = $arg;
            } else {
                $argsByPosition[$index] = $arg;
            }
        }

        // Check for skips in parameter list
        foreach ($parameters as $position => $parameter) {
            $parameterName = $parameter->getName();
            // Check if provided by name
            if (isset($argsByName[$parameterName])) {
                continue;
            }
            // Check if provided by position
            if (isset($argsByPosition[$position])) {
                continue;
            }
            // If not provided, it is skipped using a default value
            $skippedParameters[] = $parameterName;
        }

        $message = sprintf(
            'Method call %s::%s() uses named arguments.',
            $calledClassName,
            $methodName
        );
        if ($skippedParameters !== []) {
            $message .= sprintf(
                ' It skips the following optional parameters: %s.',
                implode(', ', $skippedParameters)
            );
        }
        return [
            RuleErrorBuilder::message($message)->identifier('custom.namedArguments')->build(),
        ];
    }

    private function hasNamedArgument(CallLike $node): bool
    {
        foreach ($node->getArgs() as $arg) {
            if ($arg->name !== null) {
                return true;
            }
        }
        return false;
    }

    private function isInAllowedNamespace(Scope $scope): bool
    {
        if ($this->allowedNamespaces === []) {
            return false;
        }
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            // Not in a class context, check the namespace directly
            $namespace = $scope->getNamespace();
            if ($namespace === null) {
                return false;
            }
            foreach ($this->allowedNamespaces as $allowedNamespace) {
                if (str_starts_with($namespace, $allowedNamespace)) {
                    return true;
                }
            }
            return false;
        }
        $className = $classReflection->getName();
        foreach ($this->allowedNamespaces as $allowedNamespace) {
            if (str_starts_with($className, $allowedNamespace)) {
                return true;
            }
        }
        return false;
    }
}
