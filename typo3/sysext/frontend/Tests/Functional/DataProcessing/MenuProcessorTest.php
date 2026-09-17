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

namespace TYPO3\CMS\Frontend\Tests\Functional\DataProcessing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\RegisterStack;
use TYPO3\CMS\Frontend\DataProcessing\MenuProcessor;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MenuProcessorTest extends FunctionalTestCase
{
    public static function menuDataProvider(): array
    {
        return [
            'basicMenuReturnsExpectedStructure' => [
                'configuration' => [],
                'expected' => [
                    [
                        'title' => 'About',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 0,
                    ],
                    [
                        'title' => 'Our Services',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 1,
                    ],
                ],
            ],
            'levelsLimitsMenuDepth' => [
                'configuration' => [
                    'levels' => 2,
                ],
                'expected' => [
                    [
                        'title' => 'About',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 0,
                    ],
                    [
                        'title' => 'Our Services',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 1,
                        'children' => [
                            [
                                'title' => 'Consulting',
                                'link' => '',
                                'target' => '',
                                'active' => 0,
                                'current' => 0,
                                'spacer' => 0,
                                'hasSubpages' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'spacerPagesAreIncludedWhenConfigured' => [
                'configuration' => [
                    'includeSpacer' => 1,
                ],
                'expected' => [
                    [
                        'title' => 'About',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 0,
                    ],
                    [
                        'title' => 'Our Services',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 1,
                    ],
                    [
                        'title' => 'Spacer',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 1,
                        'hasSubpages' => 0,
                    ],
                ],
            ],
            'titleFieldRespectsFallbackChain' => [
                'configuration' => [
                    'titleField' => 'subtitle // nav_title // title',
                    'includeNotInMenu' => true,
                ],
                'expected' => [
                    [
                        'title' => 'About Company',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 0,
                    ],
                    [
                        'title' => 'Our Services',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 1,
                    ],
                    [
                        'title' => 'Contact',
                        'link' => '',
                        'target' => '',
                        'active' => 0,
                        'current' => 0,
                        'spacer' => 0,
                        'hasSubpages' => 0,
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('menuDataProvider')]
    public function menuProcessingReturnsExpectedStructure(array $configuration, array $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/MenuProcessor.csv');
        $site = new Site('main', 1, []);
        $page = ['uid' => 1, 'title' => 'Home'];
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord($page);
        $pageInformation->setLocalRootLine([
            0 => $page,
        ]);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = new ServerRequest()
            ->withAttribute('site', $site)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.register.stack', new RegisterStack())
            ->withAttribute('frontend.typoscript', $typoScript);
        $cObj = $this->get(ContentObjectRenderer::class);
        $cObj->setRequest($request);

        $subject = $this->get(MenuProcessor::class);
        $result = $subject->process($cObj, [], $configuration, []);

        // Remove 'data' from all menu items: It contains the full page
        // records, which are not subject of this test.
        foreach ($result['menu'] as &$menuItem) {
            unset($menuItem['data']);
            if (isset($menuItem['children'])) {
                foreach ($menuItem['children'] as &$childItem) {
                    unset($childItem['data']);
                }
            }
        }
        unset($menuItem, $childItem);

        self::assertSame($expected, $result['menu']);
    }

    /**
     * A mount point in overlay mode is replaced by the mounted page, whose pid points
     * somewhere else. The parent of such a mount point must still be recognized as having
     * subpages, and its submenu must be built.
     */
    #[Test]
    public function mountPointsAreRecognizedAsSubpages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/MenuProcessorMountPoints.csv');

        $result = $this->processMenu(['levels' => 2, 'includeSpacer' => 1]);

        self::assertSame(
            [
                ['title' => 'Normal Parent', 'hasSubpages' => 1, 'children' => ['Normal Child']],
                ['title' => 'Mount Parent', 'hasSubpages' => 1, 'children' => ['Mounted Target']],
                ['title' => 'Shortcut Parent', 'hasSubpages' => 1, 'children' => ['Shortcut Child']],
                ['title' => 'Link Parent', 'hasSubpages' => 1, 'children' => ['Link Child']],
                ['title' => 'Spacer Parent', 'hasSubpages' => 1, 'children' => ['Mounted Target']],
            ],
            $this->reduceMenu($result['menu'])
        );
    }

    private function processMenu(array $configuration): array
    {
        $site = new Site('main', 1, []);
        $page = ['uid' => 1, 'title' => 'Home'];
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord($page);
        $pageInformation->setLocalRootLine([
            0 => $page,
        ]);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = new ServerRequest()
            ->withAttribute('site', $site)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.register.stack', new RegisterStack())
            ->withAttribute('frontend.typoscript', $typoScript);
        $cObj = $this->get(ContentObjectRenderer::class);
        $cObj->setRequest($request);

        return $this->get(MenuProcessor::class)->process($cObj, [], $configuration, []);
    }

    private function reduceMenu(array $menu): array
    {
        return array_map(
            static fn(array $item) => [
                'title' => $item['title'],
                'hasSubpages' => $item['hasSubpages'],
                'children' => array_column($item['children'] ?? [], 'title'),
            ],
            $menu
        );
    }
}
