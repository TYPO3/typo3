
.. include:: ../../Includes.txt

=============================================================
Breaking: #67749 - Force class auto loading for various hooks
=============================================================

See :issue:`67749`

Description
===========

Some hook registrations now rely on class auto loading.


Impact
======

Hooks may not be called any longer if class auto loader can not find the class.


Affected Installations
======================

Instances using

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck']


Migration
=========

Make sure the hook classes are found with the casual auto loading mechanism
that is also used for all other PHP classes. The hook registration can be
simplified to an empty value, example:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\TYPO3\CMS\Saltedpasswords\Evaluation\FrontendEvaluator::class] = '';

