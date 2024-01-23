.. include:: /Includes.rst.txt

.. _breaking-UnableToLinkException-1687953808:

==========================================================
Breaking: #101186 - Strict typing in UnableToLinkException
==========================================================

See :issue:`101186`

Description
===========

The class constructor in :php:`\TYPO3\CMS\Frontend\Exception\UnableToLinkException`
is now strictly typed. In addition, the variable :php:`$linkText` has type :php:`string`.

Impact
======

The class constructor is now strictly typed.

Affected installations
======================

TYPO3 sites using the :php:`\TYPO3\CMS\Frontend\Exception\UnableToLinkException` exception.

Migration
=========

Ensure that the class constructor is called properly, according to the changed signature:

.. code-block:: php

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        string $linkText = ''
    );


.. index:: Backend, NotScanned, ext:fluid, ext:frontend, ext:redirects
