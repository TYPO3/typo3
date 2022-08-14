.. include:: /Includes.rst.txt

.. _important-98090-1660490213:

============================================================================
Important: #98090 - Use preconfigured UTF-8 filesystem on first installation
============================================================================

See :issue:`98090`

Description
===========

Back in the old and dark days, some filesystems did not know about "special"
chars. TYPO3 has the :php:`TYPO3_CONF_VARS` toggle :php:`['SYS']['UTF8filesystem']`
to declare if filesystems are UTF-8 aware.

This toggle is :php:`false` by default since ever. It triggers functionality to
rename any file that contains characters like umlauts, or maybe entirely consist
of "special" chars only (japanese) to something "safe".

This is a usability issue since information is lost and language-specific
characters are destroyed.

Nowadays every serious filesystem supports UTF-8.

There are no issues related to UTF8filesystem=true for years.

The patch now sets UTF8filesystem=true for new installations to see if anything
still pops up. If that works out, we'll continue with further patches in v13 to
further phase out the option entirely.

.. index:: LocalConfiguration, ext:core
