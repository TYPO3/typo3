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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class RelationConstraintEnableFieldsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationConstraintEnableFieldsTestImport.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function parentMatchingOwnConstraintIsReturnedRegardlessOfInvisibleChildren(): void
    {
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalOr(
                $query->like('title', 'Blog%'),
                $query->like('posts.title', 'does not match'),
            )
        );
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        $expected = ['Blog with visible post', 'Blog with hidden post', 'Blog with deleted post', 'Blog without posts'];
        self::assertSame($expected, $this->getTitles($query));
        self::assertSame(4, $query->count());
    }

    #[Test]
    public function invisibleChildrenDoNotMatchRelationConstraint(): void
    {
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->like('posts.title', '%post'));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        self::assertSame(['Blog with visible post'], $this->getTitles($query));
        self::assertSame(1, $query->count());
    }

    #[Test]
    public function parentMatchingOwnConstraintIsReturnedRegardlessOfInvisibleSingleRelation(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalOr(
                $query->like('title', 'Article%'),
                $query->equals('author.lastname', 'does not match'),
            )
        );
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        self::assertSame(
            ['Article with hidden relations', 'Article with deleted relations', 'Article without relations'],
            $this->getTitles($query)
        );
    }

    #[Test]
    public function invisibleSingleRelationDoesNotMatchRelationConstraint(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('author.lastname', 'Person'));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        self::assertSame(['Visible post'], $this->getTitles($query));
    }

    #[Test]
    public function parentMatchingOwnConstraintIsReturnedRegardlessOfInvisibleMmRelations(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalOr(
                $query->like('title', 'Article%'),
                $query->equals('tags.name', 'does not match'),
            )
        );
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        self::assertSame(
            ['Article with hidden relations', 'Article with deleted relations', 'Article without relations'],
            $this->getTitles($query)
        );
        self::assertSame(3, $query->count());
    }

    #[Test]
    public function invisibleMmRelationsDoNotMatchRelationConstraint(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->like('tags.name', '%tag'));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        self::assertSame(['Visible post'], $this->getTitles($query));
    }

    #[Test]
    public function orderingBySingleRelationKeepsRecordsWithInvisibleRelation(): void
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings(['author.firstname' => QueryInterface::ORDER_ASCENDING]);

        self::assertEqualsCanonicalizing(
            ['Visible post', 'Article with hidden relations', 'Article with deleted relations', 'Article without relations'],
            $this->getTitles($query)
        );
        self::assertSame(4, $query->count());
    }

    public static function querySettingsAreRespectedForRelationConstraintDataProvider(): \Generator
    {
        yield 'all enable fields ignored' => [
            'enableFieldsToBeIgnored' => [],
            'includeDeleted' => false,
            'expected' => ['Blog with visible post', 'Blog with hidden post'],
        ];
        yield 'disabled field ignored' => [
            'enableFieldsToBeIgnored' => ['disabled'],
            'includeDeleted' => false,
            'expected' => ['Blog with visible post', 'Blog with hidden post'],
        ];
        yield 'not matching enable field ignored' => [
            'enableFieldsToBeIgnored' => ['starttime'],
            'includeDeleted' => false,
            'expected' => ['Blog with visible post'],
        ];
        yield 'all enable fields ignored and deleted included' => [
            'enableFieldsToBeIgnored' => [],
            'includeDeleted' => true,
            'expected' => ['Blog with visible post', 'Blog with hidden post', 'Blog with deleted post'],
        ];
    }

    #[DataProvider('querySettingsAreRespectedForRelationConstraintDataProvider')]
    #[Test]
    public function querySettingsAreRespectedForRelationConstraint(array $enableFieldsToBeIgnored, bool $includeDeleted, array $expected): void
    {
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()
            ->setRespectStoragePage(false)
            ->setIgnoreEnableFields(true)
            ->setEnableFieldsToBeIgnored($enableFieldsToBeIgnored)
            ->setIncludeDeleted($includeDeleted);
        $query->matching($query->like('posts.title', '%post'));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        self::assertSame($expected, $this->getTitles($query));
    }

    private function getTitles(QueryInterface $query): array
    {
        $titles = [];
        foreach ($query->execute() as $object) {
            $titles[] = $object->getTitle();
        }
        return $titles;
    }
}
