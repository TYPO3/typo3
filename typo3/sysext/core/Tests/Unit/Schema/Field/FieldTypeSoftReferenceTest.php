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

namespace TYPO3\CMS\Core\Tests\Unit\Schema\Field;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\Field\CheckboxFieldType;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Field\InputFieldType;
use TYPO3\CMS\Core\Schema\Field\NoneFieldType;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\Field\PassthroughFieldType;
use TYPO3\CMS\Core\Schema\Field\SelectRelationFieldType;
use TYPO3\CMS\Core\Schema\Field\SystemInternalFieldType;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FieldTypeSoftReferenceTest extends UnitTestCase
{
    #[Test]
    public function getSoftReferenceKeysReturnsFalseForEmptyConfiguration(): void
    {
        $fieldType = new TextFieldType('test', []);
        self::assertFalse($fieldType->getSoftReferenceKeys());
    }

    #[Test]
    public function getSoftReferenceKeysReturnsFalseForNoSoftRefConfiguration(): void
    {
        $fieldType = new TextFieldType('test', ['type' => 'text']);
        self::assertFalse($fieldType->getSoftReferenceKeys());
    }

    #[Test]
    public function getSoftReferenceKeysReturnsEmptyArrayForEmptySoftRefConfiguration(): void
    {
        $fieldType = new TextFieldType('test', ['softref' => '']);
        $result = $fieldType->getSoftReferenceKeys();
        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    #[Test]
    public function getSoftReferenceKeysReturnsSingleKeyForSingleSoftRef(): void
    {
        $fieldType = new TextFieldType('test', ['softref' => 'email']);
        $result = $fieldType->getSoftReferenceKeys();
        self::assertIsArray($result);
        self::assertEquals(['email'], $result);
    }

    #[Test]
    public function getSoftReferenceKeysReturnsMultipleKeysForCommaSeparatedSoftRef(): void
    {
        $fieldType = new TextFieldType('test', ['softref' => 'email,typolink']);
        $result = $fieldType->getSoftReferenceKeys();
        self::assertIsArray($result);
        self::assertEquals(['email', 'typolink'], $result);
    }

    #[Test]
    public function getSoftReferenceKeysTrimsWhitespaceInCommaSeparatedSoftRef(): void
    {
        $fieldType = new TextFieldType('test', ['softref' => 'email, typolink , url']);
        $result = $fieldType->getSoftReferenceKeys();
        self::assertIsArray($result);
        self::assertEquals(['email', 'typolink', 'url'], $result);
    }

    #[Test]
    public function getSoftReferenceKeysHandlesSoftRefWithParameters(): void
    {
        $fieldType = new InputFieldType('test', ['softref' => 'email[subst],typolink']);
        $result = $fieldType->getSoftReferenceKeys();
        self::assertIsArray($result);
        self::assertEquals(['email[subst]', 'typolink'], $result);
    }

    #[Test]
    public function getSoftReferenceKeysIgnoresEmptyKeysInCommaSeparatedList(): void
    {
        $fieldType = new TextFieldType('test', ['softref' => 'email,,typolink,']);
        $result = $fieldType->getSoftReferenceKeys();
        self::assertIsArray($result);
        self::assertEquals(['email', 'typolink'], $result);
    }

    #[Test]
    public function getSoftReferenceKeysReturnsEmptyArrayForWhitespaceOnlyConfiguration(): void
    {
        $fieldType = new TextFieldType('test', ['softref' => '  ,  , ']);
        $result = $fieldType->getSoftReferenceKeys();
        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public static function fieldTypesThatNeverSupportSoftReferencesDataProvider(): array
    {
        return [
            'CheckboxFieldType' => [CheckboxFieldType::class, ['type' => 'check']],
            'FlexFormFieldType' => [FlexFormFieldType::class, ['type' => 'flex']],
            'NoneFieldType' => [NoneFieldType::class, ['type' => 'none']],
            'NumberFieldType' => [NumberFieldType::class, ['type' => 'number']],
            'PassthroughFieldType' => [PassthroughFieldType::class, ['type' => 'passthrough']],
            'SelectRelationFieldType' => [SelectRelationFieldType::class, ['type' => 'select', 'foreign_table' => 'pages'], []],
            'SystemInternalFieldType' => [SystemInternalFieldType::class, []],
        ];
    }

    #[Test]
    #[DataProvider('fieldTypesThatNeverSupportSoftReferencesDataProvider')]
    public function getSoftReferenceKeysReturnsFalseForFieldTypesThatNeverSupportSoftReferences(string $fieldTypeClass, array $config, array $relations = []): void
    {
        if ($fieldTypeClass === SelectRelationFieldType::class) {
            $fieldType = new $fieldTypeClass('test', $config, $relations);
        } else {
            $fieldType = new $fieldTypeClass('test', $config);
        }

        self::assertFalse($fieldType->getSoftReferenceKeys());
    }

    #[Test]
    #[DataProvider('fieldTypesThatNeverSupportSoftReferencesDataProvider')]
    public function getSoftReferenceKeysReturnsFalseEvenWithSoftRefConfigurationForRestrictedFieldTypes(string $fieldTypeClass, array $config, array $relations = []): void
    {
        $configWithSoftRef = array_merge($config, ['softref' => 'email,typolink']);

        if ($fieldTypeClass === SelectRelationFieldType::class) {
            $fieldType = new $fieldTypeClass('test', $configWithSoftRef, $relations);
        } else {
            $fieldType = new $fieldTypeClass('test', $configWithSoftRef);
        }

        self::assertFalse($fieldType->getSoftReferenceKeys());
    }
}
