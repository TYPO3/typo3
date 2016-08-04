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
class PageRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\Page\PageRepository|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $pageSelectObject;

    protected $defaultTcaForPages = array(
        'ctrl' => array(
            'label' => 'title',
            'tstamp' => 'tstamp',
            'sortby' => 'sorting',
            'type' => 'doktype',
            'versioningWS' => true,
            'origUid' => 't3_origuid',
            'delete' => 'deleted',
            'enablecolumns' => array(
                'disabled' => 'hidden',
                'starttime' => 'starttime',
                'endtime' => 'endtime',
                'fe_group' => 'fe_group'
            ),
        ),
        'columns' => array()
    );

    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMockBuilder(\TYPO3\CMS\Core\Database\DatabaseConnection::class)
            ->setMethods(array('exec_SELECTquery', 'sql_fetch_assoc', 'sql_free_result', 'exec_SELECTgetSingleRow'))
            ->getMock();
        $this->pageSelectObject = $this->getAccessibleMock(\TYPO3\CMS\Frontend\Page\PageRepository::class, array('getMultipleGroupsWhereClause'));
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
        $this->assertEquals('', $this->pageSelectObject->getPathFromRootline(array()));
    }

    ///////////////////////////////
    // Tests concerning getExtURL
    ///////////////////////////////
    /**
     * @test
     */
    public function getExtUrlForDokType3AndUrlType1AddsHttpSchemeToUrl()
    {
        $this->assertEquals('http://www.example.com', $this->pageSelectObject->getExtURL(array(
            'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
            'urltype' => 1,
            'url' => 'www.example.com'
        )));
    }

    /**
     * @test
     */
    public function getExtUrlForDokType3AndUrlType0PrependsSiteUrl()
    {
        $this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'hello/world/', $this->pageSelectObject->getExtURL(array(
            'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
            'urltype' => 0,
            'url' => 'hello/world/'
        )));
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
        $GLOBALS['TCA']['fake_table']['columns'] = array(
            'exclude' => array(
                'l10n_mode' => 'exclude',
                'config' => array('type' => 'input'),
            ),
            'mergeIfNotBlank' => array(
                'l10n_mode' => 'mergeIfNotBlank',
                'config' => array('type' => 'input'),
            ),
            'mergeIfNotBlank_group' => array(
                'l10n_mode' => 'mergeIfNotBlank',
                'config' => array('type' => 'group'),
            ),
            'default' => array(
                // no l10n_mode set
                'config' => array('type' => 'input'),
            ),
            'noCopy' => array(
                'l10n_mode' => 'noCopy',
                'config' => array('type' => 'input'),
            ),
            'prefixLangTitle' => array(
                'l10n_mode' => 'prefixLangTitle',
                'config' => array('type' => 'input'),
            ),
        );

        $result = $this->pageSelectObject->_call('shouldFieldBeOverlaid', $table, $field, $value);
        unset($GLOBALS['TCA']['fake_table']);

        $this->assertSame($expected, $result, $comment);
    }

    /**
     * Data provider for shouldFieldBeOverlaid
     */
    public function getShouldFieldBeOverlaidData()
    {
        return array(
            array('default',               'fake_table', 'foobar', true,  'default is to merge non-empty string'),
            array('default',               'fake_table', '',       true,  'default is to merge empty string'),

            array('exclude',               'fake_table', '',       false, 'exclude field with empty string'),
            array('exclude',               'fake_table', 'foobar', false, 'exclude field with non-empty string'),

            array('mergeIfNotBlank',       'fake_table', '',       false, 'mergeIfNotBlank is not merged with empty string'),
            array('mergeIfNotBlank',       'fake_table', 0,        true,  'mergeIfNotBlank is merged with 0'),
            array('mergeIfNotBlank',       'fake_table', '0',      true,  'mergeIfNotBlank is merged with "0"'),
            array('mergeIfNotBlank',       'fake_table', 'foobar', true,  'mergeIfNotBlank is merged with non-empty string'),

            array('mergeIfNotBlank_group', 'fake_table', '',       false, 'mergeIfNotBlank on group is not merged empty string'),
            array('mergeIfNotBlank_group', 'fake_table', 0,        false, 'mergeIfNotBlank on group is not merged with 0'),
            array('mergeIfNotBlank_group', 'fake_table', '0',      false, 'mergeIfNotBlank on group is not merged with "0"'),
            array('mergeIfNotBlank_group', 'fake_table', 'foobar', true,  'mergeIfNotBlank on group is merged with non-empty string'),

            array('noCopy',                'fake_table', 'foobar', true,  'noCopy is merged with non-empty string'),
            array('noCopy',                'fake_table', '',       true,  'noCopy is merged with empty string'),

            array('prefixLangTitle',       'fake_table', 'foobar', true,  'prefixLangTitle is merged with non-empty string'),
            array('prefixLangTitle',       'fake_table', '',       true,  'prefixLangTitle is merged with empty string'),
        );
    }
}
