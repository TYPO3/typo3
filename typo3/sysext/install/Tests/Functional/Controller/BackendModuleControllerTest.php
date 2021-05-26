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

namespace TYPO3\CMS\Install\Tests\Functional\Controller;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\Controller\BackendModuleController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleControllerTest extends FunctionalTestCase
{
    /**
     * @test
     * @dataProvider environmentContextIsRespectedTestDataProvider
     *
     * @param string $module
     */
    public function environmentContextIsRespectedTest(string $module): void
    {
        $subject = new BackendModuleController(
            $this->getContainer()->get(UriBuilder::class),
            $this->getContainer()->get(ModuleTemplateFactory::class)
        );
        $action = $module . 'Action';

        self::assertIsCallable([$subject, $action]);

        // Ensure we are not in development context
        self::assertFalse(Environment::getContext()->isDevelopment());

        // Sudo mode is required
        self::assertEquals(403, $subject->{$action}()->getStatusCode());

        // Initialize environment with development context
        Environment::initialize(
            new ApplicationContext('Development'),
            Environment::isComposerMode(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        // Authorized redirect to the install tool is performed, sudo mode is not required
        $response = $subject->{$action}();
        self::assertEquals(303, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('location'));
        self::assertStringContainsString(
            'typo3/install.php?install[controller]=' . $module . '&install[context]=backend',
            $response->getHeaderLine('location')
        );
    }

    public function environmentContextIsRespectedTestDataProvider(): \Generator
    {
        yield 'maintenance module' => ['maintenance'];
        yield 'settings module' => ['settings'];
        yield 'upgrade module' => ['upgrade'];
        yield 'environment module' => ['environment'];
    }
}
