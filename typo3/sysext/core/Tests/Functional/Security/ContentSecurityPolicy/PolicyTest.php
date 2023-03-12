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

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashProxy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * In contrast to the other `PolicyTest` (unit-test), these
 * tests rely on platform information like, package states.
 */
class PolicyTest extends FunctionalTestCase
{
    private Nonce $nonce;
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nonce = Nonce::create();
    }

    /**
     * @test
     */
    public function hashValueIsCompiled(): void
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
}
