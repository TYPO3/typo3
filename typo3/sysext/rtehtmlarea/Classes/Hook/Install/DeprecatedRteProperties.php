<?php
namespace TYPO3\CMS\Rtehtmlarea\Hook\Install;

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

use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Contains the update class for the replacement of deprecated RTE properties in Page TSconfig.
 * Used by the upgrade wizard in the install tool.
 */
class DeprecatedRteProperties extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Deprecated RTE properties in Page TSconfig';

    /**
     * Properties that may be replaced automatically in Page TSconfig (except inludes from external files)
     * Syntax: 'oldProperty' => 'newProperty'
     *
     * @var array
     */
    protected $replacementRteProperties = [
        'disableRightClick' => 'contextMenu.disable',
        'disableContextMenu' => 'contextMenu.disable',
        'hidePStyleItems' => 'buttons.formatblock.removeItems',
        'hideFontFaces' => 'buttons.fontstyle.removeItems',
        'fontFace' => 'buttons.fontstyle.addItems',
        'hideFontSizes' => 'buttons.fontsize.removeItems',
        'classesCharacter' => 'buttons.textstyle.tags.span.allowedClasses',
        'classesParagraph' => 'buttons.blockstyle.tags.div.allowedClasses',
        'classesTable' => 'buttons.blockstyle.tags.table.allowedClasses',
        'classesTD' => 'buttons.blockstyle.tags.td.allowedClasses',
        'classesImage' => 'buttons.image.properties.class.allowedClasses',
        'classesLinks' => 'buttons.link.properties.class.allowedClasses',
        'blindImageOptions' => 'buttons.image.options.removeItems',
        'blindLinkOptions' => 'buttons.link.options.removeItems',
        'defaultLinkTarget' => 'buttons.link.properties.target.default'
    ];

    /**
     * Properties that may be replaced automatically in Page TSconfig (except inludes from external files)
     * Syntax: 'oldProperty' => [ 'newProperty', 'newProperty' ]
     *
     * @var array
     */
    protected $doubleReplacementRteProperties = [
        'disableTYPO3Browsers' => [
            'buttons.image.TYPO3Browser.disabled',
            'buttons.link.TYPO3Browser.disabled'
        ],
        'showTagFreeClasses' => [
            'buttons.blockstyle.showTagFreeClasses',
            'buttons.textstyle.showTagFreeClasses'
        ],
        'disablePCexamples' => [
            'buttons.blockstyle.disableStyleOnOptionLabel',
            'buttons.textstyle.disableStyleOnOptionLabel'
        ]
    ];

    /**
     * Properties that may not be replaced automatically in Page TSconfig
     * Syntax: 'oldProperty' => 'newProperty'
     *
     * @var array
     */
    protected $useInsteadRteProperties = [
        'fontSize' => 'buttons.fontsize.addItems',
        'RTE.default.classesAnchor' => 'RTE.default.buttons.link.properties.class.allowedClasses',
        'RTE.default.classesAnchor.default.[link-type]' => 'RTE.default.buttons.link.[link-type].properties.class.default',
        'mainStyleOverride' => 'contentCSS',
        'mainStyleOverride_add.[key]' => 'contentCSS',
        'mainStyle_font' => 'contentCSS',
        'mainStyle_size' => 'contentCSS',
        'mainStyle_color' => 'contentCSS',
        'mainStyle_bgcolor' => 'contentCSS',
        'inlineStyle.[any-keystring]' => 'contentCSS',
        'ignoreMainStyleOverride' => 'n.a.'
    ];

    /**
     * Function which checks if update is needed. Called in the beginning of an update process.
     *
     * @param string $description Pointer to description for the update
     * @return bool TRUE if update is needs to be performed, FALSE otherwise.
     */
    public function checkForUpdate(&$description)
    {
        $result = false;

        $pages = $this->getPagesWithDeprecatedRteProperties($dbQueries, $customMessages);
        $pagesCount = count($pages);
        $deprecatedProperties = '';
        $deprecatedRteProperties = array_merge($this->replacementRteProperties, $this->useInsteadRteProperties);
        foreach ($deprecatedRteProperties as $deprecatedProperty => $replacementProperty) {
            $deprecatedProperties .= '<tr><td>' . $deprecatedProperty . '</td><td>' . $replacementProperty . '</td></tr>' . LF;
        }
        foreach ($this->doubleReplacementRteProperties as $deprecatedProperty => $replacementProperties) {
            $deprecatedProperties .= '<tr><td>' . $deprecatedProperty . '</td><td>' . implode(' and ', $replacementProperties) . '</td></tr>' . LF;
        }
        $description = '<p>The following Page TSconfig RTE properties are deprecated since TYPO3 4.6 and have been removed in TYPO3 6.0.</p>' . LF . '<table><thead><tr><th>Deprecated property</th><th>Use instead</th></tr></thead>' . LF . '<tbody>' . $deprecatedProperties . '</tboby></table>' . LF . '<p>You are currently using some of these properties on <strong>' . strval($pagesCount) . '&nbsp;pages</strong>  (including deleted and hidden pages).</p>' . LF;
        if ($pagesCount) {
            $pagesUids = [];
            foreach ($pages as $page) {
                $pagesUids[] = $page['uid'];
            }
            $description .= '<p>Pages id\'s: ' . implode(', ', $pagesUids) . '</p>';
        }
        $replacementProperties = '';
        foreach ($this->useInsteadRteProperties as $deprecatedProperty => $replacementProperty) {
            $replacementProperties .= '<tr><td>' . $deprecatedProperty . '</td><td>' . $replacementProperty . '</td></tr>' . LF;
        }
        if ($pagesCount) {
            $updateablePages = $this->findUpdateablePagesWithDeprecatedRteProperties($pages);
            if (!empty($updateablePages)) {
                $replacementProperties = '';
                foreach ($this->replacementRteProperties as $deprecatedProperty => $replacementProperty) {
                    $replacementProperties .= '<tr><td>' . $deprecatedProperty . '</td><td>' . $replacementProperty . '</td></tr>' . LF;
                }
                $description .= '<p>This wizard will perform automatic replacement of the following properties on <strong>' . strval(count($updateablePages)) . '&nbsp;pages</strong> (including deleted and hidden):</p>' . LF . '<table><thead><tr><th>Deprecated property</th><th>Will be replaced by</th></tr></thead><tbody>' . $replacementProperties . '</tboby></table>' . LF . '<p>The Page TSconfig column of the remaining pages will need to be updated manually.</p>' . LF;
            } else {
                $replacementProperties = '';
                foreach ($this->useInsteadRteProperties as $deprecatedProperty => $_) {
                    $replacementProperties .= '<tr><td>' . $deprecatedProperty . '</td></tr>' . LF;
                }
                foreach ($this->doubleReplacementRteProperties as $deprecatedProperty => $_) {
                    $replacementProperties .= '<tr><td>' . $deprecatedProperty . '</td></tr>' . LF;
                }
                $description .= '<p>This wizard cannot update the following properties, some of which are present on those pages:</p>' . LF . '<table><thead><tr><th>Deprecated property</th></tr></thead><tbody>' . $replacementProperties . '</tboby></table>' . LF . '<p>Therefore, the Page TSconfig column of those pages will need to be updated manually.</p>' . LF;
            }
            $result = true;
        } else {
            // if we found no occurrence of deprecated settings and wizard was already executed, then
            // we do not show up anymore
            if ($this->isWizardDone()) {
                $result = false;
            }
        }
        $description .= '<p>Only page records are searched for deprecated properties. However, such properties can also be used in BE group and BE user records (prepended with page.). These are not searched nor updated by this wizard.</p>' . LF . '<p>Page TSconfig may also be included from external files. These are not updated by this wizard. If required, the update will need to be done manually.</p>' . LF . '<p>Note also that deprecated properties have been replaced in default configurations provided by htmlArea RTE';

        return $result;
    }

    /**
     * Performs the update itself
     *
     * @param array $dbQueries Pointer where to insert all DB queries made, so they can be shown to the user if wanted
     * @param string $customMessages Pointer to output custom messages
     * @return bool TRUE if update succeeded, FALSE otherwise
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $customMessages = '';
        $pages = $this->getPagesWithDeprecatedRteProperties($dbQueries, $customMessages);
        if (empty($customMessages)) {
            $pagesCount = count($pages);
            if ($pagesCount) {
                $updateablePages = $this->findUpdateablePagesWithDeprecatedRteProperties($pages);
                if (!empty($updateablePages)) {
                    $this->updatePages($updateablePages, $dbQueries, $customMessages);
                    // If the update was successful
                    if (empty($customMessages)) {
                        // If all pages were updated, we query again to check if any deprecated properties are still present.
                        if (count($updateablePages) === $pagesCount) {
                            $pagesAfter = $this->getPagesWithDeprecatedRteProperties($dbQueries, $customMessages);
                            if (empty($customMessages)) {
                                if (!empty($pagesAfter)) {
                                    $customMessages = 'Some deprecated Page TSconfig properties were found. However, the wizard was unable to automatically replace all the deprecated properties found. Some properties will have to be replaced manually.';
                                }
                            }
                        } else {
                            $customMessages = 'Some deprecated Page TSconfig properties were found. However, the wizard was unable to automatically replace all the deprecated properties found. Some properties will have to be replaced manually.';
                        }
                    }
                } else {
                    $customMessages = 'Some deprecated Page TSconfig properties were found. However, the wizard was unable to automatically replace any of the deprecated properties found. These properties will have to be replaced manually.';
                }
            }
        }
        $this->markWizardAsDone();
        return empty($customMessages);
    }

    /**
     * Gets the pages with deprecated RTE properties in TSconfig column
     *
     * @param array $dbQueries Pointer where to insert all DB queries made, so they can be shown to the user if wanted
     * @param string $customMessages Pointer to output custom messages
     * @return array uid and inclusion string for the pages with deprecated RTE properties in TSconfig column
     */
    protected function getPagesWithDeprecatedRteProperties(&$dbQueries, &$customMessages)
    {
        $fields = 'uid, TSconfig';
        $table = 'pages';
        $where = '';
        $db = $this->getDatabaseConnection();
        foreach (array_merge($this->replacementRteProperties, $this->useInsteadRteProperties, $this->doubleReplacementRteProperties) as $deprecatedRteProperty => $_) {
            $where .= ($where ? ' OR ' : '') . '(TSconfig LIKE BINARY ' . $db->fullQuoteStr('%RTE.%' . $deprecatedRteProperty . '%', 'pages') . ' AND TSconfig NOT LIKE BINARY ' . $db->fullQuoteStr('%RTE.%' . $deprecatedRteProperty . 's%', 'pages') . ')' . LF;
        }
        $res = $db->exec_SELECTquery($fields, $table, $where);
        $dbQueries[] = str_replace(LF, ' ', $db->debug_lastBuiltQuery);
        if ($db->sql_error()) {
            $customMessages = 'SQL-ERROR: ' . htmlspecialchars($db->sql_error());
        }
        $pages = [];
        while ($row = $db->sql_fetch_assoc($res)) {
            $pages[] = $row;
        }
        return $pages;
    }

    /**
     * Gets the pages with updateable deprecated RTE properties in TSconfig column
     *
     * @param array $pages reference to pages with deprecated property
     * @return array uid and inclusion string for the pages with deprecated RTE properties in TSconfig column
     */
    protected function findUpdateablePagesWithDeprecatedRteProperties(&$pages)
    {
        foreach ($pages as $index => $page) {
            $deprecatedProperties = explode(',', '/' . implode('/,/((RTE\\.(default\\.|config\\.[a-zA-Z0-9_\\-]*\\.[a-zA-Z0-9_\\-]*\\.))|\\s)', array_keys($this->replacementRteProperties)) . '/');
            $replacementProperties = explode(',', '$1' . implode(',$1', array_values($this->replacementRteProperties)));
            $updatedPageTSConfig = preg_replace($deprecatedProperties, $replacementProperties, $page['TSconfig']);
            if ($updatedPageTSConfig == $page['TSconfig']) {
                unset($pages[$index]);
            } else {
                $pages[$index]['TSconfig'] = $updatedPageTSConfig;
            }
        }
        return $pages;
    }

    /**
     * updates the pages records with updateable Page TSconfig properties
     *
     * @param array $pages Page records to update, fetched by getTemplates() and filtered by
     * @param array $dbQueries Pointer where to insert all DB queries made, so they can be shown to the user if wanted
     * @param string $customMessages Pointer to output custom messages
     */
    protected function updatePages($pages, &$dbQueries, &$customMessages)
    {
        $db = $this->getDatabaseConnection();
        foreach ($pages as $page) {
            $table = 'pages';
            $where = 'uid =' . $page['uid'];
            $field_values = [
                'TSconfig' => $page['TSconfig']
            ];
            $db->exec_UPDATEquery($table, $where, $field_values);
            $dbQueries[] = str_replace(LF, ' ', $db->debug_lastBuiltQuery);
            if ($db->sql_error()) {
                $customMessages .= 'SQL-ERROR: ' . htmlspecialchars($db->sql_error()) . LF . LF;
            }
        }
    }
}
