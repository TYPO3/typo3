.. include:: /Includes.rst.txt

.. _deprecation-100721-1682333511:

==========================================================
Deprecation: #100721 - Label-related methods and arguments
==========================================================

See :issue:`100721`

Description
===========

The method :php:`\TYPO3\CMS\Core\Localization\LanguageService->getLL()` has been
marked as deprecated.

Along with the deprecation the method
:php:`\TYPO3\CMS\Core\Localization\LanguageService->includeLLFile()` has been
marked as internal, as it is still used in TYPO3 Core for backwards-compatibility
internally, but not part of TYPO3's Core API anymore.

With the introduction of :ref:`Locales <feature-99694-1674552209>`, it is also now not recommended anymore to use
custom alternative language keys.

For this reason the argument "alternativeLanguageKeys" of the
:html:`<f:translate>` ViewHelper has been deprecated as well, along with the
method argument of the same name in
:php:`\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate()`.


Impact
======

Calling the method :php:`\TYPO3\CMS\Core\Localization\LanguageService->getLL()`
will trigger a PHP deprecation warning.

Calling :php:`\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate()` with
the argument "alternativeLanguageKeys" will also trigger a PHP deprecation warning,
which is the underlying deprecation warning when using the argument
"alternativeLanguageKeys" of the :html:`<f:translate>` ViewHelper.


Affected installations
======================

TYPO3 installations within backend modules using the method :php:`getLL()` or
extensions or templates using the translate methods.

The former usually happens in extensions which have been migrated from older
TYPO3 versions with legacy functionality in backend modules along
with :php:`$GLOBALS['LANG']` as :php:`LanguageService` object.


Migration
=========

It is highly recommended to use the full path to a label file along
with the :php:`sL()` method of :php:`\TYPO3\CMS\Core\Localization\LanguageService`:

Before:

..  code-block:: php

    $GLOBALS['LANG']->includeLLfile('EXT:my_extension/Resources/Private/Language/db.xlf');
    $label = htmlspecialchars($GLOBALS['LANG']->getLL('my_label'));

After:

..  code-block:: php

    $label = $GLOBALS['LANG']->sL('LLL:EXT:my_extension/Resources/Private/Language/db.xlf:my_label');
    $label = htmlspecialchars($label);

.. index:: PHP-API, PartiallyScanned, ext:core
