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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

use PHPUnit\Framework\Exception;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass;
use TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Select" Form view helper
 */
class SelectViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var SelectViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arguments['name'] = '';
        $this->arguments['sortByOptionLabel'] = false;
        $this->viewHelper = $this->getAccessibleMock(SelectViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration', 'renderChildren']);
        $this->tagBuilder = $this->createMock(TagBuilder::class);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
    }

    /**
     * @test
     */
    public function selectCorrectlySetsTagName()
    {
        $this->tagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('select');

        $this->arguments['options'] = [];
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptions()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1">label1</option>' . \chr(10) . '<option value="value2" selected="selected">label2</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2'
        ];
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectShouldSetTheRequiredAttribute()
    {
        $this->tagBuilder->expects(self::exactly(2))->method('addAttribute')->withConsecutive(
            ['required', 'required'],
            ['name', 'myName']
        );
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1">label1</option>' . \chr(10) . '<option value="value2" selected="selected">label2</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2'
        ];
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->arguments['required'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArraysAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder->expects(self::once())->method('setContent')->with(
            '<option value="2"></option>' . \chr(10) .
            '<option value="-1">Bar</option>' . \chr(10) .
            '<option value="">Baz</option>' . \chr(10) .
            '<option value="1">Foo</option>' . \chr(10)
        );

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = [
            [
                'uid' => 1,
                'title' => 'Foo'
            ],
            [
                'uid' => -1,
                'title' => 'Bar'
            ],
            [
                'title' => 'Baz'
            ],
            [
                'uid' => '2'
            ],
        ];

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithStdClassesAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder->expects(self::once())->method('setContent')->with(
            '<option value="2"></option>' . \chr(10) .
            '<option value="-1">Bar</option>' . \chr(10) .
            '<option value="">Baz</option>' . \chr(10) .
            '<option value="1">Foo</option>' . \chr(10)
        );

        $obj1 = new \stdClass();
        $obj1->uid = 1;
        $obj1->title = 'Foo';

        $obj2 = new \stdClass();
        $obj2->uid = -1;
        $obj2->title = 'Bar';

        $obj3 = new \stdClass();
        $obj3->title = 'Baz';

        $obj4 = new \stdClass();
        $obj4->uid = 2;

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = [$obj1, $obj2, $obj3, $obj4];

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArrayObjectsAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder->expects(self::once())->method('setContent')->with(
            '<option value="2"></option>' . \chr(10) .
            '<option value="-1">Bar</option>' . \chr(10) .
            '<option value="">Baz</option>' . \chr(10) .
            '<option value="1">Foo</option>' . \chr(10)
        );

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = new \ArrayObject([
            [
                'uid' => 1,
                'title' => 'Foo'
            ],
            [
                'uid' => -1,
                'title' => 'Bar'
            ],
            [
                'title' => 'Baz'
            ],
            [
                'uid' => '2'
            ],
        ]);

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function OrderOfOptionsIsNotAlteredByDefault()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value3">label3</option>' . \chr(10) . '<option value="value1">label1</option>' . \chr(10) . '<option value="value2" selected="selected">label2</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        ];

        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSet()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1">label1</option>' . \chr(10) . '<option value="value2" selected="selected">label2</option>' . \chr(10) . '<option value="value3">label3</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $this->arguments['options'] = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        ];

        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->arguments['sortByOptionLabel'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     * @requires OSFAMILY Linux (bug in the C libraries on BSD/OSX | unavailable locale on Windows)
     */
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSetAndLocaleEqualsUtf8()
    {
        $locale = 'de_DE.UTF-8';
        try {
            $this->setLocale(LC_COLLATE, $locale);
            $this->setLocale(LC_CTYPE, $locale);
            $this->setLocale(LC_MONETARY, $locale);
            $this->setLocale(LC_TIME, $locale);
        } catch (Exception $e) {
            self::markTestSkipped('Locale ' . $locale . ' is not available.');
        }

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1">Bamberg</option>' . \chr(10) . '<option value="value2" selected="selected">Bämm</option>' . \chr(10) . '<option value="value3">Bar</option>' . \chr(10) . '<option value="value4">Bär</option>' . \chr(10) . '<option value="value5">Burg</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');
        $this->arguments['options'] = [
            'value4' => 'Bär',
            'value2' => 'Bämm',
            'value5' => 'Burg',
            'value1' => 'Bamberg',
            'value3' => 'Bar'
        ];
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->arguments['sortByOptionLabel'] = true;
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function multipleSelectCreatesExpectedOptions()
    {
        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects(self::exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];

        $this->arguments['value'] = ['value3', 'value1'];
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $result = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>' . \chr(10) . '<option value="value2">label2</option>' . \chr(10) . '<option value="value3" selected="selected">label3</option>' . \chr(10) . '</select>';
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function multipleSelectWithoutOptionsCreatesExpectedOptions()
    {
        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $this->arguments['options'] = [];
        $this->arguments['value'] = ['value3', 'value1'];
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $result = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"></select>';
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsCreatesExpectedOptions()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn(2);
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName[__identity]');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName[__identity]');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="1">Ingmar</option>' . \chr(10) . '<option value="2" selected="selected">Sebastian</option>' . \chr(10) . '<option value="3">Robert</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = [
            $user_is,
            $user_sk,
            $user_rl
        ];

        $this->arguments['value'] = $user_sk;
        $this->arguments['optionValueField'] = 'id';
        $this->arguments['optionLabelField'] = 'firstName';
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptions()
    {
        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects(self::exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');
        $this->arguments['options'] = [
            $user_is,
            $user_sk,
            $user_rl
        ];
        $this->arguments['value'] = [$user_rl, $user_is];
        $this->arguments['optionValueField'] = 'id';
        $this->arguments['optionLabelField'] = 'lastName';
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $actual = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="1" selected="selected">Schlecht</option>' . \chr(10) .
            '<option value="2">Kurfuerst</option>' . \chr(10) .
            '<option value="3" selected="selected">Lemke</option>' . \chr(10) .
            '</select>';

        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptionsWithoutOptionValueField()
    {
        /** @var $mockPersistenceManager \PHPUnit\Framework\MockObject\MockObject|PersistenceManagerInterface */
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturnCallback(
            function ($object) {
                return $object->getId();
            }
        );
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder = new TagBuilder();
        $this->viewHelper->expects(self::exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = [$user_is, $user_sk, $user_rl];
        $this->arguments['value'] = [$user_rl, $user_is];
        $this->arguments['optionLabelField'] = 'lastName';
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $actual = $this->viewHelper->initializeArgumentsAndRender();
        $expected = '<input type="hidden" name="myName" value="" />' .
            '<select multiple="multiple" name="myName[]">' .
            '<option value="1" selected="selected">Schlecht</option>' . \chr(10) .
            '<option value="2">Kurfuerst</option>' . \chr(10) .
            '<option value="3" selected="selected">Lemke</option>' . \chr(10) .
            '</select>';
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn('fakeUID');
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="fakeUID">fakeUID</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $user = new UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = [
            $user
        ];
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesToStringForLabelIfAvailable()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn('fakeUID');
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="fakeUID">toStringResult</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');

        $user = $this->getMockBuilder(UserDomainClass::class)
            ->setMethods(['__toString'])
            ->setConstructorArgs([1, 'Ingmar', 'Schlecht'])
            ->getMock();
        $user->expects(self::atLeastOnce())->method('__toString')->willReturn('toStringResult');

        $this->arguments['options'] = [
            $user
        ];
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn(null);
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->expectException(\TYPO3Fluid\Fluid\Core\ViewHelper\Exception::class);
        $this->expectExceptionCode(1247826696);

        $user = new UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = [
            $user
        ];
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->arguments['options'] = [];

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function allOptionsAreSelectedIfSelectAllIsTrue()
    {
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . \chr(10) . '<option value="value2" selected="selected">label2</option>' . \chr(10) . '<option value="value3" selected="selected">label3</option>' . \chr(10));

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;
        $this->arguments['selectAllByDefault'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function selectAllHasNoEffectIfValueIsSet()
    {
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . \chr(10) . '<option value="value2" selected="selected">label2</option>' . \chr(10) . '<option value="value3">label3</option>' . \chr(10));

        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
        $this->arguments['value'] = ['value2', 'value1'];
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;
        $this->arguments['selectAllByDefault'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithEmptyValueIfPrependOptionLabelIsSet()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="">please choose</option>' . \chr(10) . '<option value="value1">label1</option>' . \chr(10) . '<option value="value2">label2</option>' . \chr(10) . '<option value="value3">label3</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');
        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'please choose';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithCorrectValueIfPrependOptionLabelAndPrependOptionValueAreSet()
    {
        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects(self::once())->method('setContent')->with('<option value="-1">please choose</option>' . \chr(10) . '<option value="value1">label1</option>' . \chr(10) . '<option value="value2">label2</option>' . \chr(10) . '<option value="value3">label3</option>' . \chr(10));
        $this->tagBuilder->expects(self::once())->method('render');
        $this->arguments['options'] = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        ];
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'please choose';
        $this->arguments['prependOptionValue'] = '-1';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setTagBuilder($this->tagBuilder);
        $this->viewHelper->initializeArgumentsAndRender();
    }
}
