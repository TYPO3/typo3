..  include:: /Includes.rst.txt

..  _deprecation-107813-1730000000:

=================================================================
Deprecation: #107813 - Deprecate MetaInformation API in DocHeader
=================================================================

See :issue:`107813`

Description
===========

The `MetaInformation` class and related methods in `DocHeaderComponent` have been
deprecated in favor of the new breadcrumb component architecture.

The following have been marked as deprecated:

*   :php:`DocHeaderComponent::setMetaInformation()`
*   :php:`DocHeaderComponent::setMetaInformationForResource()`
*   :php:`MetaInformation` class

These APIs were previously used to display page navigation paths in the backend
document header. This functionality is now handled by the breadcrumb component,
which provides richer context and better navigation capabilities.

Impact
======

Calling any of the deprecated methods will trigger a PHP :php:`E_USER_DEPRECATED` error.

The `MetaInformation` class is now marked as `@internal` and should not be used
in extensions.

Affected installations
=======================

All installations using custom backend modules that call:

*   :php:`$view->getDocHeaderComponent()->setMetaInformation($pageRecord)`
*   :php:`$view->getDocHeaderComponent()->setMetaInformationForResource($resource)`

Or any custom code relying on the `MetaInformation` class.

The Extension Scanner will detect usage of the deprecated methods and
classes, making it easy to identify code that needs to be updated.

Migration
=========

Replace calls to the deprecated methods with the new convenience methods on
`DocHeaderComponent`:

Before:

..  code-block:: php

    // For page records
    $view->getDocHeaderComponent()->setMetaInformation($pageInfo);

    // For file or folder resources
    $view->getDocHeaderComponent()->setMetaInformationForResource($resource);

After:

..  code-block:: php

    // For page records
    $view->getDocHeaderComponent()->setPageBreadcrumb($pageInfo);

    // For file or folder resources
    $view->getDocHeaderComponent()->setResourceBreadcrumb($resource);

An additional convenience method is available for records:

..  code-block:: php

    // For any record type (by table and UID)
    $view->getDocHeaderComponent()->setRecordBreadcrumb('tt_content', 123);

For advanced scenarios requiring custom breadcrumb logic (such as conditional
context selection based on controller state), see the implementation in
:php:`TYPO3\CMS\Backend\Controller\EditDocumentController` which uses
:php:`BreadcrumbFactory` directly with :php:`setBreadcrumbContext()`.

..  index:: Backend, PHP-API, FullyScanned, ext:backend
