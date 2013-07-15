.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _postgresql-configuring-database-access:

Configuring Database Access
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Edit file ``typo3conf/localconf.php`` again and add these lines::

	$typo_db_username = 'username';
	$typo_db_password = 'password';
	$typo_db_host = 'localhost';
	$typo_db = 'database';

Then use the install tool to create tables and configure TYPO3 as usual.
