<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// auth services needs to be added here. ext_tables.php will be read after authentication.

t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_sv_auth' /* sv key */,
		array(

			'title' => 'User authentication',
			'description' => 'Authentication with username/password.',

			'subtype' => 'getUserBE,authUserBE,getUserFE,authUserFE,getGroupsFE',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_sv_auth.php',
			'className' => 'tx_sv_auth',
		)
	);

?>