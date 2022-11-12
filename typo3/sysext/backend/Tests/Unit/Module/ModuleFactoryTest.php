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

use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ModuleFactoryTest extends UnitTestCase
{
    protected ModuleFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ModuleFactory(
            $this->createMock(IconRegistry::class),
            new NoopEventDispatcher()
        );
    }

    /**
     * @test
     */
    public function adaptAliasMappingFromModuleConfigurationMapsAliasesProperly(): void
    {
        $moduleConfiguration = [
            'module_from_extension' => [
                'parent' => 'old_name',
                'path' => '/module/my-module',
            ],
            'new_name' => [
                'path' => '/module/new',
                'aliases' => ['old_name'],
            ],
            'new_list_module' => [
                'aliases' => ['web_list', 'web_list_x'],
            ],
            'content' => [
                'aliases' => ['web'],
            ],
            'web_info' => [
                'parent' => 'web',
                'position' => ['before' => 'web_list', 'after' => 'old_name'],
            ],
            'new_info_overview' => [
                'parent' => 'web_info',
                'position' => ['before' => 'invalid_name'],
                'aliases' => ['web_info_overview'],
            ],
            'web_info_ext' => [
                'parent' => 'new_info',
                'position' => ['before' => 'web_info_overview'],
            ],
            'foo' => [
                'parent' => 'duplicated',
            ],
            'foo_bar' => [
                'parent' => 'web_list_x',
                'aliases' => ['duplicated'],
            ],
            'bar_baz' => [
                'position' => ['after' => 'foo_bar'],
                'aliases' => ['duplicated'],
            ],
        ];
        $expectedModuleConfiguration = [
            'module_from_extension' => [
                'parent' => 'new_name',
                'path' => '/module/my-module',
            ],
            'new_name' => [
                'path' => '/module/new',
                'aliases' => ['old_name'],
            ],
            'new_list_module' => [
                'aliases' => ['web_list', 'web_list_x'],
            ],
            'content' => [
              'aliases' => ['web'],
            ],
            'web_info' => [
                'parent' => 'content',
                'position' => ['before' => 'new_list_module', 'after' => 'new_name'],
            ],
            'new_info_overview' => [
                'parent' => 'web_info',
                'position' => ['before' => 'invalid_name'],
                'aliases' => ['web_info_overview'],
            ],
            'web_info_ext' => [
                'parent' => 'new_info',
                'position' => ['before' => 'new_info_overview'],
            ],
            'foo' => [
                'parent' => 'bar_baz',
            ],
            'foo_bar' => [
                'parent' => 'new_list_module',
                'aliases' => ['duplicated'],
            ],
            'bar_baz' => [
                'position' => ['after' => 'foo_bar'],
                'aliases' => ['duplicated'],
            ],
        ];
        $moduleConfiguration = $this->subject->adaptAliasMappingFromModuleConfiguration($moduleConfiguration);
        self::assertEquals($expectedModuleConfiguration, $moduleConfiguration);
    }
}
