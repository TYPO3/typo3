<?php
namespace TYPO3\CMS\Backend\Configuration;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A TS-Config parsing class which performs condition evaluation
 */
class TsConfigParser extends \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
{
    /**
     * @var array
     */
    protected $rootLine = [];

    /**
     * The uid of the page being handled
     *
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * Parses the passed TS-Config using conditions and caching
     *
     * @param string $TStext The TSConfig being parsed
     * @param string $type The type of TSConfig (either "userTS" or "PAGES")
     * @param int $id The uid of the page being handled
     * @param array $rootLine The rootline of the page being handled
     * @return array Array containing the parsed TSConfig and a flag whether the content was retrieved from cache
     */
    public function parseTSconfig($TStext, $type, $id = 0, array $rootLine = [])
    {
        $this->type = $type;
        $this->id = $id;
        $this->rootLine = $rootLine;
        $hash = md5($type . ':' . $TStext);

        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
        $cachedContent = $cache->get($hash);
        if (is_array($cachedContent)) {
            $storedData = $cachedContent[0];
            $storedMD5 = $cachedContent[1];
            $storedData['match'] = [];
            $storedData = $this->matching($storedData);
            $checkMD5 = md5(serialize($storedData));
            if ($checkMD5 == $storedMD5) {
                $res = [
                    'TSconfig' => $storedData['TSconfig'],
                    'cached' => 1,
                    'hash' => $hash
                ];
            } else {
                $shash = md5($checkMD5 . $hash);
                $cachedSpec = $cache->get($shash);
                if (is_array($cachedSpec)) {
                    $storedData = $cachedSpec;
                    $res = [
                        'TSconfig' => $storedData['TSconfig'],
                        'cached' => 1,
                        'hash' => $shash
                    ];
                } else {
                    $storeData = $this->parseWithConditions($TStext);
                    $res = [
                        'TSconfig' => $storeData['TSconfig'],
                        'cached' => 0,
                        'hash' => $shash
                    ];
                    $cache->set($shash, $storeData, ['ident_' . $type . '_TSconfig'], 0);
                }
            }
        } else {
            $storeData = $this->parseWithConditions($TStext);
            $md5 = md5(serialize($storeData));
            $cache->set($hash, [$storeData, $md5], ['ident_' . $type . '_TSconfig'], 0);
            $res = [
                'TSconfig' => $storeData['TSconfig'],
                'cached' => 0,
                'hash' => $hash
            ];
        }
        return $res;
    }

    /**
     * Does the actual parsing using the parent objects "parse" method. Creates the match-Object
     *
     * @param string $TSconfig The TSConfig being parsed
     * @return array Array containing the parsed TSConfig, the encountered sectiosn, the matched sections
     */
    protected function parseWithConditions($TSconfig)
    {
        /** @var \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher $matchObj */
        $matchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher::class);
        $matchObj->setRootline($this->rootLine);
        $matchObj->setPageId($this->id);
        $this->parse($TSconfig, $matchObj);
        return [
            'TSconfig' => $this->setup,
            'sections' => $this->sections,
            'match' => $this->sectionsMatch
        ];
    }

    /**
     * Is just going through an array of conditions to determine which are matching (for getting correct cache entry)
     *
     * @param array $cc An array containing the sections to match
     * @return array The input array with matching sections filled into the "match" key
     */
    protected function matching(array $cc)
    {
        if (is_array($cc['sections'])) {
            /** @var \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher $matchObj */
            $matchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher::class);
            $matchObj->setRootline($this->rootLine);
            $matchObj->setPageId($this->id);
            foreach ($cc['sections'] as $key => $pre) {
                if ($matchObj->match($pre)) {
                    $cc['match'][$key] = $pre;
                }
            }
        }
        return $cc;
    }
}
