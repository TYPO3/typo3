<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

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
 * Download from TER controller test
 *
 */
class DownloadControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function installFromTerReturnsArrayWithBooleanResultAndErrorArrayWhenExtensionManagerExceptionIsThrown()
    {
        $dummyExceptionMessage = 'exception message';
        $dummyException = new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($dummyExceptionMessage);

        $dummyExtensionName = 'dummy_extension';
        $dummyExtension = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class);
        $dummyExtension->expects($this->any())->method('getExtensionKey')->will($this->returnValue($dummyExtensionName));

        $downloadUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility::class);
        $downloadUtilityMock->expects($this->any())->method('setDownloadPath')->willThrowException($dummyException);

        $subject = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Controller\DownloadController::class, ['dummy']);
        $subject->_set('downloadUtility', $downloadUtilityMock);

        $result = $subject->_call('installFromTer', $dummyExtension);

        $expectedResult = [
            false,
            [
                $dummyExtensionName => [
                    [
                        'code' => 0,
                        'message' => $dummyExceptionMessage
                    ]
                ]
            ]
        ];

        $this->assertSame($expectedResult, $result);
    }
}
