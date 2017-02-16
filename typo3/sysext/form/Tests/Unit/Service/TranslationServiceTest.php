<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Service;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class TranslationServiceTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var ConfigurationManager
     */
    protected $mockConfigurationManager;

    /**
     * @var TranslationService
     */
    protected $mockTranslationService;

    /**
     * @var LanguageStore
     */
    protected $store;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->mockConfigurationManager = $this->getAccessibleMock(ConfigurationManager::class, [
            'getConfiguration'
        ], [], '', false);

        $this->mockTranslationService = $this->getAccessibleMock(TranslationService::class, [
            'getConfigurationManager',
            'getLanguageService'
        ], [], '', false);

        $this->mockTranslationService
            ->expects($this->any())
            ->method('getLanguageService')
            ->willReturn(GeneralUtility::makeInstance(LanguageService::class));

        $this->mockTranslationService
            ->expects($this->any())
            ->method('getConfigurationManager')
            ->willReturn($this->mockConfigurationManager);

        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();
        $this->store = GeneralUtility::makeInstance(LanguageStore::class);
        $this->store->initialize();

        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfFullExtDefaultLanguageKeyIsRequested()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nextButtonLabel'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfFullLLLExtDefaultLanguageKeyIsRequested()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            'LLL:' . $xlfPath . ':element.Page.renderingOptions.nextButtonLabel'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndDefaultValueIsGiven()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nextButtonLabel',
            null,
            null,
            null,
            'defaultValue'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsEmptyStringIfNonExistingDefaultLanguageKeyIsRequested()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $this->store->flushData($xlfPath);

        $expected = '';
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nonExisting'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsDefaultValueIfNonExistingDefaultLanguageKeyIsRequestedAndDefaultValueIsGiven()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $this->store->flushData($xlfPath);

        $expected = 'defaultValue';
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nonExisting',
            null,
            null,
            null,
            'defaultValue'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingLanguageKeyForLanguageIfExtPathLanguageKeyIsRequested()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM DE';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nextButtonLabel',
            null,
            null,
            'de'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsDefaultValueIfNonExistingLanguageKeyForLanguageIsRequestedAndDefaultValueIsGiven()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'defaultValue';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nonExisting',
            null,
            null,
            'de',
            'defaultValue'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsEmptyStringIfNonExistingLanguageKeyForLanguageIsRequested()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = '';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nonExisting',
            null,
            null,
            'de'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndExtFilePathIsGiven()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            'element.Page.renderingOptions.nextButtonLabel',
            null,
            $xlfPath
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndLLLExtFilePathIsGiven()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            'element.Page.renderingOptions.nextButtonLabel',
            null,
            'LLL:' . $xlfPath
        ));
    }

    /**
     * @test
     */
    public function translateValuesRecursiveTranslateRecursive()
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';

        $input = [
            'Stan' => [
                'Steve' => 'Roger'
            ],
            [
                'Francine' => [
                    'Klaus' => 'element.Page.renderingOptions.nextButtonLabel'
                ],
            ],
        ];

        $expected = [
            'Stan' => [
                'Steve' => 'Roger'
            ],
            [
                'Francine' => [
                    'Klaus' => 'FORM EN'
                ],
            ],
        ];

        $this->store->flushData($xlfPath);
        $this->assertEquals($expected, $this->mockTranslationService->_call(
            'translateValuesRecursive',
            $input,
            $xlfPath
        ));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFileAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $expected = 'form-element-identifier LABEL EN';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'label', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFileAndElementLabelIsEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $expected = 'form-element-identifier LABEL EN';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'label', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueNotTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFileAndElementLabelIsEmptyAndPropertyShouldNotBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => false
            ],
        ];

        $expected = '';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'label', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormElementIfElementRenderingOptionsContainsATranslationFileAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $expected = 'form-element-identifier LABEL EN 1';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'label', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForFormElementTypeIfElementRenderingOptionsContainsATranslationFileAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'another-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $expected = 'form-element-identifier LABEL EN 2';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'label', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslatePropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFileAndElementPropertyIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];
        $formElementProperties = [
            'placeholder' => 'placeholder',
        ];

        $expected = 'form-element-identifier PLACEHOLDER EN';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'placeholder', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueNotTranslatePropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFileAndElementPropertyIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationNotExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'another-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];
        $formElementProperties = [
            'placeholder' => 'placeholder',
        ];

        $expected = 'placeholder';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'placeholder', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateRenderingOptionForConcreteFormAndConcreteSectionElementIfElementRenderingOptionsContainsATranslationFileAndElementRenderingOptionIsNotEmptyAndRenderingOptionShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier-page';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'nextButtonLabel' => 'next button label',
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];
        $formElementProperties = [
            'placeholder' => 'placeholder',
        ];

        $expected = 'form-element-identifier nextButtonLabel EN';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(Page::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Page');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'nextButtonLabel', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateOptionsPropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFileAndElementOptionsPropertyIsAnArrayAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'options-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];
        $formElementProperties = [
            'options' => [
                'optionValue1' => 'optionLabel1',
                'optionValue2' => 'optionLabel2'
            ],
        ];

        $expected = [
            'optionValue1' => 'options-form-element-identifier option 1 EN',
            'optionValue2' => 'options-form-element-identifier option 2 EN'
        ];

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'options', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateOptionsPropertyForConcreteElementIfElementRenderingOptionsContainsATranslationFileAndElementOptionsPropertyIsAnArrayAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'options-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFile' => $textElementXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];
        $formElementProperties = [
            'options' => [
                'optionValue1' => 'optionLabel1',
                'optionValue2' => 'optionLabel2'
            ],
        ];

        $expected = [
            'optionValue1' => 'options-form-element-identifier option 1 EN',
            'optionValue2' => 'options-form-element-identifier option 2 EN'
        ];

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'options', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionForConcreteFormIfFinisherTranslationOptionsContainsATranslationFileAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $finisherIdentifier = 'SaveToDatabaseFinisher';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $finisherRenderingOptions = [
            'translationFile' => $textElementXlfPath,
            'translatePropertyValueIfEmpty' => true
        ];

        $expected = 'form-element-identifier SaveToDatabase subject EN';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionIfFinisherTranslationOptionsContainsATranslationFileAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $finisherIdentifier = 'SaveToDatabaseFinisher';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $finisherRenderingOptions = [
            'translationFile' => $textElementXlfPath,
            'translatePropertyValueIfEmpty' => true
        ];

        $expected = 'form-element-identifier SaveToDatabase subject EN 1';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementFromFormRumtimeTranslationFileIfElementRenderingOptionsContainsNoTranslationFileAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'my-form-runtime-identifier';
        $formElementIdentifier = 'my-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $formElementRenderingOptions = [];

        $expected = 'my-form-runtime-identifier my-form-element-identifier LABEL EN';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, 'label', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionForConcreteFormFromFormRuntimeIfFinisherTranslationOptionsContainsNoTranslationFileAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists()
    {
        $formRuntimeXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $textElementXlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf';

        $formRuntimeIdentifier = 'my-form-runtime-identifier';
        $finisherIdentifier = 'SaveToDatabaseFinisher';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFile' => $formRuntimeXlfPath,
                'translatePropertyValueIfEmpty' => true
            ],
        ];

        $finisherRenderingOptions = [];

        $expected = 'my-form-runtime-identifier form-element-identifier SaveToDatabase subject EN';

        $this->store->flushData($formRuntimeXlfPath);
        $this->store->flushData($textElementXlfPath);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->expects($this->any())->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->expects($this->any())->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $this->assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
    }
}
