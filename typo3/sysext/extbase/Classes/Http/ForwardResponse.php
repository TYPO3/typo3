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

namespace TYPO3\CMS\Extbase\Http;

use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Extbase\Error\Result;

class ForwardResponse extends Response
{
    private string $actionName;
    private ?string $controllerName = null;
    private ?string $extensionName = null;
    private ?array $arguments = null;
    private Result $argumentsValidationResult;

    public function __construct(string $actionName)
    {
        $this->actionName = $actionName;
        $this->argumentsValidationResult = new Result();
        parent::__construct('php://temp', 204);
    }

    public function withControllerName(string $controllerName): self
    {
        $clone = clone $this;
        $clone->controllerName = $controllerName;
        return $clone;
    }

    public function withoutControllerName(): self
    {
        $clone = clone $this;
        $clone->controllerName = null;
        return $clone;
    }

    public function withExtensionName(string $extensionName): self
    {
        $clone = clone $this;
        $clone->extensionName = $extensionName;
        return $clone;
    }

    public function withoutExtensionName(): self
    {
        $clone = clone $this;
        $this->extensionName = null;
        return $clone;
    }

    public function withArguments(array $arguments): self
    {
        $clone = clone $this;
        $clone->arguments = $arguments;
        return $clone;
    }

    public function withoutArguments(): self
    {
        $clone = clone $this;
        $this->arguments = null;
        return $clone;
    }

    public function withArgumentsValidationResult(Result $argumentsValidationResult): self
    {
        $clone = clone $this;
        $clone->argumentsValidationResult = $argumentsValidationResult;
        return $clone;
    }

    public function getActionName(): string
    {
        return $this->actionName;
    }

    public function getControllerName(): ?string
    {
        return $this->controllerName;
    }

    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }

    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    public function getArgumentsValidationResult(): Result
    {
        return $this->argumentsValidationResult;
    }
}
