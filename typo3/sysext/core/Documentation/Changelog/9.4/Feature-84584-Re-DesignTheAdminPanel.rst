.. include:: /Includes.rst.txt

===========================================
Feature: #84584 - Re-Design the admin panel
===========================================

See :issue:`84584`

Description
===========

The admin panel got a complete overhaul regarding its design as well as the underlying code and extensibility.

UI wise the following changes were done

- Ajax is used to save configuration options, so only a single reload is triggered even if multiple settings changed.
- Settings influencing page rendering are grouped together.
- Most important info is available at a glance with possibilities to show extended information.


Impact
======

The new admin panel provides a better look and feel as well as more convenient access to information and more flexible extensibility.
For backwards compatibility enabling and disabling modules or options for editors is still possible via User TSConfig.


.. index:: Frontend, PHP-API, ext:adminpanel
