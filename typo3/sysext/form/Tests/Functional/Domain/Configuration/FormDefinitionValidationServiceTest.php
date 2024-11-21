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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Configuration;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormDefinitionValidationServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function validateAllFormElementPropertyValuesByHmacThrowsExceptionWithoutHmac(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588037);
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllFormElementPropertyValuesByHmac',
            [
                'label' => 'xxx',
            ],
            '123',
            new ValidationDto('standard', 'Text', 'some-text')
        );
    }

    #[Test]
    public function validateAllFormElementPropertyValuesByHmacThrowsWithInvalidHmac(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588036);
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllFormElementPropertyValuesByHmac',
            [
                'label' => 'xxx',
                '_orig_label' => [
                    'value' => 'aaa', // does not match 'xxx', same in hash creation below, should throw.
                    'hmac' => $this->get(HashService::class)->hmac(serialize(['some-text', 'label', 'aaa']), '123'),
                ],
            ],
            '123',
            new ValidationDto('standard', 'Text', 'some-text')
        );
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function validateAllFormElementPropertyValuesByHmacOkWithValidHmac(): void
    {
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllFormElementPropertyValuesByHmac',
            [
                'label' => 'aaa',
                '_orig_label' => [
                    'value' => 'aaa',
                    'hmac' => $this->get(HashService::class)->hmac(serialize(['some-text', 'label', 'aaa']), '123'),
                ],
            ],
            '123',
            new ValidationDto('standard', 'Text', 'some-text')
        );
    }

    #[Test]
    public function validateAllPropertyCollectionElementValuesByHmacThrowsExceptionWithoutHmac(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591585);
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyCollectionElementValuesByHmac',
            [
                'identifier' => 'StringLength',
                '_orig_identifier' => [
                    'value' => 'StringLength',
                    'hmac' => $this->get(HashService::class)->hmac(serialize(['some-text', 'validators', 'StringLength', 'identifier', 'StringLength']), '123'),
                ],
                'options' => [
                    'test' => 'xxx', // no hmac setup at all
                ],
            ],
            '123',
            new ValidationDto('standard', 'Text', 'some-text', null, 'validators')
        );
    }

    #[Test]
    public function validateAllPropertyCollectionElementValuesByHmacThrowsExceptionWithInvalidHmac(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591586);
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyCollectionElementValuesByHmac',
            [
                'identifier' => 'StringLength', // valid
                '_orig_identifier' => [
                    'value' => 'StringLength',
                    'hmac' => $this->get(HashService::class)->hmac(serialize(['some-text', 'validators', 'StringLength', 'identifier', 'StringLength']), '123'),
                ],
                'options' => [
                    'test' => 'xxx', // invalid
                    '_orig_test' => [
                        'value' => 'aaa',
                        'hmac' => $this->get(HashService::class)->hmac(serialize(['some-text', 'validators', 'StringLength', 'options.test', 'aaa']), '123'),
                    ],
                ],
            ],
            '123',
            new ValidationDto('standard', 'Text', 'some-text', null, 'validators')
        );
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function validateAllPropertyCollectionElementValuesByHmacOkWithValidHmac(): void
    {
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyCollectionElementValuesByHmac',
            [
                'identifier' => 'StringLength',
                '_orig_identifier' => [
                    'value' => 'StringLength',
                    'hmac' => $this->get(HashService::class)->hmac(serialize(['some-text', 'validators', 'StringLength', 'identifier', 'StringLength']), '123'),
                ],
                'options' => [
                    'test' => 'aaa',
                    '_orig_test' => [
                        'value' => 'aaa',
                        'hmac' => $this->get(HashService::class)->hmac(serialize(['some-text', 'validators', 'StringLength', 'options.test', 'aaa']), '123'),
                    ],
                ],
            ],
            '123',
            new ValidationDto('standard', 'Text', 'some-text', null, 'validators')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatableFormElementThrowsExceptionWithoutHmac1(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588037);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatableFormElement',
            [
                'test' => 'xxx',
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatableFormElementThrowsExceptionWithoutHmac2(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538222);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatableFormElement',
            [
                'test' => 'xxx',
                '_orig_test' => [],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatableFormElementThrowsExceptionWithInvalidHmac1(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538252);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatableFormElement',
            [
                'test' => 'xxx',
                '_orig_test' => [
                    'hmac' => '4242',
                ],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatableFormElementThrowsExceptionWithInvalidHmac2(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538252);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatableFormElement',
            [
                'test' => 'xxx1',
                '_orig_test' => [
                    'hmac' => (new HashService())->hmac(serialize(['text-1', 'test', 'xxx']), '54321'),
                ],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1')
        );
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function validateAllPropertyValuesFromCreatableFormElementOkWithValidHmac(): void
    {
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatableFormElement',
            [
                'test' => 'xxx',
                '_orig_test' => [
                    'value' => 'xxx',
                    'hmac' => (new HashService())->hmac(serialize(['text-1', 'test', 'xxx']), '54321'),
                ],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElementWithoutHmac1(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591585);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatablePropertyCollectionElement',
            [
                'test' => 'xxx',
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1', null, 'validators', 'StringLength')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElementWithoutHmac2(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538222);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatablePropertyCollectionElement',
            [
                'test' => 'xxx',
                '_orig_test' => [],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1', null, 'validators', 'StringLength')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElementWithInvalidHmac1(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538252);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatablePropertyCollectionElement',
            [
                'test' => 'xxx',
                '_orig_test' => [
                    'hmac' => '4242',
                ],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1', null, 'validators', 'StringLength')
        );
    }

    #[Test]
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElementWithInvalidHmac2(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591586);
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatablePropertyCollectionElement',
            [
                'test' => 'xxx1',
                '_orig_test' => [
                    'value' => 'xxx',
                    'hmac' => (new HashService())->hmac(serialize(['text-1', 'validators', 'StringLength', 'test', 'xxx']), '54321'),
                ],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1', null, 'validators', 'StringLength')
        );
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElementOkWithValidHmac(): void
    {
        $configurationService = $this->createMock(ConfigurationService::class);
        $this->getContainer()->set(ConfigurationService::class, $configurationService); // @phpstan-ignore-line
        $subjectMock = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $subjectMock->_call(
            'validateAllPropertyValuesFromCreatablePropertyCollectionElement',
            [
                'test' => 'xxx',
                '_orig_test' => [
                    'value' => 'xxx',
                    'hmac' => (new HashService())->hmac(serialize(['text-1', 'validators', 'StringLength', 'test', 'xxx']), '54321'),
                ],
            ],
            '54321',
            new ValidationDto('standard', 'Text', 'text-1', null, 'validators', 'StringLength')
        );
    }
}
