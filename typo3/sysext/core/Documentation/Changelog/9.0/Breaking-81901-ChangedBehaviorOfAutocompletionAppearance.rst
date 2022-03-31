.. include:: /Includes.rst.txt

=================================================================
Breaking: #81901 - Changed behavior of auto-completion appearance
=================================================================

See :issue:`81901`

Description
===========

Due to streamlining t3editor's JavaScript code to CodeMirror's architecture, the behavior of the TS auto-completion
has changed.


Impact
======

After pressing the dot character (.), the auto-completion does not appear automatically. A user has to press the
keystroke Ctrl+Space now.


Affected Installations
======================

All installations are affected.

.. index:: Backend, NotScanned, ext:t3editor
