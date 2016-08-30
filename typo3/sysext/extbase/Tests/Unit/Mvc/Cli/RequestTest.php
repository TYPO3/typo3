<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Cli;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test case
 */
class RequestTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\Request|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * Sets up this test case
     */
    protected function setUp()
    {
        $this->request = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Cli\Request::class, ['dummy']);
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->request->_set('objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function getCommandReturnsTheCommandObjectReflectingTheRequestInformation()
    {
        $this->request->setControllerObjectName('Tx_Extbase_Command_CacheCommandController');
        $this->request->setControllerCommandName('flush');
        $this->mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Cli\Command::class, 'Tx_Extbase_Command_CacheCommandController', 'flush');
        $this->request->getCommand();
    }

    /**
     * @test
     */
    public function setControllerObjectNameAndSetControllerCommandNameUnsetTheBuiltCommandObject()
    {
        $this->request->setControllerObjectName('Tx_Extbase_Command_CacheCommandController');
        $this->request->setControllerCommandName('flush');
        $this->request->getCommand();
        $this->request->setControllerObjectName('Tx_SomeExtension_Command_BeerCommandController');
        $this->request->setControllerCommandName('drink');
        $this->mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Cli\Command::class, 'Tx_SomeExtension_Command_BeerCommandController', 'drink');
        $this->request->getCommand();
    }

    /**
     * @test
     */
    public function setControllerObjectNameProperlyResolvesExtensionNameWithNamespaces()
    {
        $mockCliRequest = new \TYPO3\CMS\Extbase\Mvc\Cli\Request;
        $mockCliRequest->setControllerObjectName(\TYPO3\CMS\Extbase\Command\NamespacedMockCommandController::class);

        $this->assertSame('Extbase', $mockCliRequest->getControllerExtensionName());
    }

    /**
     * @test
     */
    public function setControllerObjectNameProperlyResolvesExtensionNameWithoutNamespaces()
    {
        $mockCliRequest = new \TYPO3\CMS\Extbase\Mvc\Cli\Request;
        $mockCliRequest->setControllerObjectName('Tx_Extbase_Command_OldschoolMockCommandController');

        $this->assertSame('Extbase', $mockCliRequest->getControllerExtensionName());
    }
}
