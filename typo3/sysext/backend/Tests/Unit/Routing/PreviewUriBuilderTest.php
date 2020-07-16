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

namespace TYPO3\CMS\Backend\Tests\Unit\Routing;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PreviewUriBuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function attributesContainAlternativeUri(): void
    {
        // Make sure the hook inside viewOnClick is not fired. This may be removed if unit tests
        // bootstrap does not initialize TYPO3_CONF_VARS anymore.
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']);

        $alternativeUri = 'https://typo3.org/about/typo3-the-cms/the-history-of-typo3/#section';
        $subject = PreviewUriBuilder::create(0, $alternativeUri)->withModuleLoading(false);
        $attributes = $subject->buildDispatcherAttributes([PreviewUriBuilder::OPTION_SWITCH_FOCUS => false]);

        self::assertSame(
            [
                'data-dispatch-action' => 'TYPO3.WindowManager.localOpen',
                'data-dispatch-args' => '["https:\/\/typo3.org\/about\/typo3-the-cms\/the-history-of-typo3\/#section",false,"newTYPO3frontendWindow"]',
            ],
            $attributes
        );
    }
}
