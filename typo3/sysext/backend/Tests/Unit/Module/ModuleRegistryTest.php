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
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher\NoopEventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ModuleRegistryTest extends UnitTestCase
{
    protected ModuleFactory $moduleFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moduleFactory = new ModuleFactory(
            $this->createMock(IconRegistry::class),
            new NoopEventDispatcher()
        );
    }

    /**
     * @test
     */
    public function throwsExceptionOnDuplicateModuleIdentifier(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1642174843);

        new ModuleRegistry([
            $this->createModule('a_module'),
            $this->createModule('a_module'),
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionOnNonExistingModuleIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1642375889);

        (new ModuleRegistry([]))->getModule('a_module');
    }

    /**
     * @test
     */
    public function accessRegisteredModulesWork(): void
    {
        $aModule = $this->createModule('a_module', ['aliases' => ['a_old_module']]);
        $bModule = $this->createModule('b_module', ['parent' => 'a_module']);

        $registry = new ModuleRegistry([$aModule, $bModule]);

        self::assertTrue($registry->hasModule('a_module'));
        self::assertFalse($registry->hasModule('c_module'));
        self::assertEquals($aModule, $registry->getModule('a_module'));
        self::assertEquals($aModule, $registry->getModule('a_old_module'));
        self::assertEquals('a_module', $registry->getModule('b_module')->getParentIdentifier());
        self::assertEquals(['a_module' => $aModule, 'b_module' => $bModule], $registry->getModules());
        self::assertEquals(['a_old_module' => 'a_module'], $registry->getModuleAliases());
    }

    /**
     * @test
     */
    public function moduleAliasOverwriteStrategy(): void
    {
        $aModule = $this->createModule('a_module', ['aliases' => ['duplicate_alias']]);
        $bModule = $this->createModule('b_module', ['aliases' => ['duplicate_alias']]);

        $registry = new ModuleRegistry([$aModule, $bModule]);

        self::assertEquals($bModule, $registry->getModule('duplicate_alias'));
        self::assertEquals(['duplicate_alias' => 'b_module'], $registry->getModuleAliases());
    }

    /**
     * @test
     */
    public function addModuleAppliesSortingAndHierarchy(): void
    {
        $modules = $random = [
            'a' => $this->createModule('a', ['position' => ['top']]),
            'b' => $this->createModule('b', ['position' => ['after' => 'a']]),
            'b_a' => $this->createModule('b_a', ['parent' => 'b', 'position' => ['top']]),
            'b_a_a' => $this->createModule('b_a_a', ['parent' => 'b_a', 'position' => ['top']]),
            'b_a_b' => $this->createModule('b_a_b', ['parent' => 'b_a', 'position' => ['after' => 'b_a_a']]),
            'b_a_c' => $this->createModule('b_a_c', ['parent' => 'b_a', 'position' => ['before' => 'b_a_d']]),
            'b_a_c_a' => $this->createModule('b_a_c_a', ['parent' => 'b_a_c', 'position' => ['bottom']]),
            'b_a_c_b' => $this->createModule('b_a_c_b', ['parent' => 'b_a_c']),
            'b_a_d' => $this->createModule('b_a_d', ['parent' => 'b_a', 'position' => ['before' => 'b_a_e']]),
            'b_a_d_a' => $this->createModule('b_a_d_a', ['parent' => 'b_a_d', 'position' => ['top']]),
            'b_a_d_b' => $this->createModule('b_a_d_b', ['parent' => 'b_a_d']),
            'b_a_d_c' => $this->createModule('b_a_d_c', ['parent' => 'b_a_d', 'position' => ['after' => 'b_a_d_b']]),
            'b_a_e' => $this->createModule('b_a_e', ['parent' => 'b_a', 'position' => ['after' => '*']]),
            'b_b' => $this->createModule('b_b', ['parent' => 'b', 'position' => ['before' => 'b_c']]),
            'b_c' => $this->createModule('b_c', ['parent' => 'b', 'position' => ['bottom']]),
            'b_d' => $this->createModule('b_d', ['parent' => 'b', 'position' => ['after' => 'b_c']]),
            // @todo Shouldn't the explicit "position => bottom" enforce the bottom
            //       position over a module ("e") not defining the position at all?
            'c' => $this->createModule('c', ['position' => ['bottom']]),
            'd' => $this->createModule('d', ['position' => ['before' => 'e']]),
            'd_a' => $this->createModule('d_a', ['parent' => 'd', 'position' => ['before' => 'd_b']]),
            'd_b' => $this->createModule('d_b', ['parent' => 'd', 'position' => ['before' => '*']]),
            'd_c' => $this->createModule('d_c', ['parent' => 'd', 'position' => ['before' => 'd_d']]),
            'd_d' => $this->createModule('d_d', ['parent' => 'd']),
            'e' => $this->createModule('e'),
            'e_a' => $this->createModule('e_a', ['parent' => 'e', 'position' => ['before' => '*']]),
            'e_b' => $this->createModule('e_b', ['parent' => 'e', 'position' => ['after' => 'e_a']]),
            'e_c' => $this->createModule('e_c', ['parent' => 'e', 'position' => ['after' => '*']]),
            'e_e' => $this->createModule('e_e', ['parent' => 'e', 'position' => ['after' => 'invalid']]),
        ];

        // Add modules in random order to ensure the result does not depend on the input order
        shuffle($random);
        $registry = new ModuleRegistry($random);

        // Asser correct sorting (flat)
        self::assertEquals(array_keys($modules), array_keys($registry->getModules()));

        // Assert correct hierarchy
        self::assertEquals(['b_a', 'b_b', 'b_c', 'b_d'], array_keys($registry->getModule('b')->getSubModules()));
        self::assertEquals('b', $registry->getModule('b_a')->getParentIdentifier());
        self::assertTrue($registry->getModule('b_a')->getParentModule()->hasSubModule('b_a'));
        self::assertEquals('b', $registry->getModule('b_b')->getParentIdentifier());
        self::assertTrue($registry->getModule('b_b')->getParentModule()->hasSubModule('b_b'));
        self::assertEquals('b', $registry->getModule('b_c')->getParentIdentifier());
        self::assertTrue($registry->getModule('b_c')->getParentModule()->hasSubModule('b_c'));
        self::assertEquals('b', $registry->getModule('b_d')->getParentIdentifier());
        self::assertTrue($registry->getModule('b_d')->getParentModule()->hasSubModule('b_d'));

        self::assertEquals(['b_a_a', 'b_a_b', 'b_a_c', 'b_a_d', 'b_a_e'], array_keys($registry->getModule('b_a')->getSubModules()));
        self::assertEquals('b_a', $registry->getModule('b_a_a')->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_a')->getParentModule()->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_b')->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_a')->getParentModule()->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_c')->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_a')->getParentModule()->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_d')->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_a')->getParentModule()->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_e')->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_a')->getParentModule()->getParentIdentifier());

        self::assertEquals(['b_a_c_a', 'b_a_c_b'], array_keys($registry->getModule('b_a_c')->getSubModules()));
        self::assertEquals('b_a_c', $registry->getModule('b_a_c_a')->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_c_a')->getParentModule()->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_c_a')->getParentModule()->getParentModule()->getParentIdentifier());
        self::assertEquals('b_a_c', $registry->getModule('b_a_c_b')->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_c_b')->getParentModule()->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_c_b')->getParentModule()->getParentModule()->getParentIdentifier());

        self::assertEquals(['b_a_d_a', 'b_a_d_b', 'b_a_d_c'], array_keys($registry->getModule('b_a_d')->getSubModules()));
        self::assertEquals('b_a_d', $registry->getModule('b_a_d_a')->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_d_a')->getParentModule()->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_d_a')->getParentModule()->getParentModule()->getParentIdentifier());
        self::assertEquals('b_a_d', $registry->getModule('b_a_d_b')->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_d_b')->getParentModule()->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_d_b')->getParentModule()->getParentModule()->getParentIdentifier());
        self::assertEquals('b_a_d', $registry->getModule('b_a_d_c')->getParentIdentifier());
        self::assertEquals('b_a', $registry->getModule('b_a_d_c')->getParentModule()->getParentIdentifier());
        self::assertEquals('b', $registry->getModule('b_a_d_c')->getParentModule()->getParentModule()->getParentIdentifier());
    }

    /**
     * @test
     */
    public function keepsInputOrderWithoutPositionDefinition(): void
    {
        self::assertEquals(
            ['a', 'b', 'b_a', 'b_b', 'c'],
            array_keys((new ModuleRegistry([
                $this->createModule('a'),
                $this->createModule('b'),
                $this->createModule('b_a', ['parent' => 'b']),
                $this->createModule('b_b', ['parent' => 'b']),
                $this->createModule('c'),
            ]))->getModules())
        );
    }

    /**
     * @test
     */
    public function firstModuleDeclaringTopWillBeOnTop(): void
    {
        self::assertEquals(
            ['a', 'b', 'c', 'd', 'f', 'e'],
            array_keys((new ModuleRegistry([
                $this->createModule('f'),
                $this->createModule('c', ['position' => ['after' => '*']]),
                $this->createModule('d', ['position' => ['bottom']]),
                $this->createModule('a', ['position' => ['top']]),
                $this->createModule('b', ['position' => ['before' => '*']]),
                $this->createModule('e'),
            ]))->getModules())
        );
    }

    /**
     * @test
     */
    public function subModulesAndDependencyChainOverruleFirstLevelDependencies(): void
    {
        self::assertEquals(
            // @todo Shouldn't this better be: "a", "a_a", "a_a_a", "b", "c", "d", "e" ?
            ['a', 'a_a', 'a_a_a', 'b', 'd', 'c', 'e'],
            array_keys((new ModuleRegistry([
                $this->createModule('a'),
                $this->createModule('a_a', ['parent' => 'a']),
                $this->createModule('a_a_a', ['parent' => 'a_a']),
                $this->createModule('b', ['position' => ['after' => 'a']]),
                $this->createModule('c', ['position' => ['after' => 'a']]),
                $this->createModule('d', ['position' => ['after' => 'b']]),
                $this->createModule('e'),
            ]))->getModules())
        );
    }

    /**
     * @test
     */
    public function dependencyChainsAreRespected(): void
    {
        self::assertEquals(
            // @todo Shouldn't this better be: "a", "e", "c", "b", "d" ?
            ['a', 'b', 'd', 'c', 'e'],
            array_keys((new ModuleRegistry([
                $this->createModule('a'),
                $this->createModule('b', ['position' => ['after' => 'a']]),
                $this->createModule('c', ['position' => ['after' => 'a']]),
                $this->createModule('d', ['position' => ['after' => 'b']]),
                $this->createModule('e', ['position' => ['before' => 'c']]),
            ]))->getModules())
        );
    }

    protected function createModule($identifier, $configuration = []): ModuleInterface
    {
        return $this->moduleFactory->createModule(
            $identifier,
            $configuration
        );
    }
}
