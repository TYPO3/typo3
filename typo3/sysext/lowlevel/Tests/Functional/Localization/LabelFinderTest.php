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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Localization\DeprecatedLabels;
use TYPO3\CMS\Lowlevel\Localization\LabelFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LabelFinderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'lowlevel',
        'core',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/lowlevel/Tests/Functional/Fixtures/Extensions/test_label_finder',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Provided here rather than through pathsToProvideInTestInstance, whose destinations
        // are relative to the document root: the labels directory is typo3conf/l10n below it
        // in classic mode but var/labels beside it in composer mode, so only
        // Environment::getLabelsPath() names it correctly in both.
        $target = Environment::getLabelsPath() . '/de/core/Resources/Private/Language/de.wizard.xlf';
        GeneralUtility::mkdir_deep(dirname($target));
        copy(__DIR__ . '/Fixtures/de.wizard.xlf', $target);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderWillReturnDefaultLanguageStrings(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'default', 'Save and view page');
        self::assertCount(2, $result);
        self::assertSame('saveAndViewPage', $result[0]->labels[0]->reference);
        self::assertSame('core.common', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/locallang_common.xlf', $result[0]->resource);

        self::assertSame('rm.saveDocShow', $result[1]->labels[0]->reference);
        self::assertSame('core.core', $result[1]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/locallang_core.xlf', $result[1]->resource);
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
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'en', 'Save and view page');
        self::assertCount(2, $result);
        self::assertSame('saveAndViewPage', $result[0]->labels[0]->reference);
        self::assertSame('core.common', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/locallang_common.xlf', $result[0]->resource);

        self::assertSame('rm.saveDocShow', $result[1]->labels[0]->reference);
        self::assertSame('core.core', $result[1]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/locallang_core.xlf', $result[1]->resource);
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
        self::assertSame('wizard.step.required', $result[0]->labels[0]->reference);
        self::assertSame('core.wizard', $result[0]->domain);
        self::assertSame('EXT:core/Resources/Private/Language/wizard.xlf', $result[0]->resource);

        // Now check that this is NOT returned for english
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'en', 'Testinglabelsearchsuche');
        self::assertCount(0, $result, 'Translated label should not be found in original language file.');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderExcludesDeprecatedLabelsByDefault(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'default', 'Labelfindertest', '', false, 0, true);
        self::assertSame(['fixture.current'], array_map(static fn($label): string => $label->reference, $result));
        self::assertFalse($result[0]->deprecated);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderIncludesAndMarksDeprecatedLabels(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'default', 'Labelfindertest', '', false, 0, true, DeprecatedLabels::Include);
        $deprecatedByReference = [];
        foreach ($result as $label) {
            $deprecatedByReference[$label->reference] = $label->deprecated;
        }
        self::assertSame(['fixture.deprecated.x-unused' => true, 'fixture.current' => false], $deprecatedByReference);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderReturnsOnlyDeprecatedLabels(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'default', 'Labelfindertest', '', false, 0, true, DeprecatedLabels::Only);
        self::assertSame(['fixture.deprecated.x-unused'], array_map(static fn($label): string => $label->reference, $result));
        self::assertTrue($result[0]->deprecated);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function labelFinderLimitCountsOnlyReturnedLabels(): void
    {
        $packages = $this->get(PackageManager::class)->getActivePackages();
        // The deprecated label comes first in the file, so it must not use up the limit
        $result = $this->get(LabelFinder::class)->findLabels($packages, 'default', 'Labelfindertest', '', false, 1, true);
        self::assertSame(['fixture.current'], array_map(static fn($label): string => $label->reference, $result));
    }
}
