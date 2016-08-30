<?php
namespace TYPO3\CMS\Workspaces\Tests\Unit\ExtDirect;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ExtDirectServer test
 */
class ExtDirectServerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Workspaces\ExtDirect\ExtDirectServer
     */
    protected $subject;

    /**
     * @var FileReference[]|ObjectProphecy[]
     */
    protected $fileReferenceProphecies;

    /**
     * Set up
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(\TYPO3\CMS\Workspaces\ExtDirect\ExtDirectServer::class, ['__none']);
    }

    /**
     * Tear down.
     */
    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
        unset($this->fileReferenceProphecies);
    }

    /**
     * @return array
     */
    public function prepareFileReferenceDifferencesAreCorrectDataProvider()
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
     * @param $useThumbnails
     * @param array|null $expected
     * @dataProvider prepareFileReferenceDifferencesAreCorrectDataProvider
     * @test
     */
    public function prepareFileReferenceDifferencesAreCorrect($fileFileReferenceList, $versionFileReferenceList, $useThumbnails, array $expected = null)
    {
        $liveFileReferences = $this->getFileReferenceProphecies($fileFileReferenceList);
        $versionFileReferences = $this->getFileReferenceProphecies($versionFileReferenceList);

        $result = $this->subject->_call(
            'prepareFileReferenceDifferences',
            $liveFileReferences,
            $versionFileReferences,
            $useThumbnails
        );

        $this->assertSame($expected, $result);
    }

    /**
     * @param string $idList List of ids
     * @return FileReference[]|ObjectProphecy[]
     */
    protected function getFileReferenceProphecies($idList)
    {
        $fileReferenceProphecies = [];
        $ids = GeneralUtility::trimExplode(',', $idList, true);

        foreach ($ids as $id) {
            $fileReferenceProphecies[$id] = $this->getFileReferenceProphecy($id);
        }

        return $fileReferenceProphecies;
    }

    /**
     * @param int $id
     * @return ObjectProphecy|FileReference
     */
    protected function getFileReferenceProphecy($id)
    {
        if (isset($this->fileReferenceProphecies[$id])) {
            return $this->fileReferenceProphecies[$id];
        }

        $processedFileProphecy = $this->prophesize(ProcessedFile::class);
        $processedFileProphecy->getPublicUrl(Argument::cetera())->willReturn('/tmb/' . $id . '.png');

        $fileProphecy = $this->prophesize(File::class);
        $fileProphecy->process(Argument::cetera())->willReturn($processedFileProphecy->reveal());

        $fileReferenceProphecy = $this->prophesize(FileReference::class);
        $fileReferenceProphecy->getUid()->willReturn($id);
        $fileReferenceProphecy->getOriginalFile()->willReturn($fileProphecy->reveal());
        $fileReferenceProphecy->getPublicUrl(Argument::cetera())->willReturn('/img/' . $id . '.png');

        $this->fileReferenceProphecies[$id] = $fileReferenceProphecy->reveal();
        return $this->fileReferenceProphecies[$id];
    }
}
