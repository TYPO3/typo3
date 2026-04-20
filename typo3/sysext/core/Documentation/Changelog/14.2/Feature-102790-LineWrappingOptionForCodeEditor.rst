..  include:: /Includes.rst.txt

..  _feature-102790-1738838400:

=======================================================
Feature: #102790 - Line wrapping option for code editor
=======================================================

See :issue:`102790`

Description
===========

A new TCA appearance option `lineWrapping` has been added for the
`codeEditor` render type. When enabled, long lines are wrapped inside
the editor instead of requiring horizontal scrolling.

Example:

..  code-block:: php

    'config' => [
        'type' => 'text',
        'renderType' => 'codeEditor',
        'format' => 'html',
        'appearance' => [
            'lineWrapping' => true,
        ],
    ],

Impact
======

Code editor fields can now be configured to wrap long lines by setting
`lineWrapping` in the `appearance` array.

..  index:: Backend, TCA, ext:backend
