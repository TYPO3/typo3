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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['frontend'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileRepository.csv');
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/')
            ->withAttribute('applicationType', 1);
    }

    public static function workspaceDataProvider(): array
    {
        return [
            'live context, omitted workspace' => [0, null, 0, [2, 1]],
            'live context, explicit live workspace' => [0, 0, 0, [2, 1]],
            'live context, explicit workspace' => [0, 1, 3, [2, 1]],
            'workspace context, omitted workspace' => [1, null, 3, [1, 2]],
            'workspace context, explicit live workspace' => [1, 0, 3, [1, 2]],
            'workspace context, explicit workspace' => [1, 1, 3, [1, 2]],
        ];
    }

    #[Test]
    #[DataProvider('workspaceDataProvider')]
    public function findByRelationOnlySkipsReloadingReferencesInLiveWorkspace(
        int $contextWorkspaceId,
        ?int $workspaceId,
        int $expectedReloads,
        array $expectedReferenceUids,
    ): void {
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect($contextWorkspaceId));
        $factory = $this->getMockBuilder(ResourceFactory::class)
            ->setConstructorArgs([
                $this->get(StorageRepository::class),
                $this->get('cache.runtime'),
                $this->get(FileIndexRepository::class),
                $this->get(LinkService::class),
                $this->get(PageRepository::class),
            ])
            ->onlyMethods(['getFileReferenceData'])
            ->getMock();
        $factory->expects($this->exactly($expectedReloads))
            ->method('getFileReferenceData')
            ->willReturnCallback(fn(int $uid): ?array => $this->get(PageRepository::class)->checkRecord('sys_file_reference', $uid));
        $subject = new FileRepository($factory, $this->get(TcaSchemaFactory::class));

        self::assertSame($expectedReferenceUids, $this->getReferenceUids($subject->findByRelation('tt_content', 'image', 10, $workspaceId)));
    }

    #[Test]
    public function findByRelationFiltersReferencesAndPreservesSortingAndProperties(): void
    {
        $references = array_values($this->get(FileRepository::class)->findByRelation('tt_content', 'image', 10));

        self::assertSame([2, 1], $this->getReferenceUids($references));
        self::assertSame('Second reference', $references[0]->getReferenceProperty('title'));
        self::assertSame('First reference', $references[1]->getReferenceProperty('title'));
        self::assertSame(1, $references[0]->getOriginalFile()->getUid());
    }

    #[Test]
    public function findByRelationReturnsTranslatedReferencesForLocalizedParent(): void
    {
        $references = array_values($this->get(FileRepository::class)->findByRelation('tt_content', 'image', 11));

        self::assertSame([5], $this->getReferenceUids($references));
        self::assertSame('Translated reference', $references[0]->getReferenceProperty('title'));
        self::assertSame(1, $references[0]->getReferenceProperty('sys_language_uid'));
    }

    #[Test]
    public function findByRelationReusesCachedReferenceObject(): void
    {
        $reference = $this->get(ResourceFactory::class)->getFileReferenceObject(2);
        $references = array_values($this->get(FileRepository::class)->findByRelation('tt_content', 'image', 10));

        self::assertSame($reference, $references[0]);
    }

    #[Test]
    public function findByRelationReturnsEmptyArrayForMissingRelation(): void
    {
        self::assertSame([], $this->get(FileRepository::class)->findByRelation('tt_content', 'image', 999));
    }

    #[Test]
    public function findByRelationKeepsHiddenReferencesInBackend(): void
    {
        $this->setUpBackendUser(1);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/typo3/')
            ->withAttribute('applicationType', 2);

        $references = $this->get(FileRepository::class)->findByRelation('tt_content', 'image', 10);

        self::assertSame([3, 2, 1], $this->getReferenceUids($references));
    }

    #[Test]
    public function findByRelationAppliesWorkspaceOverlaysAndSortsOverlaidReferences(): void
    {
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $references = array_values($this->get(FileRepository::class)->findByRelation('tt_content', 'image', 10));

        self::assertSame([1, 2], $this->getReferenceUids($references));
        self::assertSame('Workspace first', $references[0]->getReferenceProperty('title'));
        self::assertSame('Workspace second', $references[1]->getReferenceProperty('title'));
        self::assertSame(101, $references[0]->getReferenceProperty('_ORIG_uid'));
    }

    public static function inaccessibleWorkspaceReferencesDataProvider(): array
    {
        return [
            'deleted in workspace' => [20],
            'hidden in workspace' => [21],
        ];
    }

    #[Test]
    #[DataProvider('inaccessibleWorkspaceReferencesDataProvider')]
    public function findByRelationOmitsInaccessibleWorkspaceReferences(int $parentUid): void
    {
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        self::assertSame([], $this->get(FileRepository::class)->findByRelation('tt_content', 'image', $parentUid));
    }

    private function getReferenceUids(array $references): array
    {
        return array_values(array_map(static fn(FileReference $reference): int => $reference->getUid(), $references));
    }
}
