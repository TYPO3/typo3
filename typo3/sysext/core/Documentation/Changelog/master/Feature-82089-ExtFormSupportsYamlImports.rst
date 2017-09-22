.. include:: ../../Includes.txt

================================================
Feature: #82089 - EXT:form supports YAML imports
================================================

See :issue:`82089`

Description
===========

The `form` extension now features imports in YAML configuration files via the special toplevel
:yaml:`imports` option. With the help of this feature, form setup and especially form definitions
can be reused without copying.


Form setup configuration
------------------------

The :yaml:`imports` option can now be used to load the form setup of the `form` extension followed
by a custom configuration.

For example a :file:`EXT:my_site_package/Configuration/Yaml/FormSetup.yaml` could look like this:

.. code-block:: yaml

    imports:
      - { resource: "EXT:form/Configuration/Yaml/FormSetup.yaml" }
      - { resource: "EXT:my_site_package/Configuration/Yaml/FormSetup/Prototypes.yaml" }

You can also combine imports with configuration:

.. code-block:: yaml

    imports:
      - { resource: "EXT:form/Configuration/Yaml/FormSetup.yaml" }

    TYPO3:
      CMS:
        Form:
          prototypes:
            # custom configuration
            # ...


Form definitions
----------------

Imports are also possible within form definitions but must be added manually to the YAML files.
Currently, the form editor does not have a graphical interface for imports.

The following example shows a basic contact form definition (e.g. in
:file:`fileadmin/form_definitions/contact.yaml`):

.. code-block:: yaml

    identifier: contact
    label: 'Contact us'
    type: Form
    prototypeName: standard
    finishers:
      EmailToReceiver:
        identifier: EmailToReceiver
        sorting: 10
        options:
          # ...
    renderables:
      page-1:
        identifier: page-1
        type: Page
        sorting: 10
        label: 'Contact Form'
        renderables:
          name:
            identifier: name
            type: Text
            label: Name
            sorting: 10
            validators:
              NotEmpty:
                identifier: NotEmpty
                sorting: 10
          subject:
            identifier: subject
            type: Text
            sorting: 20
            label: Subject
            validators:
              NotEmpty:
                identifier: NotEmpty
                sorting: 10

Other form definitions can import :file:`fileadmin/form_definitions/contact.yaml` to inherit the
definitions. Additional form definitions can then be added to extend or change existing definitions.

.. important::

   The form :yaml:`identifier` **must** be changed when importing other form definitions.

You have to do the following in oder to change the form label and to move the :yaml:`subject` field
before the :yaml:`name` field (see aforementioned
:file:`fileadmin/form_definitions/another-contact.yaml`):

.. code-block:: yaml

    imports:
      - { resource: fileadmin/form_definitions/contact.yaml }

    # The identifier MUST be changed
    identifier: inquiry

    label: Inquiry
    renderables:
      page-1:
        renderables:
          name:
            sorting: 20
          subject:
            sorting: 10

The key of every section with an :yaml:`identifier` property must be named exactly like the
:yaml:`identifier` property. This way it is ensured that form definitions importing other form
definitions and form definitions which are imported are properly merged.

For example, before this feature was introduced a list of :yaml:`finishers` was defined like this:

.. code-block:: yaml

    finishers:
      -
        identifier: EmailToReceiver
        options:
          # ...
      -
        identifier: EmailToSender
        options:
          # ...
      # ...

To guarantee imports work properly this must be rewritten slightly. Please use the :yaml:`identifier`
value as section key:

.. code-block:: yaml

    finishers:
      EmailToReceiver:
        identifier: EmailToReceiver
        options:
          # ...
      EmailToSender:
        identifier: EmailToSender
        options:
          # ...
      # ...

Aside from this, every section with an :yaml:`identifier` must have a :yaml:`sorting` property. This
property is essential to detect differences in sortings between the form definition you import and
the imported form definition.

.. tip::

   Form definitions managed with the form editor are migrated automatically once opened and saved.


.. index:: Frontend, Backend, ext:form