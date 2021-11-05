.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

For various reasons your TYPO3 installation may over time accumulate data with
integrity problems or data you wish to delete completely. For instance, why keep
old versions of published content? Keep that in your backup - don't load your
running website with that overhead!

Or what about deleted records? Why not flush them - they also fill up your
database and filesystem and most likely you can rely on your backups in case of
an emergency recovery?

Also, relations between records and files inside TYPO3 may be lost over time for
various reasons.

If your website runs as it should such "integrity problems" are mostly easy to
automatically repair by simply removing the references pointing to a missing
record or file.

However, it might also be "soft references" from eg. internal links
(:html:`<a href="t3://page?id=123">...</a>`) or a file referenced in a TypoScript
template (:typoscript:`something.file = fileadmin/template/miss_me.jpg`) which are missing.
Those cannot be automatically repaired but the cleanup script incorporates
warnings that will tell you about these problems if they exist and you can
manually fix them.

These scripts provide solutions to these problems by offering an array of tools
that can analyze your TYPO3 installation for various problems and in some cases
offer fixes for them.
