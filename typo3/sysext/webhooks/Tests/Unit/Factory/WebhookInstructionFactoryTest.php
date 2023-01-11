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

namespace TYPO3\CMS\Webhooks\Tests\Unit\Factory;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Webhooks\Factory\WebhookInstructionFactory;
use TYPO3\CMS\Webhooks\Model\WebhookType;
use TYPO3\CMS\Webhooks\WebhookTypesRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class WebhookInstructionFactoryTest extends UnitTestCase
{
    /**
     * Simulate a tt_content record
     */
    protected array $mockRecord = [
        'url' => 'https://example.com',
        'secret' => 'a random secret',
        'method' => 'POST',
        'verify_ssl' => true,
        'additional_headers' => [
            'X-My-Header' => 'My Header Value',
        ],
        'name' => 'My Webhook',
        'description' => 'My Webhook Description',
        'webhook_type' => null,
        'identifier' => '033c049f-7762-4755-b072-805350a8726a',
        'uid' => 200413,
    ];

    protected WebhookType $webhookType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->webhookType = new WebhookType('typo3/test-webhook', 'My WebhookType description', 'My\Webhook\Type', 'myFactoryMethod');
    }

    /**
     * @test
     */
    public function createWebhookInstructionWithMinimalData(): void
    {
        $webhookInstruction = WebhookInstructionFactory::create(
            $this->mockRecord['url'],
            $this->mockRecord['secret'],
        );
        self::assertSame($this->mockRecord['url'], $webhookInstruction->getTargetUrl());
        self::assertSame($this->mockRecord['secret'], $webhookInstruction->getSecret());
        self::assertSame('POST', $webhookInstruction->getHttpMethod());
        self::assertTrue($webhookInstruction->verifySSL());
        self::assertSame([], $webhookInstruction->getAdditionalHeaders());
        self::assertSame('', $webhookInstruction->getName());
        self::assertSame('', $webhookInstruction->getDescription());
        self::assertNull($webhookInstruction->getWebhookType());
        self::assertNull($webhookInstruction->getIdentifier());
        self::assertSame(0, $webhookInstruction->getUid());
    }

    /**
     * @test
     */
    public function createWebhookInstructionWithAllData(): void
    {
        $this->mockRecord['webhook_type'] = $this->webhookType;
        $webhookInstruction = WebhookInstructionFactory::create(
            $this->mockRecord['url'],
            $this->mockRecord['secret'],
            $this->mockRecord['method'],
            $this->mockRecord['verify_ssl'],
            $this->mockRecord['additional_headers'],
            $this->mockRecord['name'],
            $this->mockRecord['description'],
            $this->mockRecord['webhook_type'],
            $this->mockRecord['identifier'],
            $this->mockRecord['uid'],
        );
        self::assertSame($this->mockRecord['url'], $webhookInstruction->getTargetUrl());
        self::assertSame($this->mockRecord['secret'], $webhookInstruction->getSecret());
        self::assertSame('POST', $webhookInstruction->getHttpMethod());
        self::assertTrue($webhookInstruction->verifySSL());
        self::assertSame([ 'X-My-Header' => 'My Header Value'], $webhookInstruction->getAdditionalHeaders());
        self::assertSame('My Webhook', $webhookInstruction->getName());
        self::assertSame('My Webhook Description', $webhookInstruction->getDescription());
        self::assertSame($this->webhookType, $webhookInstruction->getWebhookType());
        self::assertSame($this->webhookType->getIdentifier(), $webhookInstruction->getWebhookType()->getIdentifier());
        self::assertSame($this->webhookType->getDescription(), $webhookInstruction->getWebhookType()->getDescription());
        self::assertSame('033c049f-7762-4755-b072-805350a8726a', $webhookInstruction->getIdentifier());
        self::assertSame(200413, $webhookInstruction->getUid());
    }

    /**
     * @test
     */
    public function createWebhookInstructionFromRow(): void
    {
        $webhookTypesRegistryMock = $this->createMock(WebhookTypesRegistry::class);
        $webhookTypesRegistryMock
            ->method('getWebhookByType')
            ->with($this->webhookType->getIdentifier())
            ->willReturn($this->webhookType);

        GeneralUtility::addInstance(WebhookTypesRegistry::class, $webhookTypesRegistryMock);

        $this->mockRecord['webhook_type'] = 'typo3/test-webhook';
        $webhookInstruction = WebhookInstructionFactory::createFromRow($this->mockRecord);
        self::assertSame($this->mockRecord['url'], $webhookInstruction->getTargetUrl());
        self::assertSame($this->mockRecord['url'], $webhookInstruction->getTargetUrl());
        self::assertSame($this->mockRecord['secret'], $webhookInstruction->getSecret());
        self::assertSame('POST', $webhookInstruction->getHttpMethod());
        self::assertTrue($webhookInstruction->verifySSL());
        self::assertSame([ 'X-My-Header' => 'My Header Value'], $webhookInstruction->getAdditionalHeaders());
        self::assertSame('My Webhook', $webhookInstruction->getName());
        self::assertSame('My Webhook Description', $webhookInstruction->getDescription());
        self::assertSame($this->webhookType, $webhookInstruction->getWebhookType());
        self::assertSame($this->webhookType->getIdentifier(), $webhookInstruction->getWebhookType()->getIdentifier());
        self::assertSame($this->webhookType->getDescription(), $webhookInstruction->getWebhookType()->getDescription());
        self::assertSame('033c049f-7762-4755-b072-805350a8726a', $webhookInstruction->getIdentifier());
        self::assertSame(200413, $webhookInstruction->getUid());
    }
}
