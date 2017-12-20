
.. include:: ../../Includes.txt

=========================================================================
Feature: #64921 - Needed changes for flexible configuration of submodules
=========================================================================

See :issue:`64921`

Description
===========

The additional configuration for submodules can now be configured with an extra
parameter in `ExtensionManagementUtility::addModule()` since https://forge.typo3.org/issues/62880.
This makes it possible to remove the conf.php file by setting the configuration within `ExtensionManagementUtility::addModule()` in ext_tables.php.

When doing so you might have some issues with $this->MCONF not being set. This happens if your backend module extends
from `\TYPO3\CMS\Backend\Module\BaseScriptClass`.

To fix this problem you need to add the module name in $this->MCONF.

.. code-block:: php

	/**
	 * The name of the module
	 *
	 * @var string
	 */
	protected $moduleName = 'file_list';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->MCONF = array(
			'name' => $this->moduleName
		);
	}


.. index:: PHP-API, Backend
