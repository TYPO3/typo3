<?php
/**
 * PostgreSQL configuration
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
global $TYPO3_CONF_VARS;
$TYPO3_CONF_VARS['EXTCONF']['dbal']['handlerCfg'] = array(
	'_DEFAULT' => array(
		'type' => 'adodb',
		'config' => array(
			'driver' => 'postgres'
		)
	)
);
$TYPO3_CONF_VARS['EXTCONF']['dbal']['mapping'] = array(
	'tx_templavoila_tmplobj' => array(
		'mapFieldNames' => array(
			'datastructure' => 'ds'
		)
	),
	'Members' => array(
		'mapFieldNames' => array(
			'pid' => '0',
			'cruser_id' => '1',
			'uid' => 'MemberID'
		)
	)
);
?>