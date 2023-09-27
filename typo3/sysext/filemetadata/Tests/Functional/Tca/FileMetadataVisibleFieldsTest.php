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

namespace TYPO3\CMS\Filemetadata\Tests\Functional\Tca;

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileMetadataVisibleFieldsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filemetadata'];

    /**
     * @test
     * @dataProvider metadataFieldsDataDataProvider
     */
    public function fileMetadataFormContainsExpectedFields(FileType $filetype, array $fields): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        $GLOBALS['TCA']['sys_file_metadata']['ctrl']['type'] = 'fileype';

        $formEngineTestService = GeneralUtility::makeInstance(FormTestService::class);

        $formResult = $formEngineTestService->createNewRecordForm(
            'sys_file_metadata',
            ['fileype' => $filetype->value]
        );

        foreach ($fields as $expectedFields) {
            self::assertTrue(
                $formEngineTestService->formHtmlContainsField($expectedFields, $formResult['html']),
                'The field ' . $expectedFields . ' is not in the form HTML for file type ' . $filetype->name
            );
        }
    }

    /**
     * @return array[]
     */
    public static function metadataFieldsDataDataProvider(): array
    {
        return [
            FileType::UNKNOWN->name => [
                FileType::UNKNOWN,
                [
                    'title',
                    'description',
                    'ranking',
                    'keywords',
                    'caption',
                    'download_name',
                    'visible',
                    'status',
                    'fe_groups',
                    'creator',
                    'creator_tool',
                    'publisher',
                    'source',
                    'copyright',
                    'location_country',
                    'location_region',
                    'location_city',
                    'categories',
                ],
            ],
            FileType::TEXT->value => [
                FileType::TEXT,
                [
                    'title',
                    'description',
                    'ranking',
                    'keywords',
                    'caption',
                    'download_name',
                    'visible',
                    'status',
                    'fe_groups',
                    'creator',
                    'creator_tool',
                    'publisher',
                    'source',
                    'copyright',
                    'language',
                    'location_country',
                    'location_region',
                    'location_city',
                    'categories',
                ],
            ],
            FileType::IMAGE->value => [
                FileType::IMAGE,
                [
                    'title',
                    'description',
                    'ranking',
                    'keywords',
                    'alternative',
                    'caption',
                    'download_name',
                    'visible',
                    'status',
                    'fe_groups',
                    'creator',
                    'creator_tool',
                    'publisher',
                    'source',
                    'copyright',
                    'language',
                    'location_country',
                    'location_region',
                    'location_city',
                    'latitude',
                    'longitude',
                    'content_creation_date',
                    'content_modification_date',
                    'categories',
                ],
            ],
            FileType::AUDIO->value => [
                FileType::AUDIO,
                [
                    'title',
                    'description',
                    'ranking',
                    'keywords',
                    'caption',
                    'download_name',
                    'visible',
                    'status',
                    'fe_groups',
                    'creator',
                    'creator_tool',
                    'publisher',
                    'source',
                    'copyright',
                    'language',
                    'content_creation_date',
                    'content_modification_date',
                    'duration',
                    'categories',
                ],
            ],
            FileType::VIDEO->value => [
                FileType::VIDEO,
                [
                    'title',
                    'description',
                    'ranking',
                    'keywords',
                    'caption',
                    'download_name',
                    'visible',
                    'status',
                    'fe_groups',
                    'creator',
                    'creator_tool',
                    'publisher',
                    'source',
                    'copyright',
                    'language',
                    'content_creation_date',
                    'content_modification_date',
                    'duration',
                    'categories',
                ],
            ],
        ];
    }
}
