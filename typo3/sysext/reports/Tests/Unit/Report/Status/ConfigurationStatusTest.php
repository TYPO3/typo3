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

namespace TYPO3\CMS\Reports\Tests\Unit\Report\Status;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Report\Status\ConfigurationStatus;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class ConfigurationStatusTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function referenceIndexStatusChecksForOneRow(): void
    {
        $GLOBALS['LANG'] = self::createStub(LanguageService::class);

        $result = self::createStub(Result::class);
        $result->method('fetchOne')->willReturn(false);

        $queryBuilder = self::createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())->method('select')->with('hash')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setMaxResults')->with(1)->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);

        $connectionPool = self::createStub(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool);
        GeneralUtility::setSingletonInstance(Registry::class, self::createStub(Registry::class));
        GeneralUtility::setSingletonInstance(UriBuilder::class, self::createStub(UriBuilder::class));

        $subject = new ConfigurationStatus();

        $method = new \ReflectionMethod($subject, 'getReferenceIndexStatus');
        $method->invoke($subject);
    }
}
