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

namespace TYPO3\CMS\Backend\Tests\Unit\Module;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ExtbaseModule;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtbaseModuleTest extends UnitTestCase
{
    private const CONTROLLER_CLASS_NAME = 'MyVendor\\MyExtension\\Controller\\FirstController';

    #[Test]
    public function createFromConfigurationKeepsControllerActionsGivenAsArray(): void
    {
        $subject = ExtbaseModule::createFromConfiguration('my_module', [
            'path' => '/module/my-module',
            'extensionName' => 'MyExtension',
            'controllerActions' => [
                self::CONTROLLER_CLASS_NAME => ['index', 'show'],
            ],
        ]);
        $expectedResult = [
            self::CONTROLLER_CLASS_NAME => [
                'actions' => ['index', 'show'],
                'alias' => 'First',
                'className' => self::CONTROLLER_CLASS_NAME,
            ],
        ];
        self::assertSame($expectedResult, $subject->getControllerActions());
    }

    #[Test]
    #[IgnoreDeprecations]
    public function createFromConfigurationTriggersDeprecationForCommaSeparatedControllerActions(): void
    {
        $this->expectUserDeprecationMessage(
            'Passing actions as comma-separated string for controller "' . self::CONTROLLER_CLASS_NAME . '" in the'
            . ' "controllerActions" configuration of backend module "my_module" is deprecated since'
            . ' TYPO3 v15.0 and will stop working in TYPO3 v16.0. Pass an array of action names instead.'
        );
        $subject = ExtbaseModule::createFromConfiguration('my_module', [
            'path' => '/module/my-module',
            'extensionName' => 'MyExtension',
            'controllerActions' => [
                self::CONTROLLER_CLASS_NAME => 'index, show',
            ],
        ]);
        $expectedResult = [
            self::CONTROLLER_CLASS_NAME => [
                'actions' => ['index', 'show'],
                'alias' => 'First',
                'className' => self::CONTROLLER_CLASS_NAME,
            ],
        ];
        self::assertSame($expectedResult, $subject->getControllerActions());
    }
}
