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

namespace TYPO3\CMS\Backend\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TranslationConfigurationProviderTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationsInReverseLanguageOrder.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function translationInfoReturnsTranslationsOrderedByLanguage(): void
    {
        $translationInfo = $this->get(TranslationConfigurationProvider::class)->translationInfo('tt_content', 1);

        self::assertSame([1, 2], array_keys($translationInfo['translations']));
    }
}
