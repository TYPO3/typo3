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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Pins the observable behaviour of Extbase persistence for translated and versioned records.
 *
 * The expectations describe what happens today, including known quirks. A quirk carries a
 * "@todo" describing the inconsistency. A refactoring that needs one of these
 * expectations changed is a behaviour change, not a refactoring.
 *
 * Fixture (Fixtures/BehaviourConformanceTestImport.csv), storage page 20, frontend request:
 * - Language 1 (DA): P2 DA (11), P3 DA (13, default P3 is hidden), P5 DA (15, hidden),
 *   P16 floating DA (16, no default record). Language 2 (DE): P2 DE (22).
 *   Language 3 has no records and falls back to 2 where configured.
 * - P4 (4) is in "all languages" (-1). The "content" column sorts translations after defaults.
 * - Workspace 1: P2 modified (102), P5 delete placeholder (105), P6 new (106),
 *   P1 hidden in the workspace (107), P8 moved to page 21 (108), P2 DA modified (111),
 *   Blog A modified (101).
 * Descriptions: "title|uid|_localizedUid|_languageUid" and, for workspace cases,
 * "title|uid|_versionedUid|pid".
 */
final class BehaviourConformanceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BehaviourConformanceTestImport.csv');
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 20],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    private function setLanguage(int $languageId, string $overlayType, array $fallbackChain = []): LanguageAspect
    {
        $languageAspect = new LanguageAspect($languageId, $languageId, $overlayType, $fallbackChain);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        return $languageAspect;
    }

    private function createPostQuery(LanguageAspect $languageAspect, bool $respectSysLanguage = true): QueryInterface
    {
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setLanguageAspect($languageAspect);
        $query->getQuerySettings()->setRespectSysLanguage($respectSysLanguage);
        $query->setOrderings(['content' => QueryInterface::ORDER_ASCENDING, 'uid' => QueryInterface::ORDER_ASCENDING]);
        return $query;
    }

    private static function describe(?DomainObjectInterface $object): string
    {
        if ($object === null) {
            return 'null';
        }
        $label = match (true) {
            $object instanceof Post, $object instanceof Blog => $object->getTitle(),
            $object instanceof Person => $object->getFirstname(),
            $object instanceof Tag => $object->getName(),
            default => get_class($object),
        };
        return sprintf(
            '%s|%d|%d|%d',
            $label,
            $object->getUid(),
            $object->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $object->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID),
        );
    }

    /**
     * @param iterable<DomainObjectInterface> $objects
     * @return list<string>
     */
    private static function describeAll(iterable $objects): array
    {
        $result = [];
        foreach ($objects as $object) {
            $result[] = self::describe($object);
        }
        return $result;
    }

    public static function rootQueryDataProvider(): array
    {
        return [
            'default language, overlays off' => [0, LanguageAspect::OVERLAYS_OFF, [], [
                'P5|5|5|0', 'P4 all languages|4|4|-1', 'P2|2|2|0', 'P1|1|1|0', 'P8 moved in WS|8|8|0',
            ]],
            'default language, overlays on' => [0, LanguageAspect::OVERLAYS_ON, [], [
                'P5|5|5|0', 'P4 all languages|4|4|-1', 'P2|2|2|0', 'P1|1|1|0', 'P8 moved in WS|8|8|0',
            ]],
            // Free mode selects rows of the language as they are, even P3 DA whose default record is hidden.
            'DA, overlays off' => [1, LanguageAspect::OVERLAYS_OFF, [], [
                'P4 all languages|4|4|-1', 'P16 floating DA|16|16|1', 'P3 DA|3|13|1', 'P2 DA|2|11|1',
            ]],
            // Only translations of visible default records. P3 DA (hidden default), P5 DA (hidden translation)
            // and the floating P16 are dropped. Sorting uses the translated "content" values.
            // pending review.typo3.org/c/Packages/TYPO3.CMS/+/94510
            'DA, overlays on' => [1, LanguageAspect::OVERLAYS_ON, [], [
                'P4 all languages|4|4|-1', 'P2 DA|2|11|1',
            ]],
            // pending review.typo3.org/c/Packages/TYPO3.CMS/+/94510
            'DA, overlays on with floating' => [1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [], [
                'P4 all languages|4|4|-1', 'P16 floating DA|16|16|1', 'P2 DA|2|11|1',
            ]],
            // Untranslated defaults are kept; P5 stays in default language because its translation is hidden.
            'DA, overlays mixed' => [1, LanguageAspect::OVERLAYS_MIXED, [], [
                'P5|5|5|0', 'P4 all languages|4|4|-1', 'P1|1|1|0', 'P8 moved in WS|8|8|0', 'P2 DA|2|11|1',
            ]],
            // P2 has no language 3 record and falls back to DE; the others keep the default language.
            'language 3 with fallback to DE, overlays mixed' => [3, LanguageAspect::OVERLAYS_MIXED, [2], [
                'P5|5|5|0', 'P4 all languages|4|4|-1', 'P2 DE|2|22|2', 'P1|1|1|0', 'P8 moved in WS|8|8|0',
            ]],
            // The SQL only selects language 3 rows, so the fallback chain is never used for root records.
            // pending review.typo3.org/c/Packages/TYPO3.CMS/+/94510
            'language 3 with fallback to DE, overlays on' => [3, LanguageAspect::OVERLAYS_ON, [2], [
                'P4 all languages|4|4|-1',
            ]],
        ];
    }

    /**
     * Rows selected per language and overlay mode, their overlay, and sorting on translated values.
     */
    #[DataProvider('rootQueryDataProvider')]
    #[Test]
    public function rootQueryReturnsRowsPerOverlayMode(int $languageId, string $overlayType, array $fallbackChain, array $expected): void
    {
        $languageAspect = $this->setLanguage($languageId, $overlayType, $fallbackChain);
        self::assertSame($expected, self::describeAll($this->createPostQuery($languageAspect)->execute()));
    }

    public static function relationsOfTranslatedRecordDataProvider(): array
    {
        return [
            'default language, overlays on' => [0, LanguageAspect::OVERLAYS_ON, [], [
                'P2|2|2|0', 'John|1|1|0', 'Blog A|1|1|0', ['T1|1|1|0', 'T2|2|2|0'],
            ]],
            // n:1 relations stored with default uids (author) and translated uids (blog) both resolve to the
            // default uid with the translation as _localizedUid. MM tags come from the translation's own MM rows.
            'DA, overlays on' => [1, LanguageAspect::OVERLAYS_ON, [], [
                'P2 DA|2|11|1', 'John DA|1|2|1', 'Blog A DA|1|2|1', ['T1 DA|1|21|1'],
            ]],
            // Relations are loaded with "mixed" when the root uses free mode, so untranslated T3 is kept.
            'DA, overlays off' => [1, LanguageAspect::OVERLAYS_OFF, [], [
                'P2 DA|2|11|1', 'John DA|1|2|1', 'Blog A DA|1|2|1', ['T1 DA|1|21|1', 'T3|3|3|0'],
            ]],
            'DA, overlays mixed' => [1, LanguageAspect::OVERLAYS_MIXED, [], [
                'P2 DA|2|11|1', 'John DA|1|2|1', 'Blog A DA|1|2|1', ['T1 DA|1|21|1', 'T3|3|3|0'],
            ]],
            // Tags are selected by the DE record's uid (22), which has no MM rows of its own.
            'language 3 with fallback to DE, overlays mixed' => [3, LanguageAspect::OVERLAYS_MIXED, [2], [
                'P2 DE|2|22|2', 'John|1|1|0', 'Blog A|1|1|0', [],
            ]],
            // Relations without a record in language 3 or 2 are dropped in "on" mode.
            // pending review.typo3.org/c/Packages/TYPO3.CMS/+/94510
            'language 3 with fallback to DE, overlays on' => [3, LanguageAspect::OVERLAYS_ON, [2], [
                'P2 DE|2|22|2', 'null', 'null', [],
            ]],
        ];
    }

    /**
     * Identity fields of an overlaid record and of its n:1 and m:n relations.
     */
    #[DataProvider('relationsOfTranslatedRecordDataProvider')]
    #[Test]
    public function findByUidOverlaysRecordAndRelations(int $languageId, string $overlayType, array $fallbackChain, array $expected): void
    {
        $this->setLanguage($languageId, $overlayType, $fallbackChain);
        $post = $this->get(PostRepository::class)->findByUid(2);
        self::assertSame($expected, [
            self::describe($post),
            self::describe($post->getAuthor()),
            self::describe($post->getBlog()),
            self::describeAll($post->getTags()),
        ]);
    }

    public static function childrenOfTranslatedParentDataProvider(): array
    {
        return [
            // @todo P2 is listed twice, once as P2 and once as P2 DE, because the DE
            //       translation points to the default blog and is re-based onto its default record.
            'default language, overlays on' => [0, LanguageAspect::OVERLAYS_ON, [
                'Blog A|1|1|0', ['P1|1|1|0', 'P2|2|2|0', 'P2 DE|2|22|2', 'P4 all languages|4|4|-1', 'P5|5|5|0', 'P8 moved in WS|8|8|0'],
            ]],
            // Children are selected by the parent's _localizedUid (2): the DA posts, not the default ones.
            // P3 DA is shown although its default record is hidden; P5 DA (hidden) is not.
            'DA, overlays on' => [1, LanguageAspect::OVERLAYS_ON, [
                'Blog A DA|1|2|1', ['P2 DA|2|11|1', 'P3 DA|3|13|1', 'P16 floating DA|16|16|1'],
            ]],
            'DA, overlays mixed' => [1, LanguageAspect::OVERLAYS_MIXED, [
                'Blog A DA|1|2|1', ['P2 DA|2|11|1', 'P3 DA|3|13|1', 'P16 floating DA|16|16|1'],
            ]],
        ];
    }

    /**
     * 1:n children (foreign_field) are loaded by the translated parent uid.
     */
    #[DataProvider('childrenOfTranslatedParentDataProvider')]
    #[Test]
    public function oneToManyChildrenAreLoadedByLocalizedParentUid(int $languageId, string $overlayType, array $expected): void
    {
        $this->setLanguage($languageId, $overlayType);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        self::assertSame($expected, [self::describe($blog), self::describeAll($blog->getPosts())]);
    }

    /**
     * Ignored enable fields are mirrored into the overlay lookup, so hidden translations
     * and translations of hidden default records are returned.
     * pending review.typo3.org/c/Packages/TYPO3.CMS/+/94510
     */
    #[Test]
    public function ignoredEnableFieldsAlsoApplyToTranslationLookup(): void
    {
        $languageAspect = $this->setLanguage(1, LanguageAspect::OVERLAYS_ON);
        $query = $this->createPostQuery($languageAspect);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        self::assertSame(
            ['P4 all languages|4|4|-1', 'P5 DA hidden|5|15|1', 'P3 DA|3|13|1', 'P2 DA|2|11|1'],
            self::describeAll($query->execute())
        );
    }

    /**
     * Records in "all languages" are not overlaid, their relations follow the query language.
     */
    #[Test]
    public function recordInAllLanguagesIsNotOverlaidButItsRelationsAre(): void
    {
        $this->setLanguage(1, LanguageAspect::OVERLAYS_ON);
        $post = $this->get(PostRepository::class)->findByUid(4);
        self::assertSame(['P4 all languages|4|4|-1', 'John DA|1|2|1'], [self::describe($post), self::describe($post->getAuthor())]);
    }

    /**
     * A translated uid returns the translation, re-based onto the default uid, in any language.
     */
    #[Test]
    public function findByTranslatedUidReturnsTranslationInDefaultLanguage(): void
    {
        $this->setLanguage(0, LanguageAspect::OVERLAYS_ON);
        $post = $this->get(PostRepository::class)->findByUid(11);
        self::assertSame(['P2 DA|2|11|1', 'John DA|1|2|1', ['T1 DA|1|21|1']], [
            self::describe($post),
            self::describe($post->getAuthor()),
            self::describeAll($post->getTags()),
        ]);
    }

    /**
     * Known hole: findByUid(2) and findByUid(11) in one request without clearing the session
     * (QueryLocalizedDataTest calls clearState() between them). Both calls return distinct objects
     * with the same uid; the default object is reused by the session.
     */
    #[Test]
    public function defaultAndTranslatedUidInOneRequestYieldTwoObjectsWithSameUid(): void
    {
        $this->setLanguage(0, LanguageAspect::OVERLAYS_ON);
        $postRepository = $this->get(PostRepository::class);
        $persistenceManager = $this->get(PersistenceManager::class);
        $default = $postRepository->findByUid(2);
        $translated = $postRepository->findByUid(11);
        $defaultAgain = $postRepository->findByUid(2);
        self::assertSame('P2|2|2|0', self::describe($default));
        self::assertSame('P2 DA|2|11|1', self::describe($translated));
        self::assertNotSame($default, $translated);
        self::assertSame($default, $defaultAgain);
        self::assertSame('2', $persistenceManager->getIdentifierByObject($default));
        self::assertSame('2_11', $persistenceManager->getIdentifierByObject($translated));
    }

    /**
     * The identity map key includes the overlay type, so the same translated row loaded
     * under "on" and "mixed" yields two objects.
     */
    #[Test]
    public function sameRowUnderTwoOverlayTypesYieldsTwoObjects(): void
    {
        $this->setLanguage(1, LanguageAspect::OVERLAYS_ON);
        $query = $this->createPostQuery(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $viaOn = $query->matching($query->equals('uid', 11))->execute()->getFirst();
        $viaMixed = null;
        foreach ($this->createPostQuery(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED))->execute() as $post) {
            if ($post->getUid() === 2) {
                $viaMixed = $post;
            }
        }
        self::assertSame('P2 DA|2|11|1', self::describe($viaOn));
        self::assertSame('P2 DA|2|11|1', self::describe($viaMixed));
        self::assertNotSame($viaOn, $viaMixed);
    }

    /**
     * The "uid_localizedUid" identity is accepted by property mapping; the result depends on
     * the current language, not on the localized uid in the identity.
     */
    #[Test]
    public function propertyMapperAcceptsLocalizedIdentity(): void
    {
        $this->setLanguage(0, LanguageAspect::OVERLAYS_ON);
        self::assertSame('P2|2|2|0', self::describe($this->get(PropertyMapper::class)->convert('2_11', Post::class)));
        $this->get(PersistenceManager::class)->clearState();
        $this->setLanguage(1, LanguageAspect::OVERLAYS_ON);
        self::assertSame('P2 DA|2|11|1', self::describe($this->get(PropertyMapper::class)->convert('2_11', Post::class)));
    }

    /**
     * Relations of a root record fetched without respectSysLanguage use the record's own language.
     */
    #[Test]
    public function relationsUseParentLanguageWithoutRespectSysLanguage(): void
    {
        $languageAspect = $this->setLanguage(0, LanguageAspect::OVERLAYS_ON);
        $query = $this->createPostQuery($languageAspect, false);
        $post = $query->matching($query->equals('uid', 11))->execute()->getFirst();
        self::assertSame(['P2 DA|2|11|1', 'John DA|1|2|1', ['T1 DA|1|21|1']], [
            self::describe($post),
            self::describe($post->getAuthor()),
            self::describeAll($post->getTags()),
        ]);
    }

    /**
     * @todo rows of every language are selected and each is overlaid, so the same
     *       record appears more than once (P2 DA twice here).
     */
    #[Test]
    public function withoutRespectSysLanguageTranslationsAreReturnedTwice(): void
    {
        $languageAspect = $this->setLanguage(1, LanguageAspect::OVERLAYS_ON);
        self::assertSame(
            ['P4 all languages|4|4|-1', 'P2 DA|2|11|1', 'P2 DE|2|22|2', 'P16 floating DA|16|16|1', 'P3 DA|3|13|1', 'P2 DA|2|11|1'],
            self::describeAll($this->createPostQuery($languageAspect, false)->execute())
        );
    }

    /**
     * @todo count() runs SQL without the overlay, so it counts rows the overlay drops.
     */
    #[Test]
    public function countIgnoresRowsDroppedByOverlay(): void
    {
        $languageAspect = $this->setLanguage(1, LanguageAspect::OVERLAYS_ON);
        self::assertCount(9, $this->createPostQuery($languageAspect, false)->execute());
        self::assertCount(6, $this->createPostQuery($languageAspect, false)->execute()->toArray());
        // With respectSysLanguage the SQL already excludes what the overlay would drop.
        self::assertCount(2, $this->createPostQuery($languageAspect)->execute());
        self::assertCount(2, $this->createPostQuery($languageAspect)->execute()->toArray());
    }

    /**
     * @todo in the live workspace LIMIT is applied in SQL before the overlay drops rows.
     */
    #[Test]
    public function limitIsAppliedBeforeOverlayDropsRows(): void
    {
        $languageAspect = $this->setLanguage(1, LanguageAspect::OVERLAYS_ON);
        $query = $this->createPostQuery($languageAspect, false);
        $query->setLimit(3);
        self::assertSame(['P4 all languages|4|4|-1', 'P2 DA|2|11|1'], self::describeAll($query->execute()));
    }
}
