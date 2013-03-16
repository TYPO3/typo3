<?php
namespace TYPO3\CMS\Rtehtmlarea\Hook\Install;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Stanislas Rolland <typo3@sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Contains the update class for the replacement of deprecated RTE properties in Page TSconfig. Used by the update wizard in the install tool.
 *
 * @author Stanislas Rolland <typo3@sjbr.ca>
 */
class DeprecatedRteProperties extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	protected $title = 'Deprecated RTE properties in Page TSconfig';

	// Properties that may be replaced automatically in Page TSconfig (except inludes from external files)
	protected $replacementRteProperties = array(
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
	);

	protected $doubleReplacementRteProperties = array(
		'disableTYPO3Browsers' => array(
			'buttons.image.TYPO3Browser.disabled',
			'buttons.link.TYPO3Browser.disabled'
		),
		'showTagFreeClasses' => array(
			'buttons.blockstyle.showTagFreeClasses',
			'buttons.textstyle.showTagFreeClasses'
		),
		'disablePCexamples' => array(
			'buttons.blockstyle.disableStyleOnOptionLabel',
			'buttons.textstyle.disableStyleOnOptionLabel'
		)
	);

	// Properties that may not be replaced automatically in Page TSconfig
	protected $useInsteadRteProperties = array(
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
	);

	/**
	 * Function which checks if update is needed. Called in the beginning of an update process.
	 *
	 * @param 	string		pointer to description for the update
	 * @return 	boolean		TRUE if update is needs to be performed, FALSE otherwise.
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		// TYPO3 version 4.6 and above
		if ($this->versionNumber >= 4006000) {
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
				$pagesUids = array();
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
				if (count($updateablePages)) {
					$replacementProperties = '';
					foreach ($this->replacementRteProperties as $deprecatedProperty => $replacementProperty) {
						$replacementProperties .= '<tr><td>' . $deprecatedProperty . '</td><td>' . $replacementProperty . '</td></tr>' . LF;
					}
					$description .= '<p>This wizard will perform automatic replacement of the following properties on <strong>' . strval(count($updateablePages)) . '&nbsp;pages</strong> (including deleted and hidden):</p>' . LF . '<table><thead><tr><th>Deprecated property</th><th>Will be replaced by</th></tr></thead><tbody>' . $replacementProperties . '</tboby></table>' . LF . '<p>The Page TSconfig column of the remaining pages will need to be updated manually.</p>' . LF;
				} else {
					$replacementProperties = '';
					foreach (array_keys(array_merge($this->useInsteadRteProperties, $this->doubleReplacementRteProperties)) as $deprecatedProperty) {
						$replacementProperties .= '<tr><td>' . $deprecatedProperty . '</td></tr>' . LF;
					}
					$description .= '<p>This wizard cannot update the following properties, some of which are present on those pages:</p>' . LF . '<table><thead><tr><th>Deprecated property</th></tr></thead><tbody>' . $replacementProperties . '</tboby></table>' . LF . '<p>Therefore, the Page TSconfig column of those pages will need to be updated manually.</p>' . LF;
				}
				$result = TRUE;
			} else {
				// if we found no occurence of deprecated settings and wizard was already executed, then
				// we do not show up anymore
				if ($this->isWizardDone()) {
					$result = FALSE;
				}
			}
			$description .= '<p>Only page records were searched for deprecated properties. However, such properties can also be used in BE group and BE user records (prepended with page.). These are not searched nor updated by this wizard.</p>' . LF . '<p>Page TSconfig may also be included from external files. These are not updated by this wizard. If required, the update will need to be done manually.</p>' . LF . '<p>Note also that deprecated properties have been replaced in default configurations provided by htmlArea RTE';
		}
		return $result;
	}

	/**
	 * Performs the update itself
	 *
	 * @param 	array		pointer where to insert all DB queries made, so they can be shown to the user if wanted
	 * @param 	string		pointer to output custom messages
	 * @return 	boolean		TRUE if update succeeded, FALSE otherwise
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$success = FALSE;
		$pages = $this->getPagesWithDeprecatedRteProperties($dbQueries, $customMessages);
		if (empty($customMessages)) {
			if (count($pages)) {
				$updateablePages = $this->findUpdateablePagesWithDeprecatedRteProperties($pages);
				if (count($updateablePages)) {
					$this->updatePages($updateablePages, $dbQueries, $customMessages);
				} else {
					$customMessages = '<p>Some deprecated Page TSconfig properties were found. However, the wizard was unable to automatically replace any of the deprecated properties found. They will have to be replaced manually.</p>' . LF;
					$success = TRUE;
				}
			} else {
				$customMessages = '<p>No deprecated Page TSconfig properties were found on page records.</p>' . LF;
				$success = TRUE;
			}
			$customMessages .= '<p>Only page records were searched for deprecated properties. However, such properties can also be used in BE group and BE user records (prepended with page.). These are not searched nor updated by this wizard.</p>' . LF . '<p>Page TSconfig may also be included from external files. These were not updated by this wizard. If required, the update will need to be done manually.</p>';
		}
		$this->markWizardAsDone();
		return empty($customMessages) || $success;
	}

	/**
	 * Gets the pages with deprecated RTE properties in TSConfig column
	 *
	 * @param 	array		pointer where to insert all DB queries made, so they can be shown to the user if wanted
	 * @param 	string		pointer to output custom messages
	 * @return 	array		uid and inclusion string for the pages with deprecated RTE properties in TSConfig column
	 */
	protected function getPagesWithDeprecatedRteProperties(&$dbQueries, &$customMessages) {
		$fields = 'uid, TSconfig';
		$table = 'pages';
		$deprecatedRteProperties = array_keys(array_merge($this->replacementRteProperties, $this->useInsteadRteProperties, $this->doubleReplacementRteProperties));
		$where = '';
		foreach ($deprecatedRteProperties as $deprecatedRteProperty) {
			$where .= ($where ? ' OR ' : '') . '(TSConfig LIKE BINARY "%RTE.%' . $deprecatedRteProperty . '%" AND TSConfig NOT LIKE BINARY "%RTE.%' . $deprecatedRteProperty . 's%") ';
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
		if ($GLOBALS['TYPO3_DB']->sql_error()) {
			$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
		}
		$pages = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$pages[] = $row;
		}
		return $pages;
	}

	/**
	 * Gets the pages with updated deprecated RTE properties in TSConfig column
	 *
	 * @param 	array		$pages: reference to pages with deprecated property
	 * @return 	array		uid and inclusion string for the pages with deprecated RTE properties in TSConfig column
	 */
	protected function findUpdateablePagesWithDeprecatedRteProperties(&$pages) {
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
	 * @param 	array		pages records to update, fetched by getTemplates() and filtered by
	 * @param 	array		pointer where to insert all DB queries made, so they can be shown to the user if wanted
	 * @param 	string		pointer to output custom messages
	 */
	protected function updatePages($pages, &$dbQueries, &$customMessages) {
		foreach ($pages as $page) {
			$table = 'pages';
			$where = 'uid =' . $page['uid'];
			$field_values = array(
				'TSconfig' => $page['TSconfig']
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $field_values);
			$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
			}
		}
	}

}


?>