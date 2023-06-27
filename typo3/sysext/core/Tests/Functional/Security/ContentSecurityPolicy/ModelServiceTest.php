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

namespace TYPO3\CMS\Core\Tests\Functional\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashProxy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ModelService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\RawValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ModelServiceTest extends FunctionalTestCase
{
    private ModelService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(ModelService::class);
    }

    protected function tearDown(): void
    {
        unset($this->subject);
    }

    public static function enumSourceInterfaceIsBuiltFromStringDataProvider(): \Generator
    {
        yield 'nonce-proxy' => ["'nonce-anything'", SourceKeyword::nonceProxy];
        yield 'none' => ["'none'", SourceKeyword::none];
        yield 'self' => ["'self'", SourceKeyword::self];
        yield 'strict-dynamic' => ["'strict-dynamic'", SourceKeyword::strictDynamic];
        yield 'unsafe-inline' => ["'unsafe-inline'", SourceKeyword::unsafeInline];
        yield 'unsafe-eval' => ["'unsafe-eval'", SourceKeyword::unsafeEval];

        yield 'blob:' => ['blob:', SourceScheme::blob];
        yield 'data:' => ['data:', SourceScheme::data];
        yield 'https:' => ['https:', SourceScheme::https];
        yield 'wss:' => ['wss:', SourceScheme::wss];
    }

    /**
     * @test
     * @dataProvider enumSourceInterfaceIsBuiltFromStringDataProvider
     */
    public function enumSourceInterfaceIsBuiltFromString(string $string, SourceInterface $expectation): void
    {
        self::assertSame($expectation, $this->subject->buildSourceFromString($string));
    }

    /**
     * @test
     */
    public function uriValueIsBuiltFromString(): void
    {
        $uri = 'https://*.example.org/';
        $source = $this->subject->buildSourceFromString($uri);
        self::assertInstanceOf(UriValue::class, $source);
        self::assertSame($uri, (string)$source);
    }

    /**
     * @test
     */
    public function rawValueIsBuiltFromString(): void
    {
        $value = 'https:////slashes.example.org';
        $source = $this->subject->buildSourceFromString($value);
        self::assertInstanceOf(RawValue::class, $source);
        self::assertSame($value, (string)$source);
    }

    /**
     * @test
     */
    public function hashValueIsBuiltFromString(): void
    {
        $hash = hash('sha256', 'test', true);
        $hashB64 = base64_encode($hash);
        $value = sprintf("'sha256-%s'", $hashB64);
        $source = $this->subject->buildSourceFromString($value);
        self::assertInstanceOf(HashValue::class, $source);
        self::assertSame($hashB64, $source->value);
    }

    /**
     * @test
     */
    public function urlHashProxyIsBuiltFromString(): void
    {
        $url = 'https://example.org/file.js';
        $value = '\'hash-proxy-{"type":"sha256","urls":["' . $url . '"]}\'';
        $source = $this->subject->buildSourceFromString($value);

        self::assertInstanceOf(HashProxy::class, $source);
        $object = new \ReflectionObject($source);
        $property = $object->getProperty('urls');
        self::assertSame($url, $property->getValue($source)[0] ?? null);
    }
}
