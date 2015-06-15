=============================================================
Deprecation: #67402 - Extbase AbstractDomainObject __wakeup()
=============================================================

Description
===========

Method ``__wakeup()`` has been marked as deprecated in ``TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject``.


Affected Installations
======================

An instance is affected if own domain objects extending AbstractDomainObject
implement ``__wakeup()`` and call ``parent::__wakeup()`` as documented.


Migration
=========

Remove calls to ``parent::__wakeup()`` from own ``__wakeup()`` implementations.
