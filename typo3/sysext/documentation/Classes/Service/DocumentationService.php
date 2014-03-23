<?php
namespace TYPO3\CMS\Documentation\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013-2014 Xavier Perseguers <xavier@typo3.org>
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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class to connect to docs.typo3.org.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class DocumentationService {

	/**
	 * Returns the list of official documents on docs.typo3.org.
	 *
	 * @return array
	 */
	public function getOfficialDocuments() {
		$documents = array();

		$json = GeneralUtility::getUrl('http://docs.typo3.org/typo3cms/documents.json');
		if ($json) {
			$documents = json_decode($json, TRUE);
			foreach ($documents as &$document) {
				$document['icon'] = \TYPO3\CMS\Documentation\Utility\MiscUtility::getIcon($document['key']);
			}

			// Cache file locally to be able to create a composer.json file when fetching a document
			$absoluteCacheFilename = GeneralUtility::getFileAbsFileName('typo3temp/documents.json');
			GeneralUtility::writeFile($absoluteCacheFilename, $json);
		}
		return $documents;
	}

	/**
	 * Returns the list of local extensions.
	 *
	 * @return array
	 */
	public function getLocalExtensions() {
		$documents = array();

		foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $extensionKey => $extensionData) {
			$absoluteExtensionPath = GeneralUtility::getFileAbsFileName($extensionData['siteRelPath']);
			if (is_file($absoluteExtensionPath . 'README.rst') || is_file($absoluteExtensionPath . 'Documentation' . DIRECTORY_SEPARATOR . 'Index.rst')) {
				$metadata = \TYPO3\CMS\Documentation\Utility\MiscUtility::getExtensionMetaData($extensionKey);
				if ($extensionData['type'] === 'S') {
					$version = TYPO3_branch;
				} else {
					$version = substr($metadata['release'], -4) === '-dev' ? 'latest' : $metadata['release'];
				}

				$documentKey = 'typo3cms.extensions.' . $extensionKey;
				$documents[] = array(
					'title'   => $metadata['title'],
					'icon'    => \TYPO3\CMS\Documentation\Utility\MiscUtility::getIcon($documentKey),
					'type'    => 'Extension',
					'key'     => $documentKey,
					'shortcut' => $extensionKey,
					'url'     => 'http://docs.typo3.org/typo3cms/extensions/' . $extensionKey . '/',
					'version' => $version,
				);
			}
		}

		return $documents;
	}

	/**
	 * Fetches the nearest version of a document from docs.typo3.org.
	 *
	 * Algorithm is as follows:
	 *
	 * 1) If exact version/language pair exists, fetch it
	 * 2) If document with version trimmed down to 2 digits and given language exists, fetch it
	 * 3) If document with version 'latest' and given language exists, fetch it
	 * 4) Restart at step 1) with language 'default'
	 *
	 * @param string $url
	 * @param string $key
	 * @param string $version
	 * @param string $language
	 * @return boolean TRUE if fetch succeeded, otherwise FALSE
	 */
	public function fetchNearestDocument($url, $key, $version = 'latest', $language = 'default') {
		// In case we could not find a working combination
		$success = FALSE;

		$packages = $this->getAvailablePackages($url);
		if (count($packages) == 0) {
			return $success;
		}

		$languages = array($language);
		if ($language !== 'default') {
			$languages[] = 'default';
		}
		foreach ($languages as $language) {
			// Step 1)
			if (isset($packages[$version][$language])) {
				$success |= $this->fetchDocument($url, $key, $version, $language);
				// Fetch next language
				continue;
			} else {
				foreach ($packages[$version] as $locale => $_) {
					if (GeneralUtility::isFirstPartOfStr($locale, $language)) {
						$success |= $this->fetchDocument($url, $key, $version, $locale);
						// Fetch next language (jump current foreach up to the loop of $languages)
						continue 2;
					}
				}
			}
			// Step 2)
			if (preg_match('/^(\d+\.\d+)\.\d+$/', $version, $matches)) {
				// Instead of a 3-digit version, try to get it on 2 digits
				$shortVersion = $matches[1];
				if (isset($packages[$shortVersion][$language])) {
					$success |= $this->fetchDocument($url, $key, $shortVersion, $language);
					// Fetch next language
					continue;
				}
			}
			// Step 3)
			if ($version !== 'latest' && isset($packages['latest'][$language])) {
				$success |= $this->fetchDocument($url, $key, 'latest', $language);
				// Fetch next language
				continue;
			}
		}

		return $success;
	}

	/**
	 * Fetches a document from docs.typo3.org.
	 *
	 * @param string $url
	 * @param string $key
	 * @param string $version
	 * @param string $language
	 * @return boolean TRUE if fetch succeeded, otherwise FALSE
	 */
	public function fetchDocument($url, $key, $version = 'latest', $language = 'default') {
		$result = FALSE;
		$url = rtrim($url, '/') . '/';

		$packagePrefix = substr($key, strrpos($key, '.') + 1);
		$languageSegment = str_replace('_', '-', strtolower($language));
		$packageName = sprintf('%s-%s-%s.zip', $packagePrefix, $version, $languageSegment);
		$packageUrl = $url . 'packages/' . $packageName;
		$absolutePathToZipFile = GeneralUtility::getFileAbsFileName('typo3temp/' . $packageName);

		$packages = $this->getAvailablePackages($url);
		if (count($packages) == 0 || !isset($packages[$version][$language])) {
			return FALSE;
		}

		// Check if a local version of the package is already present
		$hasArchive = FALSE;
		if (is_file($absolutePathToZipFile)) {
			$localMd5 = md5_file($absolutePathToZipFile);
			$remoteMd5 = $packages[$version][$language];
			$hasArchive = $localMd5 === $remoteMd5;
		}

		if (!$hasArchive) {
			/** @var $http \TYPO3\CMS\Core\Http\HttpRequest */
			$http = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Http\HttpRequest', $packageUrl);
			$response = $http->send();
			if ($response->getStatus() == 200) {
				GeneralUtility::writeFile($absolutePathToZipFile, $response->getBody());
			}
		}

		if (is_file($absolutePathToZipFile)) {
			$absoluteDocumentPath = GeneralUtility::getFileAbsFileName('typo3conf/Documentation/');

			$result = $this->unzipDocumentPackage($absolutePathToZipFile, $absoluteDocumentPath);

			// Create a composer.json file
			$absoluteCacheFilename = GeneralUtility::getFileAbsFileName('typo3temp/documents.json');
			$documents = json_decode(file_get_contents($absoluteCacheFilename), TRUE);
			foreach ($documents as $document) {
				if ($document['key'] === $key) {
					$composerData = array(
						'name' => $document['title'],
						'type' => 'documentation',
						'description' => 'TYPO3 ' . $document['type'],
					);
					$relativeComposerFilename = $key . '/' . $language . '/composer.json';
					$absoluteComposerFilename = GeneralUtility::getFileAbsFileName('typo3conf/Documentation/' . $relativeComposerFilename);
					GeneralUtility::writeFile($absoluteComposerFilename, json_encode($composerData));
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the available packages (version + language) for a given
	 * document on docs.typo3.org.
	 *
	 * @param string $url
	 * @return array
	 */
	protected function getAvailablePackages($url) {
		$packages = array();
		$url = rtrim($url, '/') . '/';
		$indexUrl = $url . 'packages/packages.xml';

		$remote = GeneralUtility::getUrl($indexUrl);
		if ($remote) {
			$packages = $this->parsePackagesXML($remote);
		}

		return $packages;
	}

	/**
	 * Parses content of packages.xml into a suitable array.
	 *
	 * @param string $string: XML data to parse
	 * @throws \TYPO3\CMS\Documentation\Exception\XmlParser
	 * @return array Array representation of XML data
	 */
	protected function parsePackagesXML($string) {
		$data = json_decode(json_encode((array)simplexml_load_string($string)), TRUE);
		if (count($data) != 2) {
			throw new \TYPO3\CMS\Documentation\Exception\XmlParser('Error in XML parser while decoding packages XML file.', 1374222437);
		}

		// SimpleXML does not properly handle arrays with only 1 item
		if ($data['languagePackIndex']['languagepack'][0] === NULL) {
			$data['languagePackIndex']['languagepack'] = array($data['languagePackIndex']['languagepack']);
		}

		$packages = array();
		foreach ($data['languagePackIndex']['languagepack'] as $languagePack) {
			$language = $languagePack['@attributes']['language'];
			$version = $languagePack['@attributes']['version'];
			$packages[$version][$language] = $languagePack['md5'];
		}

		return $packages;
	}

	/**
	 * Unzips a document package.
	 *
	 * @param string $file path to zip file
	 * @param string $path path to extract to
	 * @throws \TYPO3\CMS\Documentation\Exception\Document
	 * @return boolean
	 */
	protected function unzipDocumentPackage($file, $path) {
		$zip = zip_open($file);
		if (is_resource($zip)) {
			$result = TRUE;

			if (!is_dir($path)) {
				GeneralUtility::mkdir_deep($path);
			}

			while (($zipEntry = zip_read($zip)) !== FALSE) {
				$zipEntryName = zip_entry_name($zipEntry);
				if (strpos($zipEntryName, '/') !== FALSE) {
					$zipEntryPathSegments =  explode('/', $zipEntryName);
					$fileName = array_pop($zipEntryPathSegments);
					// It is a folder, because the last segment is empty, let's create it
					if (empty($fileName)) {
						GeneralUtility::mkdir_deep($path, implode('/', $zipEntryPathSegments));
					} else {
						$absoluteTargetPath = GeneralUtility::getFileAbsFileName($path . implode('/', $zipEntryPathSegments) . '/' . $fileName);
						if (strlen(trim($absoluteTargetPath)) > 0) {
							$return = GeneralUtility::writeFile(
								$absoluteTargetPath, zip_entry_read($zipEntry, zip_entry_filesize($zipEntry))
							);
							if ($return === FALSE) {
								throw new \TYPO3\CMS\Documentation\Exception\Document('Could not write file ' . $zipEntryName, 1374161546);
							}
						} else {
							throw new \TYPO3\CMS\Documentation\Exception\Document('Could not write file ' . $zipEntryName, 1374161532);
						}
					}
				} else {
					throw new \TYPO3\CMS\Documentation\Exception\Document('Extension directory missing in zip file!', 1374161519);
				}
			}
		} else {
			throw new \TYPO3\CMS\Documentation\Exception\Document('Unable to open zip file ' . $file, 1374161508);
		}

		return $result;
	}

}
