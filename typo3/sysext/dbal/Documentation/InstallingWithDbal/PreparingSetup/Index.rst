.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _preparing-setup:

Preparing setup
^^^^^^^^^^^^^^^

Unpack the TYPO3 source as usual, and unpack a dummy package. Set
everything up as explained in the setup documentation, until you come
to the point where you are asked to start the install tool – DON'T DO
THIS YET!

Extensions DBAL and ADOdb are part of the system extensions shipped
with TYPO3. As such, you only have to load them.

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
	           'driver' => 'mysql',
	       )
	   ),
	);

Of course you need to adjust the DBAL configuration as you need to,
the example above does nothing but route everything through ADOdb
inside the DBAL extension. See appendices for specific DBMS setup
tutorials.
