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

namespace TYPO3\CMS\Core\Tests\Functional\Error\PageErrorHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageContentErrorHandlerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->setUpFrontendRootPage(
            1,
            ['EXT:core/Tests/Functional/Error/PageErrorHandler/Fixtures/PageWithPropagatedNotFoundResponse.typoscript']
        );
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')],
            $this->buildErrorHandlingConfiguration('Page', [404])
        );
    }

    #[Test]
    public function errorPageSubRequestDoesNotInheritPageRendererStateOfTheRequestItAnswers(): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://website.local/failing'));
        $body = (string)$response->getBody();

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('contentOfNotFoundPage', $body);
        self::assertStringNotContainsString('contentOfRequestedPage', $body);
        self::assertStringContainsString('headerDataOfNotFoundPage', $body);
        self::assertStringNotContainsString('headerDataOfRequestedPage', $body);
        self::assertStringContainsString('footerDataOfNotFoundPage', $body);
        self::assertStringNotContainsString('footerDataOfRequestedPage', $body);
    }
}
