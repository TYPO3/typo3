.. include:: ../../Includes.txt

=============================================================================
Deprecation: #83592 - impexp: Removed "Maximum number of records" restriction
=============================================================================

See :issue:`83592`


Description
===========

When exporting pages or records using the "Export" interface of
extension :php:`impexp`, the restriction to export only a maximum
number of records has been removed.


Impact
======

The export module now exports any number of records. It is up
to the user to restrict this in a sane way: During import the
number of records influences import runtime. This heavily depends
on given server performance, an artificial limit given by the system
is not suitable.

On PHP level, two export related methods changed their signature:

* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->addRecordsForPid()` -
  Third method argument marked as deprecated

* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->exec_listQueryPid` -
  Third method argument marked as deprecated


Affected Installations
======================

Backend interface users are probably not affected much: Users of the
export module usually set the 'maximum number of records' value high
enough to export everything they wanted already. The interface now
just misses the according input fields.


Migration
=========

On PHP level, the extension scanner will find extensions that use
the changed methods and checks if they are called with the
correct number of arguments. Additionally, :php:`E_USER_DEPRECATED`
errors are logged at runtime if using these methods the old way.

.. index:: Backend, PHP-API, FullyScanned, ext:impexp