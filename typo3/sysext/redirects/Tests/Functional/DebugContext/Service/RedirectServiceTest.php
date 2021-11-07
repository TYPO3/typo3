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

namespace TYPO3\CMS\Redirects\Tests\Functional\DebugContext\Service;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RedirectServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['redirects'];

    /**
     * @var string[]
     */
    protected $testFilesToDelete = [];

    protected $configurationToUseInTestInstance = [
        'FE' => [
            'debug' => true,
        ],
        'SYS' => [
            'defIPmask' => '*',
            'displayErrors' => 1,
            'exceptionalErrors' => 12290,
        ],
    ];

    protected function tearDown(): void
    {
        foreach ($this->testFilesToDelete as $filename) {
            if (@is_file($filename)) {
                unlink($filename);
            }
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function checkRegExpRedirectsWithSiteLanguageFallbackConditionInTypoScript(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/RedirectService_typoScriptSiteLanguageFallbackCondition.xml');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/redirects/Tests/Functional/DebugContext/Service/Fixtures/RedirectsSiteLanguageFallbackCondition.typoscript']
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest('https://acme.com/redirect-to-page2'),
            null,
            false
        );
        self::assertEquals(301, $response->getStatusCode());
        self::assertIsArray($response->getHeader('X-Redirect-By'));
        self::assertIsArray($response->getHeader('location'));
        self::assertEquals('TYPO3 Redirect ' . 1, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals('https://acme.com/page2', $response->getHeader('location')[0]);
    }
}
