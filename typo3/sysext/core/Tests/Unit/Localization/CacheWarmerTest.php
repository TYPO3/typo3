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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Localization\CacheWarmer;
use TYPO3\CMS\Core\Localization\LabelFileResolver;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheWarmerTest extends UnitTestCase
{
    #[Test]
    public function warmupCachesSkipsDeprecatedLanguageFiles(): void
    {
        $regularFile = 'EXT:foo/Resources/Private/Language/locallang.xlf';
        $deprecatedFile = 'EXT:foo/Resources/Private/Language/locallang_deprecated.xlf';

        $package = self::createStub(PackageInterface::class);
        $package->method('getPackageKey')->willReturn('foo');
        $packageManager = self::createStub(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn(['foo' => $package]);

        $labelFileResolver = self::createStub(LabelFileResolver::class);
        $labelFileResolver->method('getAllLabelFilesOfPackage')->willReturn([
            'default' => [$regularFile, $deprecatedFile],
        ]);

        $locales = self::createStub(Locales::class);
        $locales->method('getActiveLanguages')->willReturn(['default']);
        $locales->method('createLocale')->willReturn(new Locale());

        $warmedUpFiles = [];
        $parsedFiles = [];
        $localizationFactory = self::createStub(LocalizationFactory::class);
        $localizationFactory->method('isLanguageFileDeprecated')->willReturnCallback(
            static fn(string $fileReference): bool => $fileReference === $deprecatedFile
        );
        $localizationFactory->method('warmupTranslatorResource')->willReturnCallback(
            static function (string $fileReference) use (&$warmedUpFiles): void {
                $warmedUpFiles[] = $fileReference;
            }
        );
        $localizationFactory->method('getParsedData')->willReturnCallback(
            static function (string $fileReference) use (&$parsedFiles): array {
                $parsedFiles[] = $fileReference;
                return [];
            }
        );

        $subject = new CacheWarmer($packageManager, $labelFileResolver, $localizationFactory, $locales);
        $subject->warmupCaches(new CacheWarmupEvent(['system']));

        self::assertSame([$regularFile], $warmedUpFiles);
        self::assertSame([$regularFile], $parsedFiles);
    }
}
