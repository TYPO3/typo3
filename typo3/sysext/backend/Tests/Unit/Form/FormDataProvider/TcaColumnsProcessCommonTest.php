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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessCommon;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaColumnsProcessCommonTest extends UnitTestCase
{
    #[Test]
    public function addDataRegistersOrigUidColumn(): void
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'origUid' => 't3_origuid',
                ],
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['t3_origuid'];
        self::assertSame($expected, (new TcaColumnsProcessCommon())->addData($input));
    }

    #[Test]
    public function addDataRegistersRecordTypeColumn(): void
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'doktype',
                ],
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['doktype'];
        self::assertSame($expected, (new TcaColumnsProcessCommon())->addData($input));
    }

    #[Test]
    public function addDataRegistersRecordTypeRelationColumn(): void
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'relation_field:foreign_type_field',
                ],
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['relation_field'];
        self::assertSame($expected, (new TcaColumnsProcessCommon())->addData($input));
    }

    #[Test]
    public function addDataRegistersLanguageFieldColumn(): void
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                ],
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['sys_language_uid'];
        self::assertSame($expected, (new TcaColumnsProcessCommon())->addData($input));
    }

    #[Test]
    public function addDataRegistersTransOrigPointerColumn(): void
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent',
                ],
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['l10n_parent'];
        self::assertSame($expected, (new TcaColumnsProcessCommon())->addData($input));
    }

    #[Test]
    public function addDataRegistersTransOrigDiffSourceColumn(): void
    {
        $input = [
            'recordTypeValue' => 'aType',
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'transOrigDiffSourceField' => 'l18n_diffsource',
                ],
            ],
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['l18n_diffsource'];
        self::assertSame($expected, (new TcaColumnsProcessCommon())->addData($input));
    }
}
