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

For Extension Developers
=========================

Setting basic breadcrumb context
---------------------------------

Custom backend modules can easily integrate breadcrumb navigation using new
convenience methods on :php:`DocHeaderComponent`:

..  code-block:: php

    // For page-based modules
    $view->getDocHeaderComponent()->setPageBreadcrumb($pageInfo);

    // For record editing
    $view->getDocHeaderComponent()->setRecordBreadcrumb('tt_content', 123);

    // For file/folder browsing
    $view->getDocHeaderComponent()->setResourceBreadcrumb($file);

These methods automatically generate appropriate breadcrumb trails including:

* Page tree hierarchy for page-based modules
* Parent pages for content records
* Folder structure for file resources
* Module hierarchy for third-level modules

Adding suffix nodes for special states
---------------------------------------

The :php:`addBreadcrumbSuffixNode()` method allows appending custom breadcrumb
nodes after the main breadcrumb trail. This is useful for indicating special
states or actions such as:

* "Create New" actions when creating records
* "Edit Multiple" states when editing multiple records
* Custom contextual information specific to the current view

**Example: Adding a "Create New" suffix node**

..  code-block:: php

    use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;

    $view = $this->moduleTemplateFactory->create($request);
    $docHeader = $view->getDocHeaderComponent();

    // Set main breadcrumb context (e.g., current page)
    $docHeader->setPageBreadcrumb($pageInfo);

    // Add suffix node for "Create New" action
    $docHeader->addBreadcrumbSuffixNode(
        new BreadcrumbNode(
            identifier: 'new',
            label: 'Create New Content Element',
            icon: 'actions-add'
        )
    );

**Example: Multiple suffix nodes**

..  code-block:: php

    use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;

    $docHeader = $view->getDocHeaderComponent();
    $docHeader->setRecordBreadcrumb('pages', $pageUid);

    // First suffix: editing mode
    $docHeader->addBreadcrumbSuffixNode(
        new BreadcrumbNode(
            identifier: 'edit',
            label: 'Edit',
            icon: 'actions-document-open'
        )
    );

    // Second suffix: specific field
    $docHeader->addBreadcrumbSuffixNode(
        new BreadcrumbNode(
            identifier: 'field',
            label: 'Page Properties'
        )
    );

**Example: Clickable suffix nodes**

Suffix nodes can also be clickable by providing a route:

..  code-block:: php

    use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
    use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNodeRoute;

    $docHeader->addBreadcrumbSuffixNode(
        new BreadcrumbNode(
            identifier: 'preview',
            label: 'Preview Mode',
            icon: 'actions-view',
            route: new BreadcrumbNodeRoute(
                'web_layout',
                ['id' => $pageUid, 'mode' => 'preview']
            )
        )
    );

Deprecation notice
------------------

The previous :php:`setMetaInformation()` method has been deprecated in favor
of the new breadcrumb API. See :ref:`deprecation-107813-1730000000` for
migration instructions.

..  index:: Backend, UX, ext:backend
