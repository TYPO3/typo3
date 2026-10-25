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

namespace TYPO3\CMS\Backend\Tests\Functional\Module;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A module access gate of an extension replacing the Core gate for an
 * access value of TYPO3 Core keeps deciding that access value.
 */
final class ModuleAccessGateOverrideTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/test_module_access_gate_override',
    ];

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    #[Test]
    #[IgnoreDeprecations]
    public function replacedCoreGateKeepsRestrictingItsAccessValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] = [];
        $moduleFactory = $this->get(ModuleFactory::class);
        $moduleProvider = new ModuleProvider(
            new ModuleRegistry([
                $moduleFactory->createModule('admin_module', ['access' => 'admin']),
                $moduleFactory->createModule('user_module', ['access' => 'user']),
            ]),
            $this->get(AccessDecisionManagerInterface::class),
            $this->get(LegacyUserPrincipalFactory::class),
        );

        self::assertTrue($moduleProvider->accessGranted('admin_module', $this->createAdministrator(1)));
        // Granted by the gate of TYPO3 Core, denied by the replacing gate
        self::assertFalse($moduleProvider->accessGranted('admin_module', $this->createAdministrator(2)));
        // Other access values are still decided by TYPO3 Core
        self::assertTrue($moduleProvider->accessGranted('user_module', $this->createAdministrator(2)));
    }

    private function createAdministrator(int $uid): BackendUserAuthentication
    {
        $user = new BackendUserAuthentication();
        $user->user = ['uid' => $uid, 'username' => 'admin' . $uid, 'admin' => 1];
        $user->workspace = 0;
        $GLOBALS['BE_USER'] = $user;
        return $user;
    }
}
