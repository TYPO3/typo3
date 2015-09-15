<?php
namespace TYPO3\CMS\Jumpurl;

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

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Http\UrlHandlerInterface;

/**
 * This class implements the hooks for the JumpURL functionality when accessing a page
 * which has a GET parameter "jumpurl".
 * It then validates the referrer
 */
class JumpUrlHandler implements UrlHandlerInterface {

	/**
	 * @var string The current JumpURL value submitted in the GET parameters.
	 */
	protected $url;

	/**
	 * Return TRUE if this hook handles the current URL.
	 * Warning! If TRUE is returned content rendering will be disabled!
	 * This method will be called in the constructor of the TypoScriptFrontendController
	 *
	 * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::__construct()
	 * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::initializeCustomUrlHandlers()
	 * @return bool
	 */
	public function canHandleCurrentUrl() {
		$this->url = (string)GeneralUtility::_GP('jumpurl');
		return ($this->url !== '');
	}

	/**
	 * Custom processing of the current URL.
	 *
	 * If a valid hash was submitted the user will either be redirected
	 * to the given jumpUrl or if it is a secure jumpUrl the file data
	 * will be passed to the user.
	 *
	 * If canHandle() has returned TRUE this method needs to take care of redirecting the user or generating custom output.
	 * This hook will be called BEFORE the user is redirected to an external URL configured in the page properties.
	 *
	 * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::processCustomUrlHandlers()
	 * @throws \RuntimeException if Jump URL was triggered by an illegal referrer.
	 * @return void
	 */
	public function handle() {
		if (!$this->referrerIsValid()) {
			throw new \RuntimeException('The jumpUrl request was triggered by an illegal referrer.');
		}

		if ((bool)GeneralUtility::_GP('juSecure')) {
			$this->forwardJumpUrlSecureFileData($this->url);
		} else {
			$this->redirectToJumpUrl($this->url);
		}
	}

	/**
	 * Returns TRUE if the current referrer allows Jump URL handling.
	 * This is the case then the referrer check is disabled or when the referrer matches the current TYPO3 host.
	 *
	 * @return bool if the referer is valid.
	 */
	protected function referrerIsValid() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer'])) {
			return TRUE;
		}

		$referrer = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
		// everything is fine if no host is set, or the host matches the TYPO3_HOST
		return (!isset($referrer['host']) || $referrer['host'] === GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
	}


	/**
	 * Redirects the user to the given jump URL if all submitted values
	 * are valid
	 *
	 * @param string $jumpUrl The URL to which the user should be redirected
	 * @throws \Exception
	 */
	protected function redirectToJumpUrl($jumpUrl) {
		$this->validateIfJumpUrlRedirectIsAllowed($jumpUrl);

		$pageTSconfig = $this->getTypoScriptFrontendController()->getPagesTSconfig();
		if (is_array($pageTSconfig['TSFE.'])) {
			$pageTSconfig = $pageTSconfig['TSFE.'];
		} else {
			$pageTSconfig = array();
		}

		$jumpUrl = $this->addParametersToTransferSession($jumpUrl, $pageTSconfig);
		$statusCode = $this->getRedirectStatusCode($pageTSconfig);
		$this->redirect($jumpUrl, $statusCode);
	}

	/**
	 * If the submitted hash is correct and the user has access to the
	 * related content element the contents of the submitted file will
	 * be output to the user.
	 *
	 * @param string $jumpUrl The URL to the file that should be output to the user
	 * @throws \Exception
	 */
	protected function forwardJumpUrlSecureFileData($jumpUrl) {
		// Set the parameters required for handling a secure jumpUrl link
		// The locationData GET parameter, containing information about the record that created the URL
		$locationData = (string)GeneralUtility::_GP('locationData');
		// The optional mimeType GET parameter
		$mimeType = (string)GeneralUtility::_GP('mimeType');
		// The jump Url Hash GET parameter
		$juHash = (string)GeneralUtility::_GP('juHash');

		// validate the hash GET parameter against the other parameters
		if ($juHash !== JumpUrlUtility::calculateHashSecure($jumpUrl, $locationData, $mimeType)) {
			throw new \Exception('The calculated Jump URL secure hash ("juHash") did not match the submitted "juHash" query parameter.', 1294585196);
		}

		if (!$this->isLocationDataValid($locationData)) {
			throw new \Exception('The calculated secure location data "' . $locationData . '" is not accessible.', 1294585195);
		}

		// Allow spaces / special chars in filenames.
		$jumpUrl = rawurldecode($jumpUrl);

		// Deny access to files that match TYPO3_CONF_VARS[SYS][fileDenyPattern] and whose parent directory
		// is typo3conf/ (there could be a backup file in typo3conf/ which does not match against the fileDenyPattern)
		$absoluteFileName = GeneralUtility::getFileAbsFileName(GeneralUtility::resolveBackPath($jumpUrl), FALSE);

		if (
			!GeneralUtility::isAllowedAbsPath($absoluteFileName)
			|| !GeneralUtility::verifyFilenameAgainstDenyPattern($absoluteFileName)
			|| GeneralUtility::isFirstPartOfStr($absoluteFileName, (PATH_site . 'typo3conf'))
		) {
			throw new \Exception('The requested file was not allowed to be accessed through Jump URL. The path or file is not allowed.', 1294585194);
		}

		try {
			$resourceFactory = $this->getResourceFactory();
			$file = $resourceFactory->retrieveFileOrFolderObject($absoluteFileName);
			$this->readFileAndExit($file, $mimeType);
		} catch (\Exception $e) {
			throw new \Exception('The requested file "' . $jumpUrl . '" for Jump URL was not found..', 1294585193);
		}
	}

	/**
	 * Checks if the given location data is valid and the connected record is accessible by the current user.
	 *
	 * @param string $locationData
	 * @return bool
	 */
	protected function isLocationDataValid($locationData) {
		$isValidLocationData = FALSE;
		list($pageUid, $table, $recordUid) = explode(':', $locationData);
		$pageRepository = $this->getTypoScriptFrontendController()->sys_page;
		$timeTracker = $this->getTimeTracker();
		if (empty($table) || $pageRepository->checkRecord($table, $recordUid, TRUE)) {
			// This check means that a record is checked only if the locationData has a value for a
			// record else than the page.
			if (!empty($pageRepository->getPage($pageUid))) {
				$isValidLocationData = TRUE;
			} else {
				$timeTracker->setTSlogMessage('LocationData Error: The page pointed to by location data "' . $locationData . '" was not accessible.', 2);
			}
		} else {
			$timeTracker->setTSlogMessage('LocationData Error: Location data "' . $locationData . '" record pointed to was not accessible.', 2);
		}
		return $isValidLocationData;
	}

	/**
	 * This implements a hook, e.g. for direct mail to allow the redirects but only if the handler says it's alright
	 * But also checks against the common juHash parameter first
	 *
	 * @param string $jumpUrl the URL to check
	 * @throws \Exception thrown if no redirect is allowed
	 */
	protected function validateIfJumpUrlRedirectIsAllowed($jumpUrl) {
		$allowRedirect = FALSE;
		if ($this->isJumpUrlHashValid($jumpUrl)) {
			$allowRedirect = TRUE;
		} elseif (
			isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['jumpurlRedirectHandler'])
			&& is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['jumpurlRedirectHandler'])
		) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['jumpurlRedirectHandler'] as $className) {
				$hookObject = GeneralUtility::getUserObj($className);
				if (method_exists($hookObject, 'jumpurlRedirectHandler')) {
					$allowRedirect = $hookObject->jumpurlRedirectHandler($jumpUrl, $GLOBALS['TSFE']);
				}
				if ($allowRedirect) {
					break;
				}
			}
		}

		if (!$allowRedirect) {
			throw new \Exception('The calculated Jump URL hash ("juHash") did not match the submitted "juHash" query parameter.', 1359987599);
		}
	}

	/**
	 * Validate the jumpUrl hash against the GET/POST parameter "juHash".
	 *
	 * @param string $jumpUrl The URL to check against.
	 * @return bool
	 */
	protected function isJumpUrlHashValid($jumpUrl) {
		return GeneralUtility::_GP('juHash') === JumpUrlUtility::calculateHash($jumpUrl);
	}

	/**
	 * Calls the PHP readfile function and exits.
	 *
	 * @param FileInterface $file The file that should be read.
	 * @param string $mimeType Optional mime type override. If empty the automatically detected mime type will be used.
	 */
	protected function readFileAndExit($file, $mimeType) {
		$file->getStorage()->dumpFileContents($file, TRUE, NULL, $mimeType);
		exit;
	}

	/**
	 * Simply calls the redirect method in the HttpUtility.
	 *
	 * @param string $jumpUrl
	 * @param int $statusCode
	 */
	protected function redirect($jumpUrl, $statusCode) {
		HttpUtility::redirect($jumpUrl, $statusCode);
	}

	/**
	 * Modified the URL to go to by adding the session key information to it
	 * but only if TSFE.jumpUrl_transferSession = 1 is set via pageTSconfig.
	 *
	 * @param string $jumpUrl the URL to go to
	 * @param array $pageTSconfig the TSFE. part of the TS configuration
	 *
	 * @return string the modified URL
	 */
	protected function addParametersToTransferSession($jumpUrl, $pageTSconfig) {
		// allow to send the current fe_user with the jump URL
		if (!empty($pageTSconfig['jumpUrl_transferSession'])) {
			$uParts = parse_url($jumpUrl);
			/** @noinspection PhpInternalEntityUsedInspection We need access to the current frontend user ID. */
			$params = '&FE_SESSION_KEY=' .
				rawurlencode(
					$this->getTypoScriptFrontendController()->fe_user->id . '-' .
					md5(
						$this->getTypoScriptFrontendController()->fe_user->id . '/' .
						$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
					)
				);
			// Add the session parameter ...
			$jumpUrl .= ($uParts['query'] ? '' : '?') . $params;
		}
		return $jumpUrl;
	}

	/**
	 * Returns one of the HTTP_STATUS_* constants of the HttpUtility that matches
	 * the configured HTTP status code in TSFE.jumpURL_HTTPStatusCode Page TSconfig.
	 *
	 * @param array $pageTSconfig
	 * @return string
	 * @throws \InvalidArgumentException If the configured status code is not valid.
	 */
	protected function getRedirectStatusCode($pageTSconfig) {
		$statusCode = HttpUtility::HTTP_STATUS_303;

		if (!empty($pageTSconfig['jumpURL_HTTPStatusCode'])) {
			switch ((int)$pageTSconfig['jumpURL_HTTPStatusCode']) {
				case 301:
					$statusCode = HttpUtility::HTTP_STATUS_301;
					break;
				case 302:
					$statusCode = HttpUtility::HTTP_STATUS_302;
					break;
				case 307:
					$statusCode = HttpUtility::HTTP_STATUS_307;
					break;
				default:
					throw new \InvalidArgumentException('The configured jumpURL_HTTPStatusCode option is invalid. Allowed codes are 301, 302 and 307.', 1381768833);
			}
		}

		return $statusCode;
	}

	/**
	 * @return \TYPO3\CMS\Core\TimeTracker\TimeTracker
	 */
	protected function getTimeTracker() {
		return $GLOBALS['TT'];
	}

	/**
	 * Returns an instance of the ResourceFactory.
	 *
	 * @return ResourceFactory
	 */
	protected function getResourceFactory() {
		return ResourceFactory::getInstance();
	}

	/**
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getTypoScriptFrontendController() {
		return $GLOBALS['TSFE'];
	}
}
