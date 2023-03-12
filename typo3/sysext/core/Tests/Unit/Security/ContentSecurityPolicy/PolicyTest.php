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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PolicyTest extends UnitTestCase
{
    private Nonce $nonce;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nonce = Nonce::create();
    }

    /**
     * @test
     */
    public function constructorSetsdefaultDirective(): void
    {
        $policy = (new Policy(SourceKeyword::self));
        self::assertSame("default-src 'self'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function defaultDirectiveIsModified(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->default(SourceKeyword::none);
        self::assertSame("default-src 'none'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function defaultDirectiveConsidersVeto(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->default(SourceKeyword::unsafeEval, SourceKeyword::none);
        self::assertSame("default-src 'none'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function newDirectiveExtendsDefault(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline);
        self::assertSame("default-src 'self'; script-src 'self' 'unsafe-inline'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function newDirectiveDoesNotExtendDefault(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->set(Directive::ScriptSrc, SourceKeyword::unsafeInline);
        self::assertSame("default-src 'self'; script-src 'unsafe-inline'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function directiveIsReduced(): void
    {
        $policy = (new Policy())
            ->set(Directive::ScriptSrc, SourceKeyword::self, SourceKeyword::unsafeInline)
            ->reduce(Directive::ScriptSrc, SourceKeyword::self);
        self::assertSame("script-src 'unsafe-inline'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function sourceSchemeIsCompiled(): void
    {
        $policy = (new Policy(SourceKeyword::self, SourceScheme::blob));
        self::assertSame("default-src 'self' blob:", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function nonceProxyIsCompiled(): void
    {
        $policy = (new Policy(SourceKeyword::self, SourceKeyword::nonceProxy));
        self::assertSame("default-src 'self' 'nonce-{$this->nonce->b64}'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function hashValueIsCompiled(): void
    {
        $hash = hash('sha256', 'test', true);
        $hashB64 = base64_encode($hash);
        $policy = (new Policy())->extend(Directive::ScriptSrc, HashValue::create($hash));
        self::assertSame("script-src 'sha256-{$hashB64}'", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function directiveIsRemoved(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->remove(Directive::DefaultSrc);
        self::assertSame('', $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function superfluousDirectivesArePurged(): void
    {
        $policy = (new Policy(SourceKeyword::self, SourceScheme::data))
            ->set(Directive::ScriptSrc, SourceKeyword::self, SourceScheme::data);
        self::assertSame("default-src 'self' data:", $policy->compile($this->nonce));
    }

    /**
     * @test
     */
    public function backendPolicyIsCompiled(): void
    {
        $policy = (new Policy())
            ->default(SourceKeyword::self)
            ->extend(Directive::ScriptSrc, SourceKeyword::nonceProxy)
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline)
            ->set(Directive::StyleSrcAttr, SourceKeyword::unsafeInline)
            ->extend(Directive::ImgSrc, SourceScheme::data)
            ->set(Directive::WorkerSrc, SourceKeyword::self, SourceScheme::blob)
            ->extend(Directive::FrameSrc, SourceScheme::blob);
        self::assertSame(
            "default-src 'self'; script-src 'self' 'nonce-{$this->nonce->b64}'; "
            . "style-src 'self' 'unsafe-inline'; style-src-attr 'unsafe-inline'; "
            . "img-src 'self' data:; worker-src 'self' blob:; frame-src 'self' blob:",
            $policy->compile($this->nonce)
        );
    }

    /**
     * @test
     */
    public function containedDirectiveSourcesAreDetermined(): void
    {
        $policy = (new Policy())
            ->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline, SourceScheme::data, new UriValue('https://example.org'))
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline, SourceScheme::blob, new UriValue('https://example.com/path'));

        self::assertTrue($policy->containsDirective(Directive::ScriptSrc, SourceScheme::data, new UriValue('https://example.org')));
        self::assertTrue($policy->containsDirective(Directive::StyleSrc, SourceScheme::blob, new UriValue('https://example.com/path')));
        self::assertFalse($policy->containsDirective(Directive::ConnectSrc, SourceScheme::https));
    }

    /**
     * @test
     */
    public function coveredDirectiveSourcesAreDetermined(): void
    {
        $policy = (new Policy())
            ->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline, SourceScheme::data, new UriValue('*.example.org'))
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline, SourceScheme::blob, new UriValue('https://*.example.com'));

        self::assertTrue($policy->coversDirective(Directive::ScriptSrc, SourceScheme::data, new UriValue('https://sub.example.org/path/file.js')));
        self::assertTrue($policy->coversDirective(Directive::StyleSrc, SourceScheme::blob, new UriValue('https://sub.example.com/path/file.css')));
        self::assertFalse($policy->coversDirective(Directive::ConnectSrc, SourceScheme::https));
    }

    /**
     * @test
     */
    public function containedPolicyIsDetermined(): void
    {
        $policy = (new Policy())
            ->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline, SourceScheme::data, new UriValue('https://example.org'))
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline, SourceScheme::blob, new UriValue('https://example.com/path'));
        $other = (new Policy())
            ->extend(Directive::ScriptSrc, SourceScheme::data, new UriValue('https://example.org'))
            ->extend(Directive::StyleSrc, SourceScheme::blob, new UriValue('https://example.com/path'));
        self::assertTrue($policy->contains($other));
        self::assertFalse($other->contains($policy));
    }
}
