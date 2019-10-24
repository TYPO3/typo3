<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Tests\Unit\Configuration;

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

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TranslationConfigurationProviderTest extends UnitTestCase
{
    /**
     * @var TranslationConfigurationProvider
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TranslationConfigurationProvider();
    }

    /**
     * @test
     */
    public function defaultLanguageIsAlwaysReturned(): void
    {
        $pageId = 1;
        $site = new Site('dummy', $pageId, ['base' => 'http://sub.domainhostname.tld/path/']);
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteFinderProphecy->getSiteByPageId($pageId)->willReturn($site);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderProphecy->reveal());

        $backendUserAuthentication = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication->reveal();

        $languages = $this->subject->getSystemLanguages($pageId);
        self::assertArrayHasKey(0, $languages);
    }
}
