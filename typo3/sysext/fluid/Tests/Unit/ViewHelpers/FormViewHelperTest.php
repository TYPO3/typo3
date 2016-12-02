<?php

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
        $this->mockExtensionService = $this->getMock(\TYPO3\CMS\Extbase\Service\ExtensionService::class);
        $this->mockConfigurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper
     * @return void
     */
    protected function injectDependenciesIntoViewHelper(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper $viewHelper)
    {
        $viewHelper->_set('configurationManager', $this->mockConfigurationManager);
        parent::injectDependenciesIntoViewHelper($viewHelper);
        $hashService = $this->getMock(\TYPO3\CMS\Extbase\Security\Cryptography\HashService::class, ['appendHmac']);
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
        $this->viewHelperVariableContainer->expects($this->at(0))->method('add')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject', $formObject);
        $this->viewHelperVariableContainer->expects($this->at(1))->method('add')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties', []);
        $this->viewHelperVariableContainer->expects($this->at(2))->method('remove')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject');
        $this->viewHelperVariableContainer->expects($this->at(3))->method('remove')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties');
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
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivAndAnAdditionalClassForXhtmlCompatibilityWithRewrittenPropertyMapper()
    {
        $viewHelper = $this->getMock($this->buildAccessibleProxy(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class), ['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderHiddenSecuredReferrerField', 'renderTrustedPropertiesField'], [], '', false);
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
        $this->viewHelperVariableContainer->expects($this->once())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties')->will($this->returnValue($identityProperties));
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $expected = chr(10) . '<input type="hidden" name="object1[object2][__identity]" value="42" />' . chr(10) . '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />';
        $actual = $viewHelper->_call('renderAdditionalIdentityFields');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->request->expects($this->atLeastOnce())->method('getControllerExtensionName')->will($this->returnValue('extensionName'));
        $this->request->expects($this->never())->method('getControllerSubextensionName');
        $this->request->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('controllerName'));
        $this->request->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue('controllerActionName'));
        $hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
        $expectedResult = chr(10) . '<input type="hidden" name="__referrer[@extension]" value="extensionName" />'
            . chr(10) . '<input type="hidden" name="__referrer[@controller]" value="controllerName" />'
            . chr(10) . '<input type="hidden" name="__referrer[@action]" value="controllerActionName" />'
            . chr(10) . '<input type="hidden" name="__referrer[arguments]" value="" />'
            . chr(10) . '<input type="hidden" name="__referrer[@request]" value="" />' . chr(10);
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
