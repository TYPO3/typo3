<?php
namespace TYPO3\CMS\Openid;

/**
 * This class is the OpenID return script for the TYPO3 Backend.
 *
 * @author 	Dmitry Dulepov <dmitry@typo3.org>
 */
class OpenidReturn {

	/**
	 * Processed Backend session creation and redirect to backend.php
	 *
	 * @return 	void
	 */
	public function main() {
		if ($GLOBALS['BE_USER']->user['uid']) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::cleanOutputBuffers();
			$backendURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'backend.php';
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($backendURL);
		}
	}

}


?>