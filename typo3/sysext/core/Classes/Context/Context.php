<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Context;

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

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Contains the state of a current page request, to be used when reading
 * information of the current request in which configuration/context
 * it is used.
 *
 * Typically, the current main context is initialized very early within each entry-point application,
 * and is then modified overridden in e.g. PSR-15 middlewares (e.g. authentication, preview settings etc).
 *
 * For most use-cases, the current main context is fetched via GeneralUtility::makeInstance(Context::class),
 * however, if custom settings for a single use-case is necessary, it is recommended to clone the base context:
 *
 * $mainContext = GeneralUtility::makeInstance(Context::class);
 * $customContext = clone $mainContext;
 * $customContext->setAspect(GeneralUtility::makeInstance(VisibilityAspect::class, true, true, false))
 *
 * ... which in turn can be injected in the various places where TYPO3 uses contexts.
 *
 *
 * Classic aspect names to be used are:
 * - date (DateTimeAspect)
 * - workspace
 * - visibility
 * - frontend.user
 * - backend.user
 * - language
 */
class Context implements SingletonInterface
{
    /**
     * @var AspectInterface[]
     */
    protected $aspects = [];

    /**
     * Sets up the context with pre-defined aspects
     *
     * @param array|null $defaultAspects
     */
    public function __construct(array $defaultAspects = [])
    {
        foreach ($defaultAspects as $name => $defaultAspect) {
            if ($defaultAspect instanceof AspectInterface) {
                $this->aspects[$name] = $defaultAspect;
            }
        }
        // Ensure the default aspects are set, this is mostly necessary for tests to not set up everything
        if (!$this->hasAspect('date')) {
            $time = $GLOBALS['EXEC_TIME'] ?? time();
            $this->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $time)));
        }
        if (!$this->hasAspect('visibility')) {
            $this->setAspect('visibility', new VisibilityAspect());
        }
        if (!$this->hasAspect('backend.user')) {
            $this->setAspect('backend.user', new UserAspect());
        }
        if (!$this->hasAspect('frontend.user')) {
            $this->setAspect('frontend.user', new UserAspect());
        }
        if (!$this->hasAspect('workspace')) {
            $this->setAspect('workspace', new WorkspaceAspect());
        }
        if (!$this->hasAspect('language')) {
            $this->setAspect('language', new LanguageAspect());
        }
    }

    /**
     * Checks if an aspect exists in the context
     *
     * @param string $name
     * @return bool
     */
    public function hasAspect(string $name): bool
    {
        return isset($this->aspects[$name]);
    }

    /**
     * Returns an aspect, if it is set
     *
     * @param string $name
     * @return AspectInterface
     * @throws AspectNotFoundException
     */
    public function getAspect(string $name): AspectInterface
    {
        if (!$this->hasAspect($name)) {
            throw new AspectNotFoundException('No aspect named "' . $name . '" found.', 1527777641);
        }
        return $this->aspects[$name];
    }

    /**
     * Returns a property from the aspect, but only if the property is found.
     *
     * @param string $name
     * @param string $property
     * @param mixed $default
     * @return mixed|null
     * @throws AspectNotFoundException
     */
    public function getPropertyFromAspect(string $name, string $property, $default = null)
    {
        if (!$this->hasAspect($name)) {
            throw new AspectNotFoundException('No aspect named "' . $name . '" found.', 1527777868);
        }
        try {
            return $this->aspects[$name]->get($property);
        } catch (AspectPropertyNotFoundException $e) {
            return $default;
        }
    }

    /**
     * Sets an aspect, or overrides an existing aspect if an aspect is already set
     *
     * @param string $name
     * @param AspectInterface $aspect
     */
    public function setAspect(string $name, AspectInterface $aspect): void
    {
        $this->aspects[$name] = $aspect;
    }
}
