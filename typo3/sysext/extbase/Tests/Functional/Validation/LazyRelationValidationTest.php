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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

final class LazyRelationValidationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase'];
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LazyRelationValidationImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    /**
     * A #[Lazy] to-one relation fetched from the database is a native lazy proxy of the target class,
     * not a LazyLoadingProxy instance, so validators see a regular instance of the related model.
     */
    #[Test]
    public function lazyToOneRelationLoadedFromDatabaseIsNoLazyLoadingProxy(): void
    {
        // Given: a blog with a #[Lazy] to-one relation, loaded from the database
        $blog = $this->get(BlogRepository::class)->findByUid(10);

        // When: the lazy relations are read without initializing them
        $featuredPost = $blog->getFeaturedPost();
        $administrator = $blog->getAdministrator();

        // Then: they are uninitialized native lazy objects of the target class
        self::assertNotInstanceOf(LazyLoadingProxy::class, $featuredPost);
        self::assertNotInstanceOf(LazyLoadingProxy::class, $administrator);
        self::assertInstanceOf(Post::class, $featuredPost);
        self::assertInstanceOf(Administrator::class, $administrator);
        self::assertTrue(new \ReflectionClass(Post::class)->isUninitializedLazyObject($featuredPost));
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));
    }

    /**
     * The base validator of a blog descends into the unresolved lazy relation featuredPost and
     * reports the violation of the post's own #[Validate] attribute, without any exception.
     */
    #[Test]
    public function baseValidatorReportsErrorOfUnresolvedLazyToOneRelation(): void
    {
        // Given: a blog loaded from the database whose lazy featuredPost has a too short title
        $blog = $this->get(BlogRepository::class)->findByUid(10);
        $validator = $this->get(ValidatorResolver::class)->getBaseValidatorConjunction(Blog::class);
        self::assertTrue(new \ReflectionClass(Post::class)->isUninitializedLazyObject($blog->getFeaturedPost()));

        // When: the blog is validated
        $result = $validator->validate($blog);

        // Then: the error of the related post is reported at the path of the relation
        $messages = [];
        foreach ($result->getFlattenedErrors() as $path => $errors) {
            foreach ($errors as $error) {
                $messages[$path][] = $error->getMessage();
            }
        }
        self::assertSame(
            ['featuredPost.title' => ['The length of the given string was not between 3 and 50 characters.']],
            $messages
        );
    }

    /**
     * Validating a blog whose unresolved lazy featuredPost is valid does not produce errors.
     */
    #[Test]
    public function baseValidatorAcceptsValidUnresolvedLazyToOneRelation(): void
    {
        // Given: a blog loaded from the database whose lazy featuredPost is valid
        $blog = $this->get(BlogRepository::class)->findByUid(11);
        $validator = $this->get(ValidatorResolver::class)->getBaseValidatorConjunction(Blog::class);

        // When: the blog is validated
        $result = $validator->validate($blog);

        // Then: there are no errors, and the relation is initialized by the validator
        self::assertFalse($result->hasErrors());
        self::assertSame('Valid title', $blog->getFeaturedPost()->getTitle());
    }

    /**
     * Validators added for a property typed Administrator|LazyLoadingProxy|null run against the
     * resolved related record, not against the proxy wrapper.
     */
    #[Test]
    public function propertyValidatorOfLazyAdministratorRunsAgainstResolvedRecord(): void
    {
        // Given: a validator requiring a username of the lazy administrator, and a valid record
        $administratorValidator = new GenericObjectValidator();
        $administratorValidator->addPropertyValidator('username', new NotEmptyValidator());
        $blogValidator = new GenericObjectValidator();
        $blogValidator->addPropertyValidator('administrator', $administratorValidator);
        $blog = $this->get(BlogRepository::class)->findByUid(10);
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($blog->getAdministrator()));

        // When: the blog is validated
        $result = $blogValidator->validate($blog);

        // Then: no errors
        self::assertFalse($result->hasErrors());
    }

    /**
     * An unresolved lazy administrator whose record has an empty username violates the
     * validator added for the nested property.
     */
    #[Test]
    public function propertyValidatorOfLazyAdministratorReportsErrorOfResolvedRecord(): void
    {
        // Given: a validator requiring a username of the lazy administrator, and a record without username
        $administratorValidator = new GenericObjectValidator();
        $administratorValidator->addPropertyValidator('username', new NotEmptyValidator());
        $blogValidator = new GenericObjectValidator();
        $blogValidator->addPropertyValidator('administrator', $administratorValidator);
        $blog = $this->get(BlogRepository::class)->findByUid(12);
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($blog->getAdministrator()));

        // When: the blog is validated
        $result = $blogValidator->validate($blog);

        // Then: the nested property is reported
        $paths = array_keys($result->getFlattenedErrors());
        self::assertSame(['administrator.username'], $paths);
    }
}
