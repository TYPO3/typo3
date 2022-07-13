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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Enable fields test
 */
class EnableFieldsTest extends AbstractDataHandlerActionTestCase
{
    const TABLE_Blog = 'tx_blogexample_domain_model_blog';

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/fe_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/fe_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs-with-fe_groups.csv');

        $this->setUpFrontendSite(1);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    /**
     * @test
     */
    public function protectedRecordsNotFoundIfNoUserLoggedIn(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1'));
    }

    /**
     * @test
     */
    public function onlyReturnProtectedRecordsForTheFirstUserGroup(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog2'));
    }

    /**
     * @test
     */
    public function onlyReturnProtectedRecordsForTheSecondUserGroup(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(2));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog3'));
    }

    /**
     * @test
     */
    public function onlyOwnProtectedRecordsWithQueryCacheInvolvedAreReturned(): void
    {
        // first request to fill the query cache
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog2'));

        // second request with other frontenduser
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(2));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog3'));
    }
}
