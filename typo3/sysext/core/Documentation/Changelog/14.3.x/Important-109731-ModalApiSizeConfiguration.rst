..  include:: /Includes.rst.txt

..  _important-109731-1777413600:

=============================================================
Important: #109731 - Modal API: composable size configuration
=============================================================

See :issue:`109731`

Description
===========

The backend Modal API (:js:`@typo3/backend/modal`) accepts an additional shape
for the :js:`size` option of :js:`Modal.advanced()`. In addition to the existing
:js:`Sizes` enum (:js:`small`, :js:`default`, :js:`medium`, :js:`large`,
:js:`full`, :js:`expand`), callers may now pass a :js:`SizeConfig` object that
configures :js:`width` and :js:`height` independently.

The existing enum-based :js:`size` values continue to work unchanged. No
migration is required for existing call sites.

New types
---------

A new export is available from :js:`@typo3/backend/modal`:

*   :js:`Size` – an enum of per-axis size tokens that map to width and
    height values: :js:`small`, :js:`default`, :js:`medium`, :js:`large`,
    :js:`full`.

The :js:`SizeConfig` type uses these tokens:

..  code-block:: typescript

    type SizeConfig = { width?: Size; height?: Size };

Usage
-----

Compose width and height independently:

..  code-block:: typescript

    import Modal, { Size } from '@typo3/backend/modal';

    Modal.advanced({
      title: 'New page',
      content: '...',
      size: {
        width: Size.medium,
        height: Size.large,
      },
    });

Override only one axis (the other defaults to the modal's intrinsic size):

..  code-block:: typescript

    Modal.advanced({
      // ...
      size: { width: Size.medium },
    });

..  index:: Backend, JavaScript, ext:backend, NotScanned
