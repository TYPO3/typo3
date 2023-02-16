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

namespace TYPO3\CMS\Core\Tests\FunctionalDeprecated\TypoScript;

use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PageTsConfigFactoryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function pageTsConfigMatchesRequestHttpsCondition(): void
    {
        $request = (new ServerRequest('https://www.example.com/', null, 'php://input', [], ['HTTPS' => 'ON']));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'isHttps = off'
                    . chr(10) . '[request.getNormalizedParams().isHttps()]'
                    . chr(10) . '  isHttps = on'
                    . chr(10) . '[end]',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite());
        self::assertSame('on', $pageTsConfig->getPageTsConfigArray()['isHttps']);
    }

    /**
     * @test
     */
    public function pageTsConfigMatchesRequestHttpsElseCondition(): void
    {
        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => '[request.getNormalizedParams().isHttps()]'
                    . chr(10) . '  isHttps = on'
                    . chr(10) . '[else]'
                    . chr(10) . '  isHttps = off',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $pageTsConfig = $subject->create($rootLine, new NullSite());
        self::assertSame('off', $pageTsConfig->getPageTsConfigArray()['isHttps']);
    }

    /**
     * @test
     */
    public function pageTsConfigMatchesRequestHttpsConditionUsingSiteConstant(): void
    {
        $request = (new ServerRequest('https://www.example.com/', null, 'php://input', [], ['HTTPS' => 'ON']));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => 'isHttps = off'
                    . chr(10) . '[{$aSiteSetting}]'
                    . chr(10) . '  isHttps = on'
                    . chr(10) . '[end]',
            ],
        ];
        $subject = $this->get(PageTsConfigFactory::class);
        $siteSettings = new SiteSettings(['aSiteSetting' => 'request.getNormalizedParams().isHttps()']);
        $site = new Site('siteIdentifier', 1, [], $siteSettings);
        $pageTsConfig = $subject->create($rootLine, $site);
        self::assertSame('on', $pageTsConfig->getPageTsConfigArray()['isHttps']);
    }
}
