<?php

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

namespace TYPO3\CMS\Core\Resource\TextExtraction;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TextExtractorRegistry
 */
class TextExtractorRegistry implements SingletonInterface
{
    /**
     * Registered text extractor class names
     *
     * @var array
     */
    protected $textExtractorClasses = [];

    /**
     * Instance cache for text extractor classes
     *
     * @var TextExtractorInterface[]
     */
    protected $instances = [];

    /**
     * Returns an instance of this class
     *
     * @return TextExtractorRegistry
     * @deprecated will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.
     */
    public static function getInstance()
    {
        trigger_error(__CLASS__ . '::getInstance() will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(__CLASS__);
    }

    /**
     * Allows to register a text extractor class
     *
     * @param string $className
     * @throws \InvalidArgumentException
     */
    public function registerTextExtractor($className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('The class "' . $className . '" you are trying to register is not available', 1422906893);
        }

        if (!in_array(TextExtractorInterface::class, class_implements($className) ?: [], true)) {
            throw new \InvalidArgumentException($className . ' must implement interface' . TextExtractorInterface::class, 1422771427);
        }

        $this->textExtractorClasses[] = $className;
    }

    /**
     * Get all registered text extractor instances
     *
     * @return TextExtractorInterface[]
     */
    public function getTextExtractorInstances()
    {
        if (empty($this->instances) && !empty($this->textExtractorClasses)) {
            foreach ($this->textExtractorClasses as $className) {
                $object = $this->createTextExtractorInstance($className);
                $this->instances[] = $object;
            }
        }

        return $this->instances;
    }

    /**
     * Create an instance of a certain text extractor class
     *
     * @param string $className
     * @return TextExtractorInterface
     */
    protected function createTextExtractorInstance($className)
    {
        return GeneralUtility::makeInstance($className);
    }

    /**
     * Checks whether any registered text extractor can deal with a given file
     * and returns it.
     *
     * @param FileInterface $file
     * @return TextExtractorInterface|null
     */
    public function getTextExtractor(FileInterface $file)
    {
        foreach ($this->getTextExtractorInstances() as $textExtractor) {
            if ($textExtractor->canExtractText($file)) {
                return $textExtractor;
            }
        }

        return null;
    }
}
