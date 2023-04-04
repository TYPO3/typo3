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
use TYPO3\CMS\Install\Service\DatabaseUpgradeWizardsService;

/**
 * Prerequisite for upgrade wizards to ensure the database is up-to-date
 *
 * @internal
 */
class DatabaseUpdatedPrerequisite implements PrerequisiteInterface, ChattyInterface
{
    protected DatabaseUpgradeWizardsService $databaseUpgradeWizardsService;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct()
    {
        $this->databaseUpgradeWizardsService = new DatabaseUpgradeWizardsService();
    }

    public function getTitle(): string
    {
        return 'Database Up-to-Date';
    }

    public function ensure(): bool
    {
        $adds = $this->databaseUpgradeWizardsService->getBlockingDatabaseAdds();
        // Nothing to add, early return
        if ($adds === []) {
            return true;
        }

        $this->output->writeln('Performing ' . count($adds) . ' database operations.');
        // remove potentially empty error messages
        $errorMessages = array_filter($this->databaseUpgradeWizardsService->addMissingTablesAndFields());

        return $errorMessages === [];
    }

    public function isFulfilled(): bool
    {
        $adds = $this->databaseUpgradeWizardsService->getBlockingDatabaseAdds();
        return count($adds) === 0;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }
}
