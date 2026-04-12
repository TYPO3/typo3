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

namespace TYPO3\CMS\Form\Tests\Functional\Storage;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Storage\DatabaseStorageAdapter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional permission tests for DatabaseStorageAdapter
 *
 * These tests verify that backend user permissions (table access, page access)
 * are correctly enforced in the database storage adapter with real backend
 * user authentication objects.
 *
 * Test users (from be_users.csv fixture):
 * - UID 1: Admin user — full access
 * - UID 2: Non-admin without form_definition table access (be_group 1: only pages in tables_select)
 * - UID 3: Non-admin with form_definition read-only access (be_group 2: form_definition in tables_select only)
 */
final class DatabaseStoragePermissionFunctionalTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        // Set up admin user first for data import
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/form_definition.csv');
    }

    private function getSubject(): DatabaseStorageAdapter
    {
        return $this->get(DatabaseStorageAdapter::class);
    }

    private function switchToBackendUser(int $uid): BackendUserAuthentication
    {
        $backendUser = $this->setUpBackendUser($uid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        return $backendUser;
    }

    private function createBackendRequest(): ServerRequest
    {
        return (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    private function createFormData(): FormData
    {
        return FormData::fromArray([
            'identifier' => 'permission-test-form',
            'type' => 'Form',
            'label' => 'Permission Test Form',
            'prototypeName' => 'standard',
            'renderingOptions' => [],
            'finishers' => [],
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Page 1',
                    'renderables' => [],
                ],
            ],
            'variants' => [],
        ]);
    }

    #[Test]
    public function adminUserCanReadFormInBackendContext(): void
    {
        $this->switchToBackendUser(1);
        $subject = $this->getSubject();

        $result = $subject->read(new FormIdentifier('1'), $this->createBackendRequest());

        self::assertSame('crud-test-form', $result->identifier);
    }

    #[Test]
    public function adminUserCanWriteNewForm(): void
    {
        $this->switchToBackendUser(1);
        $subject = $this->getSubject();

        $result = $subject->write(new FormIdentifier('NEW12345'), $this->createFormData());

        self::assertTrue(is_numeric($result->identifier), 'admin can create form');
    }

    #[Test]
    public function adminUserCanDeleteForm(): void
    {
        $this->switchToBackendUser(1);
        $subject = $this->getSubject();

        // Delete the pre-existing form
        $subject->delete(new FormIdentifier('1'));

        self::assertFalse($subject->exists(new FormIdentifier('1')));
    }

    #[Test]
    public function nonAdminWithoutTableSelectCannotReadForm(): void
    {
        $this->switchToBackendUser(2);
        $subject = $this->getSubject();

        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1774364031);

        $subject->read(new FormIdentifier('1'), $this->createBackendRequest());
    }

    #[Test]
    public function nonAdminWithoutTableSelectCannotSeeFormsInFindAll(): void
    {
        $this->switchToBackendUser(2);
        $subject = $this->getSubject();

        $results = $subject->findAll(new SearchCriteria());

        self::assertCount(0, $results, 'user without table access sees no forms');
    }

    #[Test]
    public function nonAdminWithoutTableModifyCannotWriteNewForm(): void
    {
        $this->switchToBackendUser(2);
        $subject = $this->getSubject();

        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1767199435);

        $subject->write(new FormIdentifier('NEW12345'), $this->createFormData());
    }

    #[Test]
    public function nonAdminWithReadOnlyAccessCanReadForm(): void
    {
        $this->switchToBackendUser(3);
        $subject = $this->getSubject();

        $result = $subject->read(new FormIdentifier('1'), $this->createBackendRequest());

        self::assertSame('crud-test-form', $result->identifier);
    }

    #[Test]
    public function nonAdminWithReadOnlyAccessSeesFormsInFindAll(): void
    {
        $this->switchToBackendUser(3);
        $subject = $this->getSubject();

        $results = $subject->findAll(new SearchCriteria());

        self::assertGreaterThan(0, count($results), 'user with table_select access sees forms');
    }

    #[Test]
    public function nonAdminWithReadOnlyAccessCannotWriteNewForm(): void
    {
        $this->switchToBackendUser(3);
        $subject = $this->getSubject();

        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1767199435);

        $subject->write(new FormIdentifier('NEW12345'), $this->createFormData());
    }

    #[Test]
    public function nonAdminWithReadOnlyAccessFindAllMarksFormsAsReadOnly(): void
    {
        $this->switchToBackendUser(3);
        $subject = $this->getSubject();

        $results = $subject->findAll(new SearchCriteria());

        foreach ($results as $metadata) {
            self::assertTrue($metadata->readOnly, sprintf('form "%s" is marked as readOnly for read-only user', $metadata->identifier));
            self::assertFalse($metadata->removable, sprintf('form "%s" is marked as not removable for read-only user', $metadata->identifier));
        }
    }

    #[Test]
    public function frontendContextAlwaysAllowsReadEvenWithoutBackendUser(): void
    {
        // Remove backend user entirely — simulate pure frontend request
        unset($GLOBALS['BE_USER']);

        $subject = $this->getSubject();
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $result = $subject->read(new FormIdentifier('1'), $request);

        self::assertSame('crud-test-form', $result->identifier);
    }

    #[Test]
    public function frontendContextAllowsReadEvenWithRestrictedBackendUser(): void
    {
        // Set up user without table access
        $this->switchToBackendUser(2);

        $subject = $this->getSubject();
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        // In frontend context, permission check is skipped
        $result = $subject->read(new FormIdentifier('1'), $request);

        self::assertSame('crud-test-form', $result->identifier);
    }

    #[Test]
    public function isAccessibleReturnsTrueForAdmin(): void
    {
        $this->switchToBackendUser(1);
        self::assertTrue($this->getSubject()->isAccessible());
    }

    #[Test]
    public function isAccessibleReturnsFalseForUserWithoutTableModify(): void
    {
        $this->switchToBackendUser(2);
        self::assertFalse($this->getSubject()->isAccessible());
    }

    #[Test]
    public function isAccessibleReturnsFalseForReadOnlyUser(): void
    {
        $this->switchToBackendUser(3);
        self::assertFalse($this->getSubject()->isAccessible());
    }
}
