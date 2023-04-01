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
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Method
{
    private array $definition;
    private array $parameters = [];

    public function __construct(
        private readonly string $name,
        array $definition,
        private readonly string $className,
    ) {
        $defaults = [
            'params' => [],
            'public' => false,
            'protected' => false,
            'private' => false,
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
}
