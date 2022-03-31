.. include:: /Includes.rst.txt

==============================================================
Important: #89938 - Removed dead code from Extbase persistence
==============================================================

See :issue:`89938`

Description
===========

The following public methods have been removed from Extbase persistence:

- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend->getSession()`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend->getQomFactory()`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend->getReflectionService()`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper->isPersistableProperty()`
- :php:`TYPO3\CMS\Core\Session\SessionManager->replaceReconstitutedEntity()`
- :php:`TYPO3\CMS\Core\Session\SessionManager->isReconstitutedEntity()`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend->getMaxValueFromTable()`
- :php:`TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend->getRowByIdentifier()`


.. index:: PHP-API, FullyScanned, ext:extbase
