<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Context;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
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
 * ```
 * $mainContext = GeneralUtility::makeInstance(Context::class);
 * $customContext = clone $mainContext;
 * $customContext->setAspect(GeneralUtility::makeInstance(VisibilityAspect::class, true, true, false))
 * ```
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
 * - frontend.preview [if EXT:frontend is loaded]
 */
class Context implements SingletonInterface
{
    /**
     * @var AspectInterface[]
     */
    protected array $aspects = [];

    /**
     * Checks if an aspect exists in the context
     */
    public function hasAspect(string $name): bool
    {
        return match ($name) {
            'date', 'visibility', 'backend.user', 'frontend.user', 'workspace', 'language' => true,
            default => isset($this->aspects[$name]),
        };
    }

    /**
     * Returns an aspect, if it is set
     *
     * @throws AspectNotFoundException
     * @return ($name is 'date' ? DateTimeAspect
     *         : ($name is 'visibility' ? VisibilityAspect
     *         : ($name is 'backend.user' ? UserAspect
     *         : ($name is 'frontend.user' ? UserAspect
     *         : ($name is 'workspace' ? WorkspaceAspect
     *         : ($name is 'language' ? LanguageAspect : AspectInterface))))))
     */
    public function getAspect(string $name): AspectInterface
    {
        if (!isset($this->aspects[$name])) {
            // Ensure the default aspects are available, this is mostly necessary for tests to not set up everything
            switch ($name) {
                case 'date':
                    $this->setAspect(
                        'date',
                        new DateTimeAspect(
                            DateTimeFactory::createFromTimestamp($GLOBALS['EXEC_TIME'])
                        )
                    );
                    break;
                case 'visibility':
                    $this->setAspect('visibility', new VisibilityAspect());
                    break;
                case 'backend.user':
                    $this->setAspect('backend.user', new UserAspect());
                    break;
                case 'frontend.user':
                    $this->setAspect('frontend.user', new UserAspect());
                    break;
                case 'workspace':
                    $this->setAspect('workspace', new WorkspaceAspect());
                    break;
                case 'language':
                    $this->setAspect('language', new LanguageAspect());
                    break;
                default:
                    throw new AspectNotFoundException('No aspect named "' . $name . '" found.', 1527777641);
            }
        }
        return $this->aspects[$name];
    }

    /**
     * Returns a property from the aspect, but only if the property is found.
     *
     * @throws AspectNotFoundException
     */
    public function getPropertyFromAspect(string $name, string $property, mixed $default = null): mixed
    {
        if (!$this->hasAspect($name)) {
            throw new AspectNotFoundException('No aspect named "' . $name . '" found.', 1527777868);
        }
        try {
            return $this->getAspect($name)->get($property);
        } catch (AspectPropertyNotFoundException) {
            return $default;
        }
    }

    /**
     * Sets an aspect, or overrides an existing aspect if an aspect is already set
     */
    public function setAspect(string $name, AspectInterface $aspect): void
    {
        $this->aspects[$name] = $aspect;
    }

    /**
     * @internal Using this method is a sign of a technical debt. It is used by RedirectService,
     *           but may vanish any time when this is fixed, and thus internal.
     *           In general, Context aspects should never have to be unset.
     *           When a middleware has to use this method, it is either located
     *           at the wrong position in the chain, or has some other dependency issue.
     */
    public function unsetAspect(string $name): void
    {
        unset($this->aspects[$name]);
    }
}
