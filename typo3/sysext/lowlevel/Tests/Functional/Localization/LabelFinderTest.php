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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\Localization;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Lowlevel\Localization\LabelFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LabelFinderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'lowlevel',
        'core',
    ];

    /**
     * Note that Environment::getLabelsPath() sadly cannot be used here,
     * and there is no individual method to only retrieve 'typo3conf/l10n/' via API,
     * so the reference is added here hard-coded, since monorepo tests run in classic mode.
     */
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/lowlevel/Tests/Functional/Localization/Fixtures/de.wizard.xlf' => 'typo3conf/l10n/de/core/Resources/Private/Language/de.wizard.xlf',
    ];

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderWillReturnDefaultLanguageStrings(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'default', 'Processing...');
        self::assertCount(2, $result);
        self::assertSame('wizard.processing.title', $result[0]->labels[0]->reference);
        self::assertSame('core.wizard', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/wizard.xlf', $result[0]->resource);

        self::assertSame('localize.view.processing', $result[1]->labels[0]->reference);
        self::assertSame('backend.layout', $result[1]->domain);
        self::assertSame('EXT:backend/Resources/Private/Language/locallang_layout.xlf', $result[1]->resource);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderWillReturnDefaultLanguageStringsInLocalizedCountries(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'default', 'Switzerland');
        self::assertCount(1, $result);
        self::assertSame('CH.name', $result[0]->labels[0]->reference);
        self::assertSame('core.iso.countries', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/Iso/countries.xlf', $result[0]->resource);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderWillReturnDefaultLanguageStringsWhenUsingEnglishLocale(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'en', 'Processing...');
        self::assertCount(2, $result);
        self::assertSame('wizard.processing.title', $result[0]->labels[0]->reference);
        self::assertSame('core.wizard', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/wizard.xlf', $result[0]->resource);

        self::assertSame('localize.view.processing', $result[1]->labels[0]->reference);
        self::assertSame('backend.layout', $result[1]->domain);
        self::assertSame('EXT:backend/Resources/Private/Language/locallang_layout.xlf', $result[1]->resource);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderWillReturnLocalizedLanguageStrings(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'de', 'Weihnachtsinseln');
        self::assertCount(1, $result);
        self::assertSame('CX.name', $result[0]->labels[0]->reference);
        self::assertSame('core.iso.countries', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/Iso/de.countries.xlf', $result[0]->resource);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderWillReturnLocalizedLanguageStringsWhenVarLabelsExist(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'de', 'Testinglabelsearchsuche');
        self::assertCount(1, $result, 'Translated label not found. Proper label file was not loaded.');
        self::assertSame('wizard.button.next', $result[0]->labels[0]->reference);
        self::assertSame('core.wizard', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/wizard.xlf', $result[0]->resource);

        // Now check that this is NOT returned for english
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'en', 'Testinglabelsearchsuche');
        self::assertCount(0, $result, 'Translated label should not be found in original language file.');
    }
}
