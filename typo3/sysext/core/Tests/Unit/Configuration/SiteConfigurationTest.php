<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteConfigurationTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @var string
     * store temporarily used files here
     * will be removed after each test
     */
    protected $fixturePath;

    /**
     * @var SiteConfiguration
     */
    protected $subject;

    protected function setup(): void
    {
        $basePath = Environment::getVarPath() . '/tests/unit';
        $this->fixturePath = $basePath . '/fixture/config/sites';
        if (!file_exists($this->fixturePath)) {
            GeneralUtility::mkdir_deep($this->fixturePath);
        }
        $this->testFilesToDelete[] = $basePath;
        $cacheManager = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());
        $cacheManager->getCache('core')->willReturn(new NullFrontend('core'));
        $this->subject = new SiteConfiguration($this->fixturePath);
    }

    /**
     * @test
     */
    public function resolveAllExistingSitesReturnsEmptyArrayForNoSiteConfigsFound(): void
    {
        $this->assertEmpty($this->subject->resolveAllExistingSites());
    }

    /**
     * @test
     */
    public function resolveAllExistingSitesReadsConfiguration(): void
    {
        $configuration = [
            'rootPageId' => 42,
            'base' => 'https://example.com'
        ];
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        GeneralUtility::mkdir($this->fixturePath . '/home');
        GeneralUtility::writeFile($this->fixturePath . '/home/config.yaml', $yamlFileContents);
        $sites = $this->subject->resolveAllExistingSites();
        $this->assertCount(1, $sites);
        $currentSite = current($sites);
        $this->assertSame(42, $currentSite->getRootPageId());
        $this->assertEquals(new Uri('https://example.com'), $currentSite->getBase());
    }
}
