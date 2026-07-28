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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ImageContentObjectTest extends FunctionalTestCase
{
    #[Test]
    public function cImageRendersNothingForImageResourceWithoutPublicUrl(): void
    {
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['getImgResource', 'getTypoScriptFrontendController'])
            ->disableOriginalConstructor()
            ->getMock();
        // A file whose physical resource is gone (sys_file.missing=1) resolves to
        // an image resource of the processed file without a public URL.
        $cObj->method('getImgResource')->willReturn(
            new ImageResource(100, 100, 'jpg', 'missing-file.jpg', null, $this->createMock(File::class))
        );
        // 13.4: cImage() calls getTypoScriptFrontendController() unconditionally before any early return.
        $cObj->method('getTypoScriptFrontendController')->willReturn(
            $this->createMock(TypoScriptFrontendController::class)
        );
        $timeTracker = $this->get(TimeTracker::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with(
            'The image "{file}" has no public URL, the file is probably missing, and won\'t be included in frontend output',
            ['file' => 'missing-file.jpg']
        );
        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class), $timeTracker, $logger]);
        $subject->setRequest(new ServerRequest());
        $subject->setContentObjectRenderer($cObj);

        self::assertSame('', $subject->_call('cImage', 'missing-file.jpg', []));
        self::assertSame([], $this->get(AssetCollector::class)->getMedia());
        $logMessages = array_merge(...array_column($timeTracker->getTypoScriptLogStack(), 'message'));
        self::assertStringContainsString(
            'The image &quot;missing-file.jpg&quot; has no public URL, the file is probably missing. It is not rendered.',
            implode(LF, $logMessages)
        );
    }
}
