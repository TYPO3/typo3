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

namespace TYPO3\CMS\Frontend\Tests\Functional\DataProcessing;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlexFormProcessorTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var array Used by buildDefaultLanguageConfiguration() of SiteBasedTestTrait
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $additionalFoldersToCreate = [
        'fileadmin/user_upload',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Images/typo3-logo.png' => 'fileadmin/user_upload/typo3-logo.png',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/FlexformDataProcessor.csv');

        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
    }

    /**
     * @test
     */
    public function referencedImageWillGetResolved(): void
    {
        $this->setUpFrontendRootPage(
            1,
            ['EXT:frontend/Tests/Functional/Fixtures/Extensions/test_fluid_template/Configuration/TypoScript/flexform_dataprocessor.typoscript']
        );

        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/'))->withPageId(1));
        $body = (string)$response->getBody();
        self::assertStringContainsString('<img src="/fileadmin/user_upload/typo3-logo.png" width="238" height="100" alt="" title="TYPO3 Logo" />', $body);
    }
}
