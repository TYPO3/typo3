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

namespace TYPO3Tests\TestStartingpoint\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3Tests\TestStartingpoint\Domain\Repository\DummyRepository;

/**
 * White-box probe: renders the effective persistence.storagePid of the
 * framework configuration as seen by an Extbase plugin, so functional
 * tests can assert the "Startingpoint" override verbatim.
 */
final class StoragePidProbeController extends ActionController
{
    public function __construct(
        private DummyRepository $dummyRepository,
    ) {}

    public function frameworkAction(): ResponseInterface
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        $storagePid = (string)($frameworkConfiguration['persistence']['storagePid'] ?? '');
        return $this->htmlResponse('storagePidProbe:[' . $storagePid . ']');
    }

    public function querysettingsAction(): ResponseInterface
    {
        return $this->htmlResponse(
            'storagePidProbe:querysettings['
            . implode(',', $this->dummyRepository->getQueryStoragePageIds())
            . ']'
        );
    }
}
