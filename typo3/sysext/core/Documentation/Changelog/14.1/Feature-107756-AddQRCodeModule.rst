..  include:: /Includes.rst.txt

..  _feature-107756-1763294102:

=====================================
Feature: #107756 - Add QR Code module
=====================================

See :issue:`107756`

Description
===========

A new :guilabel:`Link Management > QR Codes` backend module has been introduced,
grouped alongside the existing :guilabel:`Link Management > Redirects` module.

The QR Codes module provides editors with an efficient way to generate reusable
QR codes for various purposes, such as printing them on promotional materials,
booth displays, or marketing collateral.

Each generated QR code contains a permanent, unique URL that never changes,
ensuring printed materials remain valid indefinitely. While the QR code URL
itself stays constant, the destination it redirects to can be updated at any
time, providing flexibility to adapt campaigns or redirect visitors to current
content without requiring reprints.

The module includes a convenient button to generate QR codes on demand, offering
multiple download options including different formats (PNG, SVG) and customizable
sizes to suit various use cases and printing requirements.

Impact
======

The new QR Code module enables users to create scannable QR codes that redirect
to any specified URL. This feature is particularly valuable for marketing campaigns,
events, and printed materials where maintaining flexibility in the destination URL
is essential while preserving the QR code itself.

..  index:: Backend, ext:redirects
