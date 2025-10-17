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
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\LabelFileResolver;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LocalizationFactoryTest extends UnitTestCase
{
    #[Test]
    public function getParsedDataCallsLocalizationOverrideIfFileNotFoundExceptionIsThrown(): void
    {
        $translatorMock = $this->createMock(Translator::class);
        $translatorMock->method('addResource')->willThrowException(new FileNotFoundException('testing', 1476049512));

        $cacheFrontendMock = $this->createMock(FrontendInterface::class);
        $cacheFrontendMock->method('get')->with(self::anything())->willReturn(false);
        $cacheFrontendMock->expects($this->atLeastOnce())->method('set')->with(self::anything());

        $labelMapperMock = $this->createMock(TranslationDomainMapper::class);
        $labelMapperMock->method('mapDomainToFileName')->willReturnArgument(0);

        $packageManagerMock = $this->createMock(PackageManager::class);

        $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'] = ['foo' => 'bar'];

        $result = (new LocalizationFactory($translatorMock, $cacheFrontendMock, new NullFrontend('runtime'), $labelMapperMock, new LabelFileResolver($packageManagerMock)))
            ->getParsedData(__DIR__ . '/Fixtures/locallang.invalid', 'en');

        // Should return empty structure when file not found
        self::assertEmpty($result);
    }

    #[Test]
    public function ensureLocalizationIsProperlyCached(): void
    {
        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('extractPackageKeyFromPackagePath')->with('EXT:core/Tests/Unit/Localization/Fixtures/locallang.xlf')->willReturn('core');

        $catalogue = $this->createMock(MessageCatalogue::class);
        $catalogue->method('getLocale')->willReturn('en');
        $catalogue->method('all')->willReturn([
            'label1' => 'This is label #1',
        ]);

        $translatorMock = $this->createMock(Translator::class);
        $translatorMock->method('getCatalogue')->willReturn($catalogue);

        $labelMapperMock = $this->createMock(TranslationDomainMapper::class);
        $labelMapperMock->method('mapDomainToFileName')->willReturnArgument(0);

        $cacheFrontendMock = $this->createMock(FrontendInterface::class);
        $cacheFrontendMock->expects($this->atLeastOnce())->method('get')->with(self::isString())->willReturn(false);
        $cacheFrontendMock->expects($this->atLeastOnce())->method('set')->with(self::isString(), [
            'label1' => 'This is label #1',
        ])->willReturn(null);

        $result = (new LocalizationFactory($translatorMock, $cacheFrontendMock, new NullFrontend('runtime'), $labelMapperMock, new LabelFileResolver($packageManagerMock)))
            ->getParsedData('EXT:core/Tests/Unit/Localization/Fixtures/locallang.xlf', 'en');

        // Verify we get the expected structure
        self::assertNotEmpty($result);
    }

    #[Test]
    public function usesSymfonyTranslatorInternally(): void
    {
        $packageManagerMock = $this->createMock(PackageManager::class);

        // Create a test file that exists
        $testFile = __DIR__ . '/Fixtures/locallang.xlf';
        $translatorMock = $this->createMock(Translator::class);

        // Expect that addResource is called - this proves we're using Symfony Translator
        $translatorMock->expects($this->exactly(2))
            ->method('addResource')
            ->with('xlf', self::stringContains('locallang.xlf'), 'en', $testFile);

        $labelMapperMock = $this->createMock(TranslationDomainMapper::class);
        $labelMapperMock->method('mapFileNameToDomain')->willReturnArgument(0);
        $labelMapperMock->method('mapDomainToFileName')->willReturnArgument(0);

        $catalogue = new MessageCatalogue('en', [$testFile => ['fine' => 'true']]);

        $translatorMock->method('getCatalogue')->willReturn($catalogue);

        $subject = new LocalizationFactory(
            $translatorMock,
            new NullFrontend('l10n'),
            new NullFrontend('runtime'),
            $labelMapperMock,
            new LabelFileResolver($packageManagerMock)
        );
        $subject->getParsedData($testFile, 'en');
    }
}
