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

Both are shown in the redirect record itself and in a "Created" column of the
redirects backend module listing, which can be sorted by creation date. The
module's filter has been extended by a "Created by" select box, allowing to
narrow the listing down to the redirects of a single backend user.

Impact
======

All newly created redirects automatically include creation information,
regardless of whether they are created:

*   Manually through the redirects backend module
*   Automatically when updating a page slug
*   Programmatically through the DataHandler API

Existing redirects created before this feature will only show the creation
date, as the user information was not tracked previously.

..  index:: Backend, ext:redirects
