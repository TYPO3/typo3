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

use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Http\ServerRequest;
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

class Typo3DbQueryParserTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderDoesNotAddAndWhereWithEmptyConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        self::assertCount(4, $compositeExpression);
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderThrowsExceptionOnNotImplementedConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsSimpleAndWhere(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->matching($query->equals('uid', 1));

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        // 5: custom constraint uid = 1
        self::assertCount(5, $compositeExpression);
        self::assertStringContainsString('uid', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsNotConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $query->matching($query->logicalNot($query->equals('uid', 1)));

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        // 5: custom constraint NOT(uid = 1)
        self::assertCount(5, $compositeExpression);
        self::assertMatchesRegularExpression('/NOT\(.*uid/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsAndConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        // 5: custom AND constraint title = 'Heinz' AND description = 'Heinz'
        self::assertCount(5, $compositeExpression);
        self::assertMatchesRegularExpression('/title.* AND .*description/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function convertQueryToDoctrineQueryBuilderAddsOrConstraint(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        // 1-4: language constraint, pid constraint, workspace constraint, enable fields constraint.
        // 5: custom AND constraint title = 'Heinz' OR description = 'Heinz'
        self::assertCount(5, $compositeExpression);
        self::assertMatchesRegularExpression('/title.* OR .*description/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function languageStatementWorksForDefaultLanguage(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/sys_language_uid. IN \(0, -1\)/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function languageStatementWorksForNonDefaultLanguage(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(1, overlayType: LanguageAspect::OVERLAYS_OFF),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/sys_language_uid. IN \(1, -1\)/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function languageStatementWorksInBackendContext(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(1, overlayType: LanguageAspect::OVERLAYS_OFF),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/sys_language_uid. IN \(1, -1\)/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForForeignLanguageWithSubselectionWithoutDeleteStatementReturned(): void
    {
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl']['delete'] = null;
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(1),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/l18n_parent. IN \(SELECT/', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksForForeignLanguageWithSubselectionTakesDeleteStatementIntoAccountIfNecessary(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(id: 1, contentId: 1, overlayType: LanguageAspect::OVERLAYS_MIXED),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/l18n_parent. IN \(SELECT/', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function addGetLanguageStatementWorksInBackendContextWithSubselectionTakesDeleteStatementIntoAccountIfNecessary(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(id: 1, contentId: 1, overlayType: LanguageAspect::OVERLAYS_MIXED),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/l18n_parent. IN \(SELECT/', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function orderStatementGenerationWorks(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(1),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setOrderings(['title' => QueryInterface::ORDER_DESCENDING]);
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $orderBy = $queryBuilder->getQueryPart('orderBy');
        self::assertMatchesRegularExpression('/title. DESC/', $orderBy[0]);
    }

    /**
     * @test
     */
    public function orderStatementGenerationThrowsExceptionOnUnsupportedOrder(): void
    {
        $this->expectException(UnsupportedOrderException::class);
        $this->expectExceptionCode(1242816074);

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(1),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setOrderings(['title' => 'FOO']);
        $query->setQuerySettings($querySettings);

        $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);
    }

    /**
     * @test
     */
    public function orderStatementGenerationWorksWithMultipleOrderings(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context(
            [
                'language' => new LanguageAspect(1),
            ]
        );
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setOrderings(
            ['title' => QueryInterface::ORDER_DESCENDING, 'description' => QueryInterface::ORDER_ASCENDING],
        );
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        $orderBy = $queryBuilder->getQueryPart('orderBy');
        self::assertMatchesRegularExpression('/title. DESC/', $orderBy[0]);
        self::assertMatchesRegularExpression('/description. ASC/', $orderBy[1]);
    }

    /**
     * @test
     */
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function expressionIsOmittedForIgnoreEnableFieldsAreAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringNotContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function expressionIsGeneratedForIgnoreEnableFieldsAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringNotContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function expressionIsGeneratedForIgnoreOnlyFeGroupAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringNotContainsString('fe_group', (string)$compositeExpression);
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function expressionIsGeneratedForDoNotIgnoreEnableFieldsAndDoNotIncludeDeletedInFrontendContext(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertStringContainsString('fe_group', (string)$compositeExpression);
        self::assertStringContainsString('hidden', (string)$compositeExpression);
        self::assertStringContainsString('deleted', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function respectEnableFieldsSettingGeneratesCorrectStatementWithOnlyEndTimeInFrontendContext(): void
    {
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl']['enablecolumns']['endtime'] = 'endtime_column';
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $typo3DbQueryParser = $this->get(Typo3DbQueryParser::class);
        $blogRepository = $this->get(BlogRepository::class);
        $dateAspect = new DateTimeAspect(new \DateTimeImmutable('3.1.2016'));
        $context = new Context(['date' => $dateAspect]);
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $querySettings = new Typo3QuerySettings(new Context(), $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);

        $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/endtime_column. = 0\) OR \(.*endtime_column. > 1451779200/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function respectEnableFieldsSettingGeneratesCorrectStatementWithOnlyEndTimeInBackendContext(): void
    {
        // simulate time for backend enable fields
        $GLOBALS['SIM_ACCESS_TIME'] = 1451779200;
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl']['enablecolumns']['endtime'] = 'endtime_column';
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression('/endtime_column. = 0\) OR \(.*endtime_column. > 1451779200/', (string)$compositeExpression);
    }

    /**
     * @test
     */
    public function visibilityConstraintStatementGenerationThrowsExceptionIfTheQuerySettingsAreInconsistent(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

    /**
     * @test
     * @dataProvider addPageIdStatementSetsPidToZeroIfTableDeclaresRootLevelDataProvider
     */
    public function addPageIdStatementSetsPidToZeroIfTableDeclaresRootLevel(int $rootLevel, string $expectedSql, array $storagePageIds): void
    {
        $GLOBALS['TCA']['tx_blogexample_domain_model_blog']['ctrl'] = [
            'rootLevel' => $rootLevel,
        ];
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
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

        /** @var CompositeExpression $compositeExpression */
        $compositeExpression = $queryBuilder->getQueryPart('where');
        self::assertMatchesRegularExpression($expectedSql, (string)$compositeExpression);
    }
}
