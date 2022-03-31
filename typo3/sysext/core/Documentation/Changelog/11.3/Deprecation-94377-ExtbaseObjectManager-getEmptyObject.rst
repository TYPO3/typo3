.. include:: /Includes.rst.txt

===========================================================
Deprecation: #94377 - Extbase ObjectManager->getEmptyObject
===========================================================

See :issue:`94377`

Description
===========

Extbase has the odd behavior that
:php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface` objects
- typically classes in :file:`Classes/Domain/Model` of Extbase enabled
extensions - don't call :php:`__construct` when the persistence layer
"thaws" a model from database - typically when a Extbase
:php:`Domain/Repository` uses a :php:`->findBy*` method.

As a side-effect of switching away from Extbase :php:`ObjectManager` towards
symfony based dependency injection, this behavior will change in TYPO3 v12:
Method :php:`__construct()` will be called in v12 when the :php:`DataMapper`
creates model instances from database rows.


Impact
======

There is no impact in TYPO3 v11 and no deprecation log entry is raised.
However, extension developers *should* prepare toward this change in v11
to avoid any impact of a breaking change in v12.


Affected Installations
======================

Extbase extensions having domain models that implement :php:`__construct()`
are affected. It is rather unlikely this has any impact on the behavior
of the extension, though.

Additionally, calls to API method
:php:`TYPO3\CMS\Extbase\Object\ObjectManager->getEmptyObject()` should be
avoided since it will vanish in v12. The vast majority of extensions will
not do this, though. The extension scanner will find candidates.


Migration
=========

No migration possible. Simply expect that :php:`__construct()` of a domain
model will be called in v12 when a domain repository :php:`findBy` method
directly or indirectly reconstitutes a model object from a database row.

.. index:: PHP-API, FullyScanned, ext:extbase
