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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Schema\VisibleSchemaFieldsCollector;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Controller\Remote\RemoteServer;
use TYPO3\CMS\Workspaces\Service\GridDataService;
use TYPO3\CMS\Workspaces\Service\HistoryService;
use TYPO3\CMS\Workspaces\Service\IntegrityService;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RemoteServerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @var array<non-empty-string, FileReference&MockObject>
     */
    protected array $fileReferenceMocks;

    public static function prepareFileReferenceDifferencesAreCorrectDataProvider(): array
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
     * @param array|null $expected
     */
    #[DataProvider('prepareFileReferenceDifferencesAreCorrectDataProvider')]
    #[Test]
    public function prepareFileReferenceDifferencesAreCorrect(string $fileFileReferenceList, string $versionFileReferenceList, bool $useThumbnails, ?array $expected = null): void
    {
        $liveFileReferences = $this->getFileReferenceMocks($fileFileReferenceList);
        $versionFileReferences = $this->getFileReferenceMocks($versionFileReferenceList);
        $subject = new RemoteServer(
            $this->createMock(GridDataService::class),
            new StagesService(),
            new WorkspaceService($this->createMock(TcaSchemaFactory::class)),
            new NoopEventDispatcher(),
            $this->createMock(FlexFormValueFormatter::class),
            new DiffUtility(),
            $this->createMock(IconFactory::class),
            $this->createMock(Avatar::class),
            new ConnectionPool(),
            $this->createMock(SearchableSchemaFieldsCollector::class),
            $this->createMock(VisibleSchemaFieldsCollector::class),
            new IntegrityService($this->createMock(TcaSchemaFactory::class)),
            $this->createMock(TcaSchemaFactory::class),
            $this->createMock(HistoryService::class),
            new NullLogger()
        );
        $subjectReflection = new \ReflectionObject($subject);
        $result = $subjectReflection->getMethod('prepareFileReferenceDifferences')
            ->invoke($subject, $liveFileReferences, $versionFileReferences, $useThumbnails);
        self::assertSame($expected, $result);
    }

    /**
     * @param string $idList List of ids
     * @return array<non-empty-string, FileReference&MockObject>
     */
    protected function getFileReferenceMocks(string $idList): array
    {
        $fileReferenceMocks = [];
        $ids = GeneralUtility::trimExplode(',', $idList, true);

        foreach ($ids as $id) {
            $fileReferenceMocks[$id] = $this->getFileReferenceMock($id);
        }

        return $fileReferenceMocks;
    }

    /**
     * @param non-empty-string $id
     */
    protected function getFileReferenceMock(string $id): FileReference&MockObject
    {
        if (isset($this->fileReferenceMocks[$id])) {
            return $this->fileReferenceMocks[$id];
        }

        $processedFileMock = $this->getMockBuilder(ProcessedFile::class)->disableOriginalConstructor()->getMock();
        $processedFileMock->expects(self::any())->method('getPublicUrl')->willReturn('/tmb/' . $id . '.png');

        $fileMock = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $fileMock->expects(self::any())->method('process')->willReturn($processedFileMock);

        $fileReferenceMock = $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
        $fileReferenceMock->method('getUid')->willReturn($id);
        $fileReferenceMock->method('getOriginalFile')->willReturn($fileMock);
        $fileReferenceMock->expects(self::any())->method('getPublicUrl')->willReturn('/img/' . $id . '.png');

        $this->fileReferenceMocks[$id] = $fileReferenceMock;
        return $this->fileReferenceMocks[$id];
    }

    #[Test]
    public function resolveFileReferencesReturnsEmptyResultForNoReferencesAvailable(): void
    {
        $tableName = 'table_a';
        $fieldName = 'field_a';
        $elementData = [
            $fieldName => 'foo',
            'uid' => 42,
        ];
        $relationHandlerMock = $this->createMock(RelationHandler::class);
        $relationHandlerMock->expects(self::once())->method('initializeForField')->with(
            $tableName,
            ['type' => 'file', 'foreign_table' => 'sys_file_reference'],
            $elementData,
            'foo'
        );
        $relationHandlerMock->expects(self::once())->method('processDeletePlaceholder');
        $relationHandlerMock->tableArray = ['sys_file_reference' => []];
        GeneralUtility::addInstance(RelationHandler::class, $relationHandlerMock);

        $fileFieldType = new FileFieldType($fieldName, [
            'type' => 'file',
            'foreign_table' => 'sys_file_reference',
        ], []);

        $remoteServerMock = $this->getAccessibleMock(RemoteServer::class, null, [], '', false);
        self::assertEmpty($remoteServerMock->_call('resolveFileReferences', $tableName, $fieldName, $fileFieldType, $elementData));
    }
}
