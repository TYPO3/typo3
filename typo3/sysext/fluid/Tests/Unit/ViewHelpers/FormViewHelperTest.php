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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the Form view helper
 */
class FormViewHelperTest extends ViewHelperBaseTestcase
{
    protected function setUp(): void
    {
        parent::setUp();
        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->expects(self::any())->method('reset')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setArguments')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setSection')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setFormat')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setCreateAbsoluteUri')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setAddQueryString')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setArgumentsToBeExcludedFromQueryString')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setLinkAccessRestrictedPages')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setTargetPageUid')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setTargetPageType')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setNoCache')->willReturn($uriBuilderMock);
        $uriBuilderMock->expects(self::any())->method('setAddQueryStringMethod')->willReturn($uriBuilderMock);
        $this->controllerContext->expects(self::any())->method('getUriBuilder')->willReturn($uriBuilderMock);
        $this->renderingContext->setControllerContext($this->controllerContext);

        $this->tagBuilder = $this->createMock(TagBuilder::class);
    }

    /**
     * @test
     */
    public function initializeArgumentsRegistersExpectedArguments()
    {
        $viewHelper = $this->getMockBuilder(FormViewHelper::class)
            ->setMethods(['registerTagAttribute', 'registerUniversalTagAttributes'])
            ->getMock();
        $viewHelper->expects(self::at(0))->method('registerTagAttribute')->with('enctype', 'string', self::anything());
        $viewHelper->expects(self::at(1))->method('registerTagAttribute')->with('method', 'string', self::anything());
        $viewHelper->expects(self::at(2))->method('registerTagAttribute')->with('name', 'string', self::anything());
        $viewHelper->expects(self::at(3))->method('registerTagAttribute')->with('onreset', 'string', self::anything());
        $viewHelper->expects(self::at(4))->method('registerTagAttribute')->with('onsubmit', 'string', self::anything());
        $viewHelper->expects(self::at(6))->method('registerTagAttribute')->with('novalidate', 'bool', self::anything());
        $viewHelper->expects(self::once())->method('registerUniversalTagAttributes');
        $viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function setFormActionUriRespectsOverriddenArgument()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['hasArgument']);
        $viewHelper->expects(self::once())->method('hasArgument')->with('actionUri')->willReturn(true);
        $tagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute'])
            ->getMock();
        $tagBuilder->expects(self::once())->method('addAttribute')->with('action', 'foobar');
        $viewHelper->setTagBuilder($tagBuilder);
        $viewHelper->setArguments(['actionUri' => 'foobar']);
        $viewHelper->_call('setFormActionUri');
    }

    /**
     * @test
     */
    public function renderAddsObjectToViewHelperVariableContainer()
    {
        $formObject = new \stdClass();
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'addFormObjectNameToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectNameFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['object' => $formObject]);
        $this->viewHelperVariableContainer->add(FormViewHelper::class, 'formObject', $formObject);
        $this->viewHelperVariableContainer->add(FormViewHelper::class, 'additionalIdentityProperties', []);
        $this->viewHelperVariableContainer->remove(FormViewHelper::class, 'formObject');
        $this->viewHelperVariableContainer->remove(FormViewHelper::class, 'additionalIdentityProperties');
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsObjectNameToTemplateVariableContainer()
    {
        $objectName = 'someObjectName';
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['name' => $objectName]);
        $this->viewHelperVariableContainer->add(FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->remove(FormViewHelper::class, 'formObjectName');
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function formObjectNameArgumentOverrulesNameArgument()
    {
        $objectName = 'someObjectName';
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['name' => 'formName', 'objectName' => $objectName]);
        $this->viewHelperVariableContainer->add(FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->remove(FormViewHelper::class, 'formObjectName');
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsRenderHiddenReferrerFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenReferrerFields', 'renderTrustedPropertiesField'], [], '', false);
        $viewHelper->expects(self::once())->method('renderHiddenReferrerFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsRenderHiddenIdentityField()
    {
        $object = new \stdClass();
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenReferrerFields', 'renderHiddenIdentityField', 'getFormObjectName', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['object' => $object]);
        $viewHelper->expects(self::atLeastOnce())->method('getFormObjectName')->willReturn('MyName');
        $viewHelper->expects(self::once())->method('renderHiddenIdentityField')->with($object, 'MyName');
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsRenderAdditionalIdentityFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenReferrerFields', 'renderAdditionalIdentityFields', 'renderTrustedPropertiesField'], [], '', false);
        $viewHelper->expects(self::once())->method('renderAdditionalIdentityFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivForXhtmlCompatibilityWithRewrittenPropertyMapper()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'renderTrustedPropertiesField'], [], '', false);
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects(self::once())->method('renderHiddenIdentityField')->willReturn('hiddenIdentityField');
        $viewHelper->expects(self::once())->method('renderAdditionalIdentityFields')->willReturn('additionalIdentityFields');
        $viewHelper->expects(self::once())->method('renderHiddenReferrerFields')->willReturn('hiddenReferrerFields');
        $viewHelper->expects(self::once())->method('renderChildren')->willReturn('formContent');
        $expectedResult =  \chr(10) . '<div>hiddenIdentityFieldadditionalIdentityFieldshiddenReferrerFields' . \chr(10) . '</div>' . \chr(10) . 'formContent';
        $this->tagBuilder->expects(self::once())->method('setContent')->with($expectedResult);
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivAndAnAdditionalClassForXhtmlCompatibilityWithRewrittenPropertyMapper()
    {
        $viewHelper = $this->getAccessibleMock(
            FormViewHelper::class,
            [
                'renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'renderTrustedPropertiesField'
            ],
            [],
            '',
            false
        );
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects(self::once())->method('renderHiddenIdentityField')->willReturn('hiddenIdentityField');
        $viewHelper->expects(self::once())->method('renderAdditionalIdentityFields')->willReturn('additionalIdentityFields');
        $viewHelper->expects(self::once())->method('renderHiddenReferrerFields')->willReturn('hiddenReferrerFields');
        $viewHelper->expects(self::once())->method('renderChildren')->willReturn('formContent');
        $expectedResult =  \chr(10) . '<div class="hidden">hiddenIdentityFieldadditionalIdentityFieldshiddenReferrerFields' . \chr(10) . '</div>' . \chr(10) . 'formContent';
        $this->tagBuilder->expects(self::once())->method('setContent')->with($expectedResult);
        $viewHelper->setArguments(['hiddenFieldClassName' => 'hidden']);
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAdditionalIdentityFieldsFetchesTheFieldsFromViewHelperVariableContainerAndBuildsHiddenFieldsForThem()
    {
        $identityProperties = [
            'object1[object2]' => '<input type="hidden" name="object1[object2][__identity]" value="42" />',
            'object1[object2][subobject]' => '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />'
        ];
        $this->viewHelperVariableContainer->exists(FormViewHelper::class, 'additionalIdentityProperties')->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'additionalIdentityProperties')->willReturn($identityProperties);
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $expected =  \chr(10) . '<input type="hidden" name="object1[object2][__identity]" value="42" />' . \chr(10) . '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />';
        $actual = $viewHelper->_call('renderAdditionalIdentityFields');
        $viewHelper->setTagBuilder($this->tagBuilder);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $hashService = $this->getMockBuilder(HashService::class)
            ->setMethods(['appendHmac'])
            ->getMock();
        $hashService->expects(self::any())->method('appendHmac')->willReturn('');
        $viewHelper->injectHashService($hashService);
        $this->request->getControllerExtensionName()->willReturn('extensionName');
        $this->request->getControllerName()->willReturn('controllerName');
        $this->request->getControllerActionName()->willReturn('controllerActionName');
        $this->request->getArguments()->willReturn([]);
        $hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
        $expectedResult =  \chr(10) . '<input type="hidden" name="__referrer[@extension]" value="extensionName" />'
            . \chr(10) . '<input type="hidden" name="__referrer[@controller]" value="controllerName" />'
            . \chr(10) . '<input type="hidden" name="__referrer[@action]" value="controllerActionName" />'
            . \chr(10) . '<input type="hidden" name="__referrer[arguments]" value="" />'
            . \chr(10) . '<input type="hidden" name="__referrer[@request]" value="" />' . \chr(10);
        $viewHelper->setTagBuilder($this->tagBuilder);
        self::assertEquals($expectedResult, $hiddenFields);
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedPrefixToTemplateVariableContainer()
    {
        $prefix = 'somePrefix';
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['fieldNamePrefix' => $prefix]);
        $this->viewHelperVariableContainer->add(FormViewHelper::class, 'fieldNamePrefix', $prefix);
        $this->viewHelperVariableContainer->remove(FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsDefaultFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecified()
    {
        $expectedPrefix = 'tx_someextension_someplugin';
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $mockExtensionService->expects(self::once())->method('getPluginNamespace')->with('SomeExtension', 'SomePlugin')->willReturn('tx_someextension_someplugin');
        $viewHelper->injectExtensionService($mockExtensionService);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['extensionName' => 'SomeExtension', 'pluginName' => 'SomePlugin']);
        $this->viewHelperVariableContainer->add(FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $this->viewHelperVariableContainer->remove(FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->setTagBuilder($this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * Data Provider for postProcessUriArgumentsForRequestHashWorks
     */
    public function argumentsForPostProcessUriArgumentsForRequestHash(): array
    {
        return [
            // simple values
            [
                [
                    'bla' => 'X',
                    'blubb' => 'Y'
                ],
                [
                    'bla',
                    'blubb'
                ]
            ],
            // Arrays
            [
                [
                    'bla' => [
                        'test1' => 'X',
                        'test2' => 'Y'
                    ],
                    'blubb' => 'Y'
                ],
                [
                    'bla[test1]',
                    'bla[test2]',
                    'blubb'
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider argumentsForPostProcessUriArgumentsForRequestHash
     * @param $arguments
     * @param $expectedResults
     */
    public function postProcessUriArgumentsForRequestHashWorks($arguments, $expectedResults)
    {
        $formViewHelper = new FormViewHelper();
        $results = [];
        $mock = \Closure::bind(static function (FormViewHelper $formViewHelper) use ($arguments, &$results) {
            return $formViewHelper->postProcessUriArgumentsForRequestHash($arguments, $results);
        }, null, FormViewHelper::class);
        $mock($formViewHelper);
        self::assertEquals($expectedResults, $results);
    }
}
