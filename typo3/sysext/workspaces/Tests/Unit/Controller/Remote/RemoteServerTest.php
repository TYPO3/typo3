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

namespace TYPO3\CMS\Workspaces\Tests\Unit\Controller\Remote;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Controller\Remote\RemoteServer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RemoteServerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @var array<string, FileReference>
     */
    protected array $fileReferenceMocks;

    /**
     * @return array
     */
    public function prepareFileReferenceDifferencesAreCorrectDataProvider(): array
    {
        return [
            // without thumbnails
            'unchanged wo/thumbnails' => ['1,2,3,4', '1,2,3,4', false, null],
            'front addition wo/thumbnails' => ['1,2,3,4', '99,1,2,3,4', false, [
                'live' => '/img/1.png /img/2.png /img/3.png /img/4.png',
                'differences' => '<ins>/img/99.png </ins>/img/1.png /img/2.png /img/3.png /img/4.png',
            ]],
            'end addition wo/thumbnails' => ['1,2,3,4', '1,2,3,4,99', false, [
                'live' => '/img/1.png /img/2.png /img/3.png /img/4.png',
                'differences' => '/img/1.png /img/2.png /img/3.png /img/4.png <ins>/img/99.png </ins>',
            ]],
            'reorder wo/thumbnails' => ['1,2,3,4', '1,3,2,4', false, [
                'live' => '/img/1.png /img/2.png /img/3.png /img/4.png',
                'differences' => '/img/1.png <ins>/img/3.png </ins>/img/2.png <del>/img/3.png </del>/img/4.png',
            ]],
            'move to end wo/thumbnails' => ['1,2,3,4', '2,3,4,1', false, [
                'live' => '/img/1.png /img/2.png /img/3.png /img/4.png',
                'differences' => '<del>/img/1.png </del>/img/2.png /img/3.png /img/4.png <ins>/img/1.png </ins>',
            ]],
            'move to front wo/thumbnails' => ['1,2,3,4', '4,1,2,3', false, [
                'live' => '/img/1.png /img/2.png /img/3.png /img/4.png',
                'differences' => '<ins>/img/4.png </ins>/img/1.png /img/2.png /img/3.png <del>/img/4.png </del>',
            ]],
            'keep last wo/thumbnails' => ['1,2,3,4', '4', false, [
                'live' => '/img/1.png /img/2.png /img/3.png /img/4.png',
                'differences' => '<del>/img/1.png /img/2.png /img/3.png </del>/img/4.png',
            ]],
            // with thumbnails
            'unchanged w/thumbnails' => ['1,2,3,4', '1,2,3,4', true, null],
            'front addition w/thumbnails' => ['1,2,3,4', '99,1,2,3,4', true, [
                'live' => '<img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" />',
                'differences' => '<ins><img src="/tmb/99.png" /> </ins><img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" />',
            ]],
            'end addition w/thumbnails' => ['1,2,3,4', '1,2,3,4,99', true, [
                'live' => '<img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" />',
                'differences' => '<img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" /> <ins><img src="/tmb/99.png" /> </ins>',
            ]],
            'reorder w/thumbnails' => ['1,2,3,4', '1,3,2,4', true, [
                'live' => '<img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" />',
                'differences' => '<img src="/tmb/1.png" /> <ins><img src="/tmb/3.png" /> </ins><img src="/tmb/2.png" /> <del><img src="/tmb/3.png" /> </del><img src="/tmb/4.png" />',
            ]],
            'move to end w/thumbnails' => ['1,2,3,4', '2,3,4,1', true, [
                'live' => '<img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" />',
                'differences' => '<del><img src="/tmb/1.png" /> </del><img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" /> <ins><img src="/tmb/1.png" /> </ins>',
            ]],
            'move to front w/thumbnails' => ['1,2,3,4', '4,1,2,3', true, [
                'live' => '<img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" />',
                'differences' => '<ins><img src="/tmb/4.png" /> </ins><img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <del><img src="/tmb/4.png" /> </del>',
            ]],
            'keep last w/thumbnails' => ['1,2,3,4', '4', true, [
                'live' => '<img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> <img src="/tmb/4.png" />',
                'differences' => '<del><img src="/tmb/1.png" /> <img src="/tmb/2.png" /> <img src="/tmb/3.png" /> </del><img src="/tmb/4.png" />',
            ]],
        ];
    }

    /**
     * @param string $fileFileReferenceList
     * @param string $versionFileReferenceList
     * @param bool $useThumbnails
     * @param array|null $expected
     * @dataProvider prepareFileReferenceDifferencesAreCorrectDataProvider
     * @test
     */
    public function prepareFileReferenceDifferencesAreCorrect(string $fileFileReferenceList, string $versionFileReferenceList, bool $useThumbnails, array $expected = null): void
    {
        $liveFileReferences = $this->getFileReferenceMocks($fileFileReferenceList);
        $versionFileReferences = $this->getFileReferenceMocks($versionFileReferenceList);

        $subject = $this->getAccessibleMock(RemoteServer::class, ['__none'], [], '', false);
        $result = $subject->_call(
            'prepareFileReferenceDifferences',
            $liveFileReferences,
            $versionFileReferences,
            $useThumbnails
        );

        self::assertSame($expected, $result);
    }

    /**
     * @param string $idList List of ids
     * @return array<string, FileReference>
     */
    protected function getFileReferenceMocks(string $idList): array
    {
        $fileReferenceProphecies = [];
        $ids = GeneralUtility::trimExplode(',', $idList, true);

        foreach ($ids as $id) {
            $fileReferenceProphecies[$id] = $this->getFileReferenceMock($id);
        }

        return $fileReferenceProphecies;
    }

    protected function getFileReferenceMock(string $id): FileReference
    {
        if (isset($this->fileReferenceMocks[$id])) {
            return $this->fileReferenceMocks[$id];
        }

        $processedFileProphecy = $this->prophesize(ProcessedFile::class);
        $processedFileProphecy->getPublicUrl(Argument::cetera())->willReturn('/tmb/' . $id . '.png');

        $fileProphecy = $this->prophesize(File::class);
        $fileProphecy->process(Argument::cetera())->willReturn($processedFileProphecy->reveal());

        $fileReferenceProphecy = $this->prophesize(FileReference::class);
        $fileReferenceProphecy->getUid()->willReturn($id);
        $fileReferenceProphecy->getOriginalFile()->willReturn($fileProphecy->reveal());
        $fileReferenceProphecy->getPublicUrl(Argument::cetera())->willReturn('/img/' . $id . '.png');

        $this->fileReferenceMocks[$id] = $fileReferenceProphecy->reveal();
        return $this->fileReferenceMocks[$id];
    }
}
