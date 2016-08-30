<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Utility;

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
 * Test suite for filtering files by their extensions.
 */
class FileExtensionFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter
     */
    protected $filter;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler|PHPUnit_Framework_MockObject_MockObject
     */
    protected $tceMainMock;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileFactoryMock;

    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        $this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
        $this->filter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
        $this->tceMainMock = $this->getMock(\TYPO3\CMS\Core\DataHandling\DataHandler::class, ['deleteAction'], []);
        $this->fileFactoryMock = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceFactory::class, ['getFileReferenceObject'], []);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class, $this->fileFactoryMock);
    }

    /**
     * Cleans up this test suite.
     */
    protected function tearDown()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function invalidInlineChildrenFilterParametersDataProvider()
    {
        return [
            [null, null, null],
            ['', '', [0, '', null, false]],
            [null, null, [0, '', null, false]]
        ];
    }

    /**
     * @param array|string $allowed
     * @param array|string $disallowed
     * @param array|string $values
     * @test
     * @dataProvider invalidInlineChildrenFilterParametersDataProvider
     */
    public function areInlineChildrenFilteredWithInvalidParameters($allowed, $disallowed, $values)
    {
        $this->parameters = [
            'allowedFileExtensions' => $allowed,
            'disallowedFileExtensions' => $disallowed,
            'values' => $values
        ];
        $this->tceMainMock->expects($this->never())->method('deleteAction');
        $this->fileFactoryMock->expects($this->never())->method('getFileReferenceObject');
        $this->filter->filterInlineChildren($this->parameters, $this->tceMainMock);
    }

    /**
     * @return array
     */
    public function extensionFilterIgnoresCaseInAllowedExtensionCheckDataProvider()
    {
        return [
            'Allowed extensions' => [
                'ext1', 'EXT1', '', true
            ],
            'Allowed extensions, lower and upper case mix' => [
                'ext1', 'ext2, ExT1, Ext3', '', true
            ],
            'Disallowed extensions' => [
                'ext1', '', 'EXT1', false
            ],
            'Disallowed extensions, lower and upper case mix' => [
                'ext1', '', 'ext2, ExT1, Ext3', false
            ],
            'Combine allowed / disallowed extensions' => [
                'ext1', 'EXT1', 'EXT1', false
            ],
        ];
    }

    /**
     * @param string $fileExtension
     * @param array|string $allowedExtensions
     * @param array|string $disallowedExtensions
     * @param bool $isAllowed
     * @test
     * @dataProvider extensionFilterIgnoresCaseInAllowedExtensionCheckDataProvider
     */
    public function extensionFilterIgnoresCaseInAllowedExtensionCheck($fileExtension, $allowedExtensions, $disallowedExtensions, $isAllowed)
    {
        /** @var \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter $filter */
        $filter = $this->getAccessibleMock(\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class, ['dummy']);
        $filter->setAllowedFileExtensions($allowedExtensions);
        $filter->setDisallowedFileExtensions($disallowedExtensions);
        $result = $filter->_call('isAllowed', 'file.' . $fileExtension);
        $this->assertEquals($isAllowed, $result);
    }
}
