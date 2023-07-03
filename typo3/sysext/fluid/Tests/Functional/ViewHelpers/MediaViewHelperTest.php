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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Fluid\ViewHelpers\MediaViewHelper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MediaViewHelperTest extends FunctionalTestCase
{
    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/user_upload/example.mp4' => 'fileadmin/user_upload/example.mp4',
    ];

    public static function renderReturnsExpectedMarkupDataProvider(): array
    {
        return [
            'fallback to image' => [
                '1:/user_upload/typo3_image2.jpg',
                ['title' => 'null'],
                '<img src="fileadmin/user_upload/typo3_image2.jpg" width="400" height="300" alt="" />',
            ],
            'show media image' => [
                '1:/user_upload/example.mp4',
                ['title' => 'null', 'additionalConfig' => ['controlsList' => 'nodownload']],
                '<video controls controlsList="nodownload"><source src="fileadmin/user_upload/example.mp4" type="video/mp4"></video>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsExpectedMarkupDataProvider
     */
    public function renderReturnsExpectedMarkup(string $file, array $arguments, string $expected): void
    {
        $file = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier($file);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = new MediaViewHelper();
        $subject->setArguments(['file' => $file] + $arguments);
        $result = $subject->render();
        self::assertEquals($expected, $result);
    }
}
