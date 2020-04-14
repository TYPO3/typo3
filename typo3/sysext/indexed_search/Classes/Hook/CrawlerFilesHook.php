<?php

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

namespace TYPO3\CMS\IndexedSearch\Hook;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\IndexedSearch\Indexer;

/**
 * Crawler hook for indexed search. Works with the "crawler" extension
 * This hook is specifically used to index external files found on pages through the crawler extension.
 * @see \TYPO3\CMS\IndexedSearch\Indexer::extractLinks()
 * @internal this is a TYPO3-internal hook implementation and not part of TYPO3's Core API.
 */
class CrawlerFilesHook
{
    /**
     * Call back function for execution of a log element
     *
     * @param array $params Params from log element.
     * @param object $pObj Parent object (tx_crawler lib)
     * @return array|null Result array
     */
    public function crawler_execute($params, &$pObj)
    {
        if (!is_array($params['conf'])) {
            return;
        }
        $indexerObj = GeneralUtility::makeInstance(Indexer::class);
        $indexerObj->init($params['conf']);
        // Index document:
        if ($params['alturl']) {
            $fI = pathinfo($params['document']);
            $ext = strtolower($fI['extension']);
            $indexerObj->indexRegularDocument($params['alturl'], true, $params['document'], $ext);
        } else {
            $indexerObj->indexRegularDocument($params['document'], true);
        }
        // Return OK:
        return ['content' => []];
    }
}
