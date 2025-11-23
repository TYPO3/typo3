..  include:: /Includes.rst.txt

..  _deprecation-107813-1730000000:

=================================================================
Deprecation: #107813 - Deprecate MetaInformation API in DocHeader
=================================================================

See :issue:`107813`

Description
===========

The :php:`\TYPO3\CMS\Backend\Template\Components\MetaInformation`
class and related methods in
:php:`\TYPO3\CMS\Backend\Template\Components\DocHeaderComponent`
have been deprecated in favor of the new breadcrumb component architecture.

The following have been marked as deprecated:

*   :php:`DocHeaderComponent::setMetaInformation()`
*   :php:`DocHeaderComponent::setMetaInformationForResource()`
*   :php:`MetaInformation` class

These APIs were previously used to display page navigation paths in the
backend document header. This functionality is now handled by the breadcrumb
component, which provides richer context and better navigation capabilities.

Impact
======

Calling any of the deprecated methods will trigger a PHP
:php:`E_USER_DEPRECATED` error.

The :php-short:`\TYPO3\CMS\Backend\Template\Components\MetaInformation`
class is now marked as :php:`@internal` and should not be used in extensions.

Affected installations
======================

All installations using custom backend modules that call:

*   :php:`$view->getDocHeaderComponent()->setMetaInformation($pageRecord)`
*   :php:`$view->getDocHeaderComponent()->setMetaInformationForResource($resource)`

or any custom code relying on the
:php-short:`\TYPO3\CMS\Backend\Template\Components\MetaInformation` class.

The Extension Scanner will detect usage of the deprecated methods and classes,
making it easy to identify code that needs to be updated.

Migration
=========

Replace calls to the deprecated methods with the new convenience methods on
:php-short:`\TYPO3\CMS\Backend\Template\Components\DocHeaderComponent`.

**Before:**

..  code-block:: php
    :caption: Example (before)

    use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;

    // For page records
    $view->getDocHeaderComponent()->setMetaInformation($pageInfo);

    // For file or folder resources
    $view->getDocHeaderComponent()->setMetaInformationForResource($resource);

**After:**

..  code-block:: php
    :caption: Example (after)

    use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;

    // For page records
    $view->getDocHeaderComponent()->setPageBreadcrumb($pageInfo);

    // For file or folder resources
    $view->getDocHeaderComponent()->setResourceBreadcrumb($resource);

An additional convenience method is available for records:

..  code-block:: php
    :caption: Example (record breadcrumb)

    use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;

    // For any record type (by table and UID)
    $view->getDocHeaderComponent()->setRecordBreadcrumb('tt_content', 123);

For advanced scenarios requiring custom breadcrumb logic (such as conditional
context selection based on controller state), see the implementation in
:php:`\TYPO3\CMS\Backend\Controller\EditDocumentController`, which uses
:php:`\TYPO3\CMS\Backend\Breadcrumb\BreadcrumbFactory`
directly with :php:`setBreadcrumbContext()`.

..  index:: Backend, PHP-API, FullyScanned, ext:backend
