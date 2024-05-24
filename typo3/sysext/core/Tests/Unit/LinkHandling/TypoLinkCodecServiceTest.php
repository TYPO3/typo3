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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\LinkHandling\Event\AfterTypoLinkDecodedEvent;
use TYPO3\CMS\Core\LinkHandling\Event\BeforeTypoLinkEncodedEvent;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TypoLinkCodecServiceTest extends UnitTestCase
{
    protected TypoLinkCodecService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TypoLinkCodecService(new NoopEventDispatcher());
    }

    #[DataProvider('encodeReturnsExpectedResultDataProvider')]
    #[Test]
    public function encodeReturnsExpectedResult(array $parts, string $expected): void
    {
        self::assertSame($expected, $this->subject->encode($parts));
    }

    public static function encodeReturnsExpectedResultDataProvider(): array
    {
        return [
            'empty input' => [
                [
                    'url' => '',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
                '',
            ],
            'full parameter usage' => [
                [
                    'url' => '19',
                    'target' => '_blank',
                    'class' => 'css-class',
                    'title' => 'testtitle with whitespace',
                    'additionalParams' => '&x=y',
                ],
                '19 _blank css-class "testtitle with whitespace" &x=y',
            ],
            'crazy title and partial items only' => [
                [
                    'url' => 'foo',
                    'title' => 'a "link\\ ti\\"tle',
                ],
                'foo - - "a \\"link\\\\ ti\\\\\\"tle"',
            ],
        ];
    }

    #[DataProvider('decodeReturnsExpectedResultDataProvider')]
    #[Test]
    public function decodeReturnsExpectedResult(string $typoLink, array $expected): void
    {
        self::assertSame($expected, $this->subject->decode($typoLink));
    }

    public static function decodeReturnsExpectedResultDataProvider(): array
    {
        return [
            'empty input' => [
                '',
                [
                    'url' => '',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'simple id input' => [
                '19',
                [
                    'url' => '19',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'external url with target' => [
                'www.web.de _blank',
                [
                    'url' => 'www.web.de',
                    'target' => '_blank',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'page with class' => [
                '42 - css-class',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => 'css-class',
                    'title' => '',
                    'additionalParams' => '',
                ],
            ],
            'page with title' => [
                '42 - - "a link title"',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '',
                ],
            ],
            'page with crazy title' => [
                '42 - - "a \\"link\\\\ ti\\\\\\"tle"',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a "link\\ ti\\"tle',
                    'additionalParams' => '',
                ],
            ],
            'page with title and parameters' => [
                '42 - - "a link title" &x=y',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a link title',
                    'additionalParams' => '&x=y',
                ],
            ],
            'page with complex title' => [
                '42 - - "a \\"link\\" title with \\\\" &x=y',
                [
                    'url' => '42',
                    'target' => '',
                    'class' => '',
                    'title' => 'a "link" title with \\',
                    'additionalParams' => '&x=y',
                ],
            ],
            'full parameter usage' => [
                '19 _blank css-class "testtitle with whitespace" &X=y',
                [
                    'url' => '19',
                    'target' => '_blank',
                    'class' => 'css-class',
                    'title' => 'testtitle with whitespace',
                    'additionalParams' => '&X=y',
                ],
            ],
        ];
    }

    #[Test]
    public function beforeTypoLinkEncodedEventIsCalled(): void
    {
        $beforeTypoLinkEncodedEvent = null;

        $container = new Container();
        $container->set(
            'before-typo-link-encoded-listener',
            static function (BeforeTypoLinkEncodedEvent $event) use (&$beforeTypoLinkEncodedEvent) {
                $beforeTypoLinkEncodedEvent = $event;
                $beforeTypoLinkEncodedEvent->setParameters(['foo', 'bar']);
            }
        );

        $listenerProvider = new ListenerProvider($container);
        $listenerProvider->addListener(BeforeTypoLinkEncodedEvent::class, 'before-typo-link-encoded-listener');

        $result = (new TypoLinkCodecService(new EventDispatcher($listenerProvider)))->encode([
            'url' => 'https://example.com',
        ]);

        self::assertEquals('bar foo', $result);
        self::assertInstanceOf(BeforeTypoLinkEncodedEvent::class, $beforeTypoLinkEncodedEvent);
        self::assertEquals(['url' => 'https://example.com'], $beforeTypoLinkEncodedEvent->getTypoLinkParts());
        self::assertEquals(['foo', 'bar'], $beforeTypoLinkEncodedEvent->getParameters());
    }

    #[Test]
    public function afterTypoLinkDecodedEventIsCalled(): void
    {
        $afterTypoLinkDecodedEvent = null;

        $container = new Container();
        $container->set(
            'after-typo-link-decoded-listener',
            static function (AfterTypoLinkDecodedEvent $event) use (&$afterTypoLinkDecodedEvent) {
                $afterTypoLinkDecodedEvent = $event;
                $afterTypoLinkDecodedEvent->setTypoLinkParts(
                    array_merge($afterTypoLinkDecodedEvent->getTypoLinkParts(), ['foo' => 'bar'])
                );
            }
        );

        $listenerProvider = new ListenerProvider($container);
        $listenerProvider->addListener(AfterTypoLinkDecodedEvent::class, 'after-typo-link-decoded-listener');

        $result = (new TypoLinkCodecService(new EventDispatcher($listenerProvider)))->decode('https://example.com');

        $expected = [
            'url' => 'https://example.com',
            'target' => '',
            'class' => '',
            'title' => '',
            'additionalParams' => '',
            'foo' => 'bar',
        ];

        self::assertEquals($expected, $result);
        self::assertInstanceOf(AfterTypoLinkDecodedEvent::class, $afterTypoLinkDecodedEvent);
        self::assertEquals('https://example.com', $afterTypoLinkDecodedEvent->getTypoLink());
        self::assertEquals($expected, $afterTypoLinkDecodedEvent->getTypoLinkParts());
    }
}
