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

namespace TYPO3\CMS\Core\Tests\Functional\Html;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Html\DefaultSanitizerBuilder;
use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\CMS\Core\Html\SanitizerInitiator;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Log\DummyWriter;
use TYPO3\CMS\Core\Tests\Functional\Html\Fixtures\ExtendedSanitizerBuilder;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DefaultSanitizerBuilderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $configurationToUseInTestInstance = [
        'LOG' => [
            'TYPO3' => [
                'HtmlSanitizer' => [
                    'writerConfiguration' => [
                        LogLevel::DEBUG => [
                            DummyWriter::class => [],
                        ],
                    ],
                ],
            ],
        ],
    ];

    protected function tearDown(): void
    {
        parent::tearDown();
        DummyWriter::$logs = [];
    }

    public static function isSanitizedDataProvider(): array
    {
        return [
            '#010' => [
                '<unknown unknown="unknown">value</unknown>',
                '&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;',
            ],
            '#011' => [
                '<div class="nested"><unknown unknown="unknown">value</unknown></div>',
                '<div class="nested">&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;</div>',
            ],
            '#012' => [
                '&lt;script&gt;alert(1)&lt;/script&gt;',
                '&lt;script&gt;alert(1)&lt;/script&gt;',
            ],
            // @todo bug in https://github.com/Masterminds/html5-php/issues
            // '#013' => [
            //    '<strong>Given that x < y and y > z...</strong>',
            //    '<strong>Given that x &lt; y and y &gt; z...</strong>',
            // ],
            '#020' => [
                '<div unknown="unknown">value</div>',
                '<div>value</div>',
            ],
            '#030' => [
                '<div class="class">value</div>',
                '<div class="class">value</div>',
            ],
            '#031' => [
                '<div data-value="value">value</div>',
                '<div data-value="value">value</div>',
            ],
            '#032' => [
                '<div data-bool>value</div>',
                '<div data-bool>value</div>',
            ],
            '#040' => [
                '<img src="mailto:noreply@typo3.org" onerror="alert(1)">',
                '',
            ],
            '#041' => [
                '<img src="https://typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="https://typo3.org/logo.svg">',
            ],
            '#042' => [
                '<img src="http://typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="http://typo3.org/logo.svg">',
            ],
            '#043' => [
                '<img src="/typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="/typo3.org/logo.svg">',
            ],
            '#044' => [
                '<img src="typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="typo3.org/logo.svg">',
            ],
            '#045' => [
                '<img src="//typo3.org/logo.svg" onerror="alert(1)">',
                '',
            ],
            '#050' => [
                '<a href="https://typo3.org/" role="button">value</a>',
                '<a href="https://typo3.org/" role="button">value</a>',
            ],
            '#051' => [
                '<a href="ssh://example.org/" role="button">value</a>',
                '<a role="button">value</a>',
            ],
            '#052' => [
                '<a href="javascript:alert(1)" role="button">value</a>',
                '<a role="button">value</a>',
            ],
            '#053' => [
                '<a href="data:text/html;..." role="button">value</a>',
                '<a role="button">value</a>',
            ],
            '#054' => [
                '<a href="t3://page?uid=1" role="button">value</a>',
                '<a href="t3://page?uid=1" role="button">value</a>',
            ],
            '#055' => [
                '<a href="tel:123456789" role="button">value</a>',
                '<a href="tel:123456789" role="button">value</a>',
            ],
            '#090' => [
                '<p data-bool><span data-bool><strong data-bool>value</strong></span></p>',
                '<p data-bool><span data-bool><strong data-bool>value</strong></span></p>',
            ],
            // @todo `style` used in Introduction Package, inline CSS should be removed
            '#810' => [
                '<span style="color: orange">value</span>',
                '<span style="color: orange">value</span>',
            ],
            '#912' => [
                '<!---><p>',
                '<!---&gt;&lt;p&gt;-->',
            ],
            '#913' => [
                '<!---!><p>',
                '<!---!&gt;&lt;p&gt;-->',
            ],
            '#941' => [
                '<?xml >s<img src=x onerror=alert(1)> ?>',
                '&lt;?xml &gt;s&lt;img src=x onerror=alert(1)&gt; ?&gt;',
            ],
            '#951' => [
                '<span class="icon"><svg class="icon__svg" role="img" aria-hidden="true"><use href="#icon"></use></svg></span>',
                '<span class="icon"><svg class="icon__svg" role="img" aria-hidden="true"><use href="#icon" /></svg></span>',
            ],
            '#952' => [
                '<span class="icon"><svg><script>alert(1)</script></svg></span>',
                '<span class="icon"></span>',
            ],
        ];
    }

    #[DataProvider('isSanitizedDataProvider')]
    #[Test]
    public function isSanitized(string $payload, string $expectation): void
    {
        $factory = new SanitizerBuilderFactory();
        $builder = $factory->build('default');
        $sanitizer = $builder->build();
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }

    #[Test]
    public function behaviorIsCachedInMemory(): void
    {
        $default = new DefaultSanitizerBuilder();
        $defaultSanitizer = $default->build();
        $defaultBehavior = $this->resolveBehaviorFromSanitizer($defaultSanitizer);

        self::assertSame(
            $defaultBehavior,
            $this->resolveBehaviorFromSanitizer($default->build()),
            'in-memory caching failed for same scope DefaultSanitizerBuilder'
        );

        $extended = new ExtendedSanitizerBuilder();
        $extendedSanitizer = $extended->build();
        $extendedBehavior = $this->resolveBehaviorFromSanitizer($extendedSanitizer);

        self::assertSame(
            $extendedBehavior,
            $this->resolveBehaviorFromSanitizer($extended->build()),
            'in-memory caching failed for same scope ExtendedSanitizerBuilder'
        );

        self::assertNotSame(
            $defaultBehavior,
            $extendedBehavior,
            'in-memory cache violation for different scopes'
        );
    }

    #[Test]
    public function incidentIsLogged(): void
    {
        $trace = bin2hex(random_bytes(8));
        $sanitizer = (new DefaultSanitizerBuilder())->build();
        $sanitizer->sanitize('<script>alert(1)</script>', new SanitizerInitiator($trace));
        $logItemDataExpectation = [
            'behavior' => 'default',
            'nodeType' => 1,
            'nodeName' => 'script',
            'initiator' => $trace,
        ];
        $logItem = end(DummyWriter::$logs);
        self::assertInstanceOf(LogRecord::class, $logItem);
        self::assertSame($logItemDataExpectation, $logItem->getData());
        self::assertSame('TYPO3.HtmlSanitizer.Visitor.CommonVisitor', $logItem->getComponent());
    }

    private function resolveBehaviorFromSanitizer(Sanitizer $sanitizer): Behavior
    {
        $visitor = (new \ReflectionObject($sanitizer))
            ->getProperty('visitors')
            ->getValue($sanitizer)[0];
        return (new \ReflectionObject($visitor))
            ->getProperty('behavior')
            ->getValue($visitor);
    }
}
