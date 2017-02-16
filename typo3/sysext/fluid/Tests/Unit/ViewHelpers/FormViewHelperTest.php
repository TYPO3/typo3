<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

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

use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

/**
 * Test for the Form view helper
 */
class FormViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $mockExtensionService;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $mockConfigurationManager;

    protected function setUp()
    {
        parent::setUp();
        $this->mockExtensionService = $this->createMock(\TYPO3\CMS\Extbase\Service\ExtensionService::class);
        $this->mockConfigurationManager = $this->createMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $this->tagBuilder = $this->createMock(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class);
    }

    /**
     * @test
     */
    public function initializeArgumentsRegistersExpectedArguments()
    {
        $viewHelper = $this->getMockBuilder(FormViewHelper::class)
            ->setMethods(['registerTagAttribute', 'registerUniversalTagAttributes'])
            ->getMock();
        $viewHelper->expects($this->at(0))->method('registerTagAttribute')->with('enctype', 'string', $this->anything());
        $viewHelper->expects($this->at(1))->method('registerTagAttribute')->with('method', 'string', $this->anything());
        $viewHelper->expects($this->at(2))->method('registerTagAttribute')->with('name', 'string', $this->anything());
        $viewHelper->expects($this->at(3))->method('registerTagAttribute')->with('onreset', 'string', $this->anything());
        $viewHelper->expects($this->at(4))->method('registerTagAttribute')->with('onsubmit', 'string', $this->anything());
        $viewHelper->expects($this->once())->method('registerUniversalTagAttributes');
        $viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function setFormActionUriRespectsOverriddenArgument()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['hasArgument']);
        $viewHelper->expects($this->once())->method('hasArgument')->with('actionUri')->willReturn(true);
        $tagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute'])
            ->getMock();
        $tagBuilder->expects($this->once())->method('addAttribute')->with('action', 'foobar');
        $viewHelper->_set('tag', $tagBuilder);
        $viewHelper->setArguments(['actionUri' => 'foobar']);
        $this->callInaccessibleMethod($viewHelper, 'setFormActionUri');
    }

    /**
     * @param ViewHelperInterface $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(ViewHelperInterface $viewHelper)
    {
        $viewHelper->_set('configurationManager', $this->mockConfigurationManager);
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $hashService = $this->getMockBuilder(\TYPO3\CMS\Extbase\Security\Cryptography\HashService::class)
            ->setMethods(['appendHmac'])
            ->getMock();
        $hashService->expects($this->any())->method('appendHmac')->will($this->returnValue(''));
        $this->mvcPropertyMapperConfigurationService->_set('hashService', $hashService);
        $viewHelper->_set('mvcPropertyMapperConfigurationService', $this->mvcPropertyMapperConfigurationService);
        $viewHelper->_set('hashService', $hashService);
    }

    /**
     * @test
     */
    public function renderAddsObjectToViewHelperVariableContainer()
    {
        $formObject = new \stdClass();
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'renderRequestHashField', 'addFormObjectNameToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectNameFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['object' => $formObject]);
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject', $formObject);
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties', []);
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject');
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties');
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsObjectNameToTemplateVariableContainer()
    {
        $objectName = 'someObjectName';
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'renderRequestHashField', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['name' => $objectName]);
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName');
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function formObjectNameArgumentOverrulesNameArgument()
    {
        $objectName = 'someObjectName';
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['name' => 'formName', 'objectName' => $objectName]);
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName');
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsRenderHiddenReferrerFields()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderRequestHashField', 'renderHiddenReferrerFields', 'renderTrustedPropertiesField'], [], '', false);
        $viewHelper->expects($this->once())->method('renderHiddenReferrerFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsRenderHiddenIdentityField()
    {
        $object = new \stdClass();
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderRequestHashField', 'renderHiddenReferrerFields', 'renderHiddenIdentityField', 'getFormObjectName', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['object' => $object]);
        $viewHelper->expects($this->atLeastOnce())->method('getFormObjectName')->will($this->returnValue('MyName'));
        $viewHelper->expects($this->once())->method('renderHiddenIdentityField')->with($object, 'MyName');
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsRenderAdditionalIdentityFields()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderRequestHashField', 'renderHiddenReferrerFields', 'renderAdditionalIdentityFields', 'renderTrustedPropertiesField'], [], '', false);
        $viewHelper->expects($this->once())->method('renderAdditionalIdentityFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivForXhtmlCompatibilityWithRewrittenPropertyMapper()
    {
        $viewHelper = $this->getAccessibleMock($this->buildAccessibleProxy(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class), ['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'renderTrustedPropertiesField'], [], '', false);
        $this->mvcPropertyMapperConfigurationService->_set('hashService', new \TYPO3\CMS\Extbase\Security\Cryptography\HashService());
        $viewHelper->_set('mvcPropertyMapperConfigurationService', $this->mvcPropertyMapperConfigurationService);
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects($this->once())->method('renderHiddenIdentityField')->will($this->returnValue('hiddenIdentityField'));
        $viewHelper->expects($this->once())->method('renderAdditionalIdentityFields')->will($this->returnValue('additionalIdentityFields'));
        $viewHelper->expects($this->once())->method('renderHiddenReferrerFields')->will($this->returnValue('hiddenReferrerFields'));
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('formContent'));
        $expectedResult = chr(10) . '<div>' . 'hiddenIdentityFieldadditionalIdentityFieldshiddenReferrerFields' . chr(10) . '</div>' . chr(10) . 'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivAndAnAdditionalClassForXhtmlCompatibilityWithRewrittenPropertyMapper()
    {
        $viewHelper = $this->getMockBuilder($this->buildAccessibleProxy(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class))
            ->setMethods(['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'renderTrustedPropertiesField'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mvcPropertyMapperConfigurationService->_set('hashService', new \TYPO3\CMS\Extbase\Security\Cryptography\HashService());
        $viewHelper->_set('mvcPropertyMapperConfigurationService', $this->mvcPropertyMapperConfigurationService);
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->expects($this->once())->method('renderHiddenIdentityField')->will($this->returnValue('hiddenIdentityField'));
        $viewHelper->expects($this->once())->method('renderAdditionalIdentityFields')->will($this->returnValue('additionalIdentityFields'));
        $viewHelper->expects($this->once())->method('renderHiddenReferrerFields')->will($this->returnValue('hiddenReferrerFields'));
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('formContent'));
        $expectedResult = chr(10) . '<div class="hidden">' . 'hiddenIdentityFieldadditionalIdentityFieldshiddenReferrerFields' . chr(10) . '</div>' . chr(10) . 'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);
        $viewHelper->setArguments(['hiddenFieldClassName' => 'hidden']);
        $viewHelper->_set('tag', $this->tagBuilder);
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
        $this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties')->willReturn(true);
        $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties')->willReturn($identityProperties);
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $expected = chr(10) . '<input type="hidden" name="object1[object2][__identity]" value="42" />' . chr(10) . '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />';
        $actual = $viewHelper->_call('renderAdditionalIdentityFields');
        $viewHelper->_set('tag', $this->tagBuilder);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->request->getControllerExtensionName()->willReturn('extensionName');
        $this->request->getControllerName()->willReturn('controllerName');
        $this->request->getControllerActionName()->willReturn('controllerActionName');
        $this->request->getControllerVendorName()->willReturn('controllerVendorName');
        $this->request->getArguments()->willReturn([]);
        $hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
        $expectedResult = chr(10) . '<input type="hidden" name="__referrer[@extension]" value="extensionName" />'
            . chr(10) . '<input type="hidden" name="__referrer[@vendor]" value="controllerVendorName" />'
            . chr(10) . '<input type="hidden" name="__referrer[@controller]" value="controllerName" />'
            . chr(10) . '<input type="hidden" name="__referrer[@action]" value="controllerActionName" />'
            . chr(10) . '<input type="hidden" name="__referrer[arguments]" value="" />'
            . chr(10) . '<input type="hidden" name="__referrer[@request]" value="" />' . chr(10);
        $viewHelper->_set('tag', $this->tagBuilder);
        $this->assertEquals($expectedResult, $hiddenFields);
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedPrefixToTemplateVariableContainer()
    {
        $prefix = 'somePrefix';
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['fieldNamePrefix' => $prefix]);
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix', $prefix);
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsDefaultFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecified()
    {
        $expectedPrefix = 'tx_someextension_someplugin';
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderTrustedPropertiesField'], [], '', false);
        $this->mockExtensionService->expects($this->once())->method('getPluginNamespace')->with('SomeExtension', 'SomePlugin')->will($this->returnValue('tx_someextension_someplugin'));
        $viewHelper->_set('extensionService', $this->mockExtensionService);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setArguments(['extensionName' => 'SomeExtension', 'pluginName' => 'SomePlugin']);
        $this->viewHelperVariableContainer->add(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $this->viewHelperVariableContainer->remove(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->_set('tag', $this->tagBuilder);
        $viewHelper->render();
    }

    /**
     * Data Provider for postProcessUriArgumentsForRequestHashWorks
     */
    public function argumentsForPostProcessUriArgumentsForRequestHash()
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
     */
    public function postProcessUriArgumentsForRequestHashWorks($arguments, $expectedResults)
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['dummy'], [], '', false);
        $results = [];
        $viewHelper->_callRef('postProcessUriArgumentsForRequestHash', $arguments, $results);
        $this->assertEquals($expectedResults, $results);
    }
}
