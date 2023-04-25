.. include:: /Includes.rst.txt

.. _important-100634-1681822129:

=============================================================
Important: #100634 - Rich Text Editor always enabled per user
=============================================================

See :issue:`100634`

Description
===========

Back in TYPO3 v3.x there was an RTE integrated into TYPO3 which only worked
in Internet Explorer 4+, but not in Mozilla / Firefox browsers. This was a
huge mess, as not every user / client was able to use an RTE and instead to had
to write pure HTML in a :html:`<textarea>` input field with special tags ("typolink" etc).

Since TYPO3 v4 a huge effort were made to integrate HTMLarea as Rich Text Editor,
which was forked and developed by the TYPO3 community. It was then possible for
most users working with a real RTE.

In v8, TYPO3 migrated towards CKEditor 4 as a dependency, and :ref:`CKEditor 5 <feature-96874-1664488673>` with
TYPO3 v12, the Rich Text Editor is working very browser-native for modern browsers
without an iframe around the RTE.

A lot of legacy code was moved and migrated, however, one option - the option
to deactivate the Rich Text Editor on a per-user basis - which was necessary in
TYPO3 v3, has now been removed, as it is not needed in 99.99%
of TYPO3 installations and users anymore nowadays.

Impact
======

The previous user TSconfig setting :typoscript:`setup.edit_RTE` has no effect anymore.

.. index:: TSConfig, ext:setup
