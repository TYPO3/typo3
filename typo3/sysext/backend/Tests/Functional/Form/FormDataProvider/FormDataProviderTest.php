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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRawRecord;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRecordOverrideValues;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaText;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Compiles a real record through the full, dependency-ordered
 * "tcaDatabaseRecord" FormDataProvider stack via FormDataCompiler.
 *
 * In contrast to the per-provider unit tests, this exercises the provider *ordering*
 * declared in DefaultConfiguration.php. It is therefore a canary for regressions in the
 * interaction / ordering of FormDataProviders.
 */
final class FormDataProviderTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_core.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormDataProviderPages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormDataProviderTtContent.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
    }

    #[Test]
    public function perTypePageTsConfigLabelOverrideIsApplied(): void
    {
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'vanillaUid' => 1,
                'request' => new ServerRequest(),
                'command' => 'edit',
                'tableName' => 'tt_content',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
        self::assertSame('Per type override', $formData['processedTca']['columns']['header']['label']);
    }

    #[Test]
    public function genericPageTsConfigLabelOverrideIsAppliedForNonMatchingType(): void
    {
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'vanillaUid' => 2,
                'request' => new ServerRequest(),
                'command' => 'edit',
                'tableName' => 'tt_content',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
        self::assertSame('Generic override', $formData['processedTca']['columns']['header']['label']);
    }

    #[Test]
    public function rawRecordContainsUnprocessedValuesOfExistingRecord(): void
    {
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'vanillaUid' => 3,
                'request' => new ServerRequest(),
                'command' => 'edit',
                'tableName' => 'tt_content',
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        self::assertSame(['menu_subpages'], $formData['databaseRow']['CType']);
        self::assertSame('tt_content.menu_subpages', $formData['rawRecord']->getFullType());
        self::assertSame(3, $formData['rawRecord']->getUid());
        self::assertSame('menu_subpages', $formData['rawRecord']->get('CType'));
        self::assertInstanceOf(\DateTimeInterface::class, $formData['databaseRow']['date']);
        self::assertSame(1700000000, (int)$formData['rawRecord']->get('date'));
        self::assertIsArray($formData['databaseRow']['pages']);
        self::assertSame('1', (string)$formData['rawRecord']->get('pages'));
    }

    #[Test]
    public function rawRecordContainsDefaultValuesOfNewRecord(): void
    {
        $formData = $this->get(FormDataCompiler::class)->compile(
            [
                'vanillaUid' => 1,
                'request' => new ServerRequest(),
                'command' => 'new',
                'tableName' => 'tt_content',
                'defaultValues' => [
                    'tt_content' => [
                        'CType' => 'header',
                        'header' => 'Default header',
                    ],
                ],
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        self::assertSame(['header'], $formData['databaseRow']['CType']);
        self::assertSame('tt_content.header', $formData['rawRecord']->getFullType());
        self::assertSame(0, $formData['rawRecord']->getUid());
        self::assertSame('header', $formData['rawRecord']->get('CType'));
        self::assertSame('Default header', $formData['rawRecord']->get('header'));
    }

    public static function rawRecordIsCreatedBeforeValuesAreProcessedDataProvider(): \Generator
    {
        yield 'tcaDatabaseRecord' => ['tcaDatabaseRecord'];
        yield 'siteConfiguration' => ['siteConfiguration'];
    }

    #[DataProvider('rawRecordIsCreatedBeforeValuesAreProcessedDataProvider')]
    #[Test]
    public function rawRecordIsCreatedBeforeValuesAreProcessed(string $formDataGroup): void
    {
        $providers = array_keys($this->get(DependencyOrderingService::class)->orderByDependencies(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup'][$formDataGroup],
            'before',
            'depends'
        ));
        $rawRecordPosition = array_search(DatabaseRawRecord::class, $providers, true);

        self::assertIsInt($rawRecordPosition);
        self::assertGreaterThan(array_search(DatabaseRecordOverrideValues::class, $providers, true), $rawRecordPosition);
        foreach ([TcaInlineExpandCollapseState::class, TcaText::class, TcaSelectItems::class, TcaRecordTitle::class] as $provider) {
            self::assertLessThan(array_search($provider, $providers, true), $rawRecordPosition, $provider);
        }
    }
}
