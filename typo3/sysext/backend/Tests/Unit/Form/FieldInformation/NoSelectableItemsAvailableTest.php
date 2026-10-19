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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FieldInformation;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FieldInformation\NoSelectableItemsAvailable;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class NoSelectableItemsAvailableTest extends UnitTestCase
{
    #[Test]
    public function renderAllowsTheHintBadgeToWrap(): void
    {
        // Badges do not wrap by default (white-space: nowrap), so this sentence
        // would overflow the form in narrow layouts and longer languages.
        $languageServiceStub = self::createStub(LanguageService::class);
        $languageServiceStub->method('sL')->willReturnMap([
            ['core.core:labels.noSelectableItemsAvailable', 'Keine auswählbaren Elemente verfügbar. Überprüfe TCA, verwandte Datensätze & TSconfig.'],
        ]);
        $GLOBALS['LANG'] = $languageServiceStub;

        $result = (new NoSelectableItemsAvailable())->render();

        self::assertStringContainsString('Keine auswählbaren Elemente verfügbar. Überprüfe TCA, verwandte Datensätze &amp; TSconfig.', $result['html']);
        self::assertMatchesRegularExpression('/<span class="badge badge-info[^"]*\btext-wrap\b/', $result['html']);
    }
}
