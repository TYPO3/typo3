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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\DateExample;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;

final class BackendTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    #[Test]
    public function getPlainValueReturnsNullForNullableDateTimeProperty(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendTest/getPlainValueReturnsNullForNullableDateTimePropertyImport.csv');
        /** @var DateExample $date */
        $date = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, DateExample::class);
        $date->setDatetimeDatetime(null);
        $changedEntities = new ObjectStorage();
        $changedEntities->attach($date);
        $backend = $this->get(Backend::class);
        $backend->setChangedEntities($changedEntities);
        $backend->commit();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/BackendTest/getPlainValueReturnsNullForNullableDateTimePropertyAssertion.csv');
    }

    #[Test]
    public function insertRelationInRelationtableSetsMmMatchFieldsInRow(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendTest/insertRelationInRelationtableSetsMmMatchFieldsInRowImport.csv');
        /** @var Post $post */
        $post = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, Post::class);
        $tagToAttach = $this->get(PersistenceManager::class)->getObjectByIdentifier(2, Tag::class);
        $post->addTag($tagToAttach);
        $changedEntities = new ObjectStorage();
        $changedEntities->attach($post);
        $subject = $this->get(Backend::class);
        $subject->setChangedEntities($changedEntities);
        $subject->commit();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/BackendTest/insertRelationInRelationtableSetsMmMatchFieldsInRowAssertion.csv');
    }

    #[Test]
    public function getIdentifierByObjectWithStringInsteadOfObjectReturnsNull(): void
    {
        $subject = $this->get(Backend::class);
        /** @phpstan-ignore-next-line */
        self::assertNull($subject->getIdentifierByObject('thisIsAString'));
    }

    #[Test]
    public function getIdentifierByObjectReturnsIdentifierForNonLazyObject(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendTest/getIdentifierByObjectReturnsIdentifierForNonLazyObjectImport.csv');
        /** @var Post $post */
        $post = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, Post::class);
        $session = $this->get(Session::class);
        $session->registerObject($post, '1');
        $subject = $this->get(Backend::class);
        $subject->getIdentifierByObject($post);
        self::assertSame('1', $subject->getIdentifierByObject($post));
    }

    #[Test]
    public function getIdentifierByObjectReturnsIdentifierForLazyObject(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendTest/getIdentifierByObjectReturnsIdentifierForLazyObjectImport.csv');
        $administrator = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, Administrator::class);
        $session = $this->get(Session::class);
        $session->registerObject($administrator, '1');
        $blog = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, Blog::class);
        $administratorLazyLoadingProxy = $blog->getAdministrator();
        $subject = $this->get(Backend::class);
        self::assertSame('1', $subject->getIdentifierByObject($administratorLazyLoadingProxy));
    }
}
