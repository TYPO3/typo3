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
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RequestTest extends UnitTestCase
{
    /**
     * @test
     */
    public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('someArgumentName', 'theValue');
        self::assertEquals('theValue', $request->getArgument('someArgumentName'));
    }

    /**
     * @test
     */
    public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsNoString()
    {
        $this->expectException(InvalidArgumentNameException::class);
        $this->expectExceptionCode(1210858767);
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument(123, 'theValue');
    }

    /**
     * @test
     */
    public function setArgumentThrowsExceptionIfTheGivenArgumentNameIsAnEmptyString()
    {
        $this->expectException(InvalidArgumentNameException::class);
        $this->expectExceptionCode(1210858767);
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('', 'theValue');
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
        self::assertEquals($arguments, $actualResult);
    }

    /**
     * @test
     */
    public function setArgumentsCallsSetArgumentForEveryArrayEntry()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)
            ->setMethods(['setArgument'])
            ->getMock();
        $request->expects(self::at(0))->method('setArgument')->with('key1', 'value1');
        $request->expects(self::at(1))->method('setArgument')->with('key2', 'value2');
        $request->setArguments(['key1' => 'value1', 'key2' => 'value2']);
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerExtensionNameIfPackageKeyIsGiven()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)
            ->setMethods(['setControllerExtensionName'])
            ->getMock();
        $request->expects(self::any())->method('setControllerExtensionName')->with('MyExtension');
        $request->setArgument('@extension', 'MyExtension');
        self::assertFalse($request->hasArgument('@extension'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerSubpackageKeyIfSubpackageKeyIsGiven()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)
            ->setMethods(['setControllerSubpackageKey'])
            ->getMock();
        $request->expects(self::any())->method('setControllerSubpackageKey')->with('MySubPackage');
        $request->setArgument('@subpackage', 'MySubPackage');
        self::assertFalse($request->hasArgument('@subpackage'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerNameIfControllerIsGiven()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)
            ->setMethods(['setControllerName'])
            ->getMock();
        $request->expects(self::any())->method('setControllerName')->with('MyController');
        $request->setArgument('@controller', 'MyController');
        self::assertFalse($request->hasArgument('@controller'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetControllerActionNameIfActionIsGiven()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)
            ->setMethods(['setControllerActionName'])
            ->getMock();
        $request->expects(self::any())->method('setControllerActionName')->with('foo');
        $request->setArgument('@action', 'foo');
        self::assertFalse($request->hasArgument('@action'));
    }

    /**
     * @test
     */
    public function setArgumentShouldSetFormatIfFormatIsGiven()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)
            ->setMethods(['setFormat'])
            ->getMock();
        $request->expects(self::any())->method('setFormat')->with('txt');
        $request->setArgument('@format', 'txt');
        self::assertFalse($request->hasArgument('@format'));
    }

    /**
     * @test
     */
    public function internalArgumentsShouldNotBeReturnedAsNormalArgument()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('__referrer', 'foo');
        self::assertFalse($request->hasArgument('__referrer'));
    }

    /**
     * @test
     */
    public function internalArgumentsShouldBeStoredAsInternalArguments()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('__referrer', 'foo');
        self::assertSame('foo', $request->getInternalArgument('__referrer'));
    }

    /**
     * @test
     */
    public function hasInternalArgumentShouldReturnNullIfArgumentNotFound()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        self::assertNull($request->getInternalArgument('__nonExistingInternalArgument'));
    }

    /**
     * @test
     */
    public function setArgumentAcceptsObjectIfArgumentIsInternal()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $object = new \stdClass();
        $request->setArgument('__theKey', $object);
        self::assertSame($object, $request->getInternalArgument('__theKey'));
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
        self::assertEquals($arguments, $request->getArguments());
    }

    /**
     * @test
     */
    public function hasArgumentTellsIfAnArgumentExists()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setArgument('existingArgument', 'theValue');
        self::assertTrue($request->hasArgument('existingArgument'));
        self::assertFalse($request->hasArgument('notExistingArgument'));
    }

    /**
     * @test
     */
    public function theActionNameCanBeSetAndRetrieved()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)
            ->setMethods(['getControllerObjectName'])
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects(self::once())->method('getControllerObjectName')->will(self::returnValue(''));
        $request->setControllerActionName('theAction');
        self::assertEquals('theAction', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function theRepresentationFormatCanBeSetAndRetrieved()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        $request->setFormat('html');
        self::assertEquals('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function aFlagCanBeSetIfTheRequestNeedsToBeDispatchedAgain()
    {
        $request = new \TYPO3\CMS\Extbase\Mvc\Request();
        self::assertFalse($request->isDispatched());
        $request->setDispatched(true);
        self::assertTrue($request->isDispatched());
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
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ],
                'TYPO3\\CMS\\Ext\\Controller\\FooController',
            ],
            'Vendor TYPO3\CMS, extension, subpackage, controlle given' => [
                [
                    'extensionName' => 'Fluid',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Paginate',
                ],
                \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\PaginateController::class,
            ],
            'Vendor VENDOR, extension, controller given' => [
                [
                    'extensionName' => 'Ext',
                    'subpackageKey' => '',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\Controller\\FooController',
            ],
            'Vendor VENDOR, extension subpackage, controller given' => [
                [
                    'extensionName' => 'Ext',
                    'subpackageKey' => 'ViewHelpers\\Widget',
                    'controllerName' => 'Foo',
                ],
                'VENDOR\\Ext\\ViewHelpers\\Widget\\Controller\\FooController',
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
    public function setControllerObjectNameResolvesControllerObjectNameArgumentsCorrectly($controllerArguments, $controllerObjectName)
    {
        /** @var $request \TYPO3\CMS\Extbase\Mvc\Request */
        $request = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Request::class, ['dummy'], [], '', false);
        $request->setControllerObjectName($controllerObjectName);

        $actualControllerArguments = [
            'extensionName' => $request->_get('controllerExtensionName'),
            'subpackageKey' => $request->_get('controllerSubpackageKey'),
            'controllerName' => $request->_get('controllerName'),
        ];

        self::assertSame($controllerArguments, $actualControllerArguments);
    }
}
