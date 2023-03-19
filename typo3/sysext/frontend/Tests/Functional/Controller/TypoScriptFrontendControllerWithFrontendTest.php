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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TypoScriptFrontendControllerWithFrontendTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    /**
     * @test
     */
    public function jsIncludesWithUserIntIsRendered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            [
                'typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/jsInline.typoscript',
            ]
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://website.local/en/'))->withPageId(2)
        );

        $body = (string)$response->getBody();
        self::assertStringContainsString('/*TS_inlineJSint*/
alert(yes);', $body);
    }
}
