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

namespace TYPO3\CMS\Recycler\Tests\Functional\Recycle\Pages;

use TYPO3\CMS\Recycler\Tests\Functional\Recycle\AbstractRecycleTestCase;

/**
 * Functional test for the Recycler
 */
class AdminRecycleTest extends AbstractRecycleTestCase
{
    /**
     * Directory which contains data sets for assertions
     *
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/recycler/Tests/Functional/Recycle/Pages/DataSet/Assertion/';

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
        // Set up "admin" user
        $this->setUpBackendUserFromFixture(1);
    }

    /**
     * @test
     */
    public function retrieveDeletedPagesNoRecursion()
    {
        $deletedPages = $this->getDeletedPages(1, 0);
        $assertData = $this->loadDataSet($this->assertionDataSetDirectory . 'deletedPage-3.xml');
        self::assertCount(1, $deletedPages);
        self::assertArrayHasKey('pages', $deletedPages);
        self::assertCount(3, $deletedPages['pages']);
        self::assertSame($assertData[0]['uid'], $deletedPages[0]['uid']);
    }

    /**
     * @test
     */
    public function retrieveDeletedPagesOneLevelRecursion()
    {
        $deletedPages = $this->getDeletedPages(1, 1);
        $assertData = $this->loadDataSet($this->assertionDataSetDirectory . 'deletedPage-3_4_5_7.xml');
        self::assertCount(1, $deletedPages);
        self::assertArrayHasKey('pages', $deletedPages);
        self::assertCount(4, $deletedPages['pages']);
        self::assertSame($assertData[0]['uid'], $deletedPages[0]['uid']);
    }
}
