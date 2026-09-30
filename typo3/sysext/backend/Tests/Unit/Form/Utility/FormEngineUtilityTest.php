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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Utility;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormEngineUtilityTest extends UnitTestCase
{
    #[Test]
    public function overrideFieldConfAllowsCopyToClipboardAppearanceOfUuidField(): void
    {
        $result = FormEngineUtility::overrideFieldConf(
            ['type' => 'uuid'],
            ['config.' => ['appearance.' => ['copyToClipboard' => '0']]]
        );

        self::assertSame(['type' => 'uuid', 'appearance' => ['copyToClipboard' => '0']], $result);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function overrideFieldConfMigratesEnableCopyToClipboardOfUuidFieldToAppearance(): void
    {
        $result = FormEngineUtility::overrideFieldConf(
            ['type' => 'uuid', 'appearance' => ['copyToClipboard' => true]],
            ['config.' => ['enableCopyToClipboard' => '0']]
        );

        self::assertSame(['type' => 'uuid', 'appearance' => ['copyToClipboard' => '0']], $result);
    }
}
