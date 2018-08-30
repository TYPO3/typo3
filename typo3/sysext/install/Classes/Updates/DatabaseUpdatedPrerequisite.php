<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Install\Service\UpgradeWizardsService;

class DatabaseUpdatedPrerequisite implements Prerequisite
{
    protected $upgradeWizardsService;

    public function __construct()
    {
        $this->upgradeWizardsService = new UpgradeWizardsService();
    }

    public function getName(): string
    {
        return 'Database Up-to-Date';
    }

    public function ensure(): void
    {
        $adds = $this->upgradeWizardsService->getBlockingDatabaseAdds();

        if (count($adds) > 0) {
            $this->upgradeWizardsService->addMissingTablesAndFields();
        }
    }

    public function met(): bool
    {
        $adds = $this->upgradeWizardsService->getBlockingDatabaseAdds();
        return $adds === 0;
    }

    public function getIdentifier(): string
    {
        return 'databaseUpdatePrerequisite';
    }
}
