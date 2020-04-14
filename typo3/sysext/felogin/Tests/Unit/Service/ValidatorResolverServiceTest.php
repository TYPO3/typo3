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

namespace TYPO3\CMS\FrontendLogin\Tests\Unit\Service;

use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\CMS\FrontendLogin\Service\ValidatorResolverService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ValidatorResolverServiceTest
 */
class ValidatorResolverServiceTest extends UnitTestCase
{
    /**
     * @var ValidatorResolverService
     */
    protected $subject;

    /**
     * @test
     */
    public function resolveShouldReturnEmptyArrayIfEmptyConfigurationIsPassed(): void
    {
        $result = $this->subject->resolve([]);

        self::assertEmpty($result->current());
    }

    /**
     * @test
     * @dataProvider validatorConfigDataProvider
     * @param array $config
     */
    public function resolveShouldReturnValidators(array $config): void
    {
        $validators = $this->subject->resolve($config);

        foreach ($validators as $key => $validator) {
            $className = is_string($config[$key]) ? $config[$key] : $config[$key]['className'];

            self::assertInstanceOf($className, $validator);
        }
    }

    public function validatorConfigDataProvider(): \Generator
    {
        return [
            yield 'simple className' => ['config' => [NotEmptyValidator::class]],
            yield 'with options' => [
                'config' => [['className' => StringLengthValidator::class, 'options' => ['minimum' => 3]]],
            ],
            yield 'complex with both options and simple class names' => [
                'config' => [NotEmptyValidator::class, ['className' => StringLengthValidator::class, 'options' => ['minimum' => 3]]],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->subject = new ValidatorResolverService();

        parent::setUp();
    }
}
