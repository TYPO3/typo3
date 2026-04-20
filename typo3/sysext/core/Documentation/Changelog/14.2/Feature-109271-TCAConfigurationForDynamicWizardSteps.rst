..  include:: /Includes.rst.txt

..  _feature-109271-1742217000:

=======================================================================
Feature: #109271 - Add TCA configuration for page creation wizard steps
=======================================================================

See :issue:`109271`

Description
===========

The page creation wizard now supports the dynamic configuration of its steps
via TCA. This allows integrators to define which fields are displayed in
which step of the wizard, depending on the `doktype`.

A new TCA configuration option `wizardSteps` is introduced. It currently
works only for the `pages` table. Each step is defined by a unique key and
contains a title and a list of fields to be displayed.

The steps are sorted, allowing them to be positioned relative to each other
using the `after` or `before` keys.

If a page type has required fields that are not explicitly assigned to a
configured wizard step, a fallback step is automatically appended at the end.

Example
-------

Defining wizard steps for a custom `doktype` in TCA:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    $GLOBALS['TCA']['pages']['types']['123']['wizardSteps'] = [
        'setup' => [
            'title' => 'LLL:backend.wizards.page:step.setup',
            'fields' => ['title', 'slug', 'nav_title', 'hidden', 'nav_hide'],
        ],
        'special' => [
            'title' => 'LLL:my_extension.messages:wizard.special_step',
            'fields' => ['my_custom_field'],
            'after' => ['setup'],
        ],
    ];

Impact
======

It is now possible to configure fields, steps, and their order in the
page creation wizard.

..  index:: TCA, Backend, ext:backend
