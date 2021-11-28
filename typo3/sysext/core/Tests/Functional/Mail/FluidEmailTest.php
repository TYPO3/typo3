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
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * @var string[]
     */
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_fluid_email',
    ];

    /**
     * @test
     */
    public function settingFormatWithTextOnlyGeneratesTextEmail(): void
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
    public function settingFormatWithHtmlOnlyGeneratesHtmlEmail(): void
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
    public function settingFormatWithTextAndHtmlGeneratesTwoBodies(): void
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

    /**
     * @test
     */
    public function settingNoFormatGeneratesTwoBodies(): void
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

    /**
     * @test
     */
    public function forcingHtmlBodyGenerationWorks(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content <strong>from</strong> Functional test')
            ->to('some-recipient@example.com');

        // Generate html body with the force argument
        $result = $subject->getHtmlBody(true);

        // Pre-check, result is not NULL
        self::assertNotNull($result);

        // Assert text content was not created
        self::assertNull($subject->getTextBody());

        // Check that subject section is evaluated
        self::assertEquals('FluidEmail subject', $subject->getSubject());

        // Check content
        self::assertStringContainsString('<!doctype html>', $result);
        self::assertStringContainsString('&lt;strong&gt;from&lt;/strong&gt;', $result);
    }

    /**
     * @test
     */
    public function forcingTextBodyGenerationWorks(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->from('benniYYYY@typo3.org')
            ->subject('Will be overridden in the template')
            ->assign('content', 'Plain content from Functional test')
            ->to('some-recipient@example.com');

        // Generate text body with the force argument
        $result = $subject->getTextBody(true);

        // Pre-check, result is not NULL
        self::assertNotNull($result);

        // Assert html content was not created
        self::assertNull($subject->getHtmlBody());

        // Check that subject section is evaluated and overrides the previously defined
        self::assertEquals('FluidEmail subject', $subject->getSubject());

        // Check content
        self::assertStringNotContainsString('<!doctype html>', $result);
        self::assertStringContainsString('Plain content from Functional test', $result);
    }
}
