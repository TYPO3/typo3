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

namespace TYPO3\CMS\Frontend\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SvgImageRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    private array $definedResources = [
        'localImage1' => 'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest1.svg',
        'localImage2' => 'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest2.svg',
        'localImage3' => 'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest3.svg',
        'localImage4' => 'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest4.svg',

        'localImage1Uid' => '1',
        'localImage2Uid' => '2',
        'localImage3Uid' => '3',
        'localImage4Uid' => '4',

        'localImage1UidUncrop' => '6',
        'localImage2UidUncrop' => '7',
        'localImage3UidUncrop' => '8',
        'localImage4UidUncrop' => '9',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest1.svg' => 'fileadmin/user_upload/FALImageViewHelperTest1.svg',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest2.svg' => 'fileadmin/user_upload/FALImageViewHelperTest2.svg',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest3.svg' => 'fileadmin/user_upload/FALImageViewHelperTest3.svg',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest4.svg' => 'fileadmin/user_upload/FALImageViewHelperTest4.svg',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest5.svg' => 'fileadmin/user_upload/FALImageViewHelperTest5.svg',
    ];

    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCsvDataSet(__DIR__ . '/../../../../frontend/Tests/Functional/Fixtures/pages_frontend.csv');
        $this->importCSVDataSet(__DIR__ . '/../../../../frontend/Tests/Functional/Fixtures/crops.csv');

        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:frontend/Tests/Functional/Rendering/Fixtures/SvgImageRenderingTest.typoscript']
        );
        $this->setTypoScriptConstantsToTemplateRecord(
            1,
            $this->compileTypoScriptConstants($this->definedResources)
        );
    }

    public static function svgsAreRenderedWithTyposcriptDataProvider(): array
    {
        return [
            'rendered svg assets contains' => [
                [
                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest1_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest2_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest3_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest4_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',

                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest1_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',
                    // @todo should be 273x273 (or 274x274)
                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest2_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest3_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]/csm_ImageViewHelperTest4_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',

                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest1_.*\.svg" width="231" height="238"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest2_.*\.svg" width="241" height="60"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest3_.*\.svg" width="176" height="320"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest4_.*\.svg" width="114" height="131"\s+alt=""\s+/?>@U',

                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest1_.*\.png" width="231" height="238"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest2_.*\.png" width="241" height="60"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest3_.*\.png" width="176" height="320"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest4_.*\.png" width="114" height="131"\s+alt=""\s+/?>@U',

                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest1_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest2_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest3_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest4_.*\.svg" width="500" height="500"\s+alt=""\s+/?>@U',

                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest1_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',
                    // @todo should be 273x273 (or 274x274)
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest2_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest3_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',
                    '@<img src="/fileadmin/_processed_/[0-9a-f]/[0-9a-f]/csm_FALImageViewHelperTest4_.*\.png" width="500" height="500"\s+alt=""\s+/?>@U',
                ],
            ],
        ];
    }

    #[DataProvider('svgsAreRenderedWithTyposcriptDataProvider')]
    #[Test]
    public function svgsAreRenderedWithTyposcript(array $expectedAssets): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => 1,
            ])
        );
        $content = (string)$response->getBody();

        preg_match('@<body>(.+)</body>@imsU', $content, $bodyContent);
        self::assertIsArray($bodyContent);

        foreach ($expectedAssets as $expectedAsset) {
            self::assertMatchesRegularExpression($expectedAsset, $bodyContent[1]);
        }
    }

    /**
     * Adds TypoScript constants snippet to the existing template record
     */
    protected function setTypoScriptConstantsToTemplateRecord(int $pageId, string $constants, bool $append = false): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_template');

        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => $pageId, 'root' => 1])->fetchAssociative();
        if (empty($template)) {
            self::fail('Cannot find root template on page with id: "' . $pageId . '"');
        }
        $updateFields = [];
        $updateFields['constants'] = ($append ? $template['constants'] . LF : '') . $constants;
        $connection->update(
            'sys_template',
            $updateFields,
            ['uid' => $template['uid']]
        );
    }

    protected function compileTypoScriptConstants(array $constants): string
    {
        $lines = [];
        foreach ($constants as $constantName => $constantValue) {
            $lines[] = $constantName . ' = ' . $constantValue;
        }
        return implode(PHP_EOL, $lines);
    }
}
