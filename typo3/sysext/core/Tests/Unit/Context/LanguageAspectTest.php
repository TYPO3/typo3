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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LanguageAspectTest extends UnitTestCase
{
    /**
     * @deprecated Remove in v16 together with LanguageAspect::OVERLAYS_ON
     */
    #[Test]
    #[IgnoreDeprecations]
    public function overlayTypeOnTriggersDeprecationAndIsKept(): void
    {
        $this->expectUserDeprecationMessageMatches('/LanguageAspect::OVERLAYS_ON\) is deprecated/');
        $subject = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON);
        self::assertSame(LanguageAspect::OVERLAYS_ON, $subject->getOverlayType());
        self::assertTrue($subject->doOverlays());
    }
}
