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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\SiteSettingsController;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteSettingsControllerTest extends UnitTestCase
{
    #[Test]
    public function resolveSettingLabelsResolvesEnumLabelsWithLanguageService(): void
    {
        $subject = $this->getAccessibleMock(SiteSettingsController::class, ['getLanguageService'], [], '', false);
        $languageService = $this->createMock(LanguageService::class);
        $languageService
            ->method('sL')
            ->willReturnCallback(static fn(string $label): string => match ($label) {
                'LLL:EXT:my_site/Configuration/Sets/MySite/labels.xlf:settings.my.enumSetting' => 'Setting label',
                'LLL:EXT:my_site/Configuration/Sets/MySite/labels.xlf:settings.description.my.enumSetting' => 'Setting description',
                'LLL:EXT:my_site/Configuration/Sets/MySite/labels.xlf:settings.explicit.my.enumSetting.optionExplicit' => 'Translated option explicit',
                default => $label,
            });
        $subject->method('getLanguageService')->willReturn($languageService);

        $settingDefinition = new SettingDefinition(
            key: 'my.enumSetting',
            type: 'string',
            default: 'optionExplicit',
            label: 'LLL:EXT:my_site/Configuration/Sets/MySite/labels.xlf:settings.my.enumSetting',
            description: 'LLL:EXT:my_site/Configuration/Sets/MySite/labels.xlf:settings.description.my.enumSetting',
            enum: [
                'optionExplicit' => 'LLL:EXT:my_site/Configuration/Sets/MySite/labels.xlf:settings.explicit.my.enumSetting.optionExplicit',
                'optionLiteral' => 'Option literal from YAML',
                'optionEmptyLiteral' => '',
                'optionKeyOnly' => 'optionKeyOnly',
            ],
        );

        $reflection = new \ReflectionClass(SiteSettingsController::class);
        $method = $reflection->getMethod('resolveSettingLabels');

        /** @var SettingDefinition $resolvedSettingDefinition */
        $resolvedSettingDefinition = $method->invoke($subject, $settingDefinition);
        self::assertSame('Setting label', $resolvedSettingDefinition->label);
        self::assertSame('Setting description', $resolvedSettingDefinition->description);
        self::assertSame(
            [
                'optionExplicit' => 'Translated option explicit',
                'optionLiteral' => 'Option literal from YAML',
                'optionEmptyLiteral' => '',
                'optionKeyOnly' => 'optionKeyOnly',
            ],
            $resolvedSettingDefinition->enum
        );
    }
}
