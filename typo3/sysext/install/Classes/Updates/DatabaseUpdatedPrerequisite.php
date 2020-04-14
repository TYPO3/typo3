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

namespace TYPO3\CMS\Install\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;

/**
 * Prerequisite for upgrade wizards to ensure the database is up-to-date
 *
 * @internal
 */
class DatabaseUpdatedPrerequisite implements PrerequisiteInterface, ChattyInterface
{
    /**
     * @var UpgradeWizardsService
     */
    protected $upgradeWizardsService;
    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct()
    {
        $this->upgradeWizardsService = new UpgradeWizardsService();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'Database Up-to-Date';
    }

    public function ensure(): bool
    {
        $adds = $this->upgradeWizardsService->getBlockingDatabaseAdds();
        $result = null;
        if (count($adds) > 0) {
            $this->output->writeln('Performing ' . count($adds) . ' database operations.');
            $result = $this->upgradeWizardsService->addMissingTablesAndFields();
        }
        return $result === null;
    }

    /**
     * @return bool
     */
    public function isFulfilled(): bool
    {
        $adds = $this->upgradeWizardsService->getBlockingDatabaseAdds();
        return count($adds) === 0;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }
}
