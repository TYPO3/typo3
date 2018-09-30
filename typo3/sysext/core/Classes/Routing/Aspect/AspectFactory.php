<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Aspect;

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

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
     * AspectFactory constructor.
     */
    public function __construct()
    {
        $this->availableAspects = $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects'] ?? [];
    }

    /**
     * Create aspects from the given settings.
     *
     * @param array $aspects
     * @param SiteLanguage $language
     * @return AspectInterface[]
     */
    public function createAspects(array $aspects, SiteLanguage $language): array
    {
        $aspects = array_map(
            function ($settings) use ($language) {
                $type = (string)($settings['type'] ?? '');
                return $this->create($type, $settings, $language);
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
     * @param SiteLanguage $language
     * @return AspectInterface
     * @throws \InvalidArgumentException
     * @throws \OutOfRangeException
     */
    protected function create(string $type, array $settings, SiteLanguage $language): AspectInterface
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
        return $this->enrich($aspect, $language);
    }

    /**
     * Checks for the language aware trait, and adds the site language.
     *
     * @param AspectInterface $aspect
     * @param SiteLanguage $language
     * @return AspectInterface|mixed
     */
    protected function enrich(AspectInterface $aspect, SiteLanguage $language): AspectInterface
    {
        if (in_array(SiteLanguageAwareTrait::class, class_uses($aspect), true)) {
            /** @var $aspect SiteLanguageAwareTrait */
            $aspect->setSiteLanguage($language);
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
