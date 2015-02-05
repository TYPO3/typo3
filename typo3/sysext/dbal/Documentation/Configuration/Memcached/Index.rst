.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _memcached:

Memcached
^^^^^^^^^

Since TYPO3 4.5.0, prepared queries started to be used in Core for
frequent queries. Since the effort to successfully parse SQL queries
is high, DBAL caches the result of this lengthy process when prepared
queries are issued. Out of the box, DBAL will use the transient memory
cache backend of TYPO3 to store this information. This allows queries
to be cached for the scope of a single request. If Memcached is
configured, it can then cache queries for much longer, thus allowing
DBAL to be much more efficient. Caching may be configured within
``localconf.php``::

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'] = array(
	    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\MemcachedBackend',
	    'options' => array(
	        // port is mandatory!
	        'servers' => array('localhost:11211', 'otherhost:11211', 'thirdhost:11211'),
	    )
	);

You need to have memcached installed as a daemon and also as a PHP
extension.