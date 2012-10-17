<?php
/*
 * @deprecated since 6.0, the classname tx_dbal_autoloader and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/dbal/Classes/Autoloader.php
 */
require_once t3lib_extMgm::extPath('dbal') . 'Classes/Autoloader.php';
// Make instance:
$SOBE = t3lib_div::makeInstance('tx_dbal_autoloader');
$SOBE->execute($this);
?>