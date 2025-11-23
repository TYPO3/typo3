..  include:: /Includes.rst.txt

..  _feature-106934-1750950272:

==============================================================
Feature: #106934 - Add recently used records to record wizards
==============================================================

See :issue:`106934`

Description
===========

A new dynamic category *"Recently used"* has been added to the record creation
wizards for the following components:

*   Content elements
*   Form elements
*   Dashboard widgets

This category lists the record or form types that a user has recently selected
in the respective wizard. It remains hidden until at least one record has been
created, keeping the interface uncluttered for new users.

This enhancement allows backend users to quickly access and reuse frequently
used record types, improving workflow efficiency and usability.

Impact
======

All record wizards now feature a *"Recently used"* section. This feature is
enabled by default and requires no additional configuration.
Users can manage the display of this category in their personal backend
settings.

This improvement streamlines access to frequently used records, enhancing the
overall editing experience for editors and integrators.

Example
=======

When creating a new content element, users will now see a *"Recently used"*
section listing previously used content types, making them accessible with
a single click.

..  index:: Backend, ext:backend
