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

namespace TYPO3\CMS\Backend\Tests\Functional\Template\Components;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ComponentFactoryTest extends FunctionalTestCase
{
    protected ComponentFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = $this->get(ComponentFactory::class);
    }

    #[Test]
    public function createBackButtonReturnsValidLinkButton(): void
    {
        $button = $this->subject->createBackButton('/some/return/url');

        self::assertTrue($button->isValid());
        self::assertSame('/some/return/url', $button->getHref());
        self::assertTrue($button->getShowLabelText());
        self::assertNotNull($button->getIcon());
    }

    #[Test]
    public function createBackButtonRendersCorrectMarkup(): void
    {
        $button = $this->subject->createBackButton('/some/return/url');
        $html = $button->render();

        self::assertStringContainsString('href="/some/return/url"', $html);
        self::assertStringContainsString('actions-view-go-back', $html);
        self::assertStringContainsString('Go back', $html);
    }

    #[Test]
    public function createCloseButtonReturnsValidLinkButton(): void
    {
        $button = $this->subject->createCloseButton('/close/url');

        self::assertTrue($button->isValid());
        self::assertSame('/close/url', $button->getHref());
        self::assertTrue($button->getShowLabelText());
        self::assertNotNull($button->getIcon());
    }

    #[Test]
    public function createCloseButtonRendersCorrectMarkup(): void
    {
        $button = $this->subject->createCloseButton('/close/url');
        $html = $button->render();

        self::assertStringContainsString('href="/close/url"', $html);
        self::assertStringContainsString('actions-close', $html);
        self::assertStringContainsString('Close', $html);
    }

    #[Test]
    public function createSaveButtonReturnsValidInputButton(): void
    {
        $button = $this->subject->createSaveButton();

        self::assertTrue($button->isValid());
        self::assertSame('_savedok', $button->getName());
        self::assertSame('1', $button->getValue());
        self::assertTrue($button->getShowLabelText());
        self::assertNotNull($button->getIcon());
    }

    #[Test]
    public function createSaveButtonWithFormNameSetsFormAttribute(): void
    {
        $button = $this->subject->createSaveButton('myform');

        self::assertSame('myform', $button->getForm());
    }

    #[Test]
    public function createSaveButtonRendersCorrectMarkup(): void
    {
        $button = $this->subject->createSaveButton('editform');
        $html = $button->render();

        self::assertStringContainsString('name="_savedok"', $html);
        self::assertStringContainsString('value="1"', $html);
        self::assertStringContainsString('form="editform"', $html);
        self::assertStringContainsString('actions-document-save', $html);
    }

    #[Test]
    public function createReloadButtonReturnsValidLinkButton(): void
    {
        $button = $this->subject->createReloadButton('/current/uri');

        self::assertTrue($button->isValid());
        self::assertSame('/current/uri', $button->getHref());
        self::assertNotNull($button->getIcon());
    }

    #[Test]
    public function createReloadButtonRendersCorrectMarkup(): void
    {
        $button = $this->subject->createReloadButton('/current/uri');
        $html = $button->render();

        self::assertStringContainsString('href="/current/uri"', $html);
        self::assertStringContainsString('actions-refresh', $html);
        self::assertStringContainsString('Reload', $html);
    }
}
