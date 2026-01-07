..  include:: /Includes.rst.txt

..  _feature-108623-1768315053:

================================================================
Feature: #108623 - Allow content element restrictions per colPos
================================================================

See :issue:`108623`

Description
===========

:ref:`Backend layouts<t3coreapi:be-layout>` have been extended with options to allow only
configured types of content elements (referencing :sql:`tt_content.CType` with names like "text",
"textmedia", "felogin_pi1" and so on) in backend layout columns (:sql:`colPos`): The two
keys :typoscript:`allowedContentTypes` and :typoscript:`disallowedContentTypes` add allow and
deny lists on column level. These settings can be set with Page TSConfig based backend layouts,
Database based backend layouts do not allow configuring this value at the moment, but this will
be added soon.

Example for a backend layout with two rows and two columns configured using Page TSConfig:

.. code-block:: typoscript

    mod.web_layout.BackendLayouts {
      exampleKey {
        title = Example
        config {
          backend_layout {
            colCount = 1
            rowCount = 2
            rows {
              1 {
                columns {
                  1 {
                    identifier = main
                    name = Main content
                    colPos = 0
                    allowedContentTypes = header, textmedia
                  }
                  2 {
                    identifier = right
                    name = Panel right
                    colPos = 1
                    allowedContentTypes = my_custom_cta
                  }
                }
              }
              2 {
                columns {
                  1 {
                    identifier footer
                    name = Footer
                    colpos = 2
                    colspan = 2
                    disallowedContentTypes = header
                }
            }
          }
        }
      }
    }

The implementation adapts the "New content element wizard" to show only allowed (or not disallowed)
content elements types when adding a content element to a column. When editing records, the select
boxes "Type" and Column position" are reduced to not allow invalid values based on the configuration.
Similar logic is applied when moving and copying content elements.

The feature has been created with extension `content_defender<https://extensions.typo3.org/extension/content_defender>`__
in mind. This extension by Nicole Hummel has been around for many years and found huge adoption rates
within the community. In comparison to content_defender, the core configuration is slightly simplified
and the core implementation does not provide the additional content_defender feature to restrict the number
of elements per column (:typoscript:`maxitems`).

The core implementation supports the content_defender syntax using the arrays :typoscript:`allowed.CType` and
:typoscript:`disallowed.CType`. With the example below, :typoscript:`allowed.CType` is internally mapped to
:typoscript:`allowedContentTypes`. When both :typoscript:`allowed.CType` and :typoscript:`allowedContentTypes`
are given, :typoscript:`allowed.CType` is ignored.

.. code-block:: typoscript

    mod.web_layout.BackendLayouts {
      exampleKey {
        config {
          backend_layout {
            rows {
              1 {
                columns {
                  1 {
                    allowed {
                      CType = header, textmedia
    [...]

Codewise, the PSR-14 event :php:`ManipulateBackendLayoutColPosConfigurationForPageEvent` has been added. It allows manipulation
of the calculated column configuration. It is marked :php:`@internal` and thus needs to be used with care since it may
change in the future without further note: The event is not dispatched as systematically as it should be, but refactoring
the surrounding code can probably not be provided with TYPO3 v14 anymore. Extensions like ext:container however
must be able to adapt column configuration with TYPO3 v14 already. The decisions was to provide an event, but to mark
it internal for the time being, declaring it as "use at your own risk" if you know what you are doing and within extensions
that set up proper automatic testing to find issues if the core changes internals.

Impact
======

Backend layout columns can now restrict, which content element types are allowed or disallowed inside of it.


..  index:: Backend, TSConfig, ext:backend
