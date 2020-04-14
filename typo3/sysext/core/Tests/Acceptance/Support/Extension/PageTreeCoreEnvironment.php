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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Extension;

use Codeception\Event\SuiteEvent;
use Symfony\Component\Mailer\Transport\NullTransport;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Acceptance\Extension\BackendEnvironment;

/**
 * Load various core extensions and styleguide and call styleguide generator
 */
class PageTreeCoreEnvironment extends BackendEnvironment
{
    /**
     * Set the list of core extensions and fixtures
     *
     * @var array
     */
    protected $localConfig = [
        'coreExtensionsToLoad' => [
            'core',
            'beuser',
            'extbase',
            'fluid',
            'filelist',
            'extensionmanager',
            'setup',
            'backend',
            'about',
            'belog',
            'install',
            'frontend',
            'recordlist',
            'redirects',
            'reports',
            'sys_note',
            'scheduler',
            'tstemplate',
        ],
        'testExtensionsToLoad' => [],
        'xmlDatabaseFixtures' => [
            'typo3/sysext/core/Tests/Acceptance/Fixtures/pages.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_users.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_sessions.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_groups.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/sys_category.xml',
        ],
        'configurationToUseInTestInstance' => [
            'MAIL' => [
                'transport' => NullTransport::class
            ]
        ]
    ];

    /**
     * Generate Page Tree data
     *
     * @param SuiteEvent $suiteEvent
     */
    public function bootstrapTypo3Environment(SuiteEvent $suiteEvent)
    {
        parent::bootstrapTypo3Environment($suiteEvent);
        Bootstrap::initializeBackendUser();
        $GLOBALS['BE_USER']->user['admin'] = 1;
        $GLOBALS['BE_USER']->user['uid'] = 1;
        $GLOBALS['BE_USER']->workspace = 0;
        Bootstrap::initializeLanguageObject();
    }
}
