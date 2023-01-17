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

namespace TYPO3\CMS\Form\Tests\Functional\Service;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TranslationServiceTest extends FunctionalTestCase
{
    protected TranslationService $subject;
    protected array $testExtensionsToLoad = ['typo3/sysext/form/Tests/Functional/Service/Fixtures/Extensions/form_labels'];

    public function setUp(): void
    {
        parent::setUp();

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['getConfiguration'], [], '', false);
        $this->subject = new TranslationService(
            $configurationManager,
            $this->get(LanguageServiceFactory::class),
            $this->get('cache.runtime')
        );

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfFullExtDefaultLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';
        self::assertEquals('FORM EN', $this->subject->translate(
            $xlfPath . ':element.Page.renderingOptions.nextButtonLabel'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfFullLLLExtDefaultLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';
        self::assertEquals('FORM EN', $this->subject->translate(
            'LLL:' . $xlfPath . ':element.Page.renderingOptions.nextButtonLabel'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndDefaultValueIsGiven(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('FORM EN', $this->subject->translate(
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
    public function translateReturnsEmptyStringIfNonExistingDefaultLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('', $this->subject->translate(
            $xlfPath . ':element.Page.renderingOptions.nonExisting'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsDefaultValueIfNonExistingDefaultLanguageKeyIsRequestedAndDefaultValueIsGiven(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('defaultValue', $this->subject->translate(
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
    public function translateReturnsExistingLanguageKeyForLanguageIfExtPathLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('FORM DE', $this->subject->translate(
            $xlfPath . ':element.Page.renderingOptions.nextButtonLabel',
            null,
            null,
            'de'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsDefaultValueIfNonExistingLanguageKeyForLanguageIsRequestedAndDefaultValueIsGiven(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('defaultValue', $this->subject->translate(
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
    public function translateReturnsEmptyStringIfNonExistingLanguageKeyForLanguageIsRequested(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('', $this->subject->translate(
            $xlfPath . ':element.Page.renderingOptions.nonExisting',
            null,
            null,
            'de'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndExtFilePathIsGiven(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('FORM EN', $this->subject->translate(
            'element.Page.renderingOptions.nextButtonLabel',
            null,
            $xlfPath
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndLLLExtFilePathIsGiven(): void
    {
        $xlfPath = 'EXT:form_labels/Resources/Private/Language/locallang_form.xlf';

        self::assertEquals('FORM EN', $this->subject->translate(
            'element.Page.renderingOptions.nextButtonLabel',
            null,
            'LLL:' . $xlfPath
        ));
    }

    /**
     * @test
     */
    public function translateValuesRecursiveTranslateRecursive(): void
    {
        $xlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];

        $input = [
            'Stan' => [
                'Steve' => 'Roger',
            ],
            [
                'Francine' => [
                    'Klaus' => 'element.Page.renderingOptions.nextButtonLabel',
                ],
            ],
        ];

        $expected = [
            'Stan' => [
                'Steve' => 'Roger',
            ],
            [
                'Francine' => [
                    'Klaus' => 'FORM EN',
                ],
            ],
        ];

        self::assertEquals($expected, $this->subject->translateValuesRecursive(
            $input,
            $xlfPaths
        ));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => 'some label',
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'form-element-identifier LABEL EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => '',
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'form-element-identifier LABEL EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueNotTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsEmptyAndPropertyShouldNotBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => false,
            ],
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => '',
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals('', $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => 'some label',
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals('form-element-identifier LABEL EN 1', $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForFormElementTypeIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'another-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => 'some label',
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'form-element-identifier LABEL EN 2';
        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslatePropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementPropertyIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];
        $formElementProperties = [
            'placeholder' => 'placeholder',
        ];

        $expected = 'form-element-identifier PLACEHOLDER EN';

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'properties' => $formElementProperties,
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['placeholder'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueNotTranslatePropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementPropertyIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationNotExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'another-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];
        $formElementProperties = [
            'placeholder' => 'placeholder',
        ];

        $expected = 'placeholder';

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'properties' => $formElementProperties,
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['placeholder'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateRenderingOptionForConcreteFormAndConcreteSectionElementIfElementRenderingOptionsContainsATranslationFilesAndElementRenderingOptionIsNotEmptyAndRenderingOptionShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier-page';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'nextButtonLabel' => 'next button label',
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElement = new Page($formElementIdentifier);
        $formElement->setOptions([
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'form-element-identifier nextButtonLabel EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['nextButtonLabel'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateOptionsPropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementOptionsPropertyIsAnArrayAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'options-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];
        $formElementProperties = [
            'options' => [
                'optionValue1' => 'optionLabel1',
                'optionValue2' => 'optionLabel2',
            ],
        ];

        $expected = [
            'optionValue1' => 'options-form-element-identifier option 1 EN',
            'optionValue2' => 'options-form-element-identifier option 2 EN',
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'properties' => $formElementProperties,
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['options'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateOptionsPropertyForConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementOptionsPropertyIsAnArrayAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $formElementIdentifier = 'options-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];
        $formElementProperties = [
            'options' => [
                'optionValue1' => 'optionLabel1',
                'optionValue2' => 'optionLabel2',
            ],
        ];

        $expected = [
            'optionValue1' => 'options-form-element-identifier option 1 EN',
            'optionValue2' => 'options-form-element-identifier option 2 EN',
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'properties' => $formElementProperties,
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['options'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionForConcreteFormIfFinisherTranslationOptionsContainsATranslationFilesAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $finisherIdentifier = 'SaveToDatabaseFinisher';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $finisherRenderingOptions = [
            'translationFiles' => $textElementXlfPaths,
            'translatePropertyValueIfEmpty' => true,
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'form-element-identifier SaveToDatabase subject EN';
        self::assertEquals($expected, $this->subject->translateFinisherOption($mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionIfFinisherTranslationOptionsContainsATranslationFilesAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_text.xlf'];

        $formRuntimeIdentifier = 'another-form-runtime-identifier';
        $finisherIdentifier = 'SaveToDatabaseFinisher';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $finisherRenderingOptions = [
            'translationFiles' => $textElementXlfPaths,
            'translatePropertyValueIfEmpty' => true,
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'form-element-identifier SaveToDatabase subject EN 1';
        self::assertEquals($expected, $this->subject->translateFinisherOption($mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementFromFormRuntimeTranslationFilesIfElementRenderingOptionsContainsNoTranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];

        $formRuntimeIdentifier = 'my-form-runtime-identifier';
        $formElementIdentifier = 'my-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => 'some label',
            'renderingOptions' => [],
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'my-form-runtime-identifier my-form-element-identifier LABEL EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function supportsArgumentsForFormElementValueTranslations(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getIdentifier')->willReturn('my-form-runtime-identifier');
        $formRuntime->method('getRenderingOptions')->willReturn([
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ]);

        $element = $this->createMock(RootRenderableInterface::class);
        $element->method('getIdentifier')->willReturn('my-form-element-with-translation-arguments');
        $element->method('getType')->willReturn(RootRenderableInterface::class);
        $element->method('getLabel')->willReturn('See %s or %s');
        $element->method('getRenderingOptions')->willReturn([
            'translation' => [
                'arguments' => [
                        'label' => [
                            'this',
                            'that',
                        ],
                ],
            ],
        ]);

        $result = $this->subject->translateFormElementValue($element, ['label'], $formRuntime);
        self::assertEquals('See this or that', $result);
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionForConcreteFormFromFormRuntimeIfFinisherTranslationOptionsContainsNoTranslationFilesAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];

        $formRuntimeIdentifier = 'my-form-runtime-identifier';
        $finisherIdentifier = 'SaveToDatabaseFinisher';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        $expected = 'my-form-runtime-identifier form-element-identifier SaveToDatabase subject EN';
        self::assertEquals($expected, $this->subject->translateFinisherOption($mockFormRuntime, $finisherIdentifier, 'subject', 'subject value'));
    }

    /**
     * @test
     */
    public function translateFinisherOptionSkipsTranslationIfTranslationShouldBeSkipped(): void
    {
        $finisherRenderingOptions = [
            'propertiesExcludedFromTranslation' => [
                'subject',
            ],
        ];

        $mockFormRuntime = $this->createMock(FormRuntime::class);

        self::assertSame(
            'subject value',
            $this->subject->translateFinisherOption($mockFormRuntime, 'SaveToDatabaseFinisher', 'subject', 'subject value', $finisherRenderingOptions)
        );
    }

    /**
     * @test
     */
    public function supportsArgumentsForFinisherOptionTranslations(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getIdentifier')->willReturn('my-form-runtime-identifier');
        $formRuntime->method('getRenderingOptions')->willReturn([
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ]);
        $renderingOptions = [
            'arguments' => [
                'subject' => [
                    'awesome',
                ],
            ],
        ];

        $result = $this->subject->translateFinisherOption($formRuntime, 'EmailToReceiverWithTranslationArguments', 'subject', 'My %s subject', $renderingOptions);
        self::assertEquals('My awesome subject', $result);
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelFromAdditionalTranslationForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_form.xlf'];
        $textElementXlfPaths = [
            10 => 'EXT:form_labels/Resources/Private/Language/locallang_text.xlf',
            20 => 'EXT:form_labels/Resources/Private/Language/locallang_additional_text.xlf',
         ];

        $formRuntimeIdentifier = 'form-runtime-identifier';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [
            'translation' => [
                'translationFiles' => $textElementXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $expected = 'form-element-identifier ADDITIONAL LABEL EN';

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => 'some label',
            'renderingOptions' => $formElementRenderingOptions,
        ]);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementTranslateFormWithContentElementUidIfFormContainsNoOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_ceuid_suffix_01.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'submitButtonLabel' => '',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => '',
        ]);

        $expected = 'form-runtime-identifier-42 submitButtonLabel EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($mockFormRuntime, ['submitButtonLabel'], $mockFormRuntime));

        $expected = 'form-runtime-identifier-42 form-element-identifierlabel EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementTranslateFormWithContentElementUidIfFormContainsOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_ceuid_suffix_02.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'submitButtonLabel' => '',
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => '',
        ]);

        $expected = 'form-runtime-identifier submitButtonLabel EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($mockFormRuntime, ['submitButtonLabel'], $mockFormRuntime));

        $expected = 'form-runtime-identifier form-element-identifierlabel EN';
        self::assertEquals($expected, $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementErrorTranslateErrorFromFormWithContentElementUidIfFormContainsNoOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_ceuid_suffix_01.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => '',
            'properties' => [],
        ]);

        $expected = 'form-runtime-identifier-42 error 123 EN';
        self::assertEquals($expected, $this->subject->translateFormElementError($mockFormRuntime, 123, [], 'default value', $mockFormRuntime));

        $expected = 'form-runtime-identifier-42 form-element-identifier error 123 EN';
        self::assertEquals($expected, $this->subject->translateFormElementError($formElement, 123, [], 'default value', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementErrorTranslateErrorFromFormWithContentElementUidIfFormContainsOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_ceuid_suffix_02.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => '',
            'properties' => [],
        ]);

        $expected = 'form-runtime-identifier error 123 EN';
        self::assertEquals($expected, $this->subject->translateFormElementError($mockFormRuntime, 123, [], 'default value', $mockFormRuntime));

        $expected = 'form-runtime-identifier form-element-identifier error 123 EN';
        self::assertEquals($expected, $this->subject->translateFormElementError($formElement, 123, [], 'default value', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionFromFormWithContentElementUidIfFormContainsNoOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_ceuid_suffix_01.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $expected = 'form-runtime-identifier-42 FooFinisher test EN';
        self::assertEquals($expected, $this->subject->translateFinisherOption($mockFormRuntime, 'Foo', 'test', 'value', []));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionFromFormWithContentElementUidIfFormContainsOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_ceuid_suffix_02.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $expected = 'form-runtime-identifier FooFinisher test EN';
        self::assertEquals($expected, $this->subject->translateFinisherOption($mockFormRuntime, 'Foo', 'test', 'value', []));
    }

    /**
     * @test
     */
    public function translateFormElementErrorTranslatesErrorsWithEmptyTranslatedValues(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_empty_values.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => '',
            'properties' => [],
        ]);

        self::assertEquals('0', $this->subject->translateFormElementError($formElement, 123, [], 'default value', $mockFormRuntime));
        self::assertEquals('default value', $this->subject->translateFormElementError($formElement, 124, [], 'default value', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementTranslatesFormElementsWithEmptyTranslatedValues(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_empty_values.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'submitButtonLabel' => '',
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');

        $formElement = new GenericFormElement($formElementIdentifier, 'Text');
        $formElement->setOptions([
            'label' => 'test',
        ]);

        self::assertEquals('0', $this->subject->translateFormElementValue($mockFormRuntime, ['submitButtonLabel'], $mockFormRuntime));
        self::assertEquals('test', $this->subject->translateFormElementValue($formElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslatesFinisherOptionsWithEmptyTranslatedValues(): void
    {
        $formRuntimeXlfPaths = ['EXT:form_labels/Resources/Private/Language/locallang_empty_values.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        self::assertEquals('0', $this->subject->translateFinisherOption($mockFormRuntime, 'Foo', 'test1', 'value', []));
        self::assertEquals('value', $this->subject->translateFinisherOption($mockFormRuntime, 'Foo', 'test2', 'value', []));
    }
}
