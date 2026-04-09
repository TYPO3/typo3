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

namespace TYPO3\CMS\Webhooks\Model;

/**
 * @internal not part of TYPO3's Core API
 */
readonly class WebhookType
{
    public function __construct(
        protected string $identifier,
        protected string $description,
        protected string $serviceName,
        protected string $factoryMethodName,
        protected ?string $connectedEvent = null
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getFactoryMethod(): string
    {
        return $this->serviceName . '::' . $this->factoryMethodName;
    }

    public function getConnectedEvent(): ?string
    {
        return $this->connectedEvent;
    }
}
