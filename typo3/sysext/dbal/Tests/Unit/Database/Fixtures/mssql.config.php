<?php
/**
 * MS SQL configuration
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
global $TYPO3_CONF_VARS;
$TYPO3_CONF_VARS['EXTCONF']['dbal']['handlerCfg'] = array(
	'_DEFAULT' => array(
		'type' => 'adodb',
		'config' => array(
			'driver' => 'mssql',
			'useNameQuote' => FALSE,
			'quoteClob' => FALSE
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
$TYPO3_CONF_VARS['EXTCONF']['dbal']['table2handlerKeys'] = array();
?>