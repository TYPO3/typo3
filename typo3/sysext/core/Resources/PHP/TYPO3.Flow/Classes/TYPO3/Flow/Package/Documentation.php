<?php
namespace TYPO3\Flow\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Documentation for a package
 *
 * @api
 */
class Documentation {

	/**
	 * Reference to the package of this documentation
	 * @var \TYPO3\Flow\Package\PackageInterface
	 */
	protected $package;

	/**
	 * @var string
	 */
	protected $documentationName;

	/**
	 * Absolute path to the documentation
	 * @var string
	 */
	protected $documentationPath;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Package\PackageInterface $package Reference to the package of this documentation
	 * @param string $documentationName Name of the documentation
	 * @param string $documentationPath Absolute path to the documentation directory
	 */
	public function __construct($package, $documentationName, $documentationPath) {
		$this->package = $package;
		$this->documentationName = $documentationName;
		$this->documentationPath = $documentationPath;
	}

	/**
	 * Get the package of this documentation
	 *
	 * @return \TYPO3\Flow\Package\PackageInterface The package of this documentation
	 * @api
	 */
	public function getPackage() {
		return $this->package;
	}

	/**
	 * Get the name of this documentation
	 *
	 * @return string The name of this documentation
	 * @api
	 */
	public function getDocumentationName() {
		return $this->documentationName;
	}

	/**
	 * Get the full path to the directory of this documentation
	 *
	 * @return string Path to the directory of this documentation
	 * @api
	 */
	public function getDocumentationPath() {
		return $this->documentationPath;
	}

	/**
	 * Returns the available documentation formats for this documentation
	 *
	 * @return array Array of \TYPO3\Flow\Package\DocumentationFormat
	 * @api
	 */
	public function getDocumentationFormats() {
		$documentationFormats = array();

		$documentationFormatsDirectoryIterator = new \DirectoryIterator($this->documentationPath);
		$documentationFormatsDirectoryIterator->rewind();
		while ($documentationFormatsDirectoryIterator->valid()) {
			$filename = $documentationFormatsDirectoryIterator->getFilename();
			if ($filename[0] != '.' && $documentationFormatsDirectoryIterator->isDir()) {
				$documentationFormat = new \TYPO3\Flow\Package\Documentation\Format($filename, $this->documentationPath . $filename . '/');
				$documentationFormats[$filename] = $documentationFormat;
			}
			$documentationFormatsDirectoryIterator->next();
		}

		return $documentationFormats;
	}
}
?>