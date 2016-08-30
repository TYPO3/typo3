<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\View;

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

use TYPO3\CMS\Extbase\Mvc\View\JsonView;

/**
 * Testcase for the JSON view
 *
 */
class JsonViewTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\View\JsonView
     */
    protected $view;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Response
     */
    protected $response;

    /**
     * Sets up this test case
     * @return void
     */
    protected function setUp()
    {
        $this->view = $this->getMock(\TYPO3\CMS\Extbase\Mvc\View\JsonView::class, ['loadConfigurationFromYamlFile']);
        $this->controllerContext = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class, [], [], '', false);
        $this->response = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Web\Response::class, []);
        $this->controllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->view->setControllerContext($this->controllerContext);
    }

    /**
     * data provider for testTransformValue()
     * @return array
     */
    public function jsonViewTestData()
    {
        $output = [];

        $object = new \stdClass();
        $object->value1 = 'foo';
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value1' => 'foo', 'value2' => 1];
        $output[] = [$object, $configuration, $expected, 'all direct child properties should be serialized'];

        $configuration = ['_only' => ['value1']];
        $expected = ['value1' => 'foo'];
        $output[] = [$object, $configuration, $expected, 'if "only" properties are specified, only these should be serialized'];

        $configuration = ['_exclude' => ['value1']];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'if "exclude" properties are specified, they should not be serialized'];

        $object = new \stdClass();
        $object->value1 = new \stdClass();
        $object->value1->subvalue1 = 'Foo';
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'by default, sub objects of objects should not be serialized.'];

        $object = new \stdClass();
        $object->value1 = ['subarray' => 'value'];
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'by default, sub arrays of objects should not be serialized.'];

        $object = ['foo' => 'bar', 1 => 'baz', 'deep' => ['test' => 'value']];
        $configuration = [];
        $expected = ['foo' => 'bar', 1 => 'baz', 'deep' => ['test' => 'value']];
        $output[] = [$object, $configuration, $expected, 'associative arrays should be serialized deeply'];

        $object = ['foo', 'bar'];
        $configuration = [];
        $expected = ['foo', 'bar'];
        $output[] = [$object, $configuration, $expected, 'numeric arrays should be serialized'];

        $nestedObject = new \stdClass();
        $nestedObject->value1 = 'foo';
        $object = [$nestedObject];
        $configuration = [];
        $expected = [['value1' => 'foo']];
        $output[] = [$object, $configuration, $expected, 'array of objects should be serialized'];

        $properties = ['foo' => 'bar', 'prohibited' => 'xxx'];
        $nestedObject = $this->getMock($this->getUniqueId('Test'), ['getName', 'getPath', 'getProperties', 'getOther']);
        $nestedObject->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $nestedObject->expects($this->any())->method('getPath')->will($this->returnValue('path'));
        $nestedObject->expects($this->any())->method('getProperties')->will($this->returnValue($properties));
        $nestedObject->expects($this->never())->method('getOther');
        $object = $nestedObject;
        $configuration = [
            '_only' => ['name', 'path', 'properties'],
            '_descend' => [
                 'properties' => [
                      '_exclude' => ['prohibited']
                 ]
            ]
        ];
        $expected = [
            'name' => 'name',
            'path' => 'path',
            'properties' => ['foo' => 'bar']
        ];
        $output[] = [$object, $configuration, $expected, 'descending into arrays should be possible'];

        $nestedObject = new \stdClass();
        $nestedObject->value1 = 'foo';
        $value = new \SplObjectStorage();
        $value->attach($nestedObject);
        $configuration = [];
        $expected = [['value1' => 'foo']];
        $output[] = [$value, $configuration, $expected, 'SplObjectStorage with objects should be serialized'];

        $dateTimeObject = new \DateTime('2011-02-03T03:15:23', new \DateTimeZone('UTC'));
        $configuration = [];
        $expected = '2011-02-03T03:15:23+0000';
        $output[] = [$dateTimeObject, $configuration, $expected, 'DateTime object in UTC time zone could not be serialized.'];

        $dateTimeObject = new \DateTime('2013-08-15T15:25:30', new \DateTimeZone('America/Los_Angeles'));
        $configuration = [];
        $expected = '2013-08-15T15:25:30-0700';
        $output[] = [$dateTimeObject, $configuration, $expected, 'DateTime object in America/Los_Angeles time zone could not be serialized.'];
        return $output;
    }

    /**
     * @test
     * @dataProvider jsonViewTestData
     */
    public function testTransformValue($object, $configuration, $expected, $description)
    {
        $jsonView = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\View\JsonView::class, ['dummy'], [], '', false);

        $actual = $jsonView->_call('transformValue', $object, $configuration);

        $this->assertEquals($expected, $actual, $description);
    }

    /**
     * data provider for testTransformValueWithObjectIdentifierExposure()
     * @return array
     */
    public function objectIdentifierExposureTestData()
    {
        $output = [];

        $dummyIdentifier = 'e4f40dfc-8c6e-4414-a5b1-6fd3c5cf7a53';

        $object = new \stdClass();
        $object->value1 = new \stdClass();
        $configuration = [
            '_descend' => [
                 'value1' => [
                      '_exposeObjectIdentifier' => true
                 ]
            ]
        ];

        $expected = ['value1' => ['__identity' => $dummyIdentifier]];
        $output[] = [$object, $configuration, $expected, $dummyIdentifier, 'boolean TRUE should result in __identity key'];

        $configuration['_descend']['value1']['_exposedObjectIdentifierKey'] = 'guid';
        $expected = ['value1' => ['guid' => $dummyIdentifier]];
        $output[] = [$object, $configuration, $expected, $dummyIdentifier, 'string value should result in string-equal key'];

        return $output;
    }

    /**
     * @test
     * @dataProvider objectIdentifierExposureTestData
     */
    public function testTransformValueWithObjectIdentifierExposure($object, $configuration, $expected, $dummyIdentifier, $description)
    {
        $persistenceManagerMock = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, ['getIdentifierByObject']);
        $jsonView = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\View\JsonView::class, ['dummy'], [], '', false);
        $jsonView->_set('persistenceManager', $persistenceManagerMock);

        $persistenceManagerMock->expects($this->once())->method('getIdentifierByObject')->with($object->value1)->will($this->returnValue($dummyIdentifier));

        $actual = $jsonView->_call('transformValue', $object, $configuration);

        $this->assertEquals($expected, $actual, $description);
    }

    /**
     * A data provider
     */
    public function exposeClassNameSettingsAndResults()
    {
        $className = $this->getUniqueId('DummyClass');
        $namespace = 'TYPO3\CMS\Extbase\Tests\Unit\Mvc\View\\' . $className;
        return [
            [
                JsonView::EXPOSE_CLASSNAME_FULLY_QUALIFIED,
                $className,
                $namespace,
                ['value1' => ['__class' => $namespace . '\\' . $className]]
            ],
            [
                JsonView::EXPOSE_CLASSNAME_UNQUALIFIED,
                $className,
                $namespace,
                ['value1' => ['__class' => $className]]
            ],
            [
                null,
                $className,
                $namespace,
                ['value1' => []]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider exposeClassNameSettingsAndResults
     */
    public function viewExposesClassNameFullyIfConfiguredSo($exposeClassNameSetting, $className, $namespace, $expected)
    {
        $fullyQualifiedClassName = $namespace . '\\' . $className;
        if (class_exists($fullyQualifiedClassName) === false) {
            eval('namespace ' . $namespace . '; class ' . $className . ' {}');
        }

        $object = new \stdClass();
        $object->value1 = new $fullyQualifiedClassName();
        $configuration = [
            '_descend' => [
                'value1' => [
                    '_exposeClassName' => $exposeClassNameSetting
                ]
            ]
        ];
        $reflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $reflectionService->expects($this->any())->method('getClassNameByObject')->will($this->returnCallback(function ($object) {
            return get_class($object);
        }));

        $jsonView = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\View\JsonView::class, ['dummy'], [], '', false);
        $this->inject($jsonView, 'reflectionService', $reflectionService);
        $actual = $jsonView->_call('transformValue', $object, $configuration);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderSetsContentTypeHeader()
    {
        $this->response->expects($this->once())->method('setHeader')->with('Content-Type', 'application/json');

        $this->view->render();
    }

    /**
     * @test
     */
    public function renderReturnsJsonRepresentationOfAssignedObject()
    {
        $object = new \stdClass();
        $object->foo = 'Foo';
        $this->view->assign('value', $object);

        $expectedResult = '{"foo":"Foo"}';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsJsonRepresentationOfAssignedArray()
    {
        $array = ['foo' => 'Foo', 'bar' => 'Bar'];
        $this->view->assign('value', $array);

        $expectedResult = '{"foo":"Foo","bar":"Bar"}';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsJsonRepresentationOfAssignedSimpleValue()
    {
        $value = 'Foo';
        $this->view->assign('value', $value);

        $expectedResult = '"Foo"';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsNullIfNameOfAssignedVariableIsNotEqualToValue()
    {
        $value = 'Foo';
        $this->view->assign('foo', $value);

        $expectedResult = 'null';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderOnlyRendersVariableWithTheNameValue()
    {
        $this->view
            ->assign('value', 'Value')
            ->assign('someOtherVariable', 'Foo');

        $expectedResult = '"Value"';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function setVariablesToRenderOverridesValueToRender()
    {
        $value = 'Foo';
        $this->view->assign('foo', $value);
        $this->view->setVariablesToRender(['foo']);

        $expectedResult = '"Foo"';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRendersMultipleValuesIfTheyAreSpecifiedAsVariablesToRender()
    {
        $this->view
            ->assign('value', 'Value1')
            ->assign('secondValue', 'Value2')
            ->assign('someOtherVariable', 'Value3');
        $this->view->setVariablesToRender(['value', 'secondValue']);

        $expectedResult = '{"value":"Value1","secondValue":"Value2"}';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCanRenderMultipleComplexObjects()
    {
        $array = ['foo' => ['bar' => 'Baz']];
        $object = new \stdClass();
        $object->foo = 'Foo';

        $this->view
            ->assign('array', $array)
            ->assign('object', $object)
            ->assign('someOtherVariable', 'Value3');
        $this->view->setVariablesToRender(['array', 'object']);

        $expectedResult = '{"array":{"foo":{"bar":"Baz"}},"object":{"foo":"Foo"}}';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCanRenderPlainArray()
    {
        $array = [['name' => 'Foo', 'secret' => true], ['name' => 'Bar', 'secret' => true]];

        $this->view->assign('value', $array);
        $this->view->setConfiguration([
            'value' => [
                '_descendAll' => [
                    '_only' => ['name']
                ]
            ]
        ]);

        $expectedResult = '[{"name":"Foo"},{"name":"Bar"}]';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function descendAllKeepsArrayIndexes()
    {
        $array = [['name' => 'Foo', 'secret' => true], ['name' => 'Bar', 'secret' => true]];

        $this->view->assign('value', $array);
        $this->view->setConfiguration([
            'value' => [
                '_descendAll' => [
                    '_descendAll' => []
                ]
            ]
        ]);

        $expectedResult = '[{"name":"Foo","secret":true},{"name":"Bar","secret":true}]';
        $actualResult = $this->view->render();
        $this->assertEquals($expectedResult, $actualResult);
    }
}
