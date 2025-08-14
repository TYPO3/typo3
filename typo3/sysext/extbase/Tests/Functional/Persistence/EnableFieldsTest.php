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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EnableFieldsTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_Blog = 'tx_blogexample_domain_model_blog';
    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/EnableFieldsTestImport.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $this->setUpFrontendRootPage(1, ['EXT:extbase/Tests/Functional/Persistence/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    #[Test]
    public function protectedRecordsNotFoundIfNoUserLoggedIn(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1'));
    }

    #[Test]
    public function onlyReturnProtectedRecordsForTheFirstUserGroup(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog2'));
    }

    #[Test]
    public function onlyReturnProtectedRecordsForTheSecondUserGroup(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(2));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog3'));
    }

    #[Test]
    public function onlyOwnProtectedRecordsWithQueryCacheInvolvedAreReturned(): void
    {
        // first request to fill the query cache
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog2'));

        // second request with other frontenduser
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(2));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, (new HasRecordConstraint())
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog3'));
    }
}
