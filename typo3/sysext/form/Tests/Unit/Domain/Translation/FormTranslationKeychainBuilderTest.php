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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Translation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\Translation\FormTranslationKeychainBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormTranslationKeychainBuilderTest extends UnitTestCase
{
    #[Test]
    public function buildForElementPropertyReturnsGenericKeysWhenNoOriginalIdentifier(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForElementProperty(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-element',
            'Text',
            'properties',
            'label',
            null
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:my-form.element.my-element.properties.label',
            'EXT:my_ext/locallang.xlf:element.my-element.properties.label',
            'EXT:my_ext/locallang.xlf:element.Text.properties.label',
        ], $chain);
    }

    #[Test]
    public function buildForElementPropertyPrefixesOriginalFormIdentifierKeys(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForElementProperty(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-element',
            'Text',
            'properties',
            'label',
            'original-form'
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:original-form.element.my-element.properties.label',
            'EXT:my_ext/locallang.xlf:my-form.element.my-element.properties.label',
            'EXT:my_ext/locallang.xlf:element.my-element.properties.label',
            'EXT:my_ext/locallang.xlf:element.Text.properties.label',
        ], $chain);
    }

    #[Test]
    public function buildForFormRuntimePropertyUsesOriginalIdentifierAsElementSegment(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForFormRuntimeProperty(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-form',
            'Form',
            'renderingOptions',
            'nextButtonLabel',
            'original-form'
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:original-form.element.original-form.renderingOptions.nextButtonLabel',
            'EXT:my_ext/locallang.xlf:element.original-form.renderingOptions.nextButtonLabel',
            'EXT:my_ext/locallang.xlf:my-form.element.my-form.renderingOptions.nextButtonLabel',
            'EXT:my_ext/locallang.xlf:element.my-form.renderingOptions.nextButtonLabel',
            'EXT:my_ext/locallang.xlf:element.Form.renderingOptions.nextButtonLabel',
        ], $chain);
    }

    #[Test]
    public function buildForElementPropertyIteratesMultipleTranslationFiles(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForElementProperty(
            ['EXT:ext_a/locallang.xlf', 'EXT:ext_b/locallang.xlf'],
            'form',
            'elem',
            'Text',
            'properties',
            'label',
            null
        );

        self::assertCount(6, $chain);
        self::assertStringStartsWith('EXT:ext_a/locallang.xlf:', $chain[0]);
        self::assertStringStartsWith('EXT:ext_b/locallang.xlf:', $chain[3]);
    }

    #[Test]
    public function buildForElementOptionAppendsOptionValueToKey(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForElementOption(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-select',
            'Select',
            'properties',
            'options',
            'value1',
            null
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:my-form.element.my-select.properties.options.value1',
            'EXT:my_ext/locallang.xlf:element.my-select.properties.options.value1',
            'EXT:my_ext/locallang.xlf:element.Select.properties.options.value1',
        ], $chain);
    }

    #[Test]
    public function buildForElementOptionSupportsIntegerOptionValues(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForElementOption(
            ['EXT:my_ext/locallang.xlf'],
            'form',
            'elem',
            'Select',
            'properties',
            'options',
            42,
            null
        );

        self::assertStringEndsWith('.options.42', $chain[0]);
    }

    #[Test]
    public function buildForElementOptionPrefixesOriginalFormIdentifierKeys(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForElementOption(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-select',
            'Select',
            'properties',
            'options',
            'value1',
            'original-form'
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:original-form.element.my-select.properties.options.value1',
            'EXT:my_ext/locallang.xlf:my-form.element.my-select.properties.options.value1',
            'EXT:my_ext/locallang.xlf:element.my-select.properties.options.value1',
            'EXT:my_ext/locallang.xlf:element.Select.properties.options.value1',
        ], $chain);
    }

    #[Test]
    public function buildForFormRuntimeOptionUsesOriginalIdentifierAsElementSegment(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForFormRuntimeOption(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-form',
            'Form',
            'properties',
            'options',
            'value1',
            'original-form'
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:original-form.element.original-form.properties.options.value1',
            'EXT:my_ext/locallang.xlf:element.original-form.properties.options.value1',
            'EXT:my_ext/locallang.xlf:my-form.element.my-form.properties.options.value1',
            'EXT:my_ext/locallang.xlf:element.my-form.properties.options.value1',
            'EXT:my_ext/locallang.xlf:element.Form.properties.options.value1',
        ], $chain);
    }

    #[Test]
    public function buildForValidationErrorReturnsGenericKeysWhenNoOriginalIdentifier(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForValidationError(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-element',
            1234567,
            null
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:my-form.validation.error.my-element.1234567',
            'EXT:my_ext/locallang.xlf:my-form.validation.error.1234567',
            'EXT:my_ext/locallang.xlf:validation.error.my-element.1234567',
            'EXT:my_ext/locallang.xlf:validation.error.1234567',
        ], $chain);
    }

    #[Test]
    public function buildForValidationErrorPrefixesOriginalFormIdentifierKeys(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForValidationError(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-element',
            1234567,
            'original-form'
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:original-form.validation.error.my-element.1234567',
            'EXT:my_ext/locallang.xlf:original-form.validation.error.1234567',
            'EXT:my_ext/locallang.xlf:my-form.validation.error.my-element.1234567',
            'EXT:my_ext/locallang.xlf:my-form.validation.error.1234567',
            'EXT:my_ext/locallang.xlf:validation.error.my-element.1234567',
            'EXT:my_ext/locallang.xlf:validation.error.1234567',
        ], $chain);
    }

    #[Test]
    public function buildForFormRuntimeValidationErrorUsesOriginalIdentifierAsElementSegment(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForFormRuntimeValidationError(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'my-form',
            1234567,
            'original-form'
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:original-form.validation.error.original-form.1234567',
            'EXT:my_ext/locallang.xlf:validation.error.original-form.1234567',
            'EXT:my_ext/locallang.xlf:original-form.validation.error.1234567',
            'EXT:my_ext/locallang.xlf:my-form.validation.error.my-form.1234567',
            'EXT:my_ext/locallang.xlf:my-form.validation.error.1234567',
            'EXT:my_ext/locallang.xlf:validation.error.my-form.1234567',
            'EXT:my_ext/locallang.xlf:validation.error.1234567',
        ], $chain);
    }

    #[Test]
    public function buildForFinisherOptionReturnsGenericKeysWhenNoOriginalIdentifier(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForFinisherOption(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'SaveToDatabase',
            'subject',
            null
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:my-form.finisher.SaveToDatabase.subject',
            'EXT:my_ext/locallang.xlf:finisher.SaveToDatabase.subject',
        ], $chain);
    }

    #[Test]
    public function buildForFinisherOptionPrefixesOriginalFormIdentifierKey(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForFinisherOption(
            ['EXT:my_ext/locallang.xlf'],
            'my-form',
            'SaveToDatabase',
            'subject',
            'original-form'
        );

        self::assertSame([
            'EXT:my_ext/locallang.xlf:original-form.finisher.SaveToDatabase.subject',
            'EXT:my_ext/locallang.xlf:my-form.finisher.SaveToDatabase.subject',
            'EXT:my_ext/locallang.xlf:finisher.SaveToDatabase.subject',
        ], $chain);
    }

    #[Test]
    public function buildForFinisherOptionIteratesMultipleTranslationFiles(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForFinisherOption(
            ['EXT:ext_a/locallang.xlf', 'EXT:ext_b/locallang.xlf'],
            'form',
            'Email',
            'to',
            null
        );

        self::assertCount(4, $chain);
        self::assertStringStartsWith('EXT:ext_a/locallang.xlf:', $chain[0]);
        self::assertStringStartsWith('EXT:ext_b/locallang.xlf:', $chain[2]);
    }

    #[Test]
    public function buildForFinisherOptionReturnsEmptyArrayForEmptyTranslationFiles(): void
    {
        $chain = (new FormTranslationKeychainBuilder())->buildForFinisherOption([], 'form', 'Email', 'to', null);

        self::assertSame([], $chain);
    }
}
