<?php
namespace TYPO3\CMS\Rsaauth;

// Include backends
/**
 * Service "RSA authentication" for the "rsaauth" extension. This service will
 * authenticate a user using hos password encoded with one time public key. It
 * uses the standard TYPO3 service to do all dirty work. Firsts, it will decode
 * the password and then pass it to the parent service ('sv'). This ensures that it
 * always works, even if other TYPO3 internals change.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class RsaAuthService extends \TYPO3\CMS\Sv\AuthenticationService {

	/**
	 * An RSA backend.
	 *
	 * @var \TYPO3\CMS\Rsaauth\Backend\AbstractBackend
	 */
	protected $backend = NULL;

	/**
	 * Standard extension key for the service
	 * The extension key.
	 *
	 * @var string
	 */
	public $extKey = 'rsaauth';

	/**
	 * Standard prefix id for the service
	 * Same as class name
	 *
	 * @var string
	 */
	public $prefixId = 'tx_rsaauth_sv1';

	/**
	 * Standard relative path for the service
	 * Path to this script relative to the extension dir.
	 *
	 * @var string
	 */
	public $scriptRelPath = 'sv1/class.tx_rsaauth_sv1.php';

	/**
	 * Process the submitted credentials.
	 * In this case decrypt the password if it is RSA encrypted.
	 *
	 * @param array $loginData Credentials that are submitted and potentially modified by other services
	 * @param string $passwordTransmissionStrategy Keyword of how the password has been hashed or encrypted before submission
	 * @return boolean
	 */
	public function processLoginData(array &$loginData, $passwordTransmissionStrategy) {
		$isProcessed = FALSE;
		if ($passwordTransmissionStrategy === 'rsa') {
			$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
			/** @var $storage \TYPO3\CMS\Rsaauth\Storage\AbstractStorage */
			// Decrypt the password
			$password = $loginData['uident'];
			$key = $storage->get();
			if ($key != NULL && substr($password, 0, 4) === 'rsa:') {
				// Decode password and store it in loginData
				$decryptedPassword = $this->backend->decrypt($key, substr($password, 4));
				if ($decryptedPassword != NULL) {
					$loginData['uident_text'] = $decryptedPassword;
					$isProcessed = TRUE;
				} else {
					if ($this->pObj->writeDevLog) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Process login data: Failed to RSA decrypt password', 'TYPO3\\CMS\\Rsaauth\\RsaAuthService');
					}
				}
				// Remove the key
				$storage->put(NULL);
			} else {
				if ($this->pObj->writeDevLog) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Process login data: passwordTransmissionStrategy has been set to "rsa" but no rsa encrypted password has been found.', 'TYPO3\\CMS\\Rsaauth\\RsaAuthService');
				}
			}
		}
		return $isProcessed;
	}

	/**
	 * Initializes the service.
	 *
	 * @return boolean
	 */
	public function init() {
		$available = parent::init();
		if ($available) {
			// Get the backend
			$this->backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
			if (is_null($this->backend)) {
				$available = FALSE;
			}
		}
		return $available;
	}

}


?>