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

namespace TYPO3\CMS\Core\Tests\Functional\Mail;

use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FluidEmailTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function settingFormatWithTextOnlyGeneratesTextEmail()
    {
        $subject = new FluidEmail();
        $subject
            ->format(FluidEmail::FORMAT_PLAIN)
            ->setTemplate('Default')
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->to('some-recipient@example.com');
        $result = $subject->getBody();
        self::assertEquals('plain', $result->getMediaSubtype());
        self::assertStringContainsString('Plain content from Functional test', $result->bodyToString());
        self::assertEmpty($subject->getHtmlBody());
        self::assertNotEmpty($subject->getTextBody());
    }

    /**
     * @test
     */
    public function settingFormatWithHtmlOnlyGeneratesHtmlEmail()
    {
        $subject = new FluidEmail();
        $subject
            ->format(FluidEmail::FORMAT_HTML)
            ->setTemplate('Default')
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'HTML content <strong>from</strong> Functional test')
            ->to('some-recipient@example.com');
        $result = $subject->getBody();
        self::assertEquals('html', $result->getMediaSubtype());
        self::assertStringContainsString('&lt;strong&gt;from&lt;/strong&gt;', $result->bodyToString());
        self::assertNotEmpty($subject->getHtmlBody());
        self::assertEmpty($subject->getTextBody());
    }

    /**
     * @test
     */
    public function settingFormatWithTextAndHtmlGeneratesTwoBodies()
    {
        $subject = new FluidEmail();
        $subject
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate('Default')
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content <strong>from</strong> Functional test')
            ->to('some-recipient@example.com');
        $result = $subject->getBody();
        self::assertEquals('alternative', $result->getMediaSubtype());
        self::assertStringContainsString('&lt;strong&gt;from&lt;/strong&gt;', $result->bodyToString());
        self::assertNotEmpty($subject->getHtmlBody());
        self::assertNotEmpty($subject->getTextBody());
    }
}
