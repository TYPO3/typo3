.. include:: /Includes.rst.txt

===================================================================
Deprecation: #89870 - New PSR-14 Events for Extbase-related signals
===================================================================

See :issue:`89870`

Description
===========

The following signals have been marked as deprecated in favor of new PSR-14 events:

- :php:`TYPO3\CMS\Extbase\Mvc\Dispatcher::afterRequestDispatch`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::beforeCallActionMethod`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::afterMappingSingleRow`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::beforeGettingObjectData`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterGettingObjectData`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::endInsertObject`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterUpdateObject`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterPersistObject`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterRemoveObject`

The method :php:`emitBeforeCallActionMethodSignal` in :php:`ActionController`
has been marked as deprecated and is not called by Extbase itself anymore.

Impact
======

Using any of the signals will still work as expected, but will trigger
a PHP :php:`E_USER_DEPRECATED` error.

Calling the method :php:`emitBeforeCallActionMethodSignal` will trigger a
PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

TYPO3 installations with extensions using the Extbase framework and
Extbase-internal hooks.


Migration
=========

The following new PSR-14-based Events should be used instead:

- :php:`TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent`
- :php:`TYPO3\CMS\Extbase\Event\Mvc\BeforeActionCallEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\AfterObjectThawedEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityPersistedEvent`

.. index:: PHP-API, PartiallyScanned, ext:extbase
