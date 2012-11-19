<?php
/*
 * @deprecated since 6.0, the classname tx_dbal_autoloader and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/dbal/Classes/Autoloader.php
 */
require_once PATH_site . 'typo3/sysext/dbal/Classes/Autoloader.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Dbal\\Autoloader');
$SOBE->execute($this);
?>