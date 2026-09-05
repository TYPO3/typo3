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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Error\Result;

class ForwardResponse extends Response
{
    private ?string $controllerName = null;
    private ?string $extensionName = null;
    private ?array $arguments = null;
    private Result $argumentsValidationResult;
    /**
     * @var list<FlashMessage>
     */
    private array $flashMessages = [];

    public function __construct(private readonly string $actionName)
    {
        $this->argumentsValidationResult = new Result();
        parent::__construct('php://temp', 204);
    }

    public function withControllerName(string $controllerName): self
    {
        return clone($this, ['controllerName' => $controllerName]);
    }

    public function withoutControllerName(): self
    {
        return clone($this, ['controllerName' => null]);
    }

    public function withExtensionName(string $extensionName): self
    {
        return clone($this, ['extensionName' => $extensionName]);
    }

    public function withoutExtensionName(): self
    {
        $clone = clone $this;
        $this->extensionName = null;
        return $clone;
    }

    public function withArguments(array $arguments): self
    {
        return clone($this, ['arguments' => $arguments]);
    }

    public function withoutArguments(): self
    {
        $clone = clone $this;
        $this->arguments = null;
        return $clone;
    }

    public function withArgumentsValidationResult(Result $argumentsValidationResult): self
    {
        return clone($this, ['argumentsValidationResult' => $argumentsValidationResult]);
    }

    public function withFlashMessages(FlashMessage ...$flashMessages): self
    {
        if ($flashMessages === []) {
            return $this;
        }
        return clone($this, ['flashMessages' => array_merge($this->flashMessages, $flashMessages)]);
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

    /**
     * @return list<FlashMessage>
     */
    public function getFlashMessages(): array
    {
        return $this->flashMessages;
    }
}
