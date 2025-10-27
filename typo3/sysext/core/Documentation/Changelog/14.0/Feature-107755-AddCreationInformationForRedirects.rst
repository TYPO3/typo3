..  include:: /Includes.rst.txt

..  _feature-107755-1761763869:

=========================================================
Feature: #107755 - Add creation information for redirects
=========================================================

See :issue:`107755`

Description
===========

The redirects component has been extended with additional meta information
to improve audit trails and team collaboration. Redirects now automatically
capture and display:

*   Creation timestamp
*   Creating backend user

After months or even years, teams frequently wonder why a particular redirect
exists and who created it. Displaying the creator (backend user) and creation
date directly in the backend module makes auditing and communication
significantly easier.

Impact
======

All newly created redirects will automatically include creation information,
regardless of whether they are created:

*   Manually through the redirects backend module
*   Automatically when updating a page slug
*   Programmatically through the DataHandler API

Existing redirects created before this feature will onyl show the creation
date, as the user has not been tracked previously.

..  index:: Backend, ext:redirects

