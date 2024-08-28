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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashProxy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PolicyTest extends FunctionalTestCase
{
    private ConsumableNonce $nonce;
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nonce = new ConsumableNonce();
    }

    #[Test]
    public function hashProxyIsCompiled(): void
    {
        // ```
        // cat typo3/sysext/core/Tests/Unit/Security/ContentSecurityPolicy/Fixtures/app-fixture.js \
        //   | openssl dgst -binary -sha256 | openssl base64 -A
        // dawsv3oUbEz6NVoOxXFAu0k7W3I/PS6NucUIAmvoIng=
        // ```
        $hashProxy = HashProxy::glob(
            'EXT:core/Tests/Unit/Security/ContentSecurityPolicy/Fixtures/*.js'
        );
        $policy = (new Policy())->extend(Directive::ScriptSrc, $hashProxy);
        self::assertSame("script-src 'sha256-dawsv3oUbEz6NVoOxXFAu0k7W3I/PS6NucUIAmvoIng='", $policy->compile($this->nonce));
    }

    #[Test]
    public function hashValueIsCompiledUsingHashFactory(): void
    {
        $policy = (new Policy())->extend(Directive::ScriptSrc, HashValue::hash('test'));
        self::assertSame("script-src 'sha256-n4bQgYhMfWWaL+qgxVrQFaO/TxsrC4Is0V1sFbDwCgg='", $policy->compile($this->nonce));
    }

    #[Test]
    public function hashValueIsCompiledUsingCreateFactory(): void
    {
        $hash = hash('sha256', 'test', true);
        $policy = (new Policy())->extend(Directive::ScriptSrc, HashValue::create($hash));
        self::assertSame("script-src 'sha256-n4bQgYhMfWWaL+qgxVrQFaO/TxsrC4Is0V1sFbDwCgg='", $policy->compile($this->nonce));
    }

    #[Test]
    public function constructorSetsdefaultDirective(): void
    {
        $policy = (new Policy(SourceKeyword::self));
        self::assertSame("default-src 'self'", $policy->compile($this->nonce));
    }

    #[Test]
    public function defaultDirectiveIsModified(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->default(SourceKeyword::none);
        self::assertSame("default-src 'none'", $policy->compile($this->nonce));
    }

    #[Test]
    public function defaultDirectiveConsidersVeto(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->default(SourceKeyword::unsafeEval, SourceKeyword::none);
        self::assertSame("default-src 'none'", $policy->compile($this->nonce));
    }

    #[Test]
    public function newDirectiveExtendsDefault(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline);
        self::assertSame("default-src 'self'; script-src 'self' 'unsafe-inline'", $policy->compile($this->nonce));
    }

    #[Test]
    public function nonAncestorDirectiveDoesNotExtendDefault(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->extend(Directive::Sandbox)
            ->extend(Directive::TrustedTypes);
        self::assertSame("default-src 'self'; sandbox; trusted-types", $policy->compile($this->nonce));
    }

    public static function ancestorInheritanceIsAppliedFromMutationsDataProvider(): \Generator
    {
        yield 'script-src in inherited from default-src' => [
            new MutationCollection(
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self),
                new Mutation(MutationMode::InheritOnce, Directive::ScriptSrc),
                new Mutation(MutationMode::Append, Directive::ScriptSrc, SourceKeyword::unsafeInline),
            ),
            "default-src 'self'; script-src 'self' 'unsafe-inline'",
        ];
        yield 'script-src is inherited just once from default-src' => [
            new MutationCollection(
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self),
                new Mutation(MutationMode::InheritOnce, Directive::ScriptSrc),
                new Mutation(MutationMode::Append, Directive::ScriptSrc, SourceKeyword::unsafeInline),
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceScheme::data),
                new Mutation(MutationMode::InheritOnce, Directive::ScriptSrc),
            ),
            "default-src data:; script-src 'self' 'unsafe-inline'",
        ];
        yield 'script-src is inherited just once (via extend) from default-src' => [
            new MutationCollection(
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self),
                new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::unsafeInline),
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceScheme::data),
                new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::unsafeInline),
            ),
            "default-src data:; script-src 'self' 'unsafe-inline'",
        ];
        yield 'script-src is inherited again from default-src' => [
            new MutationCollection(
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self),
                new Mutation(MutationMode::InheritOnce, Directive::ScriptSrc),
                new Mutation(MutationMode::Append, Directive::ScriptSrc, SourceKeyword::unsafeInline),
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceScheme::data),
                new Mutation(MutationMode::InheritAgain, Directive::ScriptSrc),
            ),
            "default-src data:; script-src data: 'self' 'unsafe-inline'",
        ];
    }

    #[DataProvider('ancestorInheritanceIsAppliedFromMutationsDataProvider')]
    #[Test]
    public function ancestorInheritanceIsAppliedFromMutations(MutationCollection $mutations, string $expectation): void
    {
        $policy = (new Policy())->mutate($mutations);
        self::assertSame($expectation, $policy->compile($this->nonce));
    }

    #[Test]
    public function newDirectiveDoesNotExtendDefault(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->set(Directive::ScriptSrc, SourceKeyword::unsafeInline);
        self::assertSame("default-src 'self'; script-src 'unsafe-inline'", $policy->compile($this->nonce));
    }

    public static function directiveIsReducedDataProvider(): \Generator
    {
        yield 'one source remains' => [
            'defaultSources' => [SourceKeyword::self, SourceKeyword::unsafeInline],
            'reduceSources' => [SourceKeyword::self],
            'expectation' => "script-src 'unsafe-inline'",
        ];
        yield 'directive is purged' => [
            'defaultSources' => [SourceKeyword::self],
            'reduceSources' => [SourceKeyword::self],
            'expectation' => '',
        ];
    }

    /**
     * @param list<SourceInterface> $defaultSources
     * @param list<SourceInterface> $reduceSources
     */
    #[DataProvider('directiveIsReducedDataProvider')]
    #[Test]
    public function directiveIsReduced(array $defaultSources, array $reduceSources, string $expectation): void
    {
        $policy = (new Policy())
            ->set(Directive::ScriptSrc, ...$defaultSources)
            ->reduce(Directive::ScriptSrc, ...$reduceSources);
        self::assertSame($expectation, $policy->compile($this->nonce));
    }

    #[Test]
    public function sourceSchemeIsCompiled(): void
    {
        $policy = (new Policy(SourceKeyword::self, SourceScheme::blob));
        self::assertSame("default-src 'self' blob:", $policy->compile($this->nonce));
    }

    #[Test]
    public function nonceProxyIsCompiled(): void
    {
        $this->nonce->consume();
        $policy = (new Policy(SourceKeyword::self, SourceKeyword::nonceProxy));
        self::assertSame("default-src 'self' 'nonce-{$this->nonce->value}'", $policy->compile($this->nonce));
    }

    #[Test]
    public function nonceProxyIsOmittedIfNotConsumed(): void
    {
        $policy = (new Policy(SourceKeyword::self, SourceKeyword::nonceProxy));
        self::assertSame("default-src 'self'", $policy->compile($this->nonce));
    }

    /**
     * `strict-dynamic` is only allowed for `script-src*` and implicitly adds a `nonce-proxy`.
     */
    #[Test]
    public function strictDynamicIsApplied(): void
    {
        $this->nonce->consume();
        $policy = (new Policy(SourceKeyword::self, SourceKeyword::strictDynamic))
            ->extend(Directive::ScriptSrc, SourceKeyword::strictDynamic)
            ->extend(Directive::StyleSrc, SourceKeyword::strictDynamic);
        self::assertSame(
            "default-src 'self'; script-src 'self' 'strict-dynamic' 'nonce-{$this->nonce->value}'",
            $policy->compile($this->nonce)
        );
    }

    #[Test]
    public function directiveIsRemoved(): void
    {
        $policy = (new Policy(SourceKeyword::self))
            ->remove(Directive::DefaultSrc);
        self::assertSame('', $policy->compile($this->nonce));
    }

    #[Test]
    public function superfluousDirectivesArePurged(): void
    {
        $policy = (new Policy(SourceKeyword::self, SourceScheme::data))
            ->set(Directive::ScriptSrc, SourceKeyword::self, SourceScheme::data);
        self::assertSame("default-src 'self' data:", $policy->compile($this->nonce));
    }

    #[Test]
    public function backendPolicyIsCompiled(): void
    {
        $this->nonce->consume();
        $policy = (new Policy())
            ->default(SourceKeyword::self)
            ->extend(Directive::ScriptSrc, SourceKeyword::nonceProxy)
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline)
            ->set(Directive::StyleSrcAttr, SourceKeyword::unsafeInline)
            ->extend(Directive::ImgSrc, SourceScheme::data)
            ->set(Directive::WorkerSrc, SourceKeyword::self, SourceScheme::blob)
            ->extend(Directive::FrameSrc, SourceScheme::blob);
        self::assertSame(
            "default-src 'self'; script-src 'self' 'nonce-{$this->nonce->value}'; "
            . "style-src 'self' 'unsafe-inline'; style-src-attr 'unsafe-inline'; "
            . "img-src 'self' data:; worker-src 'self' blob:; frame-src 'self' blob:",
            $policy->compile($this->nonce)
        );
    }

    #[Test]
    public function containedDirectiveSourcesAreDetermined(): void
    {
        $policy = (new Policy())
            ->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline, SourceScheme::data, new UriValue('https://example.org'))
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline, SourceScheme::blob, new UriValue('https://example.com/path'));

        self::assertTrue($policy->containsDirective(Directive::ScriptSrc, SourceScheme::data, new UriValue('https://example.org')));
        self::assertTrue($policy->containsDirective(Directive::StyleSrc, SourceScheme::blob, new UriValue('https://example.com/path')));
        self::assertFalse($policy->containsDirective(Directive::ConnectSrc, SourceScheme::https));
    }

    #[Test]
    public function coveredDirectiveSourcesAreDetermined(): void
    {
        $policy = (new Policy())
            ->extend(Directive::ScriptSrc, SourceKeyword::unsafeInline, SourceScheme::data, new UriValue('*.example.org'))
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline, SourceScheme::blob, new UriValue('https://*.example.com'));

        self::assertTrue($policy->coversDirective(Directive::ScriptSrc, SourceScheme::data, new UriValue('https://sub.example.org/path/file.js')));
        self::assertTrue($policy->coversDirective(Directive::StyleSrc, SourceScheme::blob, new UriValue('https://sub.example.com/path/file.css')));
        self::assertFalse($policy->coversDirective(Directive::ConnectSrc, SourceScheme::https));
    }

    #[Test]
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
