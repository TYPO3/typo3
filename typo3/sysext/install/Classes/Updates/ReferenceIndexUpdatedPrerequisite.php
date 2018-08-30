<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ReferenceIndexUpdatedPrerequisite implements Prerequisite
{
    private $referenceIndex;

    public function __construct()
    {
        if (!($GLOBALS['BE_USER'] instanceof BackendUserAuthentication)) {
            Bootstrap::initializeBackendAuthentication();
        }
        $this->referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
    }

    public function getName(): string
    {
        return 'Reference Index Up-to-Date';
    }

    public function ensure(): void
    {
        $this->referenceIndex->enableRuntimeCache();
        $this->referenceIndex->updateIndex(false, false);
    }

    public function met(): bool
    {
        $this->referenceIndex->enableRuntimeCache();
        $result = $this->referenceIndex->updateIndex(true, false);
        return $result['errorCount'] === 0;
    }
}
