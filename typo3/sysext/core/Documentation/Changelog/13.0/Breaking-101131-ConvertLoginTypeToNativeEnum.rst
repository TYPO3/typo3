.. include:: /Includes.rst.txt

.. _breaking-101131-1687289195:

===========================================================
Breaking: #101131 - Convert LoginType to native backed enum
===========================================================

See :issue:`101131`

Description
===========

The class :php:`\TYPO3\CMS\Core\Authentication\LoginType` is now
converted to a native backed enum.

Impact
======

Since :php:`\TYPO3\CMS\Core\Authentication\LoginType` is no longer
a class, the existing class constants are no longer available.

Affected installations
======================

Custom authenticators using the following class constants:

- :php:`\TYPO3\CMS\Core\Authentication\LoginType::LOGIN`
- :php:`\TYPO3\CMS\Core\Authentication\LoginType::LOGOUT`

Migration
=========

Use the new syntax:
:php:`\TYPO3\CMS\Core\Authentication\LoginType::LOGIN->value`
:php:`\TYPO3\CMS\Core\Authentication\LoginType::LOGOUT->value`

Alternatively, use the enum method :php:`tryFrom` to convert a
value to an enum. For direct comparison of two enums, the null-coalescing
operator shall be used to ensure that the parameter is a string:

.. code-block:: php

    <?php

    use TYPO3\CMS\Core\Authentication\LoginType;

    if (LoginType::tryFrom($value ?? '') === LoginType::LOGIN) {
        // Do login stuff
    }
    if (LoginType::tryFrom($value ?? '') === LoginType::LOGOUT) {
        // Do logout stuff
    }

.. index:: Backend, Authentication, NotScanned, ext:core
