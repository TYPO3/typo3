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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;

final class MmRelationEnableFieldsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MmRelationEnableFieldsTestImport.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function hiddenMmRelatedRecordIsNotReturnedWithEnableFieldsRespected(): void
    {
        $query = $this->createMmRelationQueryForTagsOfPost(1);

        self::assertSame(['Tag 1'], $this->getTagNames($query));
    }

    #[Test]
    public function hiddenMmRelatedRecordIsReturnedWhenEnableFieldsAreIgnored(): void
    {
        $query = $this->createMmRelationQueryForTagsOfPost(1);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        self::assertSame(['Tag 1', 'Tag 2 hidden'], $this->getTagNames($query));
    }

    #[Test]
    public function hiddenMmRelatedRecordIsReturnedWhenOnlyTheDisabledFieldIsIgnored(): void
    {
        $query = $this->createMmRelationQueryForTagsOfPost(1);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['disabled']);

        self::assertSame(['Tag 1', 'Tag 2 hidden'], $this->getTagNames($query));
    }

    /**
     * Builds the query the same way DataMapper does for a MM relation:
     * the relation table is joined with the child table.
     */
    private function createMmRelationQueryForTagsOfPost(int $postUid): QueryInterface
    {
        $columnMap = $this->get(DataMapper::class)->getDataMap(Post::class)->getColumnMap('tags');
        $qomFactory = $this->get(QueryObjectModelFactory::class);
        $query = $this->get(PersistenceManagerInterface::class)->createQueryForType(Tag::class);
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setSource($qomFactory->join(
            $qomFactory->selector(null, $columnMap->relationTableName),
            $qomFactory->selector(Tag::class, $columnMap->childTableName),
            Query::JCR_JOIN_TYPE_INNER,
            $qomFactory->equiJoinCondition($columnMap->relationTableName, $columnMap->childKeyFieldName, $columnMap->childTableName, 'uid')
        ));
        $query->matching($query->equals($columnMap->parentKeyFieldName, $postUid));
        $query->setOrderings(['sorting' => QueryInterface::ORDER_ASCENDING]);
        return $query;
    }

    private function getTagNames(QueryInterface $query): array
    {
        $names = [];
        foreach ($query->execute() as $tag) {
            $names[] = $tag->getName();
        }
        return $names;
    }
}
