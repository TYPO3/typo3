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

namespace TYPO3\CMS\Extbase\Event\Mvc;

/**
 * Event that is triggered before any Extbase Action is called within the ActionController or one
 * of its subclasses.
 */
final class BeforeActionCallEvent
{
    public function __construct(
        private readonly string $controllerClassName,
        private readonly string $actionMethodName,
        private readonly array $preparedArguments
    ) {
    }

    public function getControllerClassName(): string
    {
        return $this->controllerClassName;
    }

    public function getActionMethodName(): string
    {
        return $this->actionMethodName;
    }

    public function getPreparedArguments(): array
    {
        return $this->preparedArguments;
    }
}
