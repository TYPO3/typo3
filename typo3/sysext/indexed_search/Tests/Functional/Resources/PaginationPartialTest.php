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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Resources;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\FluidViewFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PaginationPartialTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    #[Test]
    public function previousAndNextItemsCarryTheirOwnCssClass(): void
    {
        $paginator = new ArrayPaginator(range(1, 30), 2, 10);
        /** @var FluidViewAdapter $view */
        $view = $this->get(FluidViewFactory::class)->create(
            new ViewFactoryData(partialRootPaths: [__DIR__ . '/../../../Resources/Private/Partials'])
        );

        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource(
            '<f:render partial="Pagination" arguments="{pagination: pagination, searchParams: searchParams, freeIndexUid: 0}" />'
        );
        $result = $view->assignMultiple([
            'pagination' => new SimplePagination($paginator),
            'searchParams' => ['pointer' => 1],
        ])->render();

        self::assertSame(1, substr_count($result, 'tx-indexedsearch-browselist-prev'));
        self::assertSame(1, substr_count($result, 'tx-indexedsearch-browselist-next'));
    }
}
