.. include:: /Includes.rst.txt

.. _breaking-97787-1657629392:

====================================================================================
Breaking: #97787 - AbstractMessage->getSeverity() returns ContextualFeedbackSeverity
====================================================================================

See :issue:`97787`

Description
===========

The class :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage` and the extended
class :php:`\TYPO3\CMS\Core\Messaging\FlashMessage` both have a method
:php:`getSeverity()` to return a flash message's severity. The return type of
the method is changed to return an instance of :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity`.

As this method isn't supposed to be used publicly, it is declared `internal` now.

Impact
======

Relying on the return type of :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage->getSeverity()`
being `int` will throw a :php:`TypeError` exception.

There is no negative impact in the following cases:

* Using the severity enum in Fluid for direct rendering
* Using the severity enum in :php:`json_encode()`

In these cases, the enum's value is automatically used.

Affected installations
======================

All extensions using :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage->getSeverity()`
in PHP are affected, if the integer type is expected.

Migration
=========

If the integer type of :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage->getSeverity()`
is expected, use the :php:`value` property of the :php:`ContextualFeedbackSeverity` enum:

..  code-block:: php

    $flashMessage = new \TYPO3\CMS\Core\Messaging\FlashMessage('This is a message');
    $severityAsInt = $flashMessage->getSeverity()->value;

The same applies to Fluid template, where the severity is used within another
structure, e.g. as an array key:

..  code-block:: html

    <div class="x" class="{severityClassMapping.{status.severity.value}}">
        <!-- stuff happens here -->
    </div>

.. index:: PHP-API, NotScanned, ext:core
