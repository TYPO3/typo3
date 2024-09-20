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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class MediaViewHelperTest extends FunctionalTestCase
{
    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/user_upload/example.mp4' => 'fileadmin/user_upload/example.mp4',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/MediaViewHelper/Folders/fileadmin/user_upload/example.youtube' => 'fileadmin/user_upload/example.youtube',
    ];

    public static function renderReturnsExpectedMarkupDataProvider(): array
    {
        return [
            'fallback to image' => [
                '<f:media file="{file}" width="400" height="300" />',
                '1:/user_upload/typo3_image2.jpg',
                '<img src="fileadmin/user_upload/typo3_image2.jpg" width="400" height="300" alt="" />',
            ],
            'show media video' => [
                '<f:media file="{file}" additionalConfig="{controlsList: \'nodownload\'}" />',
                '1:/user_upload/example.mp4',
                '<video controls controlsList="nodownload"><source src="fileadmin/user_upload/example.mp4" type="video/mp4"></video>',
            ],
            'show youtube video with title' => [
                '<f:media file="{file}" title="Youtube Video Example" additionalConfig="{allowFullScreen: \'true\'}" />',
                '1:/user_upload/example.youtube',
                '<iframe src="https://www.youtube-nocookie.com/embed/hsrAtnI9244?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2F" allowfullscreen title="Youtube Video Example" allow="fullscreen"></iframe>',
            ],
            'show youtube video with empty title' => [
                '<f:media file="{file}" title="" additionalConfig="{allowFullScreen: \'true\'}" />',
                '1:/user_upload/example.youtube',
                '<iframe src="https://www.youtube-nocookie.com/embed/hsrAtnI9244?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2F" allowfullscreen allow="fullscreen"></iframe>',
            ],
            'show youtube video with title is null' => [
                '<f:media file="{file}" title="null" additionalConfig="{allowFullScreen: \'true\'}" />',
                '1:/user_upload/example.youtube',
                '<iframe src="https://www.youtube-nocookie.com/embed/hsrAtnI9244?autohide=1&amp;controls=1&amp;enablejsapi=1&amp;origin=http%3A%2F%2F" allowfullscreen allow="fullscreen"></iframe>',
            ],
        ];
    }

    #[DataProvider('renderReturnsExpectedMarkupDataProvider')]
    #[Test]
    public function renderReturnsExpectedMarkup(string $template, string $file, string $expected): void
    {
        $file = $this->get(ResourceFactory::class)->getFileObjectFromCombinedIdentifier($file);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->assign('file', $file);
        $result = $view->render();
        self::assertEquals($expected, $result);
    }
}
