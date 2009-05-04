<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

// Define the table for keys. Make sure that it cannot be edited or seen by
// any user in any way.
$TCA['tx_rsaauth_keys'] = array (
	'ctrl' => array (
		'adminOnly' => true,
		'hideTable' => true,
		'is_static' => true,
		'label' => 'uid',
		'readOnly' => true,
		'rootLevel' => 1,
		'title' => 'Oops! You should not see this!'
	),
	'columns' => array(
	),
	'types' => array(
		'0' => array(
			'showitem' => ''
		)
	)
);

?>