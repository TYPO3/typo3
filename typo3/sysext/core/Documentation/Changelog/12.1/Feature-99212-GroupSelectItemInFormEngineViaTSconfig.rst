.. include:: /Includes.rst.txt

.. _feature-99212-1669896293:

==============================================================
Feature: #99212 - Group select item in FormEngine via TSconfig
==============================================================

See :issue:`99212`

Description
===========

The existing TSconfig feature :typoscript:`TCEFORM.{tablename}.{fieldname}.addItems`
can now be used to add new items into existing select item groups by using the
:typoscript:`.group` sub-property set to the group identifier. This grouping is
usually shown in select fields with groups available.


Impact
======

When using the TSconfig :typoscript:`addItems` feature, the :typoscript:`group`
property can now be used:

Example:

..  code-block:: typoscript

    TCEFORM.tt_content.layout.addItems {
        new-layout = My new layout
        new-layout.icon = icon-identifier
        new-layout.group = special
    }

.. index:: TSConfig, ext:backend
