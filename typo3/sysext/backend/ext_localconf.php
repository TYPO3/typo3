<?php
if (TYPO3_MODE == 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LoginForm']['provideLoginForm'][100]
		= array(
			'callback' => 'TYPO3\\CMS\\Backend\\View\\LoginForm\\Password->render',
			'conf' => array(
				'template' => 'EXT:t3skin/Resources/Private/Templates/LoginForm/Password.html'
			)
		);
}
?>
