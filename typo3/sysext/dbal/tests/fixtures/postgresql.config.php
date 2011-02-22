<?php
/**
 * PostgreSQL configuration
 *
 * $Id: postgresql.config.php 37022 2010-08-19 19:34:19Z xperseguers $
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
global $TYPO3_CONF_VARS;

$TYPO3_CONF_VARS['EXTCONF']['dbal']['handlerCfg'] = array(
	'_DEFAULT' => array(
		'type' => 'adodb',
		'config' => array(
			'driver' => 'postgres',
		),
	),
);
?>