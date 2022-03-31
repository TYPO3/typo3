.. include:: /Includes.rst.txt

==========================================================================
Important: #18079 - pages.doktype restriction for frontend queries refined
==========================================================================

See :issue:`18079`

Description
===========

Since over 15 years, TYPO3's Frontend rendering had a restriction to only allow
pages with a "page type" (pages.doktype such as "Shortcut", "Link to external URL") to be limited to a fixed number less than 200.

This meant that pages of certain types such as a Sys Folder and Recycler never were
respected when fetching content from a specific page (via Typoscript) or querying records from there.

This limitation has now been lifted in order to fix certain bugs,
such as "content sliding" via TypoScript. But this also allows custom page doktypes to be used that have a number higher than 200.

This could potentially result in unexpected behavior in TypoScript or content fetching, if the previous limited behavior was mis-used
for certain purposes.

.. index:: Frontend, TypoScript, ext:frontend
