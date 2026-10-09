..  include:: /Includes.rst.txt

..  _feature-89760-1791560506:

==========================================================
Feature: #89760 - Filter the form list by storage location
==========================================================

See :issue:`89760`

Description
===========

The form list in the :guilabel:`Forms` backend module can now be filtered by
the location the forms are stored in. A :guilabel:`Location` select box is
shown next to the search field as soon as forms exist in at least two
locations.

Forms stored in extensions are grouped by the path configured in
:yaml:`persistenceManager.allowedExtensionPaths` they are stored in, for
example :file:`EXT:my_sitepackage/Resources/Private/Forms/`. All forms stored
in the database are one location.

The filter is applied together with the search term when the
:guilabel:`Filter` button is clicked, and :guilabel:`Reset filter` shows all
forms again. The selected location is kept when paginating, sorting the list
and creating a shortcut to the module.

The :guilabel:`Location` column of the list shows the same location that is
used by the filter, instead of the full path of the form definition file.

Impact
======

Editors and integrators can narrow down the form list to the forms of a single
configured extension path or to the forms stored in the database, which makes
forms easier to find in installations with many form definitions.

..  index:: Backend, ext:form
