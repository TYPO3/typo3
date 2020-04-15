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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FileReferenceTest extends UnitTestCase
{
    /**
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @return \TYPO3\CMS\Core\Resource\FileReference|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected function prepareFixture(array $fileReferenceProperties, array $originalFileProperties)
    {
        $fixture = $this->getAccessibleMock(FileReference::class, ['dummy'], [], '', false);
        $originalFileMock = $this->getAccessibleMock(File::class, [], [], '', false);
        $originalFileMock->expects(self::any())
            ->method('getProperties')
            ->willReturn(
                $originalFileProperties
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
        self::assertSame($expectedMergedProperties, $actual);
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
            self::assertTrue($fixture->hasProperty($key));
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
            self::assertSame($expectedValue, $fixture->getProperty($key));
        }
    }

    /**
     * @test
     * @dataProvider propertiesDataProvider
     *
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     */
    public function getPropertyThrowsExceptionForNotAvailableProperty($fileReferenceProperties, $originalFileProperties)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314226805);

        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        $fixture->getProperty(StringUtility::getUniqueId('nothingHere'));
    }

    /**
     * @test
     * @dataProvider propertiesDataProvider
     *
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     */
    public function getPropertyDoesNotThrowExceptionForPropertyOnlyAvailableInOriginalFile($fileReferenceProperties, $originalFileProperties)
    {
        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        self::assertSame($originalFileProperties['file_only_property'], $fixture->getProperty('file_only_property'));
    }

    /**
     * @test
     * @dataProvider propertiesDataProvider
     *
     * @param array $fileReferenceProperties
     * @param array $originalFileProperties
     * @param array $expectedMergedProperties
     */
    public function getReferencePropertyThrowsExceptionForPropertyOnlyAvailableInOriginalFile($fileReferenceProperties, $originalFileProperties)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1360684914);

        $fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
        $fixture->getReferenceProperty('file_only_property');
    }
}
