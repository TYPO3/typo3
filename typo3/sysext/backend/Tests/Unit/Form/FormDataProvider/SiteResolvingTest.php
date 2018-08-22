<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $siteMatcherProphecy = $this->prophesize(SiteMatcher::class);
        GeneralUtility::setSingletonInstance(SiteMatcher::class, $siteMatcherProphecy->reveal());
        $siteProphecy = $this->prophesize(Site::class);
        $siteProphecyRevelation = $siteProphecy->reveal();
        $siteMatcherProphecy->matchByPageId(23)->willReturn($siteProphecyRevelation);
        $input = [
            'defaultLanguagePageRow' => [
                'uid' => 23,
            ],
            'effectivePid' => 42,
            'site' => $siteProphecyRevelation,
        ];
        $expected = $input;
        $expected['site'] = $siteProphecy->reveal();
        $this->assertSame($expected, (new SiteResolving())->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsSiteObjectOfEffectivePid()
    {
        $siteMatcherProphecy = $this->prophesize(SiteMatcher::class);
        GeneralUtility::setSingletonInstance(SiteMatcher::class, $siteMatcherProphecy->reveal());
        $siteProphecy = $this->prophesize(Site::class);
        $siteProphecyRevelation = $siteProphecy->reveal();
        $siteMatcherProphecy->matchByPageId(42)->willReturn($siteProphecyRevelation);
        $input = [
            'effectivePid' => 42,
            'site' => $siteProphecyRevelation,
        ];
        $expected = $input;
        $expected['site'] = $siteProphecy->reveal();
        $this->assertSame($expected, (new SiteResolving())->addData($input));
    }
}
