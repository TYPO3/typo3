..  include:: /Includes.rst.txt

..  _feature-107794-1730000000:

============================================================
Feature: #107794 - Improved breadcrumb navigation in backend
============================================================

See :issue:`107794`

Description
===========

The TYPO3 backend now provides contextual breadcrumb navigation in the document
header of backend modules, helping users understand their current location and
navigate back through hierarchies.

Breadcrumbs are now automatically displayed when:

* Editing records (pages, content elements, etc.)
* Creating new records
* Browsing file storages and folders
* Working with multiple records simultaneously

The breadcrumb navigation features:

Smart context detection
  Breadcrumbs automatically adapt based on what you're working with - whether
  it's a page, content element, file, or folder.

Hierarchical navigation
  Click any breadcrumb item to navigate back to that level in the hierarchy.
  For pages, the complete page tree path is shown.

Module awareness
  Breadcrumbs remember which module you're in and keep you in that module when
  navigating (e.g., staying in Info module instead of switching to Page module).

Responsive design
  On smaller screens, breadcrumb items automatically collapse into a dropdown
  to save space while maintaining full functionality.


Impact
======

Backend users benefit from improved navigation and orientation:

* **Always know where you are**: The breadcrumb trail shows your current location
  in the page tree, file system, or record hierarchy

* **Quick navigation**: Jump back to any parent level with a single click instead
  of using the back button or tree navigation

* **Context preservation**: Stay in your current module when navigating through
  parent items

* **Special states visible**: When creating new records or editing multiple items,
  this is clearly indicated in the breadcrumb trail

Examples
========

Page editing
  When editing page "Contact" in a site structure like "Home → Company → Contact",
  the breadcrumb shows: **Home** → **Company** → **Contact**

Content creation
  When creating a new content element on page "About", the breadcrumb shows:
  **Home** → **About** → **Create New Content Element**

File management
  When browsing "fileadmin/images/products/" the breadcrumb shows:
  **fileadmin** → **images** → **products**

..  index:: Backend, UX
