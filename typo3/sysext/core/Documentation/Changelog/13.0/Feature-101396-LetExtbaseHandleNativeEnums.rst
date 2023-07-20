.. include:: /Includes.rst.txt

.. _feature-101396-1689843367:

==================================================
Feature: #101396 - Let Extbase handle native enums
==================================================

See :issue:`101396`

Description
===========

With PHP 8.1, native support for enums has been introduces. This is quite handy
if a database field has a specific set of values which can be represented by a
PHP enum.

It is now possible to use those enums in entities like this:

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace Vendor\Extension\Domain\Model\Enum;

    enum Level: string
    {
        case INFO = 'info';
        case ERROR = 'error';
    }

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace Vendor\Extension\Domain\Model;

    class LogEntry extends AbstractEntity
    {
        protected Enum\Level $level;
    }

Impact
======

To implement enums, it is no longer necessary to extend the TYPO3 core class
:php:`\TYPO3\CMS\Core\Type\Enumeration`.

.. index:: PHP-API, ext:extbase
