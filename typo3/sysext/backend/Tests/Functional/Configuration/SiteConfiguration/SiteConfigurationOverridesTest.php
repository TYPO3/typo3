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

namespace TYPO3\CMS\Backend\Tests\Functional\Configuration\SiteConfiguration;

use TYPO3\CMS\Backend\Configuration\SiteTcaConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Tests\Functional\Configuration\SiteConfiguration\SiteConfigurationOverridesTest
 */
class SiteConfigurationOverridesTest extends FunctionalTestCase
{

    /** @var array */
    protected $subject;

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/backend/Tests/Functional/Configuration/SiteConfiguration/Fixtures/Extensions/conf_overriding/a',
        'typo3/sysext/backend/Tests/Functional/Configuration/SiteConfiguration/Fixtures/Extensions/conf_overriding/b',
    ];

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = (new SiteTcaConfiguration())->getTca();
    }

    /**
     * @test
     */
    public function allOverridesLoaded(): void
    {
        $columnsConfiguration = $this->subject['site']['columns'];

        self::assertArrayHasKey('tx_a_a', $columnsConfiguration);
        self::assertArrayHasKey('tx_b_a', $columnsConfiguration);
    }

    /**
     * @test
     */
    public function overrideOnlyLoadedOnce(): void
    {
        $showitemConfiguration = $this->subject['site']['types']['0']['showitem'];

        self::assertSame(1, mb_substr_count($showitemConfiguration, 'tx_a_a'));
        self::assertSame(1, mb_substr_count($showitemConfiguration, 'tx_b_a'));
    }

    /**
     * @test
     */
    public function finderUsesCorrectOrder(): void
    {
        $columnsConfiguration = $this->subject['site']['columns'];

        self::assertSame('Awesome description by extension b', $columnsConfiguration['tx_a_a']['description']);
    }
}
