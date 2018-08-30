<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class PrerequisiteCollection
{
    protected $prerequisites;

    public function __construct()
    {
        $this->prerequisites = new \SplObjectStorage();
    }

    public function addPrerequisite(string $prerequisiteClass): void
    {
        if (class_exists($prerequisiteClass) && is_a($prerequisiteClass, Prerequisite::class, true)) {
            $instance = GeneralUtility::makeInstance($prerequisiteClass);
            if (!$this->prerequisites->contains($instance)) {
                $this->prerequisites->attach($instance);
            }
        }
    }

    public function getPrerequisites(): \SplObjectStorage
    {
        return $this->prerequisites;
    }
}
