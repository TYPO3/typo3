.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _oracle-installing-and-configuring-adodb-and-dbal:

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
	           'driver' => 'oci8',
	           'driverOptions' => array(
	               'connectSID' => FALSE,
	           ),
	       )
	   ),
	);

This allows you to connect to an Oracle server using a serviceName
(see below). If you wish to use a SID instead, make sure to set the
driver option ``connectSID`` to ``TRUE``.
