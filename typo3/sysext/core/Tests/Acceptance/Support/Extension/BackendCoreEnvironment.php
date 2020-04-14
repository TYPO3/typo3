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
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\TestingFramework\Core\Acceptance\Extension\BackendEnvironment;

/**
 * Load various core extensions and styleguide and call styleguide generator
 */
class BackendCoreEnvironment extends BackendEnvironment
{
    /**
     * Load a list of core extensions and styleguide
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
        'testExtensionsToLoad' => [
            'typo3conf/ext/styleguide'
        ],
        'xmlDatabaseFixtures' => [
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_users.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_sessions.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_groups.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/sys_category.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/tx_extensionmanager_domain_model_extension.xml',
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/tx_extensionmanager_domain_model_repository.xml',
        ],
        'configurationToUseInTestInstance' => [
            'MAIL' => [
                'transport' => NullTransport::class
            ]
        ]
    ];

    /**
     * Generate styleguide data
     *
     * @param SuiteEvent $suiteEvent
     */
    public function bootstrapTypo3Environment(SuiteEvent $suiteEvent)
    {
        parent::bootstrapTypo3Environment($suiteEvent);
        // styleguide generator uses DataHandler for some parts. DataHandler needs an initialized BE user
        // with admin right and the live workspace.
        Bootstrap::initializeBackendUser();
        $GLOBALS['BE_USER']->user['admin'] = 1;
        $GLOBALS['BE_USER']->user['uid'] = 1;
        $GLOBALS['BE_USER']->workspace = 0;
        Bootstrap::initializeLanguageObject();

        $styleguideGenerator = new Generator();
        $styleguideGenerator->create();
    }
}
