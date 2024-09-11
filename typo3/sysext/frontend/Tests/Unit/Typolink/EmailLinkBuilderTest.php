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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Frontend\Typolink\EmailLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EmailLinkBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function useEmailForEmptyLinkText(): void
    {
        $linkDetails = [
            'type' => 'email',
            'email' => 'michael@bluth-company.com',
            'typoLinkParameter' => 'michael@bluth-company.com',
        ];
        $subject = new EmailLinkBuilder(new LinkService());
        $actualResult = $subject->buildLink($linkDetails, [], new ServerRequest('https://example.com'));
        self::assertSame('michael@bluth-company.com', $actualResult->getLinkText());
    }

    #[Test]
    public function spamProtectEmailAddressesWorks(): void
    {
        $linkDetails = [
            'type' => 'email',
            'email' => 'michael@bluth-company.com',
            'typoLinkParameter' => 'michael@bluth-company.com',
        ];
        $request = new ServerRequest('http://example.com');
        $request = $request->withAttribute(
            'frontend.typoscript',
            (new class () {
                public function getConfigArray(): array
                {
                    return [
                        'spamProtectEmailAddresses' => 3,
                    ];
                }
            })
        );
        $subject = new EmailLinkBuilder(new LinkService());
        $actualResult = $subject->buildLink($linkDetails, [], $request);
        self::assertSame('michael(at)bluth-company.com', $actualResult->getLinkText());
    }

    #[Test]
    public function invalidEmailGetsHscedProperly(): void
    {
        $linkDetails = [
            'type' => 'email',
            'email' => 'no\'mail@acme.com',
            'typoLinkParameter' => 'no\'mail@acme.com',
        ];
        $request = new ServerRequest('http://example.com');
        $request = $request->withAttribute(
            'frontend.typoscript',
            (new class () {
                public function getConfigArray(): array
                {
                    return [
                        'spamProtectEmailAddresses' => 3,
                    ];
                }
            })
        );
        $subject = new EmailLinkBuilder(new LinkService());
        /** @var LinkResult $actualResult */
        $actualResult = $subject->buildLink($linkDetails, [], $request);
        self::assertSame('<a href="#" data-mailto-token="pdlowr-qr&#039;pdloCdfph1frp" data-mailto-vector="3">no&#039;mail(at)acme.com</a>', (string)$actualResult);
    }
}
