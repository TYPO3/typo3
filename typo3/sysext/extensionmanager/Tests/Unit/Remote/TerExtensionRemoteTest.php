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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Remote;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Extensionmanager\Remote\TerExtensionRemote;
use TYPO3\CMS\Extensionmanager\Remote\VerificationFailedException;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TerExtensionRemoteTest extends UnitTestCase
{
    #[Test]
    public function downloadExtensionFetchesTheZipArchiveWhenAnArtifactHashIsGiven(): void
    {
        $zipContent = 'PK-the-uploaded-zip-archive';
        $fileHandler = $this->createMock(FileHandlingUtility::class);
        $fileHandler->expects($this->once())
            ->method('unzipExtensionFromFile')
            ->with(self::isString(), 'my_ext', '1.2.3');
        $fileHandler->expects($this->never())->method('unpackExtensionFromExtensionDataArray');

        $subject = $this->getAccessibleMock(TerExtensionRemote::class, ['downloadFile'], ['ter']);
        $subject->expects($this->once())
            ->method('downloadFile')
            ->with('m/y/my_ext_1.2.3.zip')
            ->willReturn($this->buildResponse($zipContent));

        $subject->downloadExtension('my_ext', '1.2.3', $fileHandler, 'sha256:' . hash('sha256', $zipContent));
    }

    #[Test]
    public function downloadExtensionRejectsAZipArchiveThatDoesNotMatchTheArtifactHash(): void
    {
        $fileHandler = $this->createMock(FileHandlingUtility::class);
        $fileHandler->expects($this->never())->method('unzipExtensionFromFile');

        $subject = $this->getAccessibleMock(TerExtensionRemote::class, ['downloadFile'], ['ter']);
        $subject->expects($this->once())
            ->method('downloadFile')
            ->with('m/y/my_ext_1.2.3.zip')
            ->willReturn($this->buildResponse('a tampered zip archive'));

        $this->expectException(VerificationFailedException::class);
        $this->expectExceptionCode(1788537157);
        $subject->downloadExtension('my_ext', '1.2.3', $fileHandler, 'sha256:' . hash('sha256', 'the expected zip archive'));
    }

    /**
     * An extension list without artifact hashes - a remote that does not publish
     * them, or a version uploaded before they were recorded - keeps working
     * through the t3x file.
     */
    #[Test]
    public function downloadExtensionFallsBackToTheExchangeFileWithoutAnArtifactHash(): void
    {
        $extensionData = [
            'extKey' => 'my_ext',
            'version' => '1.2.3',
            'EM_CONF' => ['version' => '1.2.3'],
            'FILES' => [],
        ];
        $serialized = serialize($extensionData);
        $t3xContent = md5($serialized) . ':gzcompress:' . gzcompress($serialized);

        $fileHandler = $this->createMock(FileHandlingUtility::class);
        $fileHandler->expects($this->once())
            ->method('unpackExtensionFromExtensionDataArray')
            ->with('my_ext', $extensionData, '1.2.3');
        $fileHandler->expects($this->never())->method('unzipExtensionFromFile');

        $subject = $this->getAccessibleMock(TerExtensionRemote::class, ['downloadFile'], ['ter']);
        $subject->expects($this->once())
            ->method('downloadFile')
            ->with('m/y/my_ext_1.2.3.t3x')
            ->willReturn($this->buildResponse($t3xContent));

        $subject->downloadExtension('my_ext', '1.2.3', $fileHandler, md5($t3xContent));
    }

    private function buildResponse(string $body): Response
    {
        $stream = new StreamFactory()->createStream($body);
        $stream->rewind();
        return new Response($stream);
    }
}
