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

namespace TYPO3\CMS\Install\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Install\Service\LocalConfigurationValueService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalConfigurationValueServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function passwordHashingSectionDataProvider(): array
    {
        return [
            'backend' => ['BE'],
            'frontend' => ['FE'],
        ];
    }

    #[DataProvider('passwordHashingSectionDataProvider')]
    #[Test]
    public function passwordHashingSettingsAreDescribed(string $section): void
    {
        $items = array_column(
            (new LocalConfigurationValueService())->getCurrentConfigurationData()[$section]['items'],
            null,
            'key'
        );

        self::assertNotSame('', $items['passwordHashing/className']['description']);
        self::assertStringContainsString('memory_cost', $items['passwordHashing/options']['description']);
        self::assertSame('map', $items['passwordHashing/options']['type']);
    }

    #[DataProvider('passwordHashingSectionDataProvider')]
    #[Test]
    public function passwordHashingOptionsKeepTheirNamesWhenWritten(string $section): void
    {
        $path = $section . '/passwordHashing/options';

        (new LocalConfigurationValueService())->updateLocalConfigurationValues([
            $path => ['memory_cost' => '131072', 'time_cost' => '8'],
        ]);

        self::assertSame(
            ['memory_cost' => '131072', 'time_cost' => '8'],
            $this->get(ConfigurationManager::class)->getLocalConfigurationValueByPath($path)
        );
    }
}
