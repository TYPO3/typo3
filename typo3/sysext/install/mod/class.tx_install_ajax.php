<?php
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\EidHandler');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>