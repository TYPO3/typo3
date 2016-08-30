<?php
namespace TYPO3\CMS\Fluid\Core\Compiler;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Abstract Fluid Compiled template.
 *
 * INTERNAL!!
 */
abstract class AbstractCompiledTemplate implements \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface
{
    /**
     * @var array
     */
    protected $viewHelpersByPositionAndContext = [];

    // These tokens are replaced by the Backporter for implementing different behavior in TYPO3 v4
    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\Container
     */
    protected static $objectContainer;

    /**
     * @var string
     */
    protected static $defaultEncoding = null;

    /**
     * Public such that it is callable from within closures
     *
     * @param int $uniqueCounter
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @param string $viewHelperName
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
     * @internal
     */
    public function getViewHelper($uniqueCounter, \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext, $viewHelperName)
    {
        if (self::$objectContainer === null) {
            self::$objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
        }
        if (isset($this->viewHelpersByPositionAndContext[$uniqueCounter])) {
            if ($this->viewHelpersByPositionAndContext[$uniqueCounter]->contains($renderingContext)) {
                $viewHelper = $this->viewHelpersByPositionAndContext[$uniqueCounter][$renderingContext];
                $viewHelper->resetState();
                return $viewHelper;
            } else {
                $viewHelperInstance = self::$objectContainer->getInstance($viewHelperName);
                if ($viewHelperInstance instanceof \TYPO3\CMS\Core\SingletonInterface) {
                    $viewHelperInstance->resetState();
                }
                $this->viewHelpersByPositionAndContext[$uniqueCounter]->attach($renderingContext, $viewHelperInstance);
                return $viewHelperInstance;
            }
        } else {
            $this->viewHelpersByPositionAndContext[$uniqueCounter] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class);
            $viewHelperInstance = self::$objectContainer->getInstance($viewHelperName);
            if ($viewHelperInstance instanceof \TYPO3\CMS\Core\SingletonInterface) {
                $viewHelperInstance->resetState();
            }
            $this->viewHelpersByPositionAndContext[$uniqueCounter]->attach($renderingContext, $viewHelperInstance);
            return $viewHelperInstance;
        }
    }

    /**
     * @return bool
     */
    public function isCompilable()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isCompiled()
    {
        return true;
    }

    /**
     * @return string
     * @internal
     */
    public static function resolveDefaultEncoding()
    {
        if (static::$defaultEncoding === null) {
            static::$defaultEncoding = 'UTF-8';
        }
        return static::$defaultEncoding;
    }
}
