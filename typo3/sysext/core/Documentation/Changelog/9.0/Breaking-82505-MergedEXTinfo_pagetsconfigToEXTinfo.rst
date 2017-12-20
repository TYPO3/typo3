.. include:: ../../Includes.txt

===========================================================
Breaking: #82505 - Merged EXT:info_pagetsconfig to EXT:info
===========================================================

See :issue:`82505`

Description
===========

The extension `info_pagetsconfig` has been merged into the extension `info`.


Impact
======

The extension `info` now contains the `PageTSconfig` analysis as well, without the possibility to disable this submodule
function on a per-system functionality.


Affected Installations
======================

Installations with extensions with checks for extension `info_pagetsconfig` being installed.


Migration
=========

Change the extensions to check for `info` instead of `info_pagetsconfig`.

.. index:: Backend, NotScanned, ext:info
