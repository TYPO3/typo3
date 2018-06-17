<?php
namespace TYPO3\CMS\Core\Resource\Index;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registry for MetaData extraction Services
 */
class ExtractorRegistry implements SingletonInterface
{
    /**
     * Registered ClassNames
     * @var array
     */
    protected $extractors = [];

    /**
     * Instance Cache for Extractors
     *
     * @var ExtractorInterface[]
     */
    protected $instances;

    /**
     * Returns an instance of this class
     *
     * @return ExtractorRegistry
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Allows to register MetaData extraction to the FAL Indexer
     *
     * @param string $className
     * @throws \InvalidArgumentException
     */
    public function registerExtractionService($className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('The class "' . $className . '" you are registering is not available', 1422705270);
        }
        if (!in_array(ExtractorInterface::class, class_implements($className))) {
            throw new \InvalidArgumentException('The extractor needs to implement the ExtractorInterface', 1422705271);
        }
        $this->extractors[] = $className;
    }

    /**
     * Get all registered extractors
     *
     * @return ExtractorInterface[]
     */
    public function getExtractors()
    {
        if ($this->instances === null) {
            $this->instances = [];

            $extractors = array_reverse($this->extractors);
            foreach ($extractors as $className) {
                /** @var ExtractorInterface $object */
                $object = $this->createExtractorInstance($className);
                $this->instances[] = $object;
            }

            if (count($this->instances) > 1) {
                usort($this->instances, [$this, 'compareExtractorPriority']);
            }
        }
        return $this->instances;
    }

    /**
     * Get Extractors which work for a special driver
     *
     * @param string $driverType
     * @return ExtractorInterface[]
     */
    public function getExtractorsWithDriverSupport($driverType)
    {
        $allExtractors = $this->getExtractors();

        $filteredExtractors = [];
        foreach ($allExtractors as $priority => $extractorObject) {
            if (empty($extractorObject->getDriverRestrictions())) {
                $filteredExtractors[$priority] = $extractorObject;
            } elseif (in_array($driverType, $extractorObject->getDriverRestrictions())) {
                $filteredExtractors[$priority] = $extractorObject;
            }
        }
        return $filteredExtractors;
    }

    /**
     * Compare the priority of two Extractor classes.
     * Is used for sorting array of Extractor instances by priority.
     * We want the result to be ordered from high to low so a higher
     * priority comes before a lower.
     *
     * @param ExtractorInterface $extractorA
     * @param ExtractorInterface $extractorB
     * @return int -1 a > b, 0 a == b, 1 a < b
     */
    protected function compareExtractorPriority(ExtractorInterface $extractorA, ExtractorInterface $extractorB)
    {
        return $extractorB->getPriority() - $extractorA->getPriority();
    }

    /**
     * Create an instance of a Metadata Extractor
     *
     * @param string $className
     * @return ExtractorInterface
     */
    protected function createExtractorInstance($className)
    {
        return GeneralUtility::makeInstance($className);
    }
}
