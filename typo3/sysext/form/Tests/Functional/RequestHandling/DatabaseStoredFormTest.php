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

namespace TYPO3\CMS\Form\Tests\Functional\RequestHandling;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Form\Tests\Functional\Framework\FormHandling\FormDataFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for database-stored form definitions rendered in frontend.
 */
final class DatabaseStoredFormTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const ROOT_PAGE_BASE_URI = 'http://localhost';
    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_GB.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['form', 'fluid_styled_content'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'site1',
            $this->buildSiteConfiguration(1000, self::ROOT_PAGE_BASE_URI . '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/form_definition.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $factory = DataHandlerFactory::fromYamlFile(__DIR__ . '/Fixtures/DatabaseFormScenario.yaml');
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        self::failIfArrayIsNotEmpty($writer->getErrors());
    }

    /**
     * Verifies that a database-stored form definition renders correctly
     * in the frontend without a backend user session.
     *
     * This is the E2E regression test for the bugfix #109528 that skips permission checks
     * in frontend context in DatabaseStorageAdapter::read().
     */
    #[Test]
    public function databaseStoredFormRendersInFrontendWithoutBackendUser(): void
    {
        $uri = static::ROOT_PAGE_BASE_URI . '/form';

        // The form identifier in the database is "db-test-form".
        // FormFrontendController appends "-{contentElementUid}" → "db-test-form-1005"
        $formIdentifier = 'db-test-form-1005';

        $subject = new FormDataFactory();

        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $pageMarkup = (string)$this->executeFrontendSubRequest($internalRequest, null, true)->getBody();

        self::assertStringContainsString('db-test-form-1005', $pageMarkup, 'database-stored form is rendered on the page');

        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');
        $formMarkup = $formData->getFormMarkup();

        self::assertNotEmpty($formMarkup, 'form markup is not empty');
        self::assertStringContainsString('text-1', $formMarkup, 'form contains text-1 element');

        // Submit the form and verify the confirmation finisher message
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $formPostRequest = $formData->with('text-1', 'DatabaseFormTest')->toPostRequest($internalRequest);
        $pageMarkup = (string)$this->executeFrontendSubRequest($formPostRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//*[@id="' . $formIdentifier . '"]');

        $formMarkup = $formData->getFormMarkup();
        self::assertStringContainsString('Form is submitted', $formMarkup, 'confirmation finisher message is shown');
    }
}
