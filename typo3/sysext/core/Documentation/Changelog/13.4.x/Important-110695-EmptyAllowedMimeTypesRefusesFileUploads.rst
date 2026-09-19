.. include:: /Includes.rst.txt

.. _important-110695-1758240000:

============================================================================
Important: #110695 - Empty allowedMimeTypes refuses file uploads in EXT:form
============================================================================

See :issue:`110695`

Description
===========

The :yaml:`FileUpload` and :yaml:`ImageUpload` form elements of EXT:form use the
:yaml:`allowedMimeTypes` property as an allow list of MIME types that may be
uploaded. Previously, an **empty** :yaml:`allowedMimeTypes` list was treated as
"no restriction", so any file (except those blocked by the core file deny
pattern, e.g. PHP files) could be uploaded - including browser-active content
such as :file:`.html` or :file:`.svg`.

For a CMS this is not appropriate: uploaded files are stored in the File
Abstraction Layer (by default in a public web storage below :file:`fileadmin/`)
and may later be opened by backend users in a backend context or served
directly. Allowing arbitrary file content by default can therefore be abused,
for example as a stored cross-site-scripting vector.

TYPO3 now applies **security by default**: an empty :yaml:`allowedMimeTypes`
list refuses file uploads for that element completely. Integrators must
configure at least one allowed MIME type for every :yaml:`FileUpload` /
:yaml:`ImageUpload` element, for example:

..  code-block:: yaml

    allowedMimeTypes:
      - application/pdf
      - image/jpeg

The former, insecure behaviour - an empty list means no MIME type restriction at
all - can be restored globally with the feature toggle
:php:`security.form.allowUnrestrictedFileUploadMimeTypes` (via the
:guilabel:`Admin Tools > Settings > Feature Toggles` module or in
:file:`settings.php`). Enabling this toggle is **not recommended** and should
only be used as a temporary measure while form definitions are updated to
configure an explicit allow list.

An explicitly configured :yaml:`allowedMimeTypes` list is always honoured
unchanged, including deliberate choices such as :yaml:`text/html` or
:yaml:`image/svg+xml`.

The form editor now validates the :guilabel:`Allowed file mime types` field of
:yaml:`FileUpload` and :yaml:`ImageUpload` elements with the :yaml:`NotEmpty`
property validator. A form containing such an element without any selected
mime type can not be saved in the form editor until at least one mime type is
selected. This also applies when the feature toggle mentioned above is
enabled, since the toggle is only meant to keep existing forms working.

If a required mime type is not offered in the form editor, add it to the
:yaml:`selectOptions` of the :yaml:`allowedMimeTypes` editor
(:yaml:`formEditor.editors.300`) in a custom form configuration.

.. index:: ext:form, YAML, Frontend, NotScanned
