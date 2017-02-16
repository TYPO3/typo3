<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

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

/**
 * Test case
 */
class PageRepositoryTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\Page\PageRepository|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
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
    protected function setUp()
    {
        $this->pageSelectObject = $this->getAccessibleMock(\TYPO3\CMS\Frontend\Page\PageRepository::class, ['getMultipleGroupsWhereClause']);
        $this->pageSelectObject->expects($this->any())->method('getMultipleGroupsWhereClause')->will($this->returnValue(' AND 1=1'));
    }

    /////////////////////////////////////////
    // Tests concerning getPathFromRootline
    /////////////////////////////////////////
    /**
     * @test
     */
    public function getPathFromRootLineForEmptyRootLineReturnsEmptyString()
    {
        $this->assertEquals('', $this->pageSelectObject->getPathFromRootline([]));
    }

    ///////////////////////////////
    // Tests concerning getExtURL
    ///////////////////////////////
    /**
     * @test
     */
    public function getExtUrlForDokType3AndUrlType1AddsHttpSchemeToUrl()
    {
        $this->assertEquals('http://www.example.com', $this->pageSelectObject->getExtURL([
            'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
            'urltype' => 1,
            'url' => 'www.example.com'
        ]));
    }

    /**
     * @test
     */
    public function getExtUrlForDokType3AndUrlType0PrependsSiteUrl()
    {
        $this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'hello/world/', $this->pageSelectObject->getExtURL([
            'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
            'urltype' => 0,
            'url' => 'hello/world/'
        ]));
    }

    /////////////////////////////////////////
    // Tests concerning shouldFieldBeOverlaid
    /////////////////////////////////////////
    /**
     * @test
     * @dataProvider getShouldFieldBeOverlaidData
     */
    public function shouldFieldBeOverlaid($field, $table, $value, $expected, $comment = '')
    {
        $GLOBALS['TCA']['fake_table']['columns'] = [
            'exclude' => [
                'l10n_mode' => 'exclude',
                'config' => ['type' => 'input'],
            ],
            'default' => [
                // no l10n_mode set
                'config' => ['type' => 'input'],
            ],
            'prefixLangTitle' => [
                'l10n_mode' => 'prefixLangTitle',
                'config' => ['type' => 'input'],
            ],
        ];

        $result = $this->pageSelectObject->_call('shouldFieldBeOverlaid', $table, $field, $value);
        unset($GLOBALS['TCA']['fake_table']);

        $this->assertSame($expected, $result, $comment);
    }

    /**
     * Data provider for shouldFieldBeOverlaid
     */
    public function getShouldFieldBeOverlaidData()
    {
        return [
            ['default',               'fake_table', 'foobar', true,  'default is to overlay non-empty string'],
            ['default',               'fake_table', '',       true,  'default is to overlay empty string'],

            ['exclude',               'fake_table', '',       true, 'exclude field with empty string'],
            ['exclude',               'fake_table', 'foobar', true, 'exclude field with non-empty string'],

            ['prefixLangTitle',       'fake_table', 'foobar', true,  'prefixLangTitle is merged with non-empty string'],
            ['prefixLangTitle',       'fake_table', '',       true,  'prefixLangTitle is merged with empty string'],
        ];
    }
}
