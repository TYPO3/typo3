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

/**
 * Very useful for e.g. pagination or static range like "2011 ... 2030" for years.
 *
 * Example:
 *   routeEnhancers:
 *     MyBlogPlugin:
 *       type: Extbase
 *       extension: BlogExample
 *       plugin: Pi1
 *       routes:
 *         - { routePath: '/list/{paging_widget}', _controller: 'BlogExample::list', _arguments: {'paging_widget': '@widget_0/currentPage'}}
 *         - { routePath: '/glossary/{section}', _controller: 'BlogExample::glossary'}
 *       defaultController: 'BlogExample::list'
 *       requirements:
 *         paging_widget: '\d+'
 *       aspects:
 *         paging_widget:
 *           type: StaticRangeMapper
 *           start: '1'
 *           end: '100'
 *         section:
 *           type: StaticRangeMapper
 *           start: 'a'
 *           end: 'z'
 */
class StaticRangeMapper implements StaticMappableAspectInterface, \Countable
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @var string
     */
    protected $start;

    /**
     * @var string
     */
    protected $end;

    /**
     * @var string[]
     */
    protected $range;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $start = $settings['start'] ?? null;
        $end = $settings['end'] ?? null;

        if (!is_string($start)) {
            throw new \InvalidArgumentException('start must be string', 1537277163);
        }
        if (!is_string($end)) {
            throw new \InvalidArgumentException('end must be string', 1537277164);
        }

        $this->settings = $settings;
        $this->start = $start;
        $this->end = $end;
        $this->range = $this->buildRange();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->range);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        return $this->respondWhenInRange($value);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        return $this->respondWhenInRange($value);
    }

    /**
     * @param string $value
     * @return string|null
     */
    protected function respondWhenInRange(string $value): ?string
    {
        if (in_array($value, $this->range, true)) {
            return $value;
        }
        return null;
    }

    /**
     * Builds range based on given settings and ensures each item is string.
     * The amount of items is limited to 1000 in order to avoid brute-force
     * scenarios and the risk of cache-flooding.
     *
     * In case that is not enough, creating a custom and more specific mapper
     * is encouraged. Using high values that are not distinct exposes the site
     * to the risk of cache-flooding.
     *
     * @return string[]
     * @throws \LengthException
     */
    protected function buildRange(): array
    {
        $range = array_map('strval', range($this->start, $this->end));
        if (count($range) > 1000) {
            throw new \LengthException(
                'Range is larger than 1000 items',
                1537696771
            );
        }
        return $range;
    }
}
