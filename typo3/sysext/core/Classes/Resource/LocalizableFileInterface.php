<?php
namespace TYPO3\CMS\Core\Resource;

/**
 * A file that is localizable
 */
interface LocalizableFileInterface {

	public function isLocalized();

	public function getAvailableLocales();

	public function setCurrentLocale($locale);
}