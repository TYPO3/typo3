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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Storage;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\RegistryEntryRepository;

/**
 * @todo: $GLOBALS['TYPO3_REQUEST'] is used throughout this test setup since
 *        Typo3DbQueryParser determines FE / BE mode by accessing it.
 */
final class Typo3DbQueryParserTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    public function tearDown(): void
    {
        parent::tearDown();
        // We need to re-set the TcaSchemaFactory state
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($GLOBALS['TCA'], true);
    }

    #[Test]
    public function convertQueryToDoctrineQueryBuilderDoesNotAddAndWhereWithEmptyConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        self::assertCount(4, $compositeExpression);
    }

    #[Test]
    public function convertQueryToDoctrineQueryBuilderThrowsExceptionOnNotImplementedConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);

        $query = $this->createMock(QueryInterface::class);
        $query->method('getSource')->willReturn($this->createMock(SourceInterface::class));
        $query->method('getOrderings')->willReturn([]);
        $query->method('getStatement')->willReturn(null);
        // Test part: getConstraint returns not implemented object
        $query->method('getConstraint')->willReturn($this->createMock(ConstraintInterface::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1476199898);

        $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);
    }

    #[Test]
    public function convertQueryToDoctrineQueryBuilderAddsSimpleAndWhere(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->matching($query->equals('uid', 1));

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        // 5: custom constraint uid = 1
        self::assertCount(5, $compositeExpression);
        self::assertStringContainsString('uid', (string)$compositeExpression);
    }

    #[Test]
    public function convertQueryToDoctrineQueryBuilderAddsNotConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->matching($query->logicalNot($query->equals('uid', 1)));

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        // 5: custom constraint NOT(uid = 1)
        self::assertCount(5, $compositeExpression);
        self::assertMatchesRegularExpression('/NOT\(.*uid/', (string)$compositeExpression);
    }

    #[Test]
    public function convertQueryToDoctrineQueryBuilderAddsAndConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $andConstraint = $query->logicalAnd(
            $query->equals('title', 'Heinz'),
            $query->equals('description', 'Heinz'),
        );
        $query->matching($andConstraint);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        // 1-2: custom AND constraint title = 'Heinz' AND description = 'Heinz'
        // 3-6: language constraint, pid constraint, workspace constraint, enable fields constraint.
        self::assertCount(6, $compositeExpression);
        self::assertMatchesRegularExpression('/title.* AND .*description/', (string)$compositeExpression);
    }

    #[Test]
    public function convertQueryToDoctrineQueryBuilderAddsOrConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $andConstraint = $query->logicalOr(
            $query->equals('title', 'Heinz'),
            $query->equals('description', 'Heinz'),
        );
        $query->matching($andConstraint);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        // 5: custom AND constraint title = 'Heinz' OR description = 'Heinz'
        self::assertCount(5, $compositeExpression);
        self::assertMatchesRegularExpression('/title.* OR .*description/', (string)$compositeExpression);
    }

    #[Test]
    public function languageStatementWorksForDefaultLanguage(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/sys_language_uid. IN \(0, -1\)/', (string)$compositeExpression);
    }

    #[Test]
    public function languageStatementWorksForNonDefaultLanguage(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1, overlayType: LanguageAspect::OVERLAYS_OFF));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/sys_language_uid. IN \(1, -1\)/', (string)$compositeExpression);
    }

    #[Test]
    public function languageStatementWorksInBackendContext(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1, overlayType: LanguageAspect::OVERLAYS_OFF));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/sys_language_uid. IN \(1, -1\)/', (string)$compositeExpression);
    }

    #[Test]
    public function addGetLanguageStatementWorksForForeignLanguageWithSubselectionWithoutDeleteStatementReturned(): void
    {
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl']['delete'] = null;
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/l18n_parent. IN \(SELECT/', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function addGetLanguageStatementWorksForForeignLanguageWithSubselectionTakesDeleteStatementIntoAccountIfNecessary(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(id: 1, contentId: 1, overlayType: LanguageAspect::OVERLAYS_MIXED));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/l18n_parent. IN \(SELECT/', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function addGetLanguageStatementWorksInBackendContextWithSubselectionTakesDeleteStatementIntoAccountIfNecessary(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(id: 1, contentId: 1, overlayType: LanguageAspect::OVERLAYS_MIXED));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/l18n_parent. IN \(SELECT/', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function orderStatementGenerationWorks(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setOrderings(['title' => QueryInterface::ORDER_DESCENDING]);
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $orderBy = $queryBuilder->getOrderBy();
        self::assertMatchesRegularExpression('/title. DESC/', $orderBy[0]);
    }

    #[Test]
    public function orderStatementGenerationThrowsExceptionOnUnsupportedOrder(): void
    {
        $this->expectException(UnsupportedOrderException::class);
        $this->expectExceptionCode(1242816074);

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setOrderings(['title' => 'FOO']);
        $query->setQuerySettings($querySettings);

        $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);
    }

    #[Test]
    public function orderStatementGenerationWorksWithMultipleOrderings(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setOrderings(
            ['title' => QueryInterface::ORDER_DESCENDING, 'description' => QueryInterface::ORDER_ASCENDING],
        );
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $orderBy = $queryBuilder->getOrderBy();
        self::assertMatchesRegularExpression('/title. DESC/', $orderBy[0]);
        self::assertMatchesRegularExpression('/description. ASC/', $orderBy[1]);
    }

    #[Test]
    public function expressionIsOmittedForIgnoreEnableFieldsAreAndDoNotIncludeDeletedInBackendContext(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setIncludeDeleted(true);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function expressionIsGeneratedForIgnoreEnableFieldsAndDoNotIncludeDeletedInBackendContext(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setIncludeDeleted(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function expressionIsGeneratedForDoNotIgnoreEnableFieldsAndIncludeDeletedInBackendContext(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(false);
        $querySettings->setIncludeDeleted(true);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function expressionIsGeneratedForDoNotIgnoreEnableFieldsAndDoNotIncludeDeletedInBackendContext(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(false);
        $querySettings->setIncludeDeleted(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function expressionIsOmittedForIgnoreEnableFieldsAreAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setIncludeDeleted(true);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function expressionIsGeneratedForIgnoreEnableFieldsAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setIncludeDeleted(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function expressionIsGeneratedForIgnoreOnlyFeGroupAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setEnableFieldsToBeIgnored(['fe_group']);
        $querySettings->setIncludeDeleted(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringNotContainsString('fe_group', (string)$compositeExpression);
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function expressionIsGeneratedForDoNotIgnoreEnableFieldsAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(false);
        $querySettings->setIncludeDeleted(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringContainsString('fe_group', (string)$compositeExpression);
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function respectEnableFieldsSettingGeneratesCorrectStatementWithOnlyEndTimeInFrontendContext(): void
    {
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl']['enablecolumns']['endtime'] = 'endtime_column';
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $dateAspect = new DateTimeAspect(new \DateTimeImmutable('3.1.2016'));
        $context = new Context();
        $context->setAspect('date', $dateAspect);
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/endtime_column. = 0\) OR \(.*endtime_column. > 1451779200/', (string)$compositeExpression);
    }

    #[Test]
    public function respectEnableFieldsSettingGeneratesCorrectStatementWithOnlyEndTimeInBackendContext(): void
    {
        // simulate time for backend enable fields
        $GLOBALS['SIM_ACCESS_TIME'] = 1451779200;
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl']['enablecolumns']['endtime'] = 'endtime_column';
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression('/endtime_column. = 0\) OR \(.*endtime_column. > 1451779200/', (string)$compositeExpression);
    }

    #[Test]
    public function visibilityConstraintStatementGenerationThrowsExceptionIfTheQuerySettingsAreInconsistent(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIgnoreEnableFields(false);
        $querySettings->setIncludeDeleted(true);

        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $this->expectException(InconsistentQuerySettingsException::class);
        $this->expectExceptionCode(1460975922);

        $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);
    }

    public static function addPageIdStatementSetsPidToZeroIfTableDeclaresRootLevelDataProvider(): iterable
    {
        yield 'set Pid to zero if rootLevel = 1' => [
            'rootLevel' => 1,
            'expectedSql' => '/pid. = 0/',
            'storagePageIds' => [42, 27],
        ];

        yield 'set Pid to given Pids if rootLevel = 0' => [
            'rootLevel' => 0,
            'expectedSql' => '/pid. IN \(42, 27\)/',
            'storagePageIds' => [42, 27],
        ];

        yield 'add 0 to given Pids if rootLevel = -1' => [
            'rootLevel' => -1,
            'expectedSql' => '/pid. IN \(42, 27, 0\)/',
            'storagePageIds' => [42, 27],
        ];

        yield 'set Pid to zero if rootLevel = -1 and no further pids given' => [
            'rootLevel' => -1,
            'expectedSql' => '/pid. = 0/',
            'storagePageIds' => [],
        ];

        yield 'set no statement for invalid configuration' => [
            'rootLevel' => 2,
            'expectedSql' => '//',
            'storagePageIds' => [42, 27],
        ];
    }

    #[DataProvider('addPageIdStatementSetsPidToZeroIfTableDeclaresRootLevelDataProvider')]
    #[Test]
    public function addPageIdStatementSetsPidToZeroIfTableDeclaresRootLevel(int $rootLevel, string $expectedSql, array $storagePageIds): void
    {
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl'] = [
            'rootLevel' => $rootLevel,
        ];
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context();
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setStoragePageIds($storagePageIds);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertMatchesRegularExpression($expectedSql, (string)$compositeExpression);
    }

    #[Test]
    public function tcaWithoutCtrlCreatesAValidSQLStatement(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $registryEntryRepository = $this->get(RegistryEntryRepository::class);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));

        // TcaSchema defines rootLevel flag by default, so we need to disable this query part
        $querySettings->setRespectStoragePage(false);

        $query = $registryEntryRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $compositeExpression = $queryBuilder->getWhere();
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    #[Test]
    public function generatedLikeExpressionIsCaseInsensitive(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Typo3DbQueryParserTestImport.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching($query->like('title', '%BlOg%'));
        self::assertCount(2, $query->execute());
    }
}
