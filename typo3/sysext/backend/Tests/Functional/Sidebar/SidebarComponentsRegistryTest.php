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

namespace TYPO3\CMS\Backend\Tests\Functional\Sidebar;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentContext;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentsRegistry;
use TYPO3\CMS\Backend\Tests\Functional\Sidebar\Fixtures\TestComponentA;
use TYPO3\CMS\Backend\Tests\Functional\Sidebar\Fixtures\TestComponentBBefore;
use TYPO3\CMS\Backend\Tests\Functional\Sidebar\Fixtures\TestComponentCAfter;
use TYPO3\CMS\Backend\Tests\Functional\Sidebar\Fixtures\TestComponentNoAccess;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SidebarComponentsRegistryTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * Create a registry with pre-ordered components.
     * This simulates what SidebarComponentsPass does at compile time.
     *
     * @param array<string, object> $components
     */
    private function createRegistry(array $components): SidebarComponentsRegistry
    {
        return new SidebarComponentsRegistry($components);
    }

    private function createContext(): SidebarComponentContext
    {
        $backendUser = $GLOBALS['BE_USER'];
        return new SidebarComponentContext(
            new ServerRequest('https://example.com/typo3/main'),
            $backendUser,
        );
    }

    #[Test]
    public function componentsAreStoredInOrder(): void
    {
        // Pre-ordered: B comes before A (as if ordered by DependencyOrderingService)
        $registry = $this->createRegistry([
            'test-component-b-before' => new TestComponentBBefore(),
            'test-component-a' => new TestComponentA(),
        ]);

        $components = $registry->getComponents();
        $keys = array_keys($components);

        self::assertEquals('test-component-b-before', $keys[0]);
        self::assertEquals('test-component-a', $keys[1]);
    }

    #[Test]
    public function componentsWithAfterDependencyAreStoredCorrectly(): void
    {
        // Pre-ordered: A comes before C (as if ordered by DependencyOrderingService)
        $registry = $this->createRegistry([
            'test-component-a' => new TestComponentA(),
            'test-component-c-after' => new TestComponentCAfter(),
        ]);

        $components = $registry->getComponents();
        $keys = array_keys($components);

        self::assertEquals('test-component-a', $keys[0]);
        self::assertEquals('test-component-c-after', $keys[1]);
    }

    #[Test]
    public function multipleComponentsAreProperlyStored(): void
    {
        // Pre-ordered: B, A, C (as if ordered by DependencyOrderingService)
        $registry = $this->createRegistry([
            'test-component-b-before' => new TestComponentBBefore(),
            'test-component-a' => new TestComponentA(),
            'test-component-c-after' => new TestComponentCAfter(),
        ]);

        $components = $registry->getComponents();
        $keys = array_keys($components);

        self::assertCount(3, $components);
        self::assertEquals('test-component-b-before', $keys[0]);
        self::assertEquals('test-component-a', $keys[1]);
        self::assertEquals('test-component-c-after', $keys[2]);
    }

    #[Test]
    public function hasComponentReturnsTrueForExistingComponent(): void
    {
        $registry = $this->createRegistry([
            'test-component-a' => new TestComponentA(),
        ]);

        self::assertTrue($registry->hasComponent('test-component-a'));
    }

    #[Test]
    public function hasComponentReturnsFalseForNonExistingComponent(): void
    {
        $registry = $this->createRegistry([
            'test-component-a' => new TestComponentA(),
        ]);

        self::assertFalse($registry->hasComponent('non-existing-component'));
    }

    #[Test]
    public function hasComponentReturnsFalseOnEmptyRegistry(): void
    {
        $registry = $this->createRegistry([]);

        self::assertFalse($registry->hasComponent('any-component'));
    }

    #[Test]
    public function getComponentReturnsCorrectInstance(): void
    {
        $componentA = new TestComponentA();
        $registry = $this->createRegistry([
            'test-component-a' => $componentA,
        ]);

        $retrieved = $registry->getComponent('test-component-a');
        self::assertSame($componentA, $retrieved);
    }

    #[Test]
    public function getComponentThrowsExceptionForNonExistingComponent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1765923035);
        $this->expectExceptionMessage('Sidebar component with identifier "non-existing-component" is not registered');

        $registry = $this->createRegistry([]);
        $registry->getComponent('non-existing-component');
    }

    #[Test]
    public function getComponentWorksWithMultipleComponents(): void
    {
        $componentA = new TestComponentA();
        $componentB = new TestComponentBBefore();

        $registry = $this->createRegistry([
            'test-component-a' => $componentA,
            'test-component-b' => $componentB,
        ]);

        self::assertSame($componentA, $registry->getComponent('test-component-a'));
        self::assertSame($componentB, $registry->getComponent('test-component-b'));
    }

    #[Test]
    public function checkAccessIsNotCalledDuringRegistration(): void
    {
        $componentNoAccess = new TestComponentNoAccess();
        $registry = $this->createRegistry([
            'test-component-no-access' => $componentNoAccess,
        ]);

        self::assertTrue($registry->hasComponent('test-component-no-access'), 'Components with checkAccess=false should still be registered');

        $component = $registry->getComponent('test-component-no-access');
        self::assertSame($componentNoAccess, $component);
        self::assertFalse($component->hasAccess($this->createContext()), 'checkAccess should still return false when called on component');
    }

    #[Test]
    public function emptyRegistryReturnsEmptyArray(): void
    {
        $registry = $this->createRegistry([]);

        self::assertEmpty($registry->getComponents());
    }

    #[Test]
    public function registryPreservesComponentOrder(): void
    {
        $registry = $this->createRegistry([
            'test-component-a' => new TestComponentA(),
            'test-component-no-access' => new TestComponentNoAccess(),
        ]);

        $components = $registry->getComponents();
        $keys = array_keys($components);

        self::assertEquals('test-component-a', $keys[0]);
        self::assertEquals('test-component-no-access', $keys[1]);
    }
}
