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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extensionmanager\Domain\ExtensionCatalogueInterface;
use TYPO3\CMS\Extensionmanager\Domain\FallbackExtensionCatalogue;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\ApiRequestFailedException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class FallbackExtensionCatalogueTest extends UnitTestCase
{
    #[Test]
    public function theRemoteAnswersWhileItCan(): void
    {
        $remote = $this->createMock(ExtensionCatalogueInterface::class);
        $remote->expects($this->once())->method('findAll')->with(20, 10)->willReturn([$this->extension('from_remote')]);
        $local = $this->createMock(ExtensionCatalogueInterface::class);
        $local->expects($this->never())->method('findAll');

        $subject = new FallbackExtensionCatalogue($remote, $local, new NullLogger(), $this->extensionConfiguration(false));

        self::assertSame('from_remote', $subject->findAll(20, 10)[0]->extensionKey);
    }

    #[Test]
    public function theImportedListAnswersWhenTheRemoteCannot(): void
    {
        $remote = $this->createMock(ExtensionCatalogueInterface::class);
        $remote->method('findAll')->willThrowException(new ApiRequestFailedException('offline', 1789035174));
        $local = $this->createMock(ExtensionCatalogueInterface::class);
        $local->expects($this->once())->method('findAll')->with(20, 10)->willReturn([$this->extension('from_import')]);

        $subject = new FallbackExtensionCatalogue($remote, $local, new NullLogger(), $this->extensionConfiguration(false));

        self::assertSame('from_import', $subject->findAll(20, 10)[0]->extensionKey);
    }

    /**
     * An empty page is an answer. Falling back on it would hide that the remote
     * knows nothing, and would mix two catalogues within one listing.
     */
    #[Test]
    public function anEmptyAnswerFromTheRemoteIsNotAFailure(): void
    {
        $remote = $this->createMock(ExtensionCatalogueInterface::class);
        $remote->method('findByTitleOrAuthorNameOrExtensionKey')->willReturn([]);
        $remote->method('countByTitleOrAuthorNameOrExtensionKey')->willReturn(0);
        $local = $this->createMock(ExtensionCatalogueInterface::class);
        $local->expects($this->never())->method('findByTitleOrAuthorNameOrExtensionKey');
        $local->expects($this->never())->method('countByTitleOrAuthorNameOrExtensionKey');

        $subject = new FallbackExtensionCatalogue($remote, $local, new NullLogger(), $this->extensionConfiguration(false));

        self::assertSame([], $subject->findByTitleOrAuthorNameOrExtensionKey('nothing matches', 0, 10));
        self::assertSame(0, $subject->countByTitleOrAuthorNameOrExtensionKey('nothing matches'));
    }

    /**
     * Anything the remote implementation itself throws is a bug, not a reason to
     * silently serve stale data.
     */
    #[Test]
    public function onlyAFailedRequestFallsBack(): void
    {
        $remote = $this->createMock(ExtensionCatalogueInterface::class);
        $remote->method('countAll')->willThrowException(new \RuntimeException('broken', 1789035177));
        $local = $this->createMock(ExtensionCatalogueInterface::class);
        $local->expects($this->never())->method('countAll');

        $subject = new FallbackExtensionCatalogue($remote, $local, new NullLogger(), $this->extensionConfiguration(false));

        $this->expectException(\RuntimeException::class);
        $subject->countAll();
    }

    #[Test]
    public function everyQuestionOfTheContractFallsBack(): void
    {
        $remote = $this->createMock(ExtensionCatalogueInterface::class);
        $local = $this->createMock(ExtensionCatalogueInterface::class);
        foreach ($this->contractMethods() as $method) {
            $remote->method($method)->willThrowException(new ApiRequestFailedException('offline', 1789035174));
            $local->expects($this->once())->method($method);
        }

        $subject = new FallbackExtensionCatalogue($remote, $local, new NullLogger(), $this->extensionConfiguration(false));

        $subject->countAll();
        $subject->findAll(0, 10);
        $subject->findByTitleOrAuthorNameOrExtensionKey('news', 0, 10);
        $subject->countByTitleOrAuthorNameOrExtensionKey('news');
        $subject->findByExtensionKeyOrderedByVersion('news');
        $subject->findOneByCurrentVersionByExtensionKey('news');
        $subject->findAllOfficialDistributions();
        $subject->findAllCommunityDistributions();
    }

    #[Test]
    public function inOfflineModeTheRemoteIsNeverAsked(): void
    {
        $remote = $this->createMock(ExtensionCatalogueInterface::class);
        $local = $this->createMock(ExtensionCatalogueInterface::class);
        foreach ($this->contractMethods() as $method) {
            $remote->expects($this->never())->method($method);
            $local->expects($this->once())->method($method);
        }

        $subject = new FallbackExtensionCatalogue($remote, $local, new NullLogger(), $this->extensionConfiguration(true));

        $subject->countAll();
        $subject->findAll(0, 10);
        $subject->findByTitleOrAuthorNameOrExtensionKey('news', 0, 10);
        $subject->countByTitleOrAuthorNameOrExtensionKey('news');
        $subject->findByExtensionKeyOrderedByVersion('news');
        $subject->findOneByCurrentVersionByExtensionKey('news');
        $subject->findAllOfficialDistributions();
        $subject->findAllCommunityDistributions();
    }

    private function extensionConfiguration(bool $offlineMode): ExtensionConfiguration
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($offlineMode ? '1' : '0');
        return $extensionConfiguration;
    }

    /**
     * @return string[]
     */
    private function contractMethods(): array
    {
        $contract = new \ReflectionClass(ExtensionCatalogueInterface::class);
        return array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            $contract->getMethods()
        );
    }

    private function extension(string $extensionKey): Extension
    {
        $extension = new Extension();
        $extension->extensionKey = $extensionKey;
        return $extension;
    }
}
