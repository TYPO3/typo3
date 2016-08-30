<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc;

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
class RequestTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('someArgumentName', 'theValue');
        $this->assertEquals('theValue', $request->getArgument('someArgumentName'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException
     */
    public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsNoString()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument(123, 'theValue');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException
     */
    public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsAnEmptyString()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('', 'theValue');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
     */
    public function setArgumentThrowsExceptionIfTheGivenArgumentValueIsAnObject()
    {
        $this->markTestSkipped('Differing behavior from TYPO3.Flow because of backwards compatibility reasons.');
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('theKey', new \stdClass());
    }

    /**
     * @test
     */
    public function setArgumentsOverridesAllExistingArguments()
    {
        $arguments = ['key1' => 'value1', 'key2' => 'value2'];
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('someKey', 'shouldBeOverridden');
        $request->setArguments($arguments);
        $actualResult = $request->getArguments();
        $this->assertEquals($arguments, $actualResult);
    }

    /**
     * @test
     */
    public function setArgumentsCallsSetArgumentForEveryArrayEntry()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['setArgument']);
        $request->expects($this->at(0))->method('setArgument')->with('key1', 'value1');
        $request->expects($this->at(1))->method('setArgument')->with('key2', 'value2');
        $request->setArguments(['key1' => 'value1', 'key2' => 'value2']);
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerExtensionNameIfPackageKeyIsGiven()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['setControllerExtensionName']);
        $request->expects($this->any())->method('setControllerExtensionName')->with('MyExtension');
        $request->setArgument('@extension', 'MyExtension');
        $this->assertFalse($request->hasArgument('@extension'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerSubpackageKeyIfSubpackageKeyIsGiven()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['setControllerSubpackageKey']);
        $request->expects($this->any())->method('setControllerSubpackageKey')->with('MySubPackage');
        $request->setArgument('@subpackage', 'MySubPackage');
        $this->assertFalse($request->hasArgument('@subpackage'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerNameIfControllerIsGiven()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['setControllerName']);
        $request->expects($this->any())->method('setControllerName')->with('MyController');
        $request->setArgument('@controller', 'MyController');
        $this->assertFalse($request->hasArgument('@controller'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerActionNameIfActionIsGiven()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['setControllerActionName']);
        $request->expects($this->any())->method('setControllerActionName')->with('foo');
        $request->setArgument('@action', 'foo');
        $this->assertFalse($request->hasArgument('@action'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetFormatIfFormatIsGiven()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['setFormat']);
        $request->expects($this->any())->method('setFormat')->with('txt');
        $request->setArgument('@format', 'txt');
        $this->assertFalse($request->hasArgument('@format'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetVendorIfVendorIsGiven()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['setFormat']);
        $request->expects($this->any())->method('setVendor')->with('VENDOR');
        $request->setArgument('@vendor', 'VENDOR');
        $this->assertFalse($request->hasArgument('@vendor'));
    }

    /**
     * @test
     */
    public function internalArgumentsShouldNotBeReturnedAsNormalArgument()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('__referrer', 'foo');
        $this->assertFalse($request->hasArgument('__referrer'));
    }

    /**
     * @test
     */
    public function internalArgumentsShouldBeStoredAsInternalArguments()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('__referrer', 'foo');
        $this->assertSame('foo', $request->getInternalArgument('__referrer'));
    }

    /**
     * @test
     */
    public function hasInternalArgumentShouldReturnNullIfArgumentNotFound()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $this->assertNull($request->getInternalArgument('__nonExistingInternalArgument'));
    }

    /**
     * @test
     */
    public function setArgumentAcceptsObjectIfArgumentIsInternal()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $object = new \stdClass();
        $request->setArgument('__theKey', $object);
        $this->assertSame($object, $request->getInternalArgument('__theKey'));
    }

    /**
     * @test
     */
    public function multipleArgumentsCanBeSetWithSetArgumentsAndRetrievedWithGetArguments()
    {
        $arguments = [
            'firstArgument' => 'firstValue',
            'dænishÅrgument' => 'görman välju',
            '3a' => '3v'
        ];
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArguments($arguments);
        $this->assertEquals($arguments, $request->getArguments());
    }

    /**
     * @test
     */
    public function hasArgumentTellsIfAnArgumentExists()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('existingArgument', 'theValue');
        $this->assertTrue($request->hasArgument('existingArgument'));
        $this->assertFalse($request->hasArgument('notExistingArgument'));
    }

    /**
     * @test
     */
    public function theActionNameCanBeSetAndRetrieved()
    {
        $request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['getControllerObjectName'], [], '', false);
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));
        $request->setControllerActionName('theAction');
        $this->assertEquals('theAction', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function theRepresentationFormatCanBeSetAndRetrieved()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setFormat('html');
        $this->assertEquals('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function theRepresentationFormatIsAutomaticallyLowercased()
    {
        $this->markTestSkipped('Different behavior from TYPO3.Flow because of backwards compatibility.');
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setFormat('hTmL');
        $this->assertEquals('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function aFlagCanBeSetIfTheRequestNeedsToBeDispatchedAgain()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $this->assertFalse($request->isDispatched());
        $request->setDispatched(true);
        $this->assertTrue($request->isDispatched());
    }

    /**
     * DataProvider for explodeObjectControllerName
     *
     * @return array
     */
    public function controllerArgumentsAndExpectedObjectName()
    {
        return [
            'Vendor TYPO3\CMS, extension, controller given' => [
                [
                    'vendorName' => 'TYPO3\\CMS',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ],
                'TYPO3\\CMS\\Ext\\Controller\\FooController',
            ],
            'Vendor TYPO3\CMS, extension, subpackage, controlle given' => [
                [
                    'vendorName' => 'TYPO3\\CMS',
                    'extensionName' => 'Fluid',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Paginate',
                ],
                \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController::class,
            ],
            'Vendor VENDOR, extension, controller given' => [
                [
                    'vendorName' => 'VENDOR',
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\Controller\\FooController',
            ],
            'Vendor VENDOR, extension subpackage, controller given' => [
                [
                    'vendorName' => 'VENDOR',
                    'extensionName' => 'Ext',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\ViewHelpers\\Widget\\Controller\\FooController',
            ],
            'No vendor, extension, controller given' => [
                [
                    'vendorName' => null,
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ],
                'Tx_Ext_Controller_FooController',
            ],
            'No vendor, extension, subpackage, controller given' => [
                [
                    'vendorName' => null,
                    'extensionName' => 'Fluid',
                    'subpackageKey' => 'ViewHelpers_Widget',
                    'controllerName' => 'Paginate',
                ],
                'Tx_Fluid_ViewHelpers_Widget_Controller_PaginateController',
            ],
        ];
    }

    /**
     * @dataProvider controllerArgumentsAndExpectedObjectName
     *
     * @param array $controllerArguments
     * @param string $controllerObjectName
     * @test
     */
    public function getControllerObjectNameResolvesControllerObjectNameCorrectly($controllerArguments, $controllerObjectName)
    {
        /** @var $request \TYPO3\CMS\Extbase\Mvc\Request */
        $request = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['dummy']);
        $request->_set('controllerVendorName', $controllerArguments['vendorName']);
        $request->_set('controllerExtensionName', $controllerArguments['extensionName']);
        $request->_set('controllerSubpackageKey', $controllerArguments['subpackageKey']);
        $request->_set('controllerName', $controllerArguments['controllerName']);

        $this->assertEquals($controllerObjectName, $request->getControllerObjectName());
    }

    /**
     * @dataProvider controllerArgumentsAndExpectedObjectName
     *
     * @param array $controllerArguments
     * @param string $controllerObjectName
     * @test
     */
    public function setControllerObjectNameResolvesControllerObjectNameArgumentsCorrectly($controllerArguments, $controllerObjectName)
    {
        /** @var $request \TYPO3\CMS\Extbase\Mvc\Request */
        $request = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['dummy']);
        $request->setControllerObjectName($controllerObjectName);

        $actualControllerArguments = [
            'vendorName' => $request->_get('controllerVendorName'),
            'extensionName' => $request->_get('controllerExtensionName'),
            'subpackageKey' => $request->_get('controllerSubpackageKey'),
            'controllerName' => $request->_get('controllerName'),
        ];

        $this->assertSame($controllerArguments, $actualControllerArguments);
    }
}
