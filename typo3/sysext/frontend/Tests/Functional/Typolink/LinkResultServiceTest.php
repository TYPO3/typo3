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

namespace TYPO3\CMS\Frontend\Tests\Functional\Typolink;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\LinkResultService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LinkResultServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function getStateReturnsStateArrayForLinkResult(): void
    {
        $attributes = ['class' => 'my-class', 'title' => 'My Title', 'data-any' => 'value'];

        $subject = $this->get(LinkResultService::class);
        $linkResult = (new LinkResult(LinkService::TYPE_URL, 'https://example.com'))
            ->withFlags(LinkResult::STRING_CAST_JSON)
            ->withTarget('_blank')
            ->withLinkText('Example')
            ->withAttributes($attributes);

        $state = $subject->getState($linkResult);

        self::assertSame(LinkResult::STRING_CAST_JSON, $state['flags']);
        self::assertSame(LinkResult::class, $state['className']);
        self::assertSame(LinkService::TYPE_URL, $state['type']);
        self::assertSame('https://example.com', $state['url']);
        self::assertSame('_blank', $state['target']);
        self::assertSame('Example', $state['linkText']);
        self::assertSame($attributes, $state['additionalAttributes']);
    }

    #[Test]
    public function getStateThrowsExceptionForNonStateInterface(): void
    {
        $subject = $this->get(LinkResultService::class);
        $linkResult = new class () implements LinkResultInterface {
            public function getUrl(): string
            {
                return '';
            }
            public function getType(): string
            {
                return '';
            }
            public function getTarget(): string
            {
                return '';
            }
            public function getLinkConfiguration(): array
            {
                return [];
            }
            public function getLinkText(): ?string
            {
                return null;
            }
            public function withLinkText(string $linkText): LinkResultInterface
            {
                return $this;
            }
            public function withTarget(string $target): LinkResultInterface
            {
                return $this;
            }
            public function withAttributes(array $additionalAttributes, bool $resetExistingAttributes = false): LinkResultInterface
            {
                return $this;
            }
            public function withAttribute(string $attributeName, ?string $attributeValue): LinkResultInterface
            {
                return $this;
            }
            public function hasAttribute(string $attributeName): bool
            {
                return false;
            }
            public function getAttribute(string $attributeName): ?string
            {
                return null;
            }
            public function getAttributes(): array
            {
                return [];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1769791714);

        $subject->getState($linkResult);
    }

    #[Test]
    public function fromStateReconstructsLinkResult(): void
    {
        $subject = $this->get(LinkResultService::class);
        $state = [
            'className' => LinkResult::class,
            'type' => LinkService::TYPE_PAGE,
            'url' => '/my-page',
            'target' => '_self',
            'additionalAttributes' => ['class' => 'my-link', 'title' => 'My Title', 'data-any' => 'value'],
            'linkText' => 'My Page',
            'linkConfiguration' => ['parameter' => '42'],
            'flags' => LinkResult::STRING_CAST_JSON,
        ];

        $linkResult = $subject->fromState($state);
        $expectedJson = [
            'href' => '/my-page',
            'target' => '_self',
            'class' => 'my-link',
            'title' => 'My Title',
            'linkText' => 'My Page',
            'additionalAttributes' => ['data-any' => 'value'],
        ];

        self::assertInstanceOf(LinkResult::class, $linkResult);
        self::assertSame(LinkService::TYPE_PAGE, $linkResult->getType());
        self::assertSame('/my-page', $linkResult->getUrl());
        self::assertSame('_self', $linkResult->getTarget());
        self::assertSame('My Page', $linkResult->getLinkText());
        self::assertSame('my-link', $linkResult->getAttribute('class'));
        self::assertSame(['parameter' => '42'], $linkResult->getLinkConfiguration());
        self::assertSame($expectedJson, json_decode((string)$linkResult, true));
    }

    #[Test]
    public function fromStateThrowsExceptionForMissingClassName(): void
    {
        $subject = $this->get(LinkResultService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1769678520);

        $subject->fromState(['type' => 'url', 'url' => 'https://example.com']);
    }

    #[Test]
    public function fromStateThrowsExceptionForInvalidClassName(): void
    {
        $subject = $this->get(LinkResultService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1769678520);

        $subject->fromState(['className' => 'NonExistentClass']);
    }

    #[Test]
    public function stateRoundTripPreservesData(): void
    {
        $subject = $this->get(LinkResultService::class);
        $original = (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:test@example.com'))
            ->withTarget('')
            ->withLinkText('Contact Us')
            ->withLinkConfiguration(['parameter' => 'test@example.com'])
            ->withAttribute('class', 'email-link')
            ->withAttribute('title', 'Send email');

        $state = $subject->getState($original);
        $restored = $subject->fromState($state);

        self::assertSame($original->getType(), $restored->getType());
        self::assertSame($original->getUrl(), $restored->getUrl());
        self::assertSame($original->getTarget(), $restored->getTarget());
        self::assertSame($original->getLinkText(), $restored->getLinkText());
        self::assertSame($original->getLinkConfiguration(), $restored->getLinkConfiguration());
        self::assertSame($original->getAttribute('class'), $restored->getAttribute('class'));
        self::assertSame($original->getAttribute('title'), $restored->getAttribute('title'));
    }
}
