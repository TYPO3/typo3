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

namespace TYPO3\CMS\Core\Routing\Aspect;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\ContextAwareInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteAwareInterface;
use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;
use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory for creating aspects
 */
class AspectFactory
{
    /**
     * @var array
     */
    protected $availableAspects;

    /**
     * @var Context
     */
    protected $context;

    /**
     * AspectFactory constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->availableAspects = $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects'] ?? [];
    }

    /**
     * Create aspects from the given settings.
     *
     * @param array $aspects
     * @param SiteLanguage $language
     * @param Site $site
     * @return AspectInterface[]
     */
    public function createAspects(array $aspects, SiteLanguage $language, Site $site): array
    {
        $aspects = array_map(
            function ($settings) use ($language, $site) {
                $type = (string)($settings['type'] ?? '');
                $aspect = $this->create($type, $settings);
                return $this->enrich($aspect, $language, $site);
            },
            $aspects
        );
        uasort($aspects, [$this, 'sortAspects']);
        return $aspects;
    }

    /**
     * Creates an aspect
     *
     * @param string $type
     * @param array $settings
     * @return AspectInterface
     * @throws \InvalidArgumentException
     * @throws \OutOfRangeException
     */
    protected function create(string $type, array $settings): AspectInterface
    {
        if (empty($type)) {
            throw new \InvalidArgumentException(
                'Aspect type cannot be empty',
                1538079481
            );
        }
        if (!isset($this->availableAspects[$type])) {
            throw new \OutOfRangeException(
                sprintf('No aspect found for %s', $type),
                1538079482
            );
        }
        unset($settings['type']);
        $className = $this->availableAspects[$type];
        /** @var AspectInterface $aspect */
        $aspect = GeneralUtility::makeInstance($className, $settings);
        return $aspect;
    }

    /**
     * Checks for the language aware trait, and adds the site language.
     *
     * @param AspectInterface $aspect
     * @param SiteLanguage $language
     * @param Site $site
     * @return AspectInterface
     */
    protected function enrich(AspectInterface $aspect, SiteLanguage $language, Site $site): AspectInterface
    {
        // the check for the trait is @deprecated and can be removed at any time after TYPO3 v11
        if ($aspect instanceof SiteLanguageAwareInterface || in_array(SiteLanguageAwareTrait::class, class_uses($aspect) ?: [], true)) {
            /** @var AspectInterface|SiteLanguageAwareInterface $aspect */
            $aspect->setSiteLanguage($language);
        }
        if ($aspect instanceof SiteAwareInterface) {
            $aspect->setSite($site);
        }
        if ($aspect instanceof ContextAwareInterface) {
            $aspect->setContext($this->context);
        }
        return $aspect;
    }

    /**
     * Sorts aspects with putting persisted aspects to the end, thus
     * non-persisted aspects can be executed earlier without invoking database.
     *
     * @param AspectInterface $first
     * @param AspectInterface $second
     * @return int
     */
    protected function sortAspects(AspectInterface $first, AspectInterface $second): int
    {
        // when first is persisted, move it to the end (>0)
        $first = $first instanceof PersistedMappableAspectInterface ? 1 : 0;
        // when second is persisted, move it to the beginning (<0)
        $second = $second instanceof PersistedMappableAspectInterface ? -1 : 0;
        // 0 + 0 =  0 - both are non-persisted
        // 1 - 1 =  0 - both are persisted
        // 1 + 0 =  1 - only first is persisted
        // 0 - 1 = -1 - only second is persisted
        return $first + $second;
    }
}
