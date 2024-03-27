.. include:: /Includes.rst.txt

.. _feature-83835-1711517686:

=====================================================
Feature: #83835 - Check more fields in Link Validator
=====================================================

See :issue:`83835`

Description
===========

Some additional fields were added to Page TSconfig
:typoscript:`mod.linkvalidator.searchFields`:

*   :typoscript:`pages = canonical_link`
*   :typoscript:`sys_redirect = target`
*   :typoscript:`sys_file_reference = link`

Two special fields are currently defined, but are
not checked yet due to their TCA configuration. For forward
compatibility, these are kept in the field configuration:

*   :typoscript:`pages = media` has TCA `type="file"`
*   :typoscript:`tt_content = records` has TCA `type="group"`

The following fields could theoretically be included in
custom configurations, as their type / softref is available,
but they are specifically not added in the default configuration:

*   :typoscript:`sys_webhook = url` (webhook should not be invoked)
*   :typoscript:`tt_content = subheader` (has softref `email[subst]`
    which is not a supported link type)
*   :typoscript:`pages = tsconfig_includes` (system configuration)
*   :typoscript:`sys_template = constants, include_static_file, config`
    (system configuration)
*   :typoscript:`tx_scheduler_task_group = groupName` (scheduler
    system configuration)


Impact
======

Broken links in `sys_file_reference.link`, `sys_redirect.target` and
`pages.canonical_link` will now be checked.

Any enabled field will only be checked, if there is TCA configured,
so for example `pages.canonical_link` will only be checked if `EXT:seo` is
installed.

.. index:: ext:linkvalidator
