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

namespace TYPO3\CMS\Form\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TranslationServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ConfigurationManager&MockObject&AccessibleObjectInterface $mockConfigurationManager;
    protected TranslationService&MockObject&AccessibleObjectInterface $mockTranslationService;
    protected LanguageStore $store;

    public function setUp(): void
    {
        $packageManagerMock = $this->createMock(PackageManager::class);
        parent::setUp();

        $cacheFrontendMock = $this->createMock(FrontendInterface::class);
        $cacheFrontendMock->method('get')->with(self::anything())->willReturn(false);
        $cacheFrontendMock->method('set')->with(self::anything())->willReturn(null);

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->method('getCache')->with('l10n')->willReturn($cacheFrontendMock);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        $localizationFactory = $this->createMock(LocalizationFactory::class);
        $localizationFactory->method('getParsedData')->willReturnMap([
            [
                'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf',
                'default',
                null,
                null,
                false,
                include __DIR__ . '/Fixtures/locallang_form.php',
            ],
            [
                'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf',
                'de',
                null,
                null,
                false,
                include __DIR__ . '/Fixtures/de.locallang_form.php',
            ],
            [
                'EXT:form/Tests/Unit/Service/Fixtures/locallang_additional_text.xlf',
                'default',
                null,
                null,
                false,
                include __DIR__ . '/Fixtures/locallang_additional_text.php',
            ],
            [
                'EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_01.xlf',
                'default',
                null,
                null,
                false,
                include __DIR__ . '/Fixtures/locallang_ceuid_suffix_01.php',
            ],
            [
                'EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_02.xlf',
                'default',
                null,
                null,
                false,
                include __DIR__ . '/Fixtures/locallang_ceuid_suffix_02.php',
            ],
            [
                'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf',
                'default',
                null,
                null,
                false,
                include __DIR__ . '/Fixtures/locallang_text.php',
            ],
            [
                'EXT:form/Tests/Unit/Service/Fixtures/locallang_empty_values.xlf',
                'default',
                null,
                null,
                false,
                include __DIR__ . '/Fixtures/locallang_empty_values.php',
            ],
        ]);

        GeneralUtility::setSingletonInstance(LocalizationFactory::class, $localizationFactory);

        $this->mockConfigurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['getConfiguration'], [], '', false);

        $this->mockTranslationService = $this->getAccessibleMock(TranslationService::class, [
            'getConfigurationManager',
            'getLanguageService',
        ], [], '', false);

        $languageService = new LanguageService(new Locales(), new LocalizationFactory(new LanguageStore($packageManagerMock), $cacheManagerMock), $cacheFrontendMock);
        $this->mockTranslationService
            ->method('getLanguageService')
            ->willReturn($languageService);

        $this->mockTranslationService->_set('configurationManager', $this->mockConfigurationManager);

        $this->store = GeneralUtility::makeInstance(LanguageStore::class, $packageManagerMock);
        $this->store->initialize();
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfFullExtDefaultLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nextButtonLabel'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfFullLLLExtDefaultLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            'LLL:' . $xlfPath . ':element.Page.renderingOptions.nextButtonLabel'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndDefaultValueIsGiven(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
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
    public function translateReturnsEmptyStringIfNonExistingDefaultLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $this->store->flushData($xlfPath);

        $expected = '';
        self::assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
            $xlfPath . ':element.Page.renderingOptions.nonExisting'
        ));
    }

    /**
     * @test
     */
    public function translateReturnsDefaultValueIfNonExistingDefaultLanguageKeyIsRequestedAndDefaultValueIsGiven(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $this->store->flushData($xlfPath);

        $expected = 'defaultValue';
        self::assertEquals($expected, $this->mockTranslationService->_call(
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
    public function translateReturnsExistingLanguageKeyForLanguageIfExtPathLanguageKeyIsRequested(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM DE';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
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
    public function translateReturnsDefaultValueIfNonExistingLanguageKeyForLanguageIsRequestedAndDefaultValueIsGiven(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'defaultValue';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
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
    public function translateReturnsEmptyStringIfNonExistingLanguageKeyForLanguageIsRequested(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = '';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
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
    public function translateReturnsExistingDefaultLanguageKeyIfDefaultLanguageKeyIsRequestedAndExtFilePathIsGiven(): void
    {
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
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
        $xlfPath = 'EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf';
        $expected = 'FORM EN';

        $this->store->flushData($xlfPath);
        self::assertEquals($expected, $this->mockTranslationService->_call(
            'translate',
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
        $xlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];

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

        $this->store->flushData($xlfPaths);
        self::assertEquals($expected, $this->mockTranslationService->_call(
            'translateValuesRecursive',
            $input,
            $xlfPaths
        ));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = 'form-element-identifier LABEL EN';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = 'form-element-identifier LABEL EN';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueNotTranslateLabelForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsEmptyAndPropertyShouldNotBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = '';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = 'form-element-identifier LABEL EN 1';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForFormElementTypeIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = 'form-element-identifier LABEL EN 2';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslatePropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementPropertyIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['placeholder'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueNotTranslatePropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementPropertyIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationNotExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['placeholder'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateRenderingOptionForConcreteFormAndConcreteSectionElementIfElementRenderingOptionsContainsATranslationFilesAndElementRenderingOptionIsNotEmptyAndRenderingOptionShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = 'form-element-identifier nextButtonLabel EN';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(Page::class, null, [], '', false);

        $mockFormElement->_set('type', 'Page');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['nextButtonLabel'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateOptionsPropertyForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementOptionsPropertyIsAnArrayAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['options'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateOptionsPropertyForConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementOptionsPropertyIsAnArrayAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('properties', $formElementProperties);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['options'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionForConcreteFormIfFinisherTranslationOptionsContainsATranslationFilesAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = 'form-element-identifier SaveToDatabase subject EN';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionIfFinisherTranslationOptionsContainsATranslationFilesAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

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

        $expected = 'form-element-identifier SaveToDatabase subject EN 1';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelForConcreteFormAndConcreteElementFromFormRuntimeTranslationFilesIfElementRenderingOptionsContainsNoTranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

        $formRuntimeIdentifier = 'my-form-runtime-identifier';
        $formElementIdentifier = 'my-form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $formElementRenderingOptions = [];

        $expected = 'my-form-runtime-identifier my-form-element-identifier LABEL EN';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function supportsArgumentsForFormElementValueTranslations(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];

        $this->store->flushData($formRuntimeXlfPaths);

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

        $expected = 'See this or that';
        $result = $this->mockTranslationService->_call('translateFormElementValue', $element, ['label'], $formRuntime);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionForConcreteFormFromFormRuntimeIfFinisherTranslationOptionsContainsNoTranslationFilesAndFinisherOptionIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf'];

        $formRuntimeIdentifier = 'my-form-runtime-identifier';
        $finisherIdentifier = 'SaveToDatabaseFinisher';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $finisherRenderingOptions = [];

        $expected = 'my-form-runtime-identifier form-element-identifier SaveToDatabase subject EN';

        $this->store->flushData($formRuntimeXlfPaths);
        $this->store->flushData($textElementXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, $finisherIdentifier, 'subject', 'subject value', $finisherRenderingOptions));
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

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, [], [], '', false);

        self::assertSame(
            'subject value',
            $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, 'SaveToDatabaseFinisher', 'subject', 'subject value', $finisherRenderingOptions)
        );
    }

    /**
     * @test
     */
    public function supportsArgumentsForFinisherOptionTranslations(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];

        $this->store->flushData($formRuntimeXlfPaths);

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

        $expected = 'My awesome subject';
        $result = $this->mockTranslationService->_call('translateFinisherOption', $formRuntime, 'EmailToReceiverWithTranslationArguments', 'subject', 'My %s subject', $renderingOptions);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function translateFormElementValueTranslateLabelFromAdditionalTranslationForConcreteFormAndConcreteElementIfElementRenderingOptionsContainsATranslationFilesAndElementLabelIsNotEmptyAndPropertyShouldBeTranslatedAndTranslationExists(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_form.xlf'];
        $textElementXlfPaths = [
            10 => 'EXT:form/Tests/Unit/Service/Fixtures/locallang_text.xlf',
            20 => 'EXT:form/Tests/Unit/Service/Fixtures/locallang_additional_text.xlf',
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

        $this->store->flushData($formRuntimeXlfPaths[0]);

        foreach ($textElementXlfPaths as $textElementXlfPath) {
            $this->store->flushData($textElementXlfPath);
        }

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('renderingOptions', $formElementRenderingOptions);
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions'], [], '', false);
        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);

        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementTranslateFormWithContentElementUidIfFormContainsNoOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_01.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'submitButtonLabel' => '',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $this->store->flushData($formRuntimeXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');

        $expected = 'form-runtime-identifier-42 submitButtonLabel EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormRuntime, ['submitButtonLabel'], $mockFormRuntime));

        $expected = 'form-runtime-identifier-42 form-element-identifierlabel EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementTranslateFormWithContentElementUidIfFormContainsOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_02.xlf'];

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

        $this->store->flushData($formRuntimeXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');

        $expected = 'form-runtime-identifier submitButtonLabel EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormRuntime, ['submitButtonLabel'], $mockFormRuntime));

        $expected = 'form-runtime-identifier form-element-identifierlabel EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementErrorTranslateErrorFromFormWithContentElementUidIfFormContainsNoOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_01.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $this->store->flushData($formRuntimeXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');
        $mockFormElement->_set('properties', []);

        $expected = 'form-runtime-identifier-42 error 123 EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementError', $mockFormRuntime, 123, [], 'default value', $mockFormRuntime));

        $expected = 'form-runtime-identifier-42 form-element-identifier error 123 EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementError', $mockFormElement, 123, [], 'default value', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementErrorTranslateErrorFromFormWithContentElementUidIfFormContainsOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_02.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $this->store->flushData($formRuntimeXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');
        $mockFormElement->_set('properties', []);

        $expected = 'form-runtime-identifier error 123 EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementError', $mockFormRuntime, 123, [], 'default value', $mockFormRuntime));

        $expected = 'form-runtime-identifier form-element-identifier error 123 EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementError', $mockFormElement, 123, [], 'default value', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionFromFormWithContentElementUidIfFormContainsNoOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_01.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';

        $formRuntimeRenderingOptions = [
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $this->store->flushData($formRuntimeXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $expected = 'form-runtime-identifier-42 FooFinisher test EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, 'Foo', 'test', 'value', []));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslateOptionFromFormWithContentElementUidIfFormContainsOriginalIdentifier(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_ceuid_suffix_02.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        $this->store->flushData($formRuntimeXlfPaths);

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $expected = 'form-runtime-identifier FooFinisher test EN';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, 'Foo', 'test', 'value', []));
    }

    /**
     * @test
     */
    public function translateFormElementErrorTranslatesErrorsWithEmptyTranslatedValues(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_empty_values.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';
        $formElementIdentifier = 'form-element-identifier';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        foreach ($formRuntimeXlfPaths as $formRuntimeXlfPath) {
            $this->store->flushData($formRuntimeXlfPath);
        }

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', '');
        $mockFormElement->_set('properties', []);

        $expected = '0';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementError', $mockFormElement, 123, [], 'default value', $mockFormRuntime));

        $expected = 'default value';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementError', $mockFormElement, 124, [], 'default value', $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFormElementTranslatesFormElementsWithEmptyTranslatedValues(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_empty_values.xlf'];

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

        foreach ($formRuntimeXlfPaths as $formRuntimeXlfPath) {
            $this->store->flushData($formRuntimeXlfPath);
        }

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, null, [], '', false);

        $mockFormElement->_set('type', 'Text');
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'test');

        $expected = '0';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormRuntime, ['submitButtonLabel'], $mockFormRuntime));

        $expected = 'test';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFormElementValue', $mockFormElement, ['label'], $mockFormRuntime));
    }

    /**
     * @test
     */
    public function translateFinisherOptionTranslatesFinisherOptionsWithEmptyTranslatedValues(): void
    {
        $formRuntimeXlfPaths = ['EXT:form/Tests/Unit/Service/Fixtures/locallang_empty_values.xlf'];

        $formRuntimeIdentifier = 'form-runtime-identifier-42';

        $formRuntimeRenderingOptions = [
            '_originalIdentifier' => 'form-runtime-identifier',
            'translation' => [
                'translationFiles' => $formRuntimeXlfPaths,
                'translatePropertyValueIfEmpty' => true,
            ],
        ];

        foreach ($formRuntimeXlfPaths as $formRuntimeXlfPath) {
            $this->store->flushData($formRuntimeXlfPath);
        }

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, ['getIdentifier', 'getRenderingOptions', 'getType', 'getProperties'], [], '', false);

        $mockFormRuntime->method('getIdentifier')->willReturn($formRuntimeIdentifier);
        $mockFormRuntime->method('getRenderingOptions')->willReturn($formRuntimeRenderingOptions);
        $mockFormRuntime->method('getType')->willReturn('Form');
        $mockFormRuntime->method('getProperties')->willReturn([]);

        $expected = '0';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, 'Foo', 'test1', 'value', []));

        $expected = 'value';
        self::assertEquals($expected, $this->mockTranslationService->_call('translateFinisherOption', $mockFormRuntime, 'Foo', 'test2', 'value', []));
    }
}
