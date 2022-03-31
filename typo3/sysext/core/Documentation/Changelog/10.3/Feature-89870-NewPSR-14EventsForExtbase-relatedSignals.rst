.. include:: /Includes.rst.txt

===============================================================
Feature: #89870 - New PSR-14 Events for Extbase-related signals
===============================================================

See :issue:`89870`

Description
===========

The following new PSR-14-based Events are introduced which allow
to modify various concerns in the MVC and persistence stacks of Extbase internals.

- :php:`TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent`
- :php:`TYPO3\CMS\Extbase\Event\Mvc\BeforeActionCallEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\AfterObjectThawedEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityFinalizedAfterPersistenceEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent`
- :php:`TYPO3\CMS\Extbase\Event\Persistence\EntityPersistedEvent`


Impact
======

Existing signals are replaced and should not be used anymore, as PSR-14 event classes exactly specify what can be modified or listened to.

The following signals should not be used anymore then:

- :php:`TYPO3\CMS\Extbase\Mvc\Dispatcher::afterRequestDispatch`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::beforeCallActionMethod`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::afterMappingSingleRow`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::beforeGettingObjectData`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterGettingObjectData`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterInsertObject`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::endInsertObject`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterUpdateObject`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterPersistObject`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterRemoveObject`

.. index:: PHP-API, ext:extbase
