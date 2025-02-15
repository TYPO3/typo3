<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Tasks;

use MyVendor\MyExtension\BusinessLogic;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

final class MyTask extends AbstractTask
{
    public function execute(): bool
    {
        # Dependency injection cannot be used in scheduler tasks
        $businessLogic = GeneralUtility::makeInstance(BusinessLogic::class);
        return $businessLogic->run('arg1', 'arg2', '…');
    }
}
