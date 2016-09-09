
.. include:: ../../Includes.txt

======================================================================================
Feature: #68895 - Introduced hook in BackendUserAuthentication::getDefaultUploadFolder
======================================================================================

See :issue:`68895`

Description
===========

It is now possible to change the upload folder returned by `BackendUserAuthentication::getDefaultUploadFolder()` by
registering a hook. This makes it possible to set a different upload folder for fields with direct upload enabled in the
backend.


Register own getDefaultUploadFolder hook
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

To use your own hook to manipulate the upload folder you need to register the function in `ext_localconf.php` of
your extension.

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getDefaultUploadFolder'][] =
		\Vendor\MyExtension\Hooks\DefaultUploadFolder::class . '->getDefaultUploadFolder';


Example getDefaultUploadFolder hook
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

	<?php
	namespace Vendor\MyExtension\Hooks;

	use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
	use TYPO3\CMS\Core\Resource\Folder;

	/**
	 * Class DefaultUploadFolder
	 */
	class DefaultUploadFolder {

		/**
		 * Get default upload folder
		 *
		 * If there is a folder present with the same name as the last part of the table name use that folder.
		 *
		 * @param array $params
		 * @param BackendUserAuthentication $backendUserAuthentication
		 * @return Folder
		 */
		public function getDefaultUploadFolder($params, BackendUserAuthentication $backendUserAuthentication) {

			/** @var Folder $uploadFolder */
			$uploadFolder = $params['uploadFolder'];
			$pid = $params['pid'];
			$table = $params['table'];
			$field = $params['field'];

			$matches = [];
			if (!empty($uploadFolder) && preg_match('/_([a-z]+)$/', $table, $matches)) {
				$folderName = $matches[1];
				if ($uploadFolder->hasFolder($folderName)) {
					$uploadFolder = $uploadFolder->getSubfolder($folderName);
				}
			}
			return $uploadFolder;
		}
	}
