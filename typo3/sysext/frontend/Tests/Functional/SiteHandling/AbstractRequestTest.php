<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

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

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Abstract test case for frontend requests
 */
abstract class AbstractRequestTest extends FunctionalTestCase
{
    protected const ENCRYPTION_KEY = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';

    protected const TYPO3_CONF_VARS = [
        'SYS' => [
            'encryptionKey' => self::ENCRYPTION_KEY,
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['testing[value]']
            ],
        ]
    ];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['frontend'];

    /**
     * Combines string values of multiple array as cross-product into flat items.
     *
     * Example:
     * + meltStrings(['a','b'], ['c','e'], ['f','g'])
     * + results into ['acf', 'acg', 'aef', 'aeg', 'bcf', 'bcg', 'bef', 'beg']
     *
     * @param array $arrays Distinct array that should be melted
     * @param callable $finalCallback Callback being executed on last multiplier
     * @param string $prefix Prefix containing concatenated previous values
     * @return array
     */
    protected function meltStrings(array $arrays, callable $finalCallback = null, string $prefix = ''): array
    {
        $results = [];
        $array = array_shift($arrays);
        foreach ($array as $item) {
            $resultItem = $prefix . $item;
            if (count($arrays) > 0) {
                $results = array_merge(
                    $results,
                    $this->meltStrings($arrays, $finalCallback, $resultItem)
                );
                continue;
            }
            if ($finalCallback !== null) {
                $resultItem = call_user_func($finalCallback, $resultItem);
            }
            $results[] = $resultItem;
        }
        return $results;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function wrapInArray(array $array): array
    {
        return array_map(
            function ($item) {
                return [$item];
            },
            $array
        );
    }

    /**
     * @param array $array
     * @return array
     */
    protected function keysFromValues(array $array): array
    {
        return array_combine($array, $array);
    }

    /**
     * @param array $items
     */
    protected static function failIfArrayIsNotEmpty(array $items): void
    {
        if (empty($items)) {
            return;
        }

        static::fail(
            'Array was not empty as expected, but contained these items:' . LF
            . '* ' . implode(LF . '* ', $items)
        );
    }
}
