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

namespace TYPO3\CMS\Frontend\Tests\Functional\Page;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageInformationFactoryTest extends FunctionalTestCase
{
    public static function getFromCacheSetsConfigRootlineToLocalRootlineDataProvider(): \Generator
    {
        $page1 = ['uid' => 1, 'pid' => 0, 'title' => 'Pre page without template'];
        $page2 = ['uid' => 2, 'pid' => 1, 'title' => 'Root page having template with root flag set by tests'];
        $page88 = ['uid' => 88, 'pid' => 2, 'title' => 'Sub page 1'];
        $page89 = ['uid' => 89, 'pid' => 88, 'title' => 'Sub sub page 1'];
        $page98 = ['uid' => 98, 'pid' => 2, 'title' => 'Sub page 2 having template with root flag'];
        $page99 = ['uid' => 99, 'pid' => 98, 'title' => 'Sub sub page 2'];
        yield 'page with one root template on pid 2' => [
            'pid' => 89,
            'expectedRootLine' => [ 3 => $page89, 2 => $page88, 1 => $page2, 0 => $page1 ],
            'expectedLocalRootLine' => [ 0 => $page2, 1 => $page88, 2 => $page89 ],
        ];
        yield 'page with one root template on pid 2 and one on pid 98' => [
            'pid' => 99,
            'expectedRootLine' => [ 3 => $page99, 2 => $page98, 1 => $page2, 0 => $page1 ],
            'expectedLocalRootLine' => [ 0 => $page98, 1 => $page99 ],
        ];
    }

    #[DataProvider('getFromCacheSetsConfigRootlineToLocalRootlineDataProvider')]
    #[Test]
    public function getFromCacheSetsConfigRootlineToLocalRootline(int $pid, array $expectedRootLine, array $expectedLocalRootLine): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/PageInformationFactoryTestRootlineImport.csv');
        $pageArguments = new PageArguments($pid, '0', []);
        $site = new Site('test', 2, []);
        $request = (new ServerRequest())
            ->withAttribute('routing', $pageArguments)
            ->withAttribute('site', $site);
        $subject = $this->get(PageInformationFactory::class);
        $pageInformation = $subject->create($request);
        $rootLine = $pageInformation->getRootLine();
        self::assertSame(array_keys($expectedRootLine), array_keys($rootLine));
        self::assertArrayIsEqualToArrayOnlyConsideringListOfKeys($expectedRootLine, $rootLine, ['uid', 'pid', 'title']);
        $localRootLine = $pageInformation->getLocalRootLine();
        self::assertSame(array_keys($expectedLocalRootLine), array_keys($localRootLine));
        self::assertArrayIsEqualToArrayOnlyConsideringListOfKeys($expectedLocalRootLine, $localRootLine, ['uid', 'pid', 'title']);
    }
}
