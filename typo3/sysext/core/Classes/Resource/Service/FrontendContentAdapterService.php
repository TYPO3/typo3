<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Ingmar Schlecht <ingmar@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Tslib content adapter to modify $row array ($cObj->data[]) for backwards compatibility
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 * @license http://www.gnu.org/copyleft/gpl.html
 */
class FrontendContentAdapterService {

	/**
	 * The name of the table
	 *
	 * @var string
	 */
	static protected $migrateFields = array(
		'tt_content' => array(
			'image' => array(
				'paths' => 'image',
				'titleTexts' => 'titleText',
				'captions' => 'imagecaption',
				'links' => 'image_link',
				'alternativeTexts' => 'altText',
			),
			'media' => array(
				'paths' => 'media',
				'captions' => 'imagecaption'
			)
		),
		'pages' => array(
			'media' => array(
				'paths' => 'media'
			)
		)
	);

	/**
	 * Modifies the DB row in the CONTENT cObj of tslib_content for supplying
	 * backwards compatibility for some file fields which have switched to using
	 * the new File API instead of the old uploads/ folder for storing files.
	 *
	 * This method is called by the render() method of tslib_content_Content.
	 *
	 * @param array $row typically an array, but can also be null (in extensions or e.g. FLUID viewhelpers)
	 * @param string $table the database table where the record is from
	 * @throws \RuntimeException
	 * @return void
	 */
	static public function modifyDBRow(&$row, $table) {
		if (isset($row['_MIGRATED']) && $row['_MIGRATED'] === TRUE) {
			return;
		}
		if (array_key_exists($table, static::$migrateFields)) {
			foreach (static::$migrateFields[$table] as $migrateFieldName => $oldFieldNames) {
				if ($row !== NULL && isset($row[$migrateFieldName])) {
					/** @var $fileRepository \TYPO3\CMS\Core\Resource\FileRepository */
					$fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
					if ($table === 'pages' && isset($row['_LOCALIZED_UID']) && intval($row['sys_language_uid']) > 0) {
						$table = 'pages_language_overlay';
					}
					$files = $fileRepository->findByRelation($table, $migrateFieldName, isset($row['_LOCALIZED_UID']) ? intval($row['_LOCALIZED_UID']) : intval($row['uid']));
					$fileFieldContents = array(
						'paths' => array(),
						'titleTexts' => array(),
						'captions' => array(),
						'links' => array(),
						'alternativeTexts' => array(),
						$migrateFieldName . '_fileUids' => array()
					);
					$oldFieldNames[$migrateFieldName . '_fileUids'] = $migrateFieldName . '_fileUids';

					foreach ($files as $file) {
						/** @var $file \TYPO3\CMS\Core\Resource\FileReference */
						$fileFieldContents['paths'][] = '../../' . $file->getPublicUrl();
						$fileFieldContents['titleTexts'][] = $file->getProperty('title');
						$fileFieldContents['captions'][] = $file->getProperty('description');
						$fileFieldContents['links'][] = $file->getProperty('link');
						$fileFieldContents['alternativeTexts'][] = $file->getProperty('alternative');
						$fileFieldContents[$migrateFieldName .  '_fileUids'][] = $file->getOriginalFile()->getUid();
					}
					foreach ($oldFieldNames as $oldFieldType => $oldFieldName) {
						// For paths, make comma separated list
						if ($oldFieldType === 'paths' || substr($oldFieldType, -9) == '_fileUids') {
							$fieldContents = implode(',', $fileFieldContents[$oldFieldType]);
						} else {
							// For all other fields, separate by newline
							$fieldContents = implode(chr(10), $fileFieldContents[$oldFieldType]);
						}
						if ($fieldContents) {
							$row[$oldFieldName] = $fieldContents;
						}
					}
				}
			}
		}
		$row['_MIGRATED'] = TRUE;
	}

}


?>