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
}
