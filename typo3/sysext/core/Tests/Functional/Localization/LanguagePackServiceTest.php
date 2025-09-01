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

namespace TYPO3\CMS\Core\Tests\Functional\Localization;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguagePackService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LanguagePackServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_set_labels_only',
    ];

    protected bool $initializeDatabase = false;

    #[Test]
    public function extensionLanguagePackDetailsContainExtensionWithSetLabelsOnly(): void
    {
        $details = $this->get(LanguagePackService::class)->getExtensionLanguagePackDetails();
        self::assertArrayHasKey('test_set_labels_only', $details);
    }

    #[Test]
    public function extensionLanguagePackDetailsContainExtensionWithResourcesLabels(): void
    {
        $details = $this->get(LanguagePackService::class)->getExtensionLanguagePackDetails();
        self::assertArrayHasKey('core', $details);
    }
}
