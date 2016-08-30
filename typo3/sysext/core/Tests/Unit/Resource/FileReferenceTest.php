<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

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
 * Testcase for the file class of the TYPO3 FAL
 *
 */
class FileReferenceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    protected function setUp()
    {
        $this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
        \TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
    }

    protected function tearDown()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @return \TYPO3\CMS\Core\Resource\FileReference|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected function prepareFixture(array $fileReferenceProperties, array $originalFileProperties)
    {
        $fixture = $this->getAccessibleMock(\TYPO3\CMS\Core\Resource\FileReference::class, ['dummy'], [], '', false);
        $originalFileMock = $this->getAccessibleMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $originalFileMock->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue($originalFileProperties)
        );
        $fixture->_set('originalFile', $originalFileMock);
        $fixture->_set('propertiesOfFileReference', $fileReferenceProperties);

        return $fixture;
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            'File properties correctly override file reference properties' => [
                [
                    'title' => null,
                    'description' => 'fileReferenceDescription',
                    'alternative' => '',
                ],
                [
                    'title' => 'fileTitle',
                    'description' => 'fileDescription',
                    'alternative' => 'fileAlternative',
                    'file_only_property' => 'fileOnlyPropertyValue',
                ],
                [
                    'title' => 'fileTitle',
                    'description' => 'fileReferenceDescription',
                    'alternative' => '',
                    'file_only_property' => 'fileOnlyPropertyValue',
                ],
            ]
        ];
    }

    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @param array $expectedMergedProperties
     * @test
     * @dataProvider propertiesDataProvider
     */
    public function getPropertiesReturnsMergedPropertiesAndRespectsNullValues(array $fileReferenceProperties, array $originalFileProperties, array $expectedMergedProperties)
    {
        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        $actual = $fixture->getProperties();
        $this->assertSame($expectedMergedProperties, $actual);
    }

    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @param array $expectedMergedProperties
     * @test
     * @dataProvider propertiesDataProvider
     */
    public function hasPropertyReturnsTrueForAllMergedPropertyKeys($fileReferenceProperties, $originalFileProperties, $expectedMergedProperties)
    {
        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        foreach ($expectedMergedProperties as $key => $_) {
            $this->assertTrue($fixture->hasProperty($key));
        }
    }

    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @param array $expectedMergedProperties
     * @test
     * @dataProvider propertiesDataProvider
     */
    public function getPropertyReturnsAllMergedPropertyKeys($fileReferenceProperties, $originalFileProperties, $expectedMergedProperties)
    {
        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        foreach ($expectedMergedProperties as $key => $expectedValue) {
            $this->assertSame($expectedValue, $fixture->getProperty($key));
        }
    }

    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @param array $expectedMergedProperties
     * @test
     * @dataProvider propertiesDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function getPropertyThrowsExceptionForNotAvailableProperty($fileReferenceProperties, $originalFileProperties)
    {
        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        $fixture->getProperty($this->getUniqueId('nothingHere'));
    }

    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @param array $expectedMergedProperties
     * @test
     * @dataProvider propertiesDataProvider
     */
    public function getPropertyDoesNotThrowExceptionForPropertyOnlyAvailableInOriginalFile($fileReferenceProperties, $originalFileProperties)
    {
        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        $this->assertSame($originalFileProperties['file_only_property'], $fixture->getProperty('file_only_property'));
    }

    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @param array $expectedMergedProperties
     * @test
     * @dataProvider propertiesDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function getReferencePropertyThrowsExceptionForPropertyOnlyAvailableInOriginalFile($fileReferenceProperties, $originalFileProperties)
    {
        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        $fixture->getReferenceProperty('file_only_property');
    }
}
