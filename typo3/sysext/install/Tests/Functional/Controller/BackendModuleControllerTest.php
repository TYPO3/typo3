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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\Controller\BackendModuleController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleControllerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected int $oldErrorReporting;

    public function setUp(): void
    {
        parent::setUp();
        // @todo: The install tool session handler is hardcoded within install tool.
        //        FileSessionHandler calls session_save_path() which basically can
        //        be done exactly once per process. After that it throws warnings.
        //        We can't mitigate this until the install tool session handler is
        //        refactored and enables us to add a mock here.
        //        To not disable the tests, we for now suppress warnings in this test.
        //        Even though the phpunit error handler is before the native PHP handler,
        //        phpunit currently ignores the warning as well: if (!($errorNumber & error_reporting())) {
        $this->oldErrorReporting = error_reporting(E_ALL & ~E_WARNING);
    }

    public function tearDown(): void
    {
        error_reporting($this->oldErrorReporting);
    }

    /**
     * @test
     * @dataProvider environmentContextIsRespectedTestDataProvider
     *
     * @param string $module
     */
    public function environmentContextIsRespectedTest(string $module): void
    {
        $subject = new BackendModuleController(
            $this->get(UriBuilder::class),
            $this->get(ModuleTemplateFactory::class)
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

        // Authorized redirect to the admin tool is performed
        // sudo mode is not required (due to development context)
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        // using anonymous user session, which is fine for this test case
        $GLOBALS['BE_USER']->initializeUserSessionManager();
        $GLOBALS['BE_USER']->user = ['uid' => 1];

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
