..  include:: /Includes.rst.txt

..  _feature-106934-1750950272:

==============================================================
Feature: #106934 - Add recently used records to record wizards
==============================================================

See :issue:`106934`

Description
===========

A new dynamic category "Recently used" has been added to record creation wizard,
used for creation of:

- Content elements
- Form elements
- Dashboard widgets

This category displays record / form types, the user has recently selected
via the respective wizard. It remains hidden until at least one record has
been selected, ensuring a clean interface for first-time users. This enhancement
allows backend users to quickly access and reuse often used types directly at
first glance. This improves workflow efficiency and usability.

Impact
======

All record wizards now feature a "Recently used" section. This feature is
available by default and does not require additional configuration. However,
via the user settings, it is possible to manage the display of this category.

It enhances user experience for editors and integrators by streamlining access
to frequently used records.

Example
=======

When creating a new content element, users will now see a "Recently used"
section listing previously used content types, making them accessible with
just a click.

..  index:: Backend, ext:backend
