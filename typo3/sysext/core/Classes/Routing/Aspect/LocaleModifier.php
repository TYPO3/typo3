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
 * Locale modifier to be used to modify routePath directly.
 *
 * Example:
 *   routeEnhancers:
 *     Blog:
 *       type: Extbase
 *       extension: BlogExample
 *       plugin: Pi1
 *       routes:
 *         - { routePath: '/{list_label}/{paging_widget}', _controller: 'BlogExample::list', _arguments: {'paging_widget': '@widget_0/currentPage'}}
 *       defaultController: 'BlogExample::list'
 *       requirements:
 *         paging_widget: '\d+'
 *       aspects:
 *         list_label:
 *           type: LocaleModifier
 *           default: 'list'
 *           localeMap:
 *             - locale: 'en_US.*|en_GB.*'
 *               value: 'overview'
 *             - locale: 'fr_FR'
 *               value: 'liste'
 *             - locale: 'de_.*'
 *               value: 'übersicht'
 */
class LocaleModifier implements ModifiableAspectInterface, SiteLanguageAwareInterface
{
    use SiteLanguageAwareTrait;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $localeMap;

    /**
     * @var string|null
     */
    protected $default;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $localeMap = $settings['localeMap'] ?? null;
        $default = $settings['default'] ?? null;

        if (!is_array($localeMap)) {
            throw new \InvalidArgumentException('localeMap must be array', 1537277153);
        }
        if (!is_string($default ?? '')) {
            throw new \InvalidArgumentException('default must be string', 1537277154);
        }

        $this->settings = $settings;
        $this->localeMap = $localeMap;
        $this->default = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function modify(): ?string
    {
        $locale = $this->siteLanguage->getLocale();
        foreach ($this->localeMap as $item) {
            $pattern = '#^' . $item['locale'] . '#i';
            if (preg_match($pattern, $locale)) {
                return (string)$item['value'];
            }
        }
        return $this->default;
    }
}
