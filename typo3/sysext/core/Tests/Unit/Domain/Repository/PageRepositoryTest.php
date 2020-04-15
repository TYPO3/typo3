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

namespace TYPO3\CMS\Core\Tests\Unit\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PageRepositoryTest extends UnitTestCase
{
    /**
     * @var PageRepository|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $pageSelectObject;

    protected $defaultTcaForPages = [
        'ctrl' => [
            'label' => 'title',
            'tstamp' => 'tstamp',
            'sortby' => 'sorting',
            'type' => 'doktype',
            'versioningWS' => true,
            'origUid' => 't3_origuid',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
                'starttime' => 'starttime',
                'endtime' => 'endtime',
                'fe_group' => 'fe_group'
            ],
        ],
        'columns' => []
    ];

    /**
     * Sets up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->pageSelectObject = $this->getAccessibleMock(PageRepository::class, ['getMultipleGroupsWhereClause'], [], '', false);
        $this->pageSelectObject->_set('context', new Context());
        $this->pageSelectObject->expects(self::any())->method('getMultipleGroupsWhereClause')->willReturn(' AND 1=1');
    }

    ///////////////////////////////
    // Tests concerning getExtURL
    ///////////////////////////////
    /**
     * @test
     */
    public function getExtUrlForDokType3UsesTheSameValue()
    {
        self::assertEquals('http://www.example.com', $this->pageSelectObject->getExtURL([
            'doktype' => PageRepository::DOKTYPE_LINK,
            'url' => 'http://www.example.com'
        ]));
    }

    /**
     * @test
     */
    public function getExtUrlForDokType3PrependsSiteUrl()
    {
        self::assertEquals(GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'hello/world/', $this->pageSelectObject->getExtURL([
            'doktype' => PageRepository::DOKTYPE_LINK,
            'url' => 'hello/world/'
        ]));
    }

    /**
     * @test
     */
    public function getExtUrlForDokType3AssumesAbsoluteUrl()
    {
        self::assertEquals('/hello/world/', $this->pageSelectObject->getExtURL([
            'doktype' => PageRepository::DOKTYPE_LINK,
            'url' => '/hello/world/'
        ]));
    }

    /**
     * @test
     */
    public function getExtUrlForDokType3UsesEmailAsSameValue()
    {
        self::assertEquals('mailto:mail@typo3-test.com', $this->pageSelectObject->getExtURL([
            'doktype' => PageRepository::DOKTYPE_LINK,
            'url' => 'mailto:mail@typo3-test.com'
        ]));
    }

    /**
     * @test
     */
    public function getExtUrlForDokType3UsesValidEmailWithoutProtocolAsEmail()
    {
        self::assertEquals('mailto:mail@typo3-test.com', $this->pageSelectObject->getExtURL([
            'doktype' => PageRepository::DOKTYPE_LINK,
            'url' => 'mail@typo3-test.com'
        ]));
    }
}
