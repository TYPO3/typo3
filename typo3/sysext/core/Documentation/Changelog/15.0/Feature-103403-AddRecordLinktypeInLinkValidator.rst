.. include:: /Includes.rst.txt

.. _feature-103403-1711207575:

=======================================================
Feature: #103403 - Check record links in Link Validator
=======================================================

See :issue:`103403`

Description
===========

The Link Validator is now able to check links using the record link handler.
They are used to link to records of custom tables, for example news:

..  code-block:: text

    t3://record?identifier=tx_news&uid=99999

Links in this format have not been checked so far. The former
`LinkHandler <https://github.com/TYPO3/typo3/blob/10.4/typo3/sysext/linkvalidator/Classes/Linktype/LinkHandler.php>`__
link type, which has been removed in TYPO3 v11, only supported the legacy
`record:<table>:<uid>` syntax.

The new `record` link type reports links to records which do not exist or
are deleted. Links to hidden, not yet started or expired records are reported
as well, which can be disabled with the new TSconfig option
`mod.linkvalidator.reportHiddenRecords` (see the
:ref:`TSconfig reference <ext_linkvalidator:reference>`). The similar option
`mod.linkvalidator.linkhandler.reportHiddenRecords` of the former link type
was disabled by default and has been removed in TYPO3 v12.

Impact
======

The new `record` link type is enabled by default in
`mod.linkvalidator.linktypes`. Existing installations that set
`linktypes` explicitly need to add `record` to check these links.

Links to hidden, not yet started or expired records are reported as broken
unless `mod.linkvalidator.reportHiddenRecords` is set to `0`. Links whose
table cannot be resolved, for example because no `TCEMAIN.linkHandler`
configuration applies to the page of the linking record, are reported with
the table or link identifier.

Caveat
======

..  warning::
    The :php:`RecordLinktype` only validates the existence and visibility of
    the record in the database. It does not check whether a public URL can be
    generated for the record, for example a detail page. Access restrictions
    (`fe_group`), the visibility of the page the record is stored on and
    workspace versions are not taken into account.

.. index:: Backend, TSConfig, ext:linkvalidator
