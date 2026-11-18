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

namespace TYPO3\CMS\Core\Tests\Unit\Security\Authorization\Policy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ResourceIdentifierTest extends UnitTestCase
{
    public static function factoriesDataProvider(): \Generator
    {
        yield 'table' => [ResourceIdentifier::table('tt_content'), 'table', 'tt_content'];
        yield 'admin only table' => [ResourceIdentifier::adminOnlyTable('be_users'), 'admin_only_table', 'be_users'];
        yield 'field' => [ResourceIdentifier::field('tt_content', 'header'), 'field', 'tt_content:header'];
        yield 'field value' => [ResourceIdentifier::fieldValue('tt_content', 'CType', 'text'), 'field_value', 'tt_content:CType:text'];
        yield 'language' => [ResourceIdentifier::language(-1), 'language', '-1'];
        yield 'page type' => [ResourceIdentifier::pageType(254), 'page_type', '254'];
        yield 'module' => [ResourceIdentifier::module('web_layout'), 'module', 'web_layout'];
        yield 'widget' => [ResourceIdentifier::widget('sysLogErrors'), 'widget', 'sysLogErrors'];
        yield 'mfa provider' => [ResourceIdentifier::mfaProvider('totp'), 'mfa_provider', 'totp'];
        yield 'custom option' => [ResourceIdentifier::customOption('group1:item1'), 'custom_option', 'group1:item1'];
    }

    #[Test]
    #[DataProvider('factoriesDataProvider')]
    public function factoriesCreateTypedResources(ResourceIdentifier $subject, string $type, string $identifier): void
    {
        self::assertSame($type, $subject->type);
        self::assertSame($identifier, $subject->identifier);
    }
}
