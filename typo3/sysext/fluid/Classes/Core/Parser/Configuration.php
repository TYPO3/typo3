<?php
namespace TYPO3\CMS\Fluid\Core\Parser;

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
 * The parser configuration. Contains all configuration needed to configure
 * the building of a SyntaxTree.
 */
class Configuration
{
    /**
     * Generic interceptors registered with the configuration.
     *
     * @var array<\TYPO3\CMS\Extbase\Persistence\ObjectStorage>
     */
    protected $interceptors = [];

    /**
     * Adds an interceptor to apply to values coming from object accessors.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface $interceptor
     * @return void
     */
    public function addInterceptor(\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface $interceptor)
    {
        foreach ($interceptor->getInterceptionPoints() as $interceptionPoint) {
            if (!isset($this->interceptors[$interceptionPoint])) {
                $this->interceptors[$interceptionPoint] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class);
            }
            if (!$this->interceptors[$interceptionPoint]->contains($interceptor)) {
                $this->interceptors[$interceptionPoint]->attach($interceptor);
            }
        }
    }

    /**
     * Returns all interceptors for a given Interception Point.
     *
     * @param int $interceptionPoint one of the \TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_* constants,
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface>
     */
    public function getInterceptors($interceptionPoint)
    {
        if (isset($this->interceptors[$interceptionPoint]) && $this->interceptors[$interceptionPoint] instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
            return $this->interceptors[$interceptionPoint];
        }
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class);
    }
}
