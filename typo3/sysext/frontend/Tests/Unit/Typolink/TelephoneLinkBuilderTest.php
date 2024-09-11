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
use TYPO3\CMS\Frontend\Typolink\TelephoneLinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TelephoneLinkBuilderTest extends UnitTestCase
{
    #[Test]
    public function noLinkTextForMissingDetailAndNoLinkTextProvided(): void
    {
        $linkDetails = [
            'type' => 'telephone',
            'typoLinkParameter' => 'tel:+49 221 4710 999',
        ];
        $subject = new TelephoneLinkBuilder();
        $actualResult = $subject->buildLink($linkDetails, [], new ServerRequest('https://example.com'));
        self::assertSame('', $actualResult->getLinkText());
    }

    #[Test]
    public function respectsProvidedLinkText(): void
    {
        $linkDetails = [
            'type' => 'telephone',
            'typoLinkParameter' => 'tel:+49 221 4710 999',
        ];
        $subject = new TelephoneLinkBuilder();
        $actualResult = $subject->buildLink($linkDetails, [], new ServerRequest('https://example.com'), 'Phone number');
        self::assertSame('Phone number', $actualResult->getLinkText());
    }

    #[Test]
    public function fallsBackToPhoneNumberOnMissingLinkText(): void
    {
        $linkDetails = [
            'type' => 'telephone',
            'typoLinkParameter' => 'tel:+49 221 4710 999',
            'telephone' => '+49 221 4710 999',
        ];
        $subject = new TelephoneLinkBuilder();
        $actualResult = $subject->buildLink($linkDetails, [], new ServerRequest('https://example.com'));
        self::assertSame('+49 221 4710 999', $actualResult->getLinkText());
    }

    #[Test]
    public function respectsProvidedLinkParameter(): void
    {
        $linkDetails = [
            'type' => 'telephone',
            'typoLinkParameter' => 'tel:+49 221 4710 999',
        ];
        $subject = new TelephoneLinkBuilder();
        $actualResult = $subject->buildLink($linkDetails, [], new ServerRequest('https://example.com'));
        self::assertSame('tel:+49 221 4710 999', $actualResult->getUrl());
    }
}
