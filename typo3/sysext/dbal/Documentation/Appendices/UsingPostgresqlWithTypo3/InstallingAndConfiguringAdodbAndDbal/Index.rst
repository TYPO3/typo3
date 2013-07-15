.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _postgresql-installing-and-configuring-adodb-and-dbal:

Installing and Configuring ADOdb and DBAL
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Edit the file ``typo3conf/localconf.php`` and append "adodb,dbal" to
the comma-separated list of extensions found in
``$TYPO3_CONF_VARS['EXT']``. Another option is to add following code to
the end of the file::

	$TYPO3_CONF_VARS['EXT']['extList'] .= ',adodb,dbal';

Now add following code to the end of the same file ``typo3conf/localconf.php``::

	$TYPO3_CONF_VARS['EXTCONF']['dbal']['handlerCfg'] = array(
	   '_DEFAULT' => array(
	       'type' => 'adodb',
	       'config' => array(
	           'driver' => 'postgres',
	       )
	   ),
	);
