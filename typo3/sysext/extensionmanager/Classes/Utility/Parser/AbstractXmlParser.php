<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

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
 * Abstract parser for EM related TYPO3 xml files.
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 * @since 2010-02-09
 */
abstract class AbstractXmlParser implements \SplSubject {

	/**
	 * Keeps XML parser instance.
	 *
	 * @var mixed
	 */
	protected $objXml;

	/**
	 * Keeps name of required PHP extension
	 * for this class to work properly.
	 *
	 * @var string
	 */
	protected $requiredPhpExtensions;

	/**
	 * Keeps list of attached observers.
	 *
	 * @var \SplObserver[]
	 */
	protected $observers = array();

	/**
	 * Method attaches an observer.
	 *
	 * @param \SplObserver $observer an observer to attach
	 * @return void
	 * @see $observers, detach(), notify()
	 */
	public function attach(\SplObserver $observer) {
		$this->observers[] = $observer;
	}

	/**
	 * Method detaches an attached observer
	 *
	 * @param \SplObserver $observer an observer to detach
	 * @return void
	 * @see $observers, attach(), notify()
	 */
	public function detach(\SplObserver $observer) {
		$key = array_search($observer, $this->observers, TRUE);
		if ($key !== FALSE) {
			unset($this->observers[$key]);
		}
	}

	/**
	 * Method notifies attached observers.
	 *
	 * @access public
	 * @return void
	 * @see $observers, attach(), detach()
	 */
	public function notify() {
		foreach ($this->observers as $observer) {
			$observer->update($this);
		}
	}

	/**
	 * Method determines if a necessary PHP extension is available.
	 *
	 * Method tries to load the extension if necessary and possible.
	 *
	 * @access public
	 * @return bool TRUE, if PHP extension is available, otherwise FALSE
	 */
	public function isAvailable() {
		$isAvailable = TRUE;
		if (!extension_loaded($this->requiredPhpExtensions)) {
			$prefix = PHP_SHLIB_SUFFIX === 'dll' ? 'php_' : '';
			if (!(((bool)ini_get('enable_dl') && !(bool)ini_get('safe_mode')) && function_exists('dl') && dl($prefix . $this->requiredPhpExtensions . PHP_SHLIB_SUFFIX))) {
				$isAvailable = FALSE;
			}
		}
		return $isAvailable;
	}

	/**
	 * Method parses an XML file.
	 *
	 * @param string $file GZIP stream resource
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of XML parser errors
	 */
	abstract public function parseXml($file);

	/**
	 * Create required parser
	 *
	 * @return void
	 */
	abstract protected function createParser();
}
