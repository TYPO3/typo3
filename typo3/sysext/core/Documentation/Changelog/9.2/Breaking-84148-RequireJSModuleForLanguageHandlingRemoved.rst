.. include:: /Includes.rst.txt

=================================================================
Breaking: #84148 - RequireJS module for language handling removed
=================================================================

See :issue:`84148`

Description
===========

Since the removal of ExtJS, the JavaScript files that handled the localization of labels in backend modules became
obsolete and have been removed.


Impact
======

Depending on the RequireJS module :js:`TYPO3/CMS/Lang/Lang` will result in `404` errors, as the module has been removed.


Affected Installations
======================

Every 3rd party extension depending on :js:`TYPO3/CMS/Lang/Lang` is affected.


Migration
=========

Remove the module from the affected RequireJS modules. The labels are now prepared by the PageRenderer and passed
to :js:`TYPO3.lang` without the need of additional JavaScript.

.. index:: Backend, JavaScript, NotScanned
