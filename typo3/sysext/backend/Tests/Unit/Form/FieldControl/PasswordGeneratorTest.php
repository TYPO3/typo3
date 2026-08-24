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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FieldControl;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FieldControl\PasswordGenerator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PasswordGeneratorTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'] = [
            'default' => [
                'generator' => ['className' => 'SomeGeneratorClass', 'options' => []],
            ],
            'validationOnly' => [
                'validators' => [],
            ],
        ];
    }

    public static function unusablePasswordPolicyDataProvider(): \Generator
    {
        yield 'Password policies disabled with an empty string' => [''];
        yield 'No password policy option at all' => [null];
        yield 'Password policy that is not registered' => ['neverRegistered'];
        yield 'Password policy that configures no generator' => ['validationOnly'];
    }

    #[DataProvider('unusablePasswordPolicyDataProvider')]
    #[Test]
    public function renderReturnsNothingForAnUnusablePasswordPolicy(?string $passwordPolicy): void
    {
        $options = ['title' => 'Generate password', 'allowEdit' => true];
        if ($passwordPolicy !== null) {
            $options['passwordPolicy'] = $passwordPolicy;
        }

        self::assertSame([], $this->renderWithOptions($options));
    }

    #[Test]
    public function renderReturnsTheControlForAConfiguredPasswordPolicy(): void
    {
        $result = $this->renderWithOptions([
            'title' => 'Generate password',
            'allowEdit' => true,
            'passwordPolicy' => 'default',
        ]);

        self::assertSame('actions-dice', $result['iconIdentifier']);
        self::assertSame('default', $result['linkAttributes']['data-password-policy']);
    }

    private function renderWithOptions(array $fieldControlOptions): array
    {
        $subject = new PasswordGenerator();
        $subject->setData([
            'renderData' => [
                'fieldControlOptions' => $fieldControlOptions,
            ],
            'parameterArray' => [
                'itemFormElName' => 'data[be_users][1][password]',
            ],
        ]);

        return $subject->render();
    }
}
