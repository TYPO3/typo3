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

namespace TYPO3\CMS\Core\Tests\Unit\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LabelFileResolver;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LabelFileResolverTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('getLocaleFromLanguageFileDataProvider')]
    public function getLocaleFromLanguageFileReturnsTheLocaleOnlyForValidResults(string $languageFile, ?string $expected): void
    {
        $subject = new LabelFileResolver(
            $this->createMock(PackageManager::class),
        );
        $result = $subject->getLocaleFromLanguageFile($languageFile);
        self::assertEquals($expected, $result);
    }

    public static function getLocaleFromLanguageFileDataProvider(): array
    {
        return [
            'without locale' => [
                'languageFile' => 'locallang.xlf',
                'expected' => null,
            ],
            'with locale' => [
                'languageFile' => 'de.locallang.xlf',
                'expected' => 'de',
            ],
            'with language and region code' => [
                'languageFile' => 'de_AT.locallang.xlf',
                'expected' => 'de_AT',
            ],
            'with language and three-letter region code' => [
                'languageFile' => 'en_AUS.locallang.xlf',
                'expected' => 'en_AUS',
            ],
            'with language and invalid region code' => [
                'languageFile' => 'en_AUS.locallang.xlf',
                'expected' => 'en_AUS',
            ],
            'simple prefix with two letters' => [
                'languageFile' => 'db.xlf',
                'expected' => null,
            ],
            'simple prefix with two letters and language code' => [
                'languageFile' => 'de.db.xlf',
                'expected' => 'de',
            ],
        ];
    }
}
