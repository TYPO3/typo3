
.. include:: ../../Includes.txt

========================================================
Breaking: #62983 - postProcessMirrorUrl signal has moved
========================================================

See :issue:`62983`

Description
===========

While refactoring the Language backend module, the
`\TYPO3\CMS\Lang\Service\UpdateTranslationService::postProcessMirrorUrl` signal got lost. Due to
the refactoring, it has been integrated in another class.


Impact
======

Using the old signal will prevent the slot from being called.


Affected Installations
======================

All extensions are affected that use the old
`\TYPO3\CMS\Lang\Service\UpdateTranslationService::postProcessMirrorUrl`
signal.


Migration
=========

Change the slot to use the `\TYPO3\CMS\Lang\Service\TranslationService::postProcessMirrorUrl`
signal. If it is required to serve multiple TYPO3 versions, use the following code:

.. code-block:: php

	$signalSlotDispatcher->connect(
		version_compare(TYPO3_version, '7.0', '<')
			? 'TYPO3\\CMS\\Lang\\Service\\UpdateTranslationService'
			: 'TYPO3\\CMS\\Lang\\Service\\TranslationService',
		'postProcessMirrorUrl',
		'Vendor\\Extension\\Slots\\CustomMirror',
		'postProcessMirrorUrl'
	);


.. index:: PHP-API, Backend, ext:lang
