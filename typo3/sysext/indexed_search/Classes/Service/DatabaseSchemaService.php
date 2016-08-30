<?php
namespace TYPO3\CMS\IndexedSearch\Service;

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
 * This service provides the mysql specific changes of the schema definition
 */
class DatabaseSchemaService
{
    /**
     * A slot method to inject the required mysql fulltext definition
     * to schema migration
     *
     * @param array $sqlString
     * @return array
     */
    public function addMysqlFulltextIndex(array $sqlString)
    {
        // Check again if the extension flag is enabled to be on the safe side
        // even if the slot registration is moved around in ext_localconf
        $extConf = [];
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search'])) {
            $extConf = unserialize(
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search'],
                ['allowed_classes' => false]
            );
        }
        if (isset($extConf['useMysqlFulltext']) && $extConf['useMysqlFulltext'] === '1') {
            // @todo: With MySQL 5.7 fulltext index on InnoDB is possible, check for that and keep inno if so.
            $sqlString[] = 'CREATE TABLE index_fulltext ('
                . LF . 'fulltextdata mediumtext,'
                . LF . 'metaphonedata mediumtext,'
                . LF . 'FULLTEXT fulltextdata (fulltextdata),'
                . LF . 'FULLTEXT metaphonedata (metaphonedata)'
                . LF . ') ENGINE=MyISAM;';
        }
        return [$sqlString];
    }
}
