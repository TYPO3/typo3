.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _oracle-configuring-database-access:

Configuring Database Access
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Edit file ``typo3conf/localconf.php`` again and add these lines::

	$typo_db_username = 'username';
	$typo_db_password = 'password';
	$typo_db_host = 'localhost';
	$typo_db = 'ServiceNameOrSID';

Unlike MySQL you don't connect to a server and select a database.
Instead, you connect as a user and use the default schema. To do this
you can either use the SID or the name of the service ("XE" when you
use 10g XE). You must set the hostname and the service name in
``$typo_db_host`` and ``$typo_db``. It is not possible to set them in one
as " *//hostname/servicename* ".

If you need to use another default port than 1521, use following
syntax for ``$typo_db_host``::

	$typo_db_host = 'localhost:1234';

If you wish to use caching framework introduced with TYPO3 4.3, you
have to remap very long table names to prevent the identifier to be
too long for Oracle. Edit the file ``typo3conf/localconf.php`` and
add these lines::

	$TYPO3_CONF_VARS['EXTCONF']['dbal']['mapping'] = array(
	   'cachingframework_cache_hash' => array(
	       'mapTableName' => 'cf_cache_hash',
	   ),
	   'cachingframework_cache_pages' => array(
	       'mapTableName' => 'cf_cache_pages',
	   ),
	   'cachingframework_cache_hash_tags' => array(
	       'mapTableName' => 'cf_cache_hash_tags',
	   ),
	   'cachingframework_cache_pages_tags' => array(
	       'mapTableName' => 'cf_cache_pages_tags',
	   ),
	   'cachingframework_cache_pagesection' => array(
	       'mapTableName' => 'cf_cache_ps',
	   ),
	   'cachingframework_cache_pagesection_tags' => array(
	       'mapTableName' => 'cf_cache_ps_tags',
	   ),
	);

Then use the install tool to create tables and configure TYPO3 as
usual.
