<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Updates;

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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ReferenceIndex Prerequisite
 *
 * Defines that the reference index needs to be up-to-date before an upgrade wizard may be run
 *
 * @internal
 */
class ReferenceIndexUpdatedPrerequisite implements PrerequisiteInterface, ChattyInterface
{
    /**
     * @var object|\TYPO3\CMS\Core\Database\ReferenceIndex
     */
    private $referenceIndex;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * ReferenceIndexUpdatedPrerequisite constructor
     */
    public function __construct()
    {
        $this->referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return 'Reference Index Up-to-Date';
    }

    /**
     * Updates the reference index
     *
     * @return bool
     */
    public function ensure(): bool
    {
        $this->referenceIndex->enableRuntimeCache();
        ob_clean();
        ob_start();
        $result = $this->referenceIndex->updateIndex(false, true);
        $output = ob_get_clean();
        $this->output->write($output);
        return $result[2] === 0;
    }

    /**
     * Checks whether there are reference index updates to be done
     *
     * @return bool
     */
    public function isFulfilled(): bool
    {
        $this->referenceIndex->enableRuntimeCache();
        $result = $this->referenceIndex->updateIndex(true, false);
        return $result[2] === 0;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }
}
