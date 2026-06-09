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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Storage\ExtensionStorageAdapter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtensionStorageAdapterFunctionalTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/Fixtures/Extensions/form_storage_tests',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    public static function readThrowsExceptionWhenFormDefinitionHasInvalidFileExtensionDataProvider(): iterable
    {
        yield ['EXT:form_storage_tests/Resources/Private/Forms/ContactForm.form.yaml', null];
        yield ['EXT:form_storage_tests/Resources/Private/Forms/ContactFormWithType.yaml', 1531160649];
        yield ['EXT:form_storage_tests/Resources/Private/Forms/ContactFormWithoutType.yaml', 1531160649];
    }

    #[Test]
    #[DataProvider('readThrowsExceptionWhenFormDefinitionHasInvalidFileExtensionDataProvider')]
    public function readThrowsExceptionWhenFormDefinitionHasInvalidFileExtension(string $identifier, ?int $exceptionCode): void
    {
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        if ($exceptionCode !== null) {
            $this->expectException(PersistenceManagerException::class);
            $this->expectExceptionCode($exceptionCode);
        }

        $subject = $this->get(ExtensionStorageAdapter::class);
        $formData = $subject->read(new FormIdentifier($identifier), $request);
        self::assertStringStartsWith('contact-form', $formData->identifier);
    }
}
