<?php

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

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var bool Reference index testing not relevant here.
     */
    protected $assertCleanReferenceIndex = false;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_groups.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_users.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs-with-fe_groups.xml');

        $this->setUpFrontendSite(1);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    /**
     * @test
     */
    public function protectedRecordsNotFoundIfNoUserLoggedIn()
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1'));
    }

    /**
     * @test
     */
    public function onlyReturnProtectedRecordsForTheFirstUserGroup()
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(1));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog2'));
    }

    /**
     * @test
     */
    public function onlyReturnProtectedRecordsForTheSecondUserGroup()
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1), (new InternalRequestContext())->withFrontendUserId(2));
        $responseSections = ResponseContent::fromString((string)$response->getBody())->getSections('Extbase:list()');
        self::assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog3'));
    }

    /**
     * @test
     */
    public function onlyOwnProtectedRecordsWithQueryCacheInvolvedAreReturned()
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
