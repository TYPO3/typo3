
.. include:: ../../Includes.txt

=================================================================================
Deprecation: #75340 - Methods related to generating traditional Backend AJAX URLs
=================================================================================

See :issue:`75340`

Description
===========

The following methods have been marked as deprecated:

* TYPO3\CMS\Backend\Utility\BackendUtility->getAjaxUrl()
* TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromAjaxId()


Impact
======

Calling one of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance with a third party extension that calls one of the methods above.


Migration
=========

Migrate to UriBuilder routes, which can be registered via Configuration/Backend/AjaxRoutes.php,
and can be linked to like this:

.. code-block:: php

	/** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
	$uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
	$path = $uriBuilder->buildUriFromRoute('ajax_myroute');

Keep in mind that the newly created Ajax routes need to implement PSR-7 as well.

.. index:: PHP-API, Backend