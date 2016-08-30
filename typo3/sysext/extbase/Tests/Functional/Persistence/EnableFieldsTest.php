<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

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
     * @var array
     */
    protected $coreExtensionsToLoad = ['sv', 'extbase', 'fluid'];

    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_groups.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/fe_users.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs-with-fe_groups.xml');

        $this->setUpFrontendRootPage(1, ['typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/Frontend/JsonRenderer.ts']);
    }

    /**
     * @test
     */
    public function protectedRecordsNotFoundIfNoUserLoggedIn()
    {
        $responseSections = $this->getFrontendResponse(1)->getResponseSections('Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1'));
    }

    /**
     * @test
     */
    public function onlyReturnProtectedRecordsForTheFirstUserGroup()
    {
        $responseSections = $this->getFrontendResponse(1, 0, 0, 0, true, 1)->getResponseSections('Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog2'));
    }

    /**
     * @test
     */
    public function onlyReturnProtectedRecordsForTheSecondUserGroup()
    {
        $responseSections = $this->getFrontendResponse(1, 0, 0, 0, true, 2)->getResponseSections('Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog3'));
    }

    /**
     * @test
     */
    public function onlyOwnProtectedRecordsWithQueryCacheInvolvedAreReturned()
    {
        // first request to fill the query cache
        $responseSections = $this->getFrontendResponse(1, 0, 0, 0, true, 1)->getResponseSections('Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog2'));

        // second request with other frontenduser
        $responseSections = $this->getFrontendResponse(1, 0, 0, 0, true, 2)->getResponseSections('Extbase:list()');
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Blog)->setField('title')->setValues('Blog1', 'Blog3'));
    }
}
