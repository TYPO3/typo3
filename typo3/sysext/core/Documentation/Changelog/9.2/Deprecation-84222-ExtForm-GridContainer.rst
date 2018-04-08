.. include:: ../../Includes.txt

========================================================
Deprecation: #84222- Usage of GridContainer form element
========================================================

See :issue:`84222`

Description
===========

The form element `GridContainer` is useless, buggy and will be removed in v10.


Impact
======

Usage of the form element `GridContainer` will trigger a deprecation warning:


Affected installations
======================

All instances who make usage of the form element `GridContainer`.


Migration
=========

Remove the `GridContainer` form elements from your form definition and use `GridRow` child elements only.

Change

.. code-block:: yaml

    type: Form
    identifier: test
    label: test
    prototypeName: standard
    renderables:
      -
        type: Page
        identifier: page-1
        label: Step
        renderables:
          -
            type: GridContainer
            identifier: gridcontainer-1
            label: 'Grid: Container'
            renderables:
              -
                type: GridRow
                identifier: gridrow-1
                label: 'Grid: Row'
                renderables:
                  -
                    defaultValue: ''
                    type: Text
                    identifier: text-1
                    label: Text

to

.. code-block:: yaml

    type: Form
    identifier: test
    label: test
    prototypeName: standard
    renderables:
      -
        type: Page
        identifier: page-1
        label: Step
        renderables:
          -
            type: GridRow
            identifier: gridrow-1
            label: 'Grid: Row'
            renderables:
              -
                defaultValue: ''
                type: Text
                identifier: text-1
                label: Text


.. index:: Frontend, ext:form, NotScanned
