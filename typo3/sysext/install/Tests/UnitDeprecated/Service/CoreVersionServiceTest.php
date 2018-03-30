<?php
namespace TYPO3\CMS\Install\Tests\UnitDeprecated\Service;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\CoreVersionService;

/**
 * Test case
 */
class CoreVersionServiceTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function updateVersionMatrixRemovesOldReleasesFromMatrix(): void
    {
        $this->setUpApiResponse([
            '7' => [],
            '6.2' => []
        ]);
        /** @var $instance CoreVersionService|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion']);
        $registry = $this->createMock(Registry::class);
        $registry
            ->expects($this->once())
            ->method('set')
            ->with('TYPO3.CMS.Install', 'coreVersionMatrix', $this->logicalNot($this->arrayHasKey('6.2')));
        $instance->expects($this->once())->method('getInstalledVersion')->will($this->returnValue('7.6.25'));
        $instance->_set('registry', $registry);
        $instance->updateVersionMatrix();
    }

    public function setUpApiResponse(array $responseData)
    {
        $response = new JsonResponse($responseData);
        $requestFactory = $this->prophesize(RequestFactory::class);
        $requestFactory->request('https://get.typo3.org/json', Argument::cetera())->willReturn($response);
        GeneralUtility::addInstance(RequestFactory::class, $requestFactory->reveal());
    }
}
