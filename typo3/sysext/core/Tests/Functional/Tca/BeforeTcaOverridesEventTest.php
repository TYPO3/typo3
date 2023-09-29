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

namespace TYPO3\CMS\Core\Tests\Functional\Tca;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BeforeTcaOverridesEventTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_tca_event',
    ];

    /**
     * @test
     */
    public function cannotOverrideTcaOverrides(): void
    {
        self::assertSame('text', $GLOBALS['TCA']['fruit']['columns']['name']['config']['type']);
    }

    /**
     * @test
     */
    public function canOverrideBaseTca(): void
    {
        self::assertSame('Monstera', $GLOBALS['TCA']['fruit']['columns']['name']['config']['label']);
    }
}
