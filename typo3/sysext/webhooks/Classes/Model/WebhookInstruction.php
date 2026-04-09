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
 * DTO for an instruction - that is a representation of a configured
 * webhook to a specific remote - contains all configuration information.
 *
 * What type of message should be sent (WebhookType), where should it be sent
 * to and what additional headers etc. should be sent.
 */
readonly class WebhookInstruction
{
    public function __construct(
        private string $url,
        private string $secret,
        private string $method = 'POST',
        private bool $verifySSL = true,
        private array $additionalHeaders = [],
        private ?string $name = null,
        private ?string $description = null,
        private ?WebhookType $webhookType = null,
        private ?string $identifier = null,
        private ?int $uid = null,
        private array $row = []
    ) {}

    public function getUid(): int
    {
        return $this->uid ?? 0;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function getWebhookType(): ?WebhookType
    {
        return $this->webhookType;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getTargetUrl(): string
    {
        return $this->url;
    }

    public function getHttpMethod(): string
    {
        return strtoupper($this->method);
    }

    public function verifySSL(): bool
    {
        return $this->verifySSL;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getAdditionalHeaders(): ?array
    {
        return $this->additionalHeaders;
    }

    /**
     * @internal
     */
    public function getRow(): array
    {
        return $this->row;
    }
}
