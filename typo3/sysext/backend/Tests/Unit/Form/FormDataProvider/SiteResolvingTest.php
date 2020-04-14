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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SiteResolvingTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function addDataAddsSiteObjectOfDefaultLanguageRow()
    {
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteProphecy = $this->prophesize(Site::class);
        $siteProphecyRevelation = $siteProphecy->reveal();
        $siteFinderProphecy->getSiteByPageId(23)->willReturn($siteProphecyRevelation);
        $input = [
            'defaultLanguagePageRow' => [
                'uid' => 23,
            ],
            'effectivePid' => 42,
            'site' => $siteProphecyRevelation,
        ];
        $expected = $input;
        $expected['site'] = $siteProphecy->reveal();
        self::assertSame($expected, (new SiteResolving($siteFinderProphecy->reveal()))->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsSiteObjectOfEffectivePid()
    {
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteProphecy = $this->prophesize(Site::class);
        $siteProphecyRevelation = $siteProphecy->reveal();
        $siteFinderProphecy->getSiteByPageId(42)->willReturn($siteProphecyRevelation);
        $input = [
            'effectivePid' => 42,
            'site' => $siteProphecyRevelation,
        ];
        $expected = $input;
        $expected['site'] = $siteProphecy->reveal();
        self::assertSame($expected, (new SiteResolving($siteFinderProphecy->reveal()))->addData($input));
    }
}
