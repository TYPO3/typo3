<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

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
 * Test for the "Select" Form view helper
 */
class SelectViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper
     */
    protected $viewHelper;

    /**
     * @var array Backup of current locale, it is manipulated in tests
     */
    protected $backupLocales = array();

    protected function setUp()
    {
        parent::setUp();
        // Store all locale categories manipulated in tests for reconstruction in tearDown
        $this->backupLocales = array(
            'LC_COLLATE' => setlocale(LC_COLLATE, 0),
            'LC_CTYPE' => setlocale(LC_CTYPE, 0),
            'LC_MONETARY' => setlocale(LC_MONETARY, 0),
            'LC_TIME' => setlocale(LC_TIME, 0),
        );
        $this->arguments['name'] = '';
        $this->arguments['sortByOptionLabel'] = false;
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper::class, array('setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration'));
    }

    protected function tearDown()
    {
        foreach ($this->backupLocales as $category => $locale) {
            setlocale(constant($category), $locale);
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function selectCorrectlySetsTagName()
    {
        $this->tagBuilder->expects($this->once())->method('setTagName')->with('select');

        $this->arguments['options'] = array();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptions()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2'
        );
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArraysAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with(
            '<option value="2"></option>' . chr(10) .
            '<option value="-1">Bar</option>' . chr(10) .
            '<option value="">Baz</option>' . chr(10) .
            '<option value="1">Foo</option>' . chr(10)
        );

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = array(
            array(
                'uid' => 1,
                'title' => 'Foo'
            ),
            array(
                'uid' => -1,
                'title' => 'Bar'
            ),
            array(
                'title' => 'Baz'
            ),
            array(
                'uid' => '2'
            ),
        );

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithStdClassesAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with(
            '<option value="2"></option>' . chr(10) .
            '<option value="-1">Bar</option>' . chr(10) .
            '<option value="">Baz</option>' . chr(10) .
            '<option value="1">Foo</option>' . chr(10)
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
        $this->arguments['options'] = array($obj1, $obj2, $obj3, $obj4);

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArrayObjectsAndOptionValueFieldAndOptionLabelFieldSet()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with(
            '<option value="2"></option>' . chr(10) .
            '<option value="-1">Bar</option>' . chr(10) .
            '<option value="">Baz</option>' . chr(10) .
            '<option value="1">Foo</option>' . chr(10)
        );

        $this->arguments['optionValueField'] = 'uid';
        $this->arguments['optionLabelField'] = 'title';
        $this->arguments['sortByOptionLabel'] = true;
        $this->arguments['options'] = new \ArrayObject(array(
            array(
                'uid' => 1,
                'title' => 'Foo'
            ),
            array(
                'uid' => -1,
                'title' => 'Bar'
            ),
            array(
                'title' => 'Baz'
            ),
            array(
                'uid' => '2'
            ),
        ));

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function anEmptyOptionTagIsRenderedIfOptionsArrayIsEmptyToAssureXhtmlCompatibility()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value=""></option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array();
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function OrderOfOptionsIsNotAlteredByDefault()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value3">label3</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array(
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        );

        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $this->arguments['options'] = array(
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2'
        );

        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->arguments['sortByOptionLabel'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSetAndLocaleEqualsUtf8()
    {
        $locale = 'de_DE.UTF-8';
        if (!setlocale(LC_COLLATE, $locale)) {
            $this->markTestSkipped('Locale ' . $locale . ' is not available.');
        }
        if (stristr(PHP_OS, 'Darwin')) {
            $this->markTestSkipped('Test skipped caused by a bug in the C libraries on BSD/OSX');
        }

        setlocale(LC_CTYPE, $locale);
        setlocale(LC_MONETARY, $locale);
        setlocale(LC_TIME, $locale);
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">Bamberg</option>' . chr(10) . '<option value="value2" selected="selected">Bämm</option>' . chr(10) . '<option value="value3">Bar</option>' . chr(10) . '<option value="value4">Bär</option>' . chr(10) . '<option value="value5">Burg</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->arguments['options'] = array(
            'value4' => 'Bär',
            'value2' => 'Bämm',
            'value5' => 'Burg',
            'value1' => 'Bamberg',
            'value3' => 'Bar'
        );
        $this->arguments['value'] = 'value2';
        $this->arguments['name'] = 'myName';
        $this->arguments['sortByOptionLabel'] = true;
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function multipleSelectCreatesExpectedOptions()
    {
        $this->tagBuilder = new \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder();

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );

        $this->arguments['value'] = array('value3', 'value1');
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $result = $this->viewHelper->render();
        $expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3" selected="selected">label3</option>' . chr(10) . '</select>';
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsCreatesExpectedOptions()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue(2));
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName[__identity]');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName[__identity]');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="1">Ingmar</option>' . chr(10) . '<option value="2" selected="selected">Sebastian</option>' . chr(10) . '<option value="3">Robert</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user_is = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = array(
            $user_is,
            $user_sk,
            $user_rl
        );

        $this->arguments['value'] = $user_sk;
        $this->arguments['optionValueField'] = 'id';
        $this->arguments['optionLabelField'] = 'firstName';
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptions()
    {
        $this->tagBuilder = new \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder();
        $this->viewHelper->expects($this->exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');
        $this->arguments['options'] = array(
            $user_is,
            $user_sk,
            $user_rl
        );
        $this->arguments['value'] = array($user_rl, $user_is);
        $this->arguments['optionValueField'] = 'id';
        $this->arguments['optionLabelField'] = 'lastName';
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $actual = $this->viewHelper->render();
        $expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="1" selected="selected">Schlecht</option>' . chr(10) .
            '<option value="2">Kurfuerst</option>' . chr(10) .
            '<option value="3" selected="selected">Lemke</option>' . chr(10) .
            '</select>';

        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptionsWithoutOptionValueField()
    {
        /** @var $mockPersistenceManager \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface */
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnCallback(
            function ($object) {
                return $object->getId();
            }
        ));
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder = new \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder();
        $this->viewHelper->expects($this->exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

        $user_is = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(3, 'Robert', 'Lemke');

        $this->arguments['options'] = array($user_is,$user_sk,$user_rl);
        $this->arguments['value'] = array($user_rl, $user_is);
        $this->arguments['optionLabelField'] = 'lastName';
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
        $actual = $this->viewHelper->render();
        $expected = '<input type="hidden" name="myName" value="" />' .
            '<select multiple="multiple" name="myName[]">' .
            '<option value="1" selected="selected">Schlecht</option>' . chr(10) .
            '<option value="2">Kurfuerst</option>' . chr(10) .
            '<option value="3" selected="selected">Lemke</option>' . chr(10) .
            '</select>';
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('fakeUID'));
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUID">fakeUID</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = array(
            $user
        );
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesToStringForLabelIfAvailable()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('fakeUID'));
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUID">toStringResult</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');

        $user = $this->getMockBuilder(\TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass::class)
            ->setMethods(array('__toString'))
            ->setConstructorArgs(array(1, 'Ingmar', 'Schlecht'))
            ->getMock();
        $user->expects($this->atLeastOnce())->method('__toString')->will($this->returnValue('toStringResult'));

        $this->arguments['options'] = array(
            $user
        );
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue(null));
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->expectException(\TYPO3\CMS\Fluid\Core\ViewHelper\Exception::class);
        $this->expectExceptionCode(1247826696);

        $user = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\UserDomainClass(1, 'Ingmar', 'Schlecht');

        $this->arguments['options'] = array(
            $user
        );
        $this->arguments['name'] = 'myName';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->arguments['options'] = array();

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function allOptionsAreSelectedIfSelectAllIsTrue()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3" selected="selected">label3</option>' . chr(10));

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;
        $this->arguments['selectAllByDefault'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function selectAllHasNoEffectIfValueIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));

        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['value'] = array('value2', 'value1');
        $this->arguments['name'] = 'myName';
        $this->arguments['multiple'] = true;
        $this->arguments['selectAllByDefault'] = true;

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithEmptyValueIfPrependOptionLabelIsSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'please choose';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithCorrectValueIfPrependOptionLabelAndPrependOptionValueAreSet()
    {
        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
        $this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="-1">please choose</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
        $this->tagBuilder->expects($this->once())->method('render');
        $this->arguments['options'] = array(
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3'
        );
        $this->arguments['name'] = 'myName';
        $this->arguments['prependOptionLabel'] = 'please choose';
        $this->arguments['prependOptionValue'] = '-1';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
