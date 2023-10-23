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

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema;

use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodParameterException;

/**
 * Class TYPO3\CMS\Extbase\Reflection\ClassSchema\Property
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Method
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $definition;

    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $parameters = [];

    public function __construct(string $name, array $definition, string $className)
    {
        $this->name = $name;
        $this->className = $className;

        $defaults = [
            'params' => [],
            'public' => false,
            'protected' => false,
            'private' => false,
            'injectMethod' => false,
            'static' => false,
        ];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($definition[$key])) {
                $definition[$key] = $defaultValue;
            }
        }

        $this->definition = $definition;

        foreach ($this->definition['params'] as $parameterName => $parameterDefinition) {
            $this->parameters[$parameterName] = new MethodParameter($parameterName, $parameterDefinition);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array|MethodParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @throws NoSuchMethodParameterException
     */
    public function getFirstParameter(): MethodParameter
    {
        $position = 0;

        $parameters = array_filter(
            $this->getParameters(),
            static fn(MethodParameter $parameter): bool => $parameter->getPosition() === $position
        );

        $parameter = reset($parameters);

        if (!$parameter instanceof MethodParameter) {
            throw NoSuchMethodParameterException::createForParameterPosition(
                $this->className,
                $this->name,
                $position
            );
        }

        return $parameter;
    }

    /**
     * @throws NoSuchMethodParameterException
     */
    public function getParameter(string $parameterName): MethodParameter
    {
        if (!isset($this->parameters[$parameterName])) {
            throw NoSuchMethodParameterException::createForParameterName(
                $this->className,
                $this->name,
                $parameterName
            );
        }

        return $this->parameters[$parameterName];
    }

    public function isPublic(): bool
    {
        return $this->definition['public'];
    }

    public function isProtected(): bool
    {
        return $this->definition['protected'];
    }

    public function isPrivate(): bool
    {
        return $this->definition['private'];
    }

    public function isInjectMethod(): bool
    {
        return $this->definition['injectMethod'];
    }

    public function isStatic(): bool
    {
        return $this->definition['static'];
    }
}
