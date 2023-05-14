.. include:: /Includes.rst.txt

.. _feature-99092-1668509154:

==================================================
Feature: #99092 - Allow static backdrops in modals
==================================================

See :issue:`99092`

Description
===========

The Modal API is now able to render a static backdrop to avoid closing the modal
when clicking it. This may be handy in case closing the modal would result in
a negative user experience, e.g. in the image cropper.


Impact
======

The new boolean configuration option :js:`staticBackdrop` controls whether a
static backdrop should be rendered or not; the default is :js:`false`.

Example:

..  code-block:: js

    import Modal from '@typo3/backend/modal';

    Modal.advanced({
      title: 'Hello',
      content: 'This modal is not closable via clicking the backdrop.',
      size: Modal.sizes.small,
      staticBackdrop: true
    });

Templates using the HTML class :html:`.t3js-modal-trigger` to initialize
a modal dialog can also use the new option by adding the
:html:`data-static-backdrop` attribute to the corresponding element.

Example:

..  code-block:: html

    <button class="btn btn-default t3js-modal-trigger"
        data-title="Hello"
        data-bs-content="This modal is not closable via clicking the backdrop."
        data-static-backdrop>
            Open modal
    </button>

.. index:: Backend, JavaScript, ext:backend
