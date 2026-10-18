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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseRowDefaultAsReadonlyTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/test_inline_default_as_readonly',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_core.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/InlineDefaultAsReadonly.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
    }

    #[Test]
    public function inlineChildrenOfTranslatedRecordAreReadOnlyForDefaultAsReadonlyField(): void
    {
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'vanillaUid' => 2,
                'request' => new ServerRequest(),
                'command' => 'edit',
                'tableName' => 'tx_testinlinedefaultasreadonly_parent',
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        $parentColumn = $formData['processedTca']['columns']['children'];
        self::assertTrue($parentColumn['config']['readOnly'] ?? false);
        self::assertCount(1, $parentColumn['children']);
        $child = $parentColumn['children'][0];
        self::assertTrue($child['isInlineChildExpanded']);
        self::assertSame('Child translated', $child['databaseRow']['value']);
        self::assertTrue($child['processedTca']['columns']['value']['config']['readOnly'] ?? false);
    }

    #[Test]
    public function inlineChildrenOfDefaultLanguageRecordAreEditableForDefaultAsReadonlyField(): void
    {
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'vanillaUid' => 1,
                'request' => new ServerRequest(),
                'command' => 'edit',
                'tableName' => 'tx_testinlinedefaultasreadonly_parent',
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        $parentColumn = $formData['processedTca']['columns']['children'];
        self::assertFalse($parentColumn['config']['readOnly'] ?? false);
        self::assertFalse($parentColumn['children'][0]['processedTca']['columns']['value']['config']['readOnly'] ?? false);
    }
}
