<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserDomainClass;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserDomainClassToString;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class SelectViewHelperTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function selectCorrectlySetsTagName(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select />');
        self::assertSame('<select name=""></select>', $view->render());
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptions(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select name="myName" value="value2" options="{value1: \"label1\", value2: \"label2\"}" />');
        $expected = '<select name="myName"><option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '</select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectShouldSetTheRequiredAttribute(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.select name="myName" required="true" value="value2" options="{value1: \"label1\", value2: \"label2\"}" />');
        $expected = '<select required="required" name="myName"><option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '</select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArraysAndOptionValueFieldAndOptionLabelFieldSet(): void
    {
        $options = [
            [
                'uid' => 1,
                'title' => 'Foo',
            ],
            [
                'uid' => -1,
                'title' => 'Bar',
            ],
            [
                'title' => 'Baz',
            ],
            [
                'uid' => '2',
            ],
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithStdClassesAndOptionValueFieldAndOptionLabelFieldSet(): void
    {
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

        $options = [$obj1, $obj2, $obj3, $obj4];

        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectCreatesExpectedOptionsWithArrayObjectsAndOptionValueFieldAndOptionLabelFieldSet(): void
    {
        $options = new \ArrayObject([
            [
                'uid' => 1,
                'title' => 'Foo',
            ],
            [
                'uid' => -1,
                'title' => 'Bar',
            ],
            [
                'title' => 'Baz',
            ],
            [
                'uid' => '2',
            ],
        ]);
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function OrderOfOptionsIsNotAlteredByDefault(): void
    {
        $options = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2',
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" value="value2" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="value3">label3</option>
<option value="value1">label1</option>
<option value="value2" selected="selected">label2</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSet(): void
    {
        $options = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2',
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" sortByOptionLabel="true" value="value2" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="value1">label1</option>
<option value="value2" selected="selected">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function multipleSelectCreatesExpectedOptions(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $value = ['value3', 'value1'];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->assign('value', $value);
        $view->setTemplateSource('<f:form.select multiple="true" name="myName" value="{value}" options="{options}" />');
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>
<option value="value2">label2</option>
<option value="value3" selected="selected">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function multipleSelectWithoutOptionsCreatesExpectedOptions(): void
    {
        $value = ['value3', 'value1'];
        $view = new StandaloneView();
        $view->assign('value', $value);
        $view->setTemplateSource('<f:form.select multiple="true" name="myName" value="{value}" options="{}" />');
        $expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"></select>';
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsCreatesExpectedOptions(): void
    {
        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');
        $options = [
            $user_is,
            $user_sk,
            $user_rl,
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->assign('value', $user_sk);
        $view->setTemplateSource('<f:form.select name="myName" optionValueField="id" optionLabelField="firstName" value="{value}" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="1">Ingmar</option>
<option value="2" selected="selected">Sebastian</option>
<option value="3">Robert</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptions(): void
    {
        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');
        $options = [
            $user_is,
            $user_sk,
            $user_rl,
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->assign('value', [$user_rl, $user_is]);
        $view->setTemplateSource('<f:form.select multiple="true" name="myName" optionValueField="id" optionLabelField="firstName" value="{value}" options="{options}" />');
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="1" selected="selected">Ingmar</option>
<option value="2">Sebastian</option>
<option value="3" selected="selected">Robert</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function multipleSelectOnDomainObjectsCreatesExpectedOptionsWithoutOptionValueField(): void
    {
        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $user_sk = new UserDomainClass(2, 'Sebastian', 'Kurfuerst');
        $user_rl = new UserDomainClass(3, 'Robert', 'Lemke');
        $options = [
            $user_is,
            $user_sk,
            $user_rl,
        ];

        // Mock persistence manager for our domain objects and set into container
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturnCallback(
            static function ($object) {
                return $object->getId();
            }
        );
        $container = $this->getContainer();
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->assign('value', [$user_rl, $user_is]);
        $view->setTemplateSource('<f:form.select multiple="true" name="myName" optionLabelField="firstName" value="{value}" options="{options}" />');
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="1" selected="selected">Ingmar</option>
<option value="2">Sebastian</option>
<option value="3" selected="selected">Robert</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel(): void
    {
        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $options = [
            $user_is,
        ];

        // Mock persistence manager for our domain objects and set into container
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn('fakeUid');
        $container = $this->getContainer();
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="fakeUid">fakeUid</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesToStringForLabelIfAvailable(): void
    {
        $user_is = new UserDomainClassToString(1, 'Ingmar', 'Schlecht');
        $options = [
            $user_is,
        ];

        // Mock persistence manager for our domain objects and set into container
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn('fakeUid');
        $container = $this->getContainer();
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="fakeUid">IngmarToString</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound(): void
    {
        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $options = [
            $user_is,
        ];

        // Mock persistence manager for our domain objects and set into container
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn(null);
        $container = $this->getContainer();
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select name="myName" options="{options}" />');

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1247826696);

        $view->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute(): void
    {
        // Create an extbase request that contains mapping results of the form object property we're working with.
        $mappingResult = new Result();
        $objectResult = $mappingResult->forProperty('myObjectName');
        $propertyResult = $objectResult->forProperty('someProperty');
        $propertyResult->addError(new Error('invalidProperty', 2));
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setOriginalRequestMappingResults($mappingResult);
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters);
        $extbaseRequest = new Request($psr7Request);
        GeneralUtility::addInstance(Request::class, $extbaseRequest);

        $formObject = new \stdClass();
        $view = new StandaloneView();
        $view->assign('formObject', $formObject);
        $view->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.select property="someProperty" errorClass="myError" /></f:form>');
        // The point is that 'class="myError"' is added since the form had mapping errors for this property.
        self::assertStringContainsString('<select name="myFieldPrefix[myObjectName][someProperty]" class="myError"></select>', $view->render());
    }

    /**
     * @test
     */
    public function allOptionsAreSelectedIfSelectAllIsTrue(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->setTemplateSource('<f:form.select multiple="true" selectAllByDefault="true" name="myName" options="{options}" />');
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>
<option value="value2" selected="selected">label2</option>
<option value="value3" selected="selected">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function selectAllHasNoEffectIfValueIsSet(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->assign('value', ['value2', 'value1']);
        $view->setTemplateSource('<f:form.select multiple="true" value="{value}" selectAllByDefault="true" name="myName" options="{options}" />');
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>
<option value="value2" selected="selected">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithEmptyValueIfPrependOptionLabelIsSet(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->assign('value', ['value2', 'value1']);
        $view->setTemplateSource('<f:form.select prependOptionLabel="please choose" name="myName" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="">please choose</option>
<option value="value1">label1</option>
<option value="value2">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    /**
     * @test
     */
    public function optionsContainPrependedItemWithCorrectValueIfPrependOptionLabelAndPrependOptionValueAreSet(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $view = new StandaloneView();
        $view->assign('options', $options);
        $view->assign('value', ['value2', 'value1']);
        $view->setTemplateSource('<f:form.select prependOptionLabel="please choose" prependOptionValue="-1" name="myName" options="{options}" />');
        $expected = <<< EOT
<select name="myName"><option value="-1">please choose</option>
<option value="value1">label1</option>
<option value="value2">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }
}
