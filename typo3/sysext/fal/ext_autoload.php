<?php

$extensionPath = t3lib_extMgm::extPath('fal');
return array(
	'tx_fal_migrationcontroller' => $extensionPath . 'classes/controller/class.tx_fal_migrationcontroller.php',

	'tx_fal_databasefieldnameiterator' => $extensionPath . 'classes/iterator/class.tx_fal_databasefieldnameiterator.php',
	'tx_fal_recorditerator' => $extensionPath . 'classes/iterator/class.tx_fal_recorditerator.php',

	'tx_fal_migrationtask' => $extensionPath . 'tasks/class.tx_fal_migrationtask.php',
	'tx_fal_migrationtask_additionalfieldprovider' => $extensionPath . 'tasks/class.tx_fal_migrationtask_additionalfieldprovider.php',
	'tx_fal_hooks_extfilefunchook' => $extensionPath . 'classes/class.tx_fal_hooks_extfilefunchook.php',

	'tx_fal_collection' => $extensionPath . 'classes/class.tx_fal_Collection.php',
	'tx_fal_file' => $extensionPath . 'classes/class.tx_fal_File.php',
	'tx_fal_frontendaccess' => $extensionPath . 'classes/class.tx_fal_frontendaccess.php',
	'tx_fal_indexer' => $extensionPath . 'classes/class.tx_fal_Indexer.php',
	'tx_fal_helper' => $extensionPath . 'classes/class.tx_fal_Helper.php',
	'tx_fal_mount' => $extensionPath . 'classes/class.tx_fal_Mount.php',
	'tx_fal_repository' => $extensionPath . 'classes/class.tx_fal_Repository.php',
	'tx_fal_backend_fileadminbackend' => $extensionPath . 'classes/backend/class.tx_fal_backend_FileadminBackend.php',
	'tx_fal_backend_interface' => $extensionPath . 'classes/backend/interface.tx_fal_backend_Interface.php',
	'tx_fal_storage_filesystemstorage' => $extensionPath . 'classes/storage/class.tx_fal_storage_filesystemstorage.php',
	'tx_fal_storage_interface' => $extensionPath . 'classes/storage/interface.tx_fal_storage_interface.php',
	'tx_fal_streams_streamwrapperinterface' => $extensionPath . 'classes/streams/interface.tx_fal_streams_streamwrapperinterface.php',
	'tx_fal_streams_streamwrapperadapter' => $extensionPath . 'classes/streams/class.tx_fal_streams_streamwrapperadapter.php',
	'tx_fal_exception_filenotfound' => $extensionPath . 'classes/exception/class.tx_fal_exception_filenotfound.php',

	'tx_fal_list_registry' => $extensionPath . 'mod_extjs/class.tx_fal_list_registry.php',
	'tx_fal_tcafunc' => $extensionPath . 'classes/class.tx_fal_tcafunc.php',
	'tx_fal_hooks_tceforms_dbfileicons' => $extensionPath . 'classes/hooks/class.tx_fal_hooks_tceforms_dbfileicons.php',
	'tx_fal_hooks_browselinks_browserrendering' => $extensionPath . 'classes/hooks/class.tx_fal_hooks_browselinks_browserrendering.php',
	'tslib_fe_content_cobjdata_hook' => $extensionPath . 'classes/class.tslib_fe_content_cobjdata_hook.php',

	'tx_fal_plupload_dataprovider' => $extensionPath . 'classes/dataprovider/class.tx_fal_plupload_dataprovider.php',
	'tx_fal_extfilefunc' => $extensionPath . 'classes/dataprovider/class.tx_fal_extfilefunc.php',

);

?>