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

use Codeception\Events;
use Codeception\Extension;

/**
 * @internal Used by core, do not use in extensions, may vanish later.
 */
final class ApplicationComposerEnvironment extends Extension
{
    /**
     * @var array Default configuration values
     */
    protected array $config = [
        'typo3InstancePath' => 'typo3temp/var/tests/acceptance-composer',
    ];

    public static $events = [
        Events::SUITE_BEFORE => 'bootstrapTypo3Environment',
    ];

    public function bootstrapTypo3Environment(): void
    {
        // @todo: ugly workaround for InstallTool/AbstractCest.php
        $root = realpath(__DIR__ . '/../../../../../../../' . $this->config['typo3InstancePath']);
        chdir($root);
        putenv('TYPO3_ACCEPTANCE_PATH_WEB=' . $root . '/public');
        putenv('TYPO3_ACCEPTANCE_PATH_VAR=' . $root . '/var');
        putenv('TYPO3_ACCEPTANCE_PATH_CONFIG=' . $root . '/config');
        putenv('TYPO3_ACCEPTANCE_INSTALLTOOL_PW_PRESET=1');
    }
}
