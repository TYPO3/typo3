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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserDomainClass;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserDomainClassToString;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserRoleBackedEnum;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserRoleEnum;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

final class SelectViewHelperTest extends FunctionalTestCase
{
    #[Test]
    public function selectCorrectlySetsTagName(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select />');
        self::assertSame('<select name=""></select>', (new TemplateView($context))->render());
    }

    #[Test]
    public function selectCreatesExpectedOptions(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" value="value2" options="{value1: \"label1\", value2: \"label2\"}" />');
        $expected = '<select name="myName"><option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '</select>';
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function selectShouldSetTheRequiredAttribute(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" required="true" value="value2" options="{value1: \"label1\", value2: \"label2\"}" />');
        $expected = '<select required="required" name="myName"><option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '</select>';
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    #[Test]
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectThrowsExceptionIfEitherOptionValueFieldIsNotSet(): void
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionLabelField="title" sortByOptionLabel="true" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter "optionValueField" in SelectViewHelper for array value options.');
        $this->expectExceptionCode(1682693720);

        $view->render();
    }

    #[Test]
    public function selectThrowsExceptionIfEitherOptionLabelFieldIsNotSet(): void
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionValueField="uid" sortByOptionLabel="true" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter "optionLabelField" in SelectViewHelper for array value options.');
        $this->expectExceptionCode(1682693721);

        $view->render();
    }

    #[Test]
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

        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function OrderOfOptionsIsNotAlteredByDefault(): void
    {
        $options = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2',
        ];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" value="value2" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="value3">label3</option>
<option value="value1">label1</option>
<option value="value2" selected="selected">label2</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function optionsAreSortedByLabelIfSortByOptionLabelIsSet(): void
    {
        $options = [
            'value3' => 'label3',
            'value1' => 'label1',
            'value2' => 'label2',
        ];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" sortByOptionLabel="true" value="value2" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="value1">label1</option>
<option value="value2" selected="selected">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function multipleSelectCreatesExpectedOptions(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $value = ['value3', 'value1'];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select multiple="true" name="myName" value="{value}" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $view->assign('value', $value);
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>
<option value="value2">label2</option>
<option value="value3" selected="selected">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function multipleSelectWithoutOptionsCreatesExpectedOptions(): void
    {
        $value = ['value3', 'value1'];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select multiple="true" name="myName" value="{value}" options="{}" />');
        $view = new TemplateView($context);
        $view->assign('value', $value);
        $expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"></select>';
        self::assertSame($expected, $view->render());
    }

    #[Test]
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionValueField="id" optionLabelField="firstName" value="{value}" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $view->assign('value', $user_sk);
        $expected = <<< EOT
<select name="myName"><option value="1">Ingmar</option>
<option value="2" selected="selected">Sebastian</option>
<option value="3">Robert</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select multiple="true" name="myName" optionValueField="id" optionLabelField="firstName" value="{value}" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $view->assign('value', [$user_rl, $user_is]);
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="1" selected="selected">Ingmar</option>
<option value="2">Sebastian</option>
<option value="3" selected="selected">Robert</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
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

        $mockPersistenceManager->method('getIdentifierByObject')->willReturnCallback(
            static function (UserDomainClass $object): string {
                // Note: In reality, getIdentifierByObject only returns strings (or null) as this what the used backend
                // does. So the cast here makes the test more in line with the real-world types.
                return (string)$object->getId();
            }
        );
        $container = $this->get('service_container');
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select multiple="true" name="myName" optionLabelField="firstName" value="{value}" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $view->assign('value', [$user_rl, $user_is]);
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="1" selected="selected">Ingmar</option>
<option value="2">Sebastian</option>
<option value="3" selected="selected">Robert</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel(): void
    {
        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $options = [
            $user_is,
        ];

        // Mock persistence manager for our domain objects and set into container
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturn('fakeUid');
        $container = $this->get('service_container');
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="fakeUid">fakeUid</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectWithoutFurtherConfigurationOnDomainObjectsUsesToStringForLabelIfAvailable(): void
    {
        $user_is = new UserDomainClassToString(1, 'Ingmar', 'Schlecht');
        $options = [
            $user_is,
        ];

        // Mock persistence manager for our domain objects and set into container
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturn('fakeUid');
        $container = $this->get('service_container');
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="fakeUid">IngmarToString</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound(): void
    {
        $user_is = new UserDomainClass(1, 'Ingmar', 'Schlecht');
        $options = [
            $user_is,
        ];

        // Mock persistence manager for our domain objects and set into container
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('isNewObject')->willReturn(true);
        $container = $this->get('service_container');
        $container->set(PersistenceManager::class, $mockPersistenceManager);

        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1247826696);

        $view->render();
    }

    #[Test]
    public function renderCallsSetErrorClassAttribute(): void
    {
        // Create an extbase request that contains mapping results of the form object property we're working with.
        $mappingResult = new Result();
        $objectResult = $mappingResult->forProperty('myObjectName');
        $propertyResult = $objectResult->forProperty('someProperty');
        $propertyResult->addError(new Error('invalidProperty', 2));
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setOriginalRequestMappingResults($mappingResult);
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = new Request($psr7Request);

        $formObject = new \stdClass();
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.select property="someProperty" errorClass="myError" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        // The point is that 'class="myError"' is added since the form had mapping errors for this property.
        self::assertStringContainsString('<select name="myFieldPrefix[myObjectName][someProperty]" class="myError"></select>', $view->render());
    }

    #[Test]
    public function allOptionsAreSelectedIfSelectAllIsTrue(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select multiple="true" selectAllByDefault="true" name="myName" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>
<option value="value2" selected="selected">label2</option>
<option value="value3" selected="selected">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectAllHasNoEffectIfValueIsSet(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select multiple="true" value="{value}" selectAllByDefault="true" name="myName" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $view->assign('value', ['value2', 'value1']);
        $expected = <<< EOT
<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>
<option value="value2" selected="selected">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function optionsContainPrependedItemWithEmptyValueIfPrependOptionLabelIsSet(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select prependOptionLabel="please choose" name="myName" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $view->assign('value', ['value2', 'value1']);
        $expected = <<< EOT
<select name="myName"><option value="">please choose</option>
<option value="value1">label1</option>
<option value="value2">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function optionsContainPrependedItemWithCorrectValueIfPrependOptionLabelAndPrependOptionValueAreSet(): void
    {
        $options = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select prependOptionLabel="please choose" prependOptionValue="-1" name="myName" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $view->assign('value', ['value2', 'value1']);
        $expected = <<< EOT
<select name="myName"><option value="-1">please choose</option>
<option value="value1">label1</option>
<option value="value2">label2</option>
<option value="value3">label3</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectAppliesSelectedValueFromUnitEnum(): void
    {
        $user = new UserDomainClass(1, 'Oliver', 'Bartsch');
        $options = [
            UserRoleEnum::ADMIN->name => 'Admin',
            UserRoleEnum::EDITOR->name => 'Editor',
            UserRoleEnum::GUEST->name => 'Guest',
        ];

        $serverRequest = (new ServerRequest())
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{user}" fieldNamePrefix="myFieldPrefix" objectName="user"><f:form.select prependOptionLabel="please choose" prependOptionValue="-1" options="{options}" property="role"/></f:form>');
        $view = new TemplateView($context);
        $view->assign('user', $user);
        $view->assign('options', $options);
        $view->assign('property', UserRoleEnum::EDITOR);
        $view->assign('value', UserRoleEnum::EDITOR);
        $result = $view->render();
        self::assertStringContainsString('<select name="myFieldPrefix[user][role]">', $result);
        self::assertStringContainsString('<option value="GUEST" selected="selected">Guest</option>', $result);
    }

    #[Test]
    public function selectAppliesSelectedValueFromBackedEnum(): void
    {
        $user = new UserDomainClass(1, 'Oliver', 'Bartsch');
        $options = [
            UserRoleBackedEnum::ADMIN->value => 'Admin',
            UserRoleBackedEnum::EDITOR->value => 'Editor',
            UserRoleBackedEnum::GUEST->value => 'Guest',
        ];

        $serverRequest = (new ServerRequest())
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{user}" fieldNamePrefix="myFieldPrefix" objectName="user"><f:form.select prependOptionLabel="please choose" prependOptionValue="-1" options="{options}" property="roleBacked"/></f:form>');
        $view = new TemplateView($context);
        $view->assign('user', $user);
        $view->assign('options', $options);
        $view->assign('property', UserRoleEnum::EDITOR);
        $view->assign('value', UserRoleEnum::EDITOR);
        $result = $view->render();
        self::assertStringContainsString('<select name="myFieldPrefix[user][roleBacked]">', $result);
        self::assertStringContainsString('<option value="3" selected="selected">Guest</option>', $result);
    }

    #[Test]
    public function selectCreatesExpectedOptionsWithAppendedValuesInTagContent(): void
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionsAfterContent="0" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}"><option value="4711">4712</option></f:form.select>');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
<option value="4711">4712</option></select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectCreatesExpectedOptionsWithOptionFieldsBeingNumbers(): void
    {
        $options = [
            [
                0 => 1,
                1 => 'Foo',
            ],
            [
                0 => -1,
                1 => 'Bar',
            ],
            [
                1 => 'Baz',
            ],
            [
                0 => '2',
            ],
        ];
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select name="myName" optionsAfterContent="0" optionValueField="0" optionLabelField="1" sortByOptionLabel="true" options="{options}" />');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectCreatesExpectedOptionsWithPrependedValuesInTagContent(): void
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.select optionsAfterContent="1" name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}"><option value="4711">4712</option></f:form.select>');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="4711">4712</option><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
</select>
EOT;
        self::assertSame($expected, $view->render());
    }

    #[Test]
    public function selectCreatesExpectedOptionsWithIntegerValuesInTagContent(): void
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
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:form.select name="myName" optionValueField="uid" optionLabelField="title" sortByOptionLabel="true" options="{options}"><option value="{i}">{k}</option></f:form.select></f:for>');
        $view = new TemplateView($context);
        $view->assign('options', $options);
        $expected = <<< EOT
<select name="myName"><option value="2"></option>
<option value="-1">Bar</option>
<option value="">Baz</option>
<option value="1">Foo</option>
<option value="4712">4711</option></select>
EOT;
        self::assertSame($expected, $view->render());
    }
}
