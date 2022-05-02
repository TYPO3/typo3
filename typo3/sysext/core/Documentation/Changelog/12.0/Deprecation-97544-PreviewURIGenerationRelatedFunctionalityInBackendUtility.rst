.. include:: /Includes.rst.txt

====================================================================================
Deprecation: #97544 - Preview URI Generation related functionality in BackendUtility
====================================================================================

See :issue:`97544`

Description
===========

With :issue:`91123` the :php:`PreviewUriBuilder` has been introduced.
To further streamline any preview URI generation code, the related
functionality has now been fully integrated into :php:`PreviewUriBuilder`
along with two new PSR-14 Events. Therefore, the previously used
:php:`BackendUtility::getPreviewUrl()` method, as well as the related hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']`,
has been deprecated.

Impact
======

Using the utility method or registering hooks will raise a deprecation
level log entry. The extension scanner will detect usages.


Affected installations
======================

All installations using the utility method or the hook in custom extensions.


Migration
=========

Migrate any usage of :php:`BackendUtility::getPreviewUrl()` to
:php:`PreviewUriBuilder->buildUri()`.

Replace any :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']`
hook by using the new :doc:`PSR-14 Events <../12.0/Feature-97544-PSR-14EventsForModifyingPreviewURIs>`.
The :php:`BeforePagePreviewUriGeneratedEvent` can be used as replacement for
the hooks' :php:`preProcess()` method, while the :php:`AfterPagePreviewUriGeneratedEvent`
can be used as replacement for the hooks' :php:`postProcess()` method.

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:backend
