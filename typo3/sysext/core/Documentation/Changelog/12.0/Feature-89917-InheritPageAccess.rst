.. include:: /Includes.rst.txt

.. _feature-89917:

=======================================================
Feature: #89917 - Copy page access settings from parent
=======================================================

See :issue:`89917`

Description
===========

It is now possible to copy page access permissions from the parent page,
while creating new pages. This is enabled using :typoscript:`copyFromParent`
as value for one of the page TSconfig :typoscript:`TCEMAIN.permissions.*`
sub keys.

There are casual scenarios where this can be useful to avoid additional backend
administrator work:

On a top-level page, backend admins use the access module to set a page
group owner and add TSconfig on this page to specify access details. A typical
TSconfig is :typoscript:`TCEMAIN.permissions.groupid` to a group ID, plus
:typoscript:`TCEMAIN.permissions.group=31` to allow all members of this group to do
"everything". To be sure, :typoscript:`TCEMAIN.permissions.everybody=0` is set
to deny access for non-members. Most often, a "page owner"  base group is added,
where all backend users are members of (directly or via subgroups). Access to single page
trees are then done via page mount points in backend group "mount group records"
that have this "page owner" group as subgroup. When a backend user being member
of that "mount group" and thus having access to that page tree then creates new pages
below this top-level page, the page owner is set to the creating user, and group
ownership plus access details like "Can I modify?" are forced to the TSconfig
settings of the top-level page.

With a base setup like that, administrators typically have to configure and set up
page group ownership once per root site and are then sure that everything below
"inherits" properly.

In more complex scenarios, different group owners are set for parts of a subpage
tree. Administrators then want to make sure that new pages created below this differently
restricted set of pages also inherit those changed group ownership settings when users
create new pages. Until now, they had to change  :typoscript:`TCEMAIN.permissions.groupid`
and potentially `TCEMAIN.permissions.group` and :typoscript:`TCEMAIN.permissions.everybody`
to achieve that and had to maintain TSconfig accordingly.

The new :typoscript:`copyFromParent` value can be leveraged to reduce administrator
overhead to near-zero for access settings, especially when combined with :php:`defaultPageTSconfig`.
Let's say we have a TYPO3 instance with multiple sites. There is still a "page owner" group
plus various other groups for backend user mount points to single sites. A basic
site extension now sets this in an :file:`ext_tables.php` file:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] .= '
        TCEMAIN.permissions.groupid = copyFromParent
        TCEMAIN.permissions.group = 31
        TCEMAIN.permissions.everybody = 0
    ';

This configures a default 'New pages are set to the owner of the parent page and members
of this group can do "everything"'. When an administrator now creates a new site, it would
set the "group" of that page using the access module to the "page owner" group once, and
this will inherit to subpages automatically whenever a backend user creates a page. And
all that without additional TSconfig settings. If an administrator later sets a different
group owner for a subpage, new pages in there will inherit that owner instead, too.

From a Unix administrator's point of view, this setting is similar to the "group sticky bit"
for directories - new directories get that group owner set by looking at the parent
directory and inherit it to new subdirectories.

Example
=======

..  code-block:: typoscript

    TCEMAIN.permissions.userid = copyFromParent
    TCEMAIN.permissions.groupid = copyFromParent
    TCEMAIN.permissions.user = copyFromParent
    TCEMAIN.permissions.group = copyFromParent
    TCEMAIN.permissions.everybody = copyFromParent

.. index:: Backend, ext:core
