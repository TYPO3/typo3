
.. include:: /Includes.rst.txt

===============================================================
Breaking: #69291 - Changed registration of backend module icons
===============================================================

See :issue:`69291`

Description
===========

The sprite icon support for backend modules introduced with CMS 7.3 has been adjusted again. The configuration has been streamlined.


Impact
======

The specified icon will not be recognized.


Affected Installations
======================

Any installation running TYPO3 CMS 7.3+ having third party extensions which use sprite icons for backend modules.


Migration
=========

Change the configuration from

.. code-block:: php

	'configuration' => array(
		'icon' => 'module-web',
	),

to

.. code-block:: php

	'iconIdentifier' => 'module-web',


.. index:: PHP-API, Backend
