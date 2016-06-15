<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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
 * List utility test
 *
 */
class ListUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $subject;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->subject = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class)
            ->setMethods(array('emitPackagesMayHaveChangedSignal'))
            ->getMock();
        $packageManagerMock = $this->getMockBuilder(\TYPO3\CMS\Core\Package\PackageManager::class)->getMock();
        $packageManagerMock
                ->expects($this->any())
                ->method('getActivePackages')
                ->will($this->returnValue(array(
                    'lang' => $this->getMockBuilder(\TYPO3\CMS\Core\Package::class)->disableOriginalConstructor()->getMock(),
                    'news' => $this->getMockBuilder(\TYPO3\CMS\Core\Package::class)->disableOriginalConstructor()->getMock(),
                    'saltedpasswords' => $this->getMockBuilder(\TYPO3\CMS\Core\Package::class)->disableOriginalConstructor()->getMock(),
                    'rsaauth' => $this->getMockBuilder(\TYPO3\CMS\Core\Package::class)->disableOriginalConstructor()->getMock(),
                )));
        $this->inject($this->subject, 'packageManager', $packageManagerMock);
    }

    /**
     * @return array
     */
    public function getAvailableAndInstalledExtensionsDataProvider()
    {
        return array(
            'same extension lists' => array(
                array(
                    'lang' => array(),
                    'news' => array(),
                    'saltedpasswords' => array(),
                    'rsaauth' => array()
                ),
                array(
                    'lang' => array('installed' => true),
                    'news' => array('installed' => true),
                    'saltedpasswords' => array('installed' => true),
                    'rsaauth' => array('installed' => true)
                )
            ),
            'different extension lists' => array(
                array(
                    'lang' => array(),
                    'news' => array(),
                    'saltedpasswords' => array(),
                    'rsaauth' => array()
                ),
                array(
                    'lang' => array('installed' => true),
                    'news' => array('installed' => true),
                    'saltedpasswords' => array('installed' => true),
                    'rsaauth' => array('installed' => true)
                )
            ),
            'different extension lists - set2' => array(
                array(
                    'lang' => array(),
                    'news' => array(),
                    'saltedpasswords' => array(),
                    'rsaauth' => array(),
                    'em' => array()
                ),
                array(
                    'lang' => array('installed' => true),
                    'news' => array('installed' => true),
                    'saltedpasswords' => array('installed' => true),
                    'rsaauth' => array('installed' => true),
                    'em' => array()
                )
            ),
            'different extension lists - set3' => array(
                array(
                    'lang' => array(),
                    'fluid' => array(),
                    'news' => array(),
                    'saltedpasswords' => array(),
                    'rsaauth' => array(),
                    'em' => array()
                ),
                array(
                    'lang' => array('installed' => true),
                    'fluid' => array(),
                    'news' => array('installed' => true),
                    'saltedpasswords' => array('installed' => true),
                    'rsaauth' => array('installed' => true),
                    'em' => array()
                )
            )
        );
    }

    /**
     * @test
     * @dataProvider getAvailableAndInstalledExtensionsDataProvider
     * @param $availableExtensions
     * @param $expectedResult
     * @return void
     */
    public function getAvailableAndInstalledExtensionsTest($availableExtensions, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->subject->getAvailableAndInstalledExtensions($availableExtensions));
    }

    /**
     * @return array
     */
    public function enrichExtensionsWithEmConfInformationDataProvider()
    {
        return array(
            'simple key value array emconf' => array(
                array(
                    'lang' => array('property1' => 'oldvalue'),
                    'news' => array(),
                    'saltedpasswords' => array(),
                    'rsaauth' => array()
                ),
                array(
                    'property1' => 'property value1'
                ),
                array(
                    'lang' => array('property1' => 'oldvalue'),
                    'news' => array('property1' => 'property value1'),
                    'saltedpasswords' => array('property1' => 'property value1'),
                    'rsaauth' => array('property1' => 'property value1')
                )
            )
        );
    }

    /**
     * @test
     * @dataProvider enrichExtensionsWithEmConfInformationDataProvider
     * @param $extensions
     * @param $emConf
     * @param $expectedResult
     * @return void
     */
    public function enrichExtensionsWithEmConfInformation($extensions, $emConf, $expectedResult)
    {
        $this->inject($this->subject, 'extensionRepository', $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, array('findOneByExtensionKeyAndVersion', 'findHighestAvailableVersion'), array(), '', false));
        $emConfUtilityMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Utility\EmConfUtility::class)->getMock();
        $emConfUtilityMock->expects($this->any())->method('includeEmConf')->will($this->returnValue($emConf));
        $this->inject($this->subject, 'emConfUtility', $emConfUtilityMock);
        $this->assertEquals($expectedResult, $this->subject->enrichExtensionsWithEmConfAndTerInformation($extensions));
    }
}
