..  include:: /Includes.rst.txt

..  _deprecation-107963-1762781032:

==========================================================================
Deprecation: #107963 - sys_redirect default type name changed to "default"
==========================================================================

See :issue:`107963`

Description
===========

The default type name for the :sql:`sys_redirect` table has been changed
from :php:`'1'` to :php:`'default'` in TYPO3 v14.0 to align with TYPO3's
naming conventions and allow for better type extensibility.

Extensions that directly access
:php:`$GLOBALS['TCA']['sys_redirect']['types']['1']` to manipulate the
redirect TCA configuration will need to be updated to use the new
:php:`'default'` key instead.

Impact
======

Direct access to :php:`$GLOBALS['TCA']['sys_redirect']['types']['1']` will
no longer work as the type has been renamed to :php:`'default'`.

Extensions that manipulate the sys_redirect TCA type configuration need to
be updated to use the new type name.

The TCA migration layer will automatically migrate any custom
:php:`$GLOBALS['TCA']['sys_redirect']['types']['1']` definitions to
:php:`'default'` during the TCA compilation process, but a deprecation
message will be logged.

Affected installations
======================

Instances with extensions that directly manipulate
:php:`$GLOBALS['TCA']['sys_redirect']['types']['1']` to customize the default
redirect record type.

Migration
=========

Update your TCA override files to use the new type name :php:`'default'`
instead of :php:`'1'`.

.. code-block:: php

    // Before - In Configuration/TCA/Overrides/sys_redirect.php
    $GLOBALS['TCA']['sys_redirect']['types']['1']['label'] = 'My custom label';

    // After - In Configuration/TCA/Overrides/sys_redirect.php
    $GLOBALS['TCA']['sys_redirect']['types']['default']['label'] = 'My custom label';

..  index:: TCA, NotScanned, ext:redirects
