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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FluidEmailTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_fluid_email',
    ];

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function settingNoFormatGeneratesTwoBodies(): void
    {
        $subject = new FluidEmail();
        $subject
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

    #[Test]
    public function forcingHtmlBodyGenerationWorks(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_HTML)
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
        self::assertStringContainsString("<html\n", $result);
        self::assertStringContainsString('</html>', $result);
        self::assertStringContainsString('&lt;strong&gt;from&lt;/strong&gt;', $result);
    }

    #[Test]
    public function forcingTextBodyGenerationWorks(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_PLAIN)
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
        self::assertStringNotContainsString("<html\n", $result);
        self::assertStringNotContainsString('</html>', $result);
        self::assertStringContainsString('Plain content from Functional test', $result);
    }

    #[Test]
    public function viewAssignValuesResetsGeneratedHtmlBody(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_HTML)
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

        // assign new content
        $subject->assign('content', 'Reassigned Plain content <strong>from</strong> Functional test');
        $result2 = $subject->getHtmlBody();

        // Check content
        self::assertStringContainsString('<!doctype html>', $result2);
        self::assertStringContainsString("<html\n", $result2);
        self::assertStringContainsString('</html>', $result2);
        self::assertStringContainsString('&lt;strong&gt;from&lt;/strong&gt;', $result2);
        self::assertStringContainsString('Reassigned Plain content ', $result2);
    }

    #[Test]
    public function viewAssignValuesResetsGeneratedTextBody(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_PLAIN)
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->to('some-recipient@example.com');

        // Generate text body with the force argument
        $result = $subject->getTextBody(true);

        // Pre-check, result is not NULL
        self::assertNotNull($result);

        // Assert html content was not created
        self::assertNull($subject->getHtmlBody());

        // Check that subject section is evaluated
        self::assertEquals('FluidEmail subject', $subject->getSubject());

        // assign new content
        $subject->assign('content', 'Reassigned Plain content from Functional test');
        $result2 = $subject->getTextBody();

        // Assert html content was not created
        self::assertNull($subject->getHtmlBody());

        // Check content
        self::assertStringNotContainsString('<!doctype html>', $result2);
        self::assertStringNotContainsString("<html\n", $result2);
        self::assertStringNotContainsString('</html>', $result2);
        self::assertStringContainsString('Reassigned Plain content ', $result2);
    }

    #[Test]
    public function viewAssignMultiValuesResetsGeneratedHtmlBody(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_HTML)
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

        // assign new content
        $subject->assignMultiple(['content' => 'Reassigned Plain content <strong>from</strong> Functional test']);
        $result2 = $subject->getHtmlBody();

        // Assert text content was not created
        self::assertNull($subject->getTextBody());

        // Check content
        self::assertStringContainsString('<!doctype html>', $result2);
        self::assertStringContainsString("<html\n", $result2);
        self::assertStringContainsString('</html>', $result2);
        self::assertStringContainsString('&lt;strong&gt;from&lt;/strong&gt;', $result2);
        self::assertStringContainsString('Reassigned Plain content ', $result2);
    }

    #[Test]
    public function viewAssignMultiValuesResetsGeneratedTextBody(): void
    {
        $subject = new FluidEmail();
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_PLAIN)
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->to('some-recipient@example.com');

        // Generate html body with the force argument
        $result = $subject->getTextBody(true);

        // Pre-check, result is not NULL
        self::assertNotNull($result);

        // Assert text content was not created
        self::assertNull($subject->getHtmlBody());

        // Check that subject section is evaluated
        self::assertEquals('FluidEmail subject', $subject->getSubject());

        // assign new content
        $subject->assignMultiple(['content' => 'Reassigned Plain content from Functional test']);
        $result2 = $subject->getTextBody();

        // Check content
        self::assertStringNotContainsString('<!doctype html>', $result2);
        self::assertStringNotContainsString("<html\n", $result2);
        self::assertStringNotContainsString('</html>', $result2);
        self::assertStringContainsString('Reassigned Plain content ', $result2);
    }

    #[Test]
    public function bodiesAreNotRecreatedOnMultipleEnsureValidityCalls(): void
    {
        $subject = new class () extends FluidEmail {
            public int $countRenderContentCalled = 0;

            protected function renderContent(string $format): string
            {
                $this->countRenderContentCalled++;
                return parent::renderContent($format);
            }
        };
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_BOTH)
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->to('some-recipient@example.com');

        $subject->ensureValidity();
        self::assertEquals(2, $subject->countRenderContentCalled);

        $subject->ensureValidity();
        self::assertEquals(2, $subject->countRenderContentCalled);
    }

    #[Test]
    public function bodiesAreRecreatedOnMultipleEnsureValidityCallsWithAssignUsedInBetween(): void
    {
        $subject = new class () extends FluidEmail {
            public int $countRenderContentCalled = 0;

            protected function renderContent(string $format): string
            {
                $this->countRenderContentCalled++;
                return parent::renderContent($format);
            }
        };
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_BOTH)
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->to('some-recipient@example.com');

        $subject->ensureValidity();
        self::assertEquals(2, $subject->countRenderContentCalled);

        $subject->assign('content', 'Reassigned plain content from Functional test');

        $subject->ensureValidity();
        $resultPlain = $subject->getTextBody();
        $resultHtml = $subject->getHtmlBody();

        self::assertEquals(4, $subject->countRenderContentCalled);
        self::assertNotNull($resultPlain);
        self::assertNotNull($resultHtml);
        self::assertStringContainsString('Reassigned plain content from Functional test', $resultPlain);
        self::assertStringContainsString('<!doctype html>', $resultHtml);
        self::assertStringContainsString("<html\n", $resultHtml);
        self::assertStringContainsString('</html>', $resultHtml);
        self::assertStringContainsString('Reassigned plain content from Functional test', $resultHtml);
    }

    #[Test]
    public function bodiesAreRecreatedOnMultipleEnsureValidityCallsWithAssignMultipleUsedInBetween(): void
    {
        $subject = new class () extends FluidEmail {
            public int $countRenderContentCalled = 0;

            protected function renderContent(string $format): string
            {
                $this->countRenderContentCalled++;
                return parent::renderContent($format);
            }
        };
        $subject
            ->setTemplate('WithSubject')
            ->format(FluidEmail::FORMAT_BOTH)
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->to('some-recipient@example.com');

        $subject->ensureValidity();
        self::assertEquals(2, $subject->countRenderContentCalled);

        $subject->assignMultiple(['content' => 'Reassigned plain content from Functional test']);

        $subject->ensureValidity();
        $resultPlain = $subject->getTextBody();
        $resultHtml = $subject->getHtmlBody();

        self::assertEquals(4, $subject->countRenderContentCalled);
        self::assertNotNull($resultPlain);
        self::assertNotNull($resultHtml);
        self::assertStringContainsString('Reassigned plain content from Functional test', $resultPlain);
        self::assertStringContainsString('<!doctype html>', $resultHtml);
        self::assertStringContainsString("<html\n", $resultHtml);
        self::assertStringContainsString('</html>', $resultHtml);
        self::assertStringContainsString('Reassigned plain content from Functional test', $resultHtml);
    }

    #[Test]
    public function bodiesAreNotRecreatedOnMultipleEnsureValidityCallsWithSetSubjectInBetween(): void
    {
        $subject = new class () extends FluidEmail {
            public int $countRenderContentCalled = 0;

            protected function renderContent(string $format): string
            {
                $this->countRenderContentCalled++;
                return parent::renderContent($format);
            }
        };
        $subject
            ->format(FluidEmail::FORMAT_BOTH)
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->subject('Original subject')
            ->to('some-recipient@example.com');

        $subject->ensureValidity();
        self::assertEquals(2, $subject->countRenderContentCalled);

        $subject->subject('Overridden subject');

        $subject->ensureValidity();
        $resultPlain = $subject->getTextBody();
        $resultHtml = $subject->getHtmlBody();

        self::assertEquals(2, $subject->countRenderContentCalled);
        self::assertNotNull($resultPlain);
        self::assertNotNull($resultHtml);
        self::assertEquals('Overridden subject', $subject->getSubject());
    }

    #[Test]
    public function bodiesAreNotRecreatedOnMultipleEnsureValidityCallsWithAssignedValuesButManualSetTextAndHtml(): void
    {
        $subject = new class () extends FluidEmail {
            public int $countRenderContentCalled = 0;

            protected function renderContent(string $format): string
            {
                $this->countRenderContentCalled++;
                return parent::renderContent($format);
            }
        };
        $subject
            ->format(FluidEmail::FORMAT_BOTH)
            ->from('benniYYYY@typo3.org')
            ->assign('content', 'Plain content from Functional test')
            ->subject('Original subject')
            ->to('some-recipient@example.com');

        $subject->ensureValidity();
        self::assertEquals(2, $subject->countRenderContentCalled);
        $text = $subject->getTextBody();
        $html = $subject->getHtmlBody();
        self::assertEquals(2, $subject->countRenderContentCalled);

        $subject->assign('content', 'Reassigned plain content from Functional test');
        $subject->text($text);
        $subject->html($html);

        $subject->ensureValidity();
        $resultPlain = $subject->getTextBody();
        $resultHtml = $subject->getHtmlBody();

        self::assertEquals(2, $subject->countRenderContentCalled);
        self::assertNotNull($resultPlain);
        self::assertNotNull($resultHtml);
        self::assertEquals($text, $resultPlain);
        self::assertStringContainsString('<!doctype html>', $resultHtml);
        self::assertStringContainsString("<html\n", $resultHtml);
        self::assertStringContainsString('</html>', $resultHtml);
        self::assertEquals($html, $resultHtml);
    }
}
