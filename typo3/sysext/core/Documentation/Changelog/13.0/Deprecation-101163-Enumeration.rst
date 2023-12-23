.. include:: /Includes.rst.txt

.. _deprecation-101163-1681741493:

=================================================
Deprecation: #101163 - Abstract class Enumeration
=================================================

See :issue:`101163`

Description
===========

The abstract class :php:`\TYPO3\CMS\Core\Type\Enumeration` is deprecated
in favor of PHP built-in backed `enums`_.

..  _enums: https://www.php.net/manual/en/language.types.enumerations.php

Impact
======

All classes extending :php:`\TYPO3\CMS\Core\Type\Enumeration` will trigger a
deprecation level log entry.

Affected installations
======================

Classes extending :php:`\TYPO3\CMS\Core\Type\Enumeration` need to be converted
into PHP built-in backed enums.

Migration
=========

Class definition:

..  code-block:: php

    class State extends \TYPO3\CMS\Core\Type\Enumeration
    {
        public const STATE_DEFAULT = 'somestate';
        public const STATE_DISABLED = 'disabled';
    }

should be converted into:

..  code-block:: php

    enum State: string
    {
        case STATE_DEFAULT = 'somestate';
        case STATE_DISABLED = 'disabled';
    }

Existing method calls must be adapted.

See also :ref:`feature-101396-1689843367`.

.. index:: Backend, FullyScanned, ext:core
