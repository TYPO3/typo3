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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\IncludeTree;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeStaticFileDatabaseInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SysTemplateTreeBuilderTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    public function setUp(): void
    {
        parent::setUp();
        // Register custom comparator to compare IncludeTree with its unserialized(serialized())
        // representation since we don't serialize all properties. Custom comparators are
        // unregistered after test by phpunit runBare() automatically.
        $this->registerComparator(new UnserializedIncludeTreeObjectComparator());
        $this->get(CacheManager::class)->getCache('typoscript')->flush();
        $this->writeSiteConfiguration(
            'website-local',
            [
                'rootPageId' => 1,
                'base' => 'http://localhost/',
                'settings' => [
                    'testConstantFromSite' => 'testValueFromSite',
                    'nestedConfiguration' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ]
        );
    }

    #[Test]
    public function singleRootTemplate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/singleRootTemplate.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
    }

    #[Test]
    public function singleRootTemplateLoadsFromGlobals(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/singleRootTemplate.csv');
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] = 'bar = barValue';
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
    }

    #[Test]
    public function singleRootTemplateLoadConstantFromSite(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/singleRootTemplate.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $siteFinder = $this->get(SiteFinder::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite(
            'constants',
            $sysTemplateRepository->getSysTemplateRowsByRootline($rootline),
            new LossyTokenizer(),
            $siteFinder->getSiteByPageId(1)
        );
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('testValueFromSite', $ast->getChildByName('testConstantFromSite')->getValue());
    }

    #[Test]
    public function twoPagesTwoTemplates(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoPagesTwoTemplates.csv');
        $rootline = [
            [
                'uid' => 2,
                'pid' => 1,
                'is_siteroot' => 0,
            ],
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
    }

    #[Test]
    public function twoPagesTwoTemplatesWithoutClearForConstantsStillLoadsFromGlobals(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoPagesTwoTemplatesNoClearForConstants.csv');
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] = 'globalsConstant = globalsConstantValue';
        $rootline = [
            [
                'uid' => 2,
                'pid' => 1,
                'is_siteroot' => 0,
            ],
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
        self::assertSame('globalsConstantValue', $ast->getChildByName('globalsConstant')->getValue());
    }

    #[Test]
    public function twoPagesTwoTemplatesWithoutClearForSetupStillLoadsFromGlobals(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoPagesTwoTemplatesNoClearForSetup.csv');
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] = 'globalsKey = globalsValue';
        $rootline = [
            [
                'uid' => 2,
                'pid' => 1,
                'is_siteroot' => 0,
            ],
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
        self::assertSame('globalsValue', $ast->getChildByName('globalsKey')->getValue());
    }

    #[Test]
    public function twoPagesTwoTemplatesBothClear(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoPagesTwoTemplatesBothClear.csv');
        $rootline = [
            [
                'uid' => 2,
                'pid' => 1,
                'is_siteroot' => 0,
            ],
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertNull($ast->getChildByName('foo'));
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
    }

    #[Test]
    public function twoTemplatesOnPagePrefersTheOneWithLowerSorting(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoTemplatesOnPage.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertNull($ast->getChildByName('bar'));
    }

    #[Test]
    public function basedOnSimple(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/basedOnSimple.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('loadedByBasedOn', $ast->getChildByName('bar')->getValue());
    }

    #[Test]
    public function basedOnAfterIncludeStatic(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/basedOnAfterIncludeStatic.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('loadedByBasedOn', $ast->getChildByName('bar')->getValue());
    }

    #[Test]
    public function basedOnBeforeIncludeStatic(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/basedOnBeforeIncludeStatic.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        $sysTemplateRepository = $this->get(SysTemplateRepository::class);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $includeTree = $subject->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRepository->getSysTemplateRowsByRootline($rootline), new LossyTokenizer());
        self::assertRootNodeIsSerializable($this->getAst($includeTree));
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('includeStaticTarget', $ast->getChildByName('bar')->getValue());
    }

    public static function duplicateStaticIncludesDataProvider(): array
    {
        return [
            'constants before basedOn' => ['constants', false],
            'constants after basedOn' => ['constants', true],
            'setup before basedOn' => ['setup', false],
            'setup after basedOn' => ['setup', true],
        ];
    }

    #[Test]
    #[DataProvider('duplicateStaticIncludesDataProvider')]
    public function duplicateStaticIncludesAreIncludedOnce(string $type, bool $includeStaticAfterBasedOn): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/singleRootTemplate.csv');
        $firstInclude = 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/SysTemplate';
        $secondInclude = 'EXT:core/Tests/Functional/TypoScript/IncludeTree/Fixtures/AtImport/AbsoluteImport/Scenario1';
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_template')->update(
            'sys_template',
            [
                'include_static_file' => $firstInclude . ', ' . $secondInclude . ', , ' . $firstInclude . ',',
                'includeStaticAfterBasedOn' => (int)$includeStaticAfterBasedOn,
            ],
            ['uid' => 1]
        );
        $rows = $this->get(SysTemplateRepository::class)->getSysTemplateRowsByRootline([['uid' => 1, 'pid' => 0, 'is_siteroot' => 0]]);
        $subject = $this->get(SysTemplateTreeBuilder::class);
        $cache = $this->get(CacheManager::class)->getCache('typoscript');
        self::assertInstanceOf(PhpFrontend::class, $cache);
        foreach ([null, $cache, $cache] as $typoScriptCache) {
            $tree = $subject->getTreeBySysTemplateRowsAndSite($type, $rows, new LossyTokenizer(), null, $typoScriptCache);
            $includes = [];
            foreach (iterator_to_array($tree->getNextChild())[0]->getNextChild() as $child) {
                if ($child instanceof IncludeStaticFileDatabaseInclude) {
                    $includes[] = $child->getName();
                }
            }
            self::assertCount(2, $includes);
            if ($typoScriptCache === null) {
                self::assertSame([$firstInclude, $secondInclude], $includes);
            }
        }
    }

    /**
     * Helper to calculate AST from given includeTree. Asserting AST
     * details is easier than looking up streams on the IncludeTree,
     * and we can test the Traverser and AstVisitor along the way.
     */
    private function getAst(RootInclude $rootInclude): RootNode
    {
        $astBuilderVisitor = $this->get(IncludeTreeAstBuilderVisitor::class);
        $traverser = new IncludeTreeTraverser();
        $traverser->traverse($rootInclude, [$astBuilderVisitor]);
        return $astBuilderVisitor->getAst();
    }

    /**
     * The TYPO3 caching framework serializes cached objects.
     * A successful serialize/unserialize round-trip proves the AST is cacheable.
     */
    private static function assertRootNodeIsSerializable(RootNode $ast): void
    {
        /* @phpstan-ignore unserialize.allowedClasses.insecure (Serialization within testing context does no harm) */
        self::assertEquals($ast, unserialize(serialize($ast), ['allowed_classes' => true]));
    }
}
