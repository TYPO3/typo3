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

namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing collection of backend layout data providers.
 */
final class DataProviderCollectionTest extends UnitTestCase
{
    #[Test]
    public function constructorRecognizesInvalidIdentifier(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597629);

        $identifier = StringUtility::getUniqueId('identifier__');
        $dataProviderMock = $this->createMock(DataProviderInterface::class);
        $dataProviderMock->method('getIdentifier')->willReturn($identifier);

        new DataProviderCollection([$dataProviderMock]);
    }

    #[Test]
    public function constructorRecognizesDuplicateIdentifier(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1762361129);

        $identifier = 'duplicate_provider';
        $dataProviderMock1 = $this->createMock(DataProviderInterface::class);
        $dataProviderMock1->method('getIdentifier')->willReturn($identifier);
        $dataProviderMock2 = $this->createMock(DataProviderInterface::class);
        $dataProviderMock2->method('getIdentifier')->willReturn($identifier);

        new DataProviderCollection([$dataProviderMock1, $dataProviderMock2]);
    }

    #[Test]
    public function constructorRecognizesInvalidInterface(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1381269811);

        $dataProviderMock = $this->createMock(\stdClass::class);

        /* @phpstan-ignore argument.type */
        new DataProviderCollection([$dataProviderMock]);
    }

    #[Test]
    public function defaultBackendLayoutIsFound(): void
    {
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier');

        $backendLayoutMock = $this->createMock(BackendLayout::class);
        $backendLayoutMock->method('getIdentifier')->willReturn($backendLayoutIdentifier);
        $dataProviderMock = $this->createMock(DefaultDataProvider::class);
        $dataProviderMock->method('getIdentifier')->willReturn('default');
        $dataProviderMock->expects($this->once())->method('getBackendLayout')->willReturn($backendLayoutMock);

        $subject = new DataProviderCollection([$dataProviderMock]);
        $providedBackendLayout = $subject->getBackendLayout($backendLayoutIdentifier, 123);

        self::assertNotNull($providedBackendLayout);
        self::assertEquals($backendLayoutIdentifier, $providedBackendLayout->getIdentifier());
    }

    #[Test]
    public function providedBackendLayoutIsFound(): void
    {
        $dataProviderIdentifier = StringUtility::getUniqueId('custom');
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier');

        $backendLayoutMock = $this->createMock(BackendLayout::class);
        $backendLayoutMock->method('getIdentifier')->willReturn($backendLayoutIdentifier);
        $dataProviderMock = $this->createMock(DefaultDataProvider::class);
        $dataProviderMock->method('getIdentifier')->willReturn($dataProviderIdentifier);
        $dataProviderMock->expects($this->once())->method('getBackendLayout')->willReturn($backendLayoutMock);

        $subject = new DataProviderCollection([$dataProviderMock]);
        $providedBackendLayout = $subject->getBackendLayout($dataProviderIdentifier . '__' . $backendLayoutIdentifier, 123);

        self::assertNotNull($providedBackendLayout);
        self::assertEquals($backendLayoutIdentifier, $providedBackendLayout->getIdentifier());
    }
}
