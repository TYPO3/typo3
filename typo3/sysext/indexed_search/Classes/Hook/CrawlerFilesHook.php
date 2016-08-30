<?php
namespace TYPO3\CMS\IndexedSearch\Hook;

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
 * Crawler hook for indexed search. Works with the "crawler" extension
 * This hook is specifically used to index external files found on pages through the crawler extension.
 * @see \TYPO3\CMS\IndexedSearch\Indexer::extractLinks()
 */
class CrawlerFilesHook
{
    /**
     * Call back function for execution of a log element
     *
     * @param array $params Params from log element.
     * @param object $pObj Parent object (tx_crawler lib)
     * @return null|array Result array
     */
    public function crawler_execute($params, &$pObj)
    {
        if (!is_array($params['conf'])) {
            return;
        }
        // Initialize the indexer class:
        $indexerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
        $indexerObj->conf = $params['conf'];
        $indexerObj->init();
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
