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

namespace TYPO3\CMS\Backend\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CategoryPermissionsAspectTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryPermissions.csv');
        $request = new ServerRequest('https://localhost/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    public static function categoryTreeRespectsCategoryMountsWithStartingPointDataProvider(): \Generator
    {
        yield 'mount on starting point shows its children' => [
            1,
            '1',
            '1',
            [2, 3],
        ];
        yield 'mount on child of starting point shows only that child' => [
            2,
            '1',
            '1',
            [2],
        ];
        yield 'mount outside of starting point shows nothing' => [
            5,
            '1',
            '1',
            [],
        ];
        yield 'starting point below mount shows its children' => [
            1,
            '2',
            '2',
            [4],
        ];
        yield 'several starting points below mount show all starting points' => [
            1,
            '2,3',
            '0',
            [2, 3],
        ];
        yield 'no starting point shows only the mount' => [
            1,
            null,
            '0',
            [1],
        ];
    }

    #[DataProvider('categoryTreeRespectsCategoryMountsWithStartingPointDataProvider')]
    #[Test]
    public function categoryTreeRespectsCategoryMountsWithStartingPoint(int $backendUserUid, ?string $startingPoints, string $expectedRootUid, array $expectedChildUids): void
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $tcaConfiguration = [
            'type' => 'category',
            'foreign_table' => 'sys_category',
            'treeConfig' => [
                'parentField' => 'parent',
            ],
        ];
        if ($startingPoints !== null) {
            $tcaConfiguration['treeConfig']['startingPoints'] = $startingPoints;
        }
        $dataProvider = TreeDataProviderFactory::getDataProvider($tcaConfiguration, 'pages', 'categories', ['uid' => 1]);
        $dataProvider->setItemWhiteList([1, 2, 3, 4, 5]);
        $dataProvider->initializeTreeData();

        $root = $dataProvider->getRoot();
        $childUids = [];
        if ($root->hasChildNodes()) {
            foreach ($root->getChildNodes() as $childNode) {
                $childUids[] = (int)$childNode->getId();
            }
        }
        sort($childUids);

        self::assertSame($expectedRootUid, (string)$root->getId());
        self::assertSame($expectedChildUids, $childUids);
    }
}
