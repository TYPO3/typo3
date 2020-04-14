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

use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;
use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;

/**
 * Mapper for having a static list of mapping them to value properties.
 *
 * routeEnhancers:
 *   MyBlogExample:
 *     type: Extbase
 *     extension: BlogExample
 *     plugin: Pi1
 *     routes:
 *       - { routePath: '/archive/{year}', _controller: 'Blog::archive' }
 *     defaultController: 'Blog::list'
 *     aspects:
 *       year:
 *         type: StaticValueMapper
 *         map:
 *           2k17: '2017'
 *           2k18: '2018'
 *           next: '2019'
 *         # (optional)
 *         localeMap:
 *           - locale: 'en_US.*|en_GB.*'
 *             map:
 *               twenty-seventeen: '2017'
 *               twenty-eighteen: '2018'
 *               next: '2019'
 *           - locale: 'fr_FR'
 *             map:
 *               vingt-dix-sept: '2017'
 *               vingt-dix-huit: '2018'
 *               prochain: '2019'
 */
class StaticValueMapper implements StaticMappableAspectInterface, SiteLanguageAwareInterface, \Countable
{
    use SiteLanguageAwareTrait;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $map;

    /**
     * @var array
     */
    protected $localeMap;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $map = $settings['map'] ?? null;
        $localeMap = $settings['localeMap'] ?? [];

        if (!is_array($map)) {
            throw new \InvalidArgumentException('map must be array', 1537277143);
        }
        if (!is_array($localeMap)) {
            throw new \InvalidArgumentException('localeMap must be array', 1537277144);
        }

        $this->settings = $settings;
        $this->map = array_map('strval', $map);
        $this->localeMap = $localeMap;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->retrieveLocaleMap() ?? $this->map);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        $map = $this->retrieveLocaleMap() ?? $this->map;
        $index = array_search($value, $map, true);
        return $index !== false ? (string)$index : null;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        $map = $this->retrieveLocaleMap() ?? $this->map;
        return isset($map[$value]) ? (string)$map[$value] : null;
    }

    /**
     * Fetches the map of with the matching locale.
     *
     * @return array|null
     */
    protected function retrieveLocaleMap(): ?array
    {
        $locale = $this->siteLanguage->getLocale();
        foreach ($this->localeMap as $item) {
            $pattern = '#^' . $item['locale'] . '#i';
            if (preg_match($pattern, $locale)) {
                return array_map('strval', $item['map']);
            }
        }
        return null;
    }
}
