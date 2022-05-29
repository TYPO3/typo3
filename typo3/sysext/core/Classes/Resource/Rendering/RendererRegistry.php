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

namespace TYPO3\CMS\Core\Resource\Rendering;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RendererRegistry
 */
class RendererRegistry implements SingletonInterface
{
    /**
     * Registered class names
     *
     * @var array
     */
    protected $classNames = [];

    /**
     * Instance cache for renderer classes
     *
     * @var FileRendererInterface[]
     */
    protected $instances;

    /**
     * Returns an instance of this class
     *
     * @return RendererRegistry
     * @deprecated will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.
     */
    public static function getInstance()
    {
        trigger_error(__CLASS__ . '::getInstance() will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Allows to register a Renderer class
     *
     * @param string $className
     * @throws \InvalidArgumentException
     */
    public function registerRendererClass($className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('The class "' . $className . '" you are trying to register is not available', 1411840171);
        }
        if (!in_array(FileRendererInterface::class, class_implements($className) ?: [], true)) {
            throw new \InvalidArgumentException('The renderer needs to implement the FileRendererInterface', 1411840172);
        }
        $this->classNames[] = $className;
    }

    /**
     * Get all registered renderer instances
     *
     * @return FileRendererInterface[]
     */
    public function getRendererInstances()
    {
        if ($this->instances === null) {
            $this->instances = [];

            // As the result is in reverse order we need to reverse
            // the array before processing to keep the items with same
            // priority in the same order as they were added to the registry.
            $classNames = array_reverse($this->classNames);
            foreach ($classNames as $className) {
                $object = $this->createRendererInstance($className);
                $this->instances[] = $object;
            }

            if (count($this->instances) > 1) {
                usort($this->instances, [$this, 'compareRendererPriority']);
            }
        }
        return $this->instances;
    }

    /**
     * Create an instance of a certain renderer class
     *
     * @param string $className
     * @return FileRendererInterface
     */
    protected function createRendererInstance($className)
    {
        return GeneralUtility::makeInstance($className);
    }

    /**
     * Compare the priority of two renderer classes
     * Is used for sorting array of Renderer instances by priority
     * We want the result to be ordered from high to low so a higher
     * priority comes before a lower.
     *
     * @param FileRendererInterface $rendererA
     * @param FileRendererInterface $rendererB
     * @return int -1 a > b, 0 a == b, 1 a < b
     */
    protected function compareRendererPriority(FileRendererInterface $rendererA, FileRendererInterface $rendererB)
    {
        return $rendererB->getPriority() - $rendererA->getPriority();
    }

    /**
     * Get matching renderer with highest priority
     *
     * @param FileInterface $file
     * @return FileRendererInterface|null
     */
    public function getRenderer(FileInterface $file)
    {
        $matchingFileRenderer = null;

        foreach ($this->getRendererInstances() as $fileRenderer) {
            if ($fileRenderer->canRender($file)) {
                $matchingFileRenderer = $fileRenderer;
                break;
            }
        }
        return $matchingFileRenderer;
    }
}
