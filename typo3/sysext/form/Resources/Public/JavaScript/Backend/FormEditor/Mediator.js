/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Form/Backend/FormEditor/Mediator
 */
define(['jquery',
  'TYPO3/CMS/Form/Backend/FormEditor/Helper'
], function($, Helper) {
  'use strict';

  return (function($, Helper) {

    /**
     * @private
     *
     * @var object
     */
    var _formEditorApp = null;

    /**
     * @private
     *
     * @var object
     */
    var _viewModel = null;

    /* *************************************************************
     * Private Methods
     * ************************************************************/

    /**
     * @private
     *
     * @return void
     * @throws 1478268638
     */
    function _helperSetup() {
      assert('function' === $.type(Helper.bootstrap),
        'The view model helper does not implement the method "bootstrap"',
        1478268638
      );
      Helper.bootstrap(getFormEditorApp());
    };

    /**
     * @private
     *
     * @return object
     */
    function getFormEditorApp() {
      return _formEditorApp;
    };

    /**
     * @private
     *
     * @return object
     */
    function getViewModel() {
      return _viewModel;
    };

    /**
     * @private
     *
     * @return object
     */
    function getUtility() {
      return getFormEditorApp().getUtility();
    };

    /**
     * @private
     *
     * @param mixed test
     * @param string message
     * @param int messageCode
     * @return void
     */
    function assert(test, message, messageCode) {
      return getFormEditorApp().assert(test, message, messageCode);
    };

    /**
     * @private
     *
     * @param object
     * @return object
     */
    function getHelper(configuration) {
      if (getUtility().isUndefinedOrNull(configuration)) {
        return Helper.setConfiguration(getViewModel().getConfiguration());
      }
      return Helper.setConfiguration(configuration);
    };

    /**
     * @private
     *
     * @return object
     */
    function getCurrentlySelectedFormElement() {
      return getFormEditorApp().getCurrentlySelectedFormElement();
    };

    /**
     * @private
     *
     * @return object
     */
    function getPublisherSubscriber() {
      return getFormEditorApp().getPublisherSubscriber();
    };

    /**
     * @private
     *
     * @return object
     */
    function getRootFormElement() {
      return getFormEditorApp().getRootFormElement();
    };

    /**
     * @private
     *
     * @return void
     */
    function _subscribeEvents() {

      /* *********************************************************
       * Misc
       * ********************************************************/

      /**
       * @private
       *
       * @return string
       */
      window.onbeforeunload = function(e) {
        if (!getFormEditorApp().getUnsavedContent()) {
          return;
        }
        e = e || window.event;
        if (e) {
          e.returnValue = getFormEditorApp().getFormElementDefinition(getRootFormElement(), 'modalCloseDialogMessage');
        }
        return getFormEditorApp().getFormElementDefinition(getRootFormElement(), 'modalCloseDialogTitle');
      };

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/ready
       */
      getPublisherSubscriber().subscribe('view/ready', function(topic, args) {
        getViewModel().onViewReadyBatch();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = applicationState
       *              args[1] = stackPointer
       *              args[2] = stackSize
       * @return void
       * @subscribe core/applicationState/add
       */
      getPublisherSubscriber().subscribe('core/applicationState/add', function(topic, args) {
        getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')));
        if (args[2] > 1 && args[1] <= args[2]) {
          getViewModel().enableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
        } else {
          getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
        }
      });

      /* *********************************************************
       * Ajax
       * ********************************************************/

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = response
       * @return void
       * @subscribe core/ajax/saveFormDefinition/success
       */
      getPublisherSubscriber().subscribe('core/ajax/saveFormDefinition/success', function(topic, args) {
        getFormEditorApp().setUnsavedContent(false);
        getViewModel().showSaveSuccessMessage();
        getViewModel().showSaveButtonSaveIcon();

        getFormEditorApp().setFormDefinition(args[0]['formDefinition']);

        getViewModel().addStructureRootElementSelection();
        getFormEditorApp().setCurrentlySelectedFormElement(getRootFormElement());
        getViewModel().setStructureRootElementTitle();
        getViewModel().setStageHeadline();
        getViewModel().renderAbstractStageArea();
        getViewModel().renewStructure();
        getViewModel().renderPagination();
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = object
       * @return void
       * @subscribe core/ajax/saveFormDefinition/error
       */
      getPublisherSubscriber().subscribe('core/ajax/saveFormDefinition/error', function(topic, args) {
        getViewModel().showSaveButtonSaveIcon();
        getViewModel().showSaveErrorMessage(args[0]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = html
       *              args[1] = pageIndex
       * @return void
       * @subscribe core/ajax/renderFormDefinitionPage/success
       */
      getPublisherSubscriber().subscribe('core/ajax/renderFormDefinitionPage/success', function(topic, args) {
        getViewModel().renderPreviewStageArea(args[0]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = jqXHR
       *              args[1] = textStatus
       *              args[2] = errorThrown
       * @return void
       * @subscribe core/ajax/saveFormDefinition/error
       */
      getPublisherSubscriber().subscribe('core/ajax/error', function(topic, args) {
        if (args[0].status !== 0) {
          getViewModel().showErrorFlashMessage(args[1], args[2]);
          getViewModel().renderPreviewStageArea(args[0].responseText);
        }
      });

      /* *********************************************************
       * Header
       * ********************************************************/

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/header/button/save/clicked
       */
      getPublisherSubscriber().subscribe('view/header/button/save/clicked', function(topic, args) {
        if (getFormEditorApp().validationResultsHasErrors(getFormEditorApp().validateFormElementRecursive(getRootFormElement(), true))) {
          getViewModel().showValidationErrorsModal();
        } else {
          getViewModel().showSaveButtonSpinnerIcon();
          getFormEditorApp().saveFormDefinition();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/header/formSettings/clicked
       */
      getPublisherSubscriber().subscribe('view/header/formSettings/clicked', function(topic, args) {
        getViewModel().addStructureRootElementSelection();
        getFormEditorApp().setCurrentlySelectedFormElement(getRootFormElement());
        getViewModel().renderAbstractStageArea();
        getViewModel().renewStructure();
        getViewModel().renderPagination();
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = targetEvent
       * @return void
       * @subscribe view/header/button/newPage/clicked
       */
      getPublisherSubscriber().subscribe('view/header/button/newPage/clicked', function(topic, args) {
        if (getFormEditorApp().isRootFormElementSelected()) {
          getViewModel().selectPageBatch(0);
        }
        getViewModel().showInsertPagesModal(args[0]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/header/button/close/clicked
       */
      getPublisherSubscriber().subscribe('view/header/button/close/clicked', function(topic, args) {
        getViewModel().showCloseConfirmationModal();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/undoButton/clicked
       */
      getPublisherSubscriber().subscribe('view/undoButton/clicked', function(topic, args) {
        getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
        getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')));
        getFormEditorApp().undoApplicationState();

        if (getViewModel().getPreviewMode()) {
          getFormEditorApp().renderCurrentFormPage();
        } else {
          getViewModel().renderAbstractStageArea();
        }
        getFormEditorApp().setUnsavedContent(true);

        getViewModel().renewStructure();
        getViewModel().renderPagination();
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/redoButton/clicked
       */
      getPublisherSubscriber().subscribe('view/redoButton/clicked', function(topic, args) {
        getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
        getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')));
        getFormEditorApp().redoApplicationState();

        if (getViewModel().getPreviewMode()) {
          getFormEditorApp().renderCurrentFormPage();
        } else {
          getViewModel().renderAbstractStageArea();
        }
        getFormEditorApp().setUnsavedContent(true);

        getViewModel().renewStructure();
        getViewModel().renderPagination();
        getViewModel().renderInspectorEditors();
      });

      /* *********************************************************
       * Stage
       * ********************************************************/

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementIdentifierPath
       * @return void
       * @subscribe view/stage/element/clicked
       */
      getPublisherSubscriber().subscribe('view/stage/element/clicked', function(topic, args) {
        if (getCurrentlySelectedFormElement().get('__identifierPath') !== args[0]) {
          getFormEditorApp().setCurrentlySelectedFormElement(args[0]);
          getViewModel().renewStructure();
          getViewModel().refreshSelectedElementItemsBatch();
          getViewModel().addAbstractViewValidationResults();
          getViewModel().renderInspectorEditors();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = targetEvent
       *              args[1] = configuration
       * @return void
       * @subscribe view/stage/abstract/elementToolbar/button/newElement/clicked
       */
      getPublisherSubscriber().subscribe('view/stage/abstract/elementToolbar/button/newElement/clicked', function(topic, args) {
        if (getFormEditorApp().isRootFormElementSelected()) {
          getViewModel().selectPageBatch(0);
        }
        getViewModel().showInsertElementsModal(args[0], args[1]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = targetEvent
       *              args[1] = configuration
       * @return void
       * @subscribe view/newElementButton/clicked
       */
      getPublisherSubscriber().subscribe('view/stage/abstract/button/newElement/clicked', function(topic, args) {
        if (getFormEditorApp().isRootFormElementSelected()) {
          getViewModel().selectPageBatch(0);
        }
        getViewModel().showInsertElementsModal(args[0], args[1]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = draggedFormElementDomElement
       *              args[1] = draggedFormPlaceholderDomElement
       * @return void
       * @subscribe view/stage/abstract/dnd/start
       */
      getPublisherSubscriber().subscribe('view/stage/abstract/dnd/start', function(topic, args) {
        getViewModel().onAbstractViewDndStartBatch(args[0], args[1]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = draggedFormElementIdentifierPath
       * @return void
       * @subscribe view/stage/abstract/dnd/stop
       */
      getPublisherSubscriber().subscribe('view/stage/abstract/dnd/stop', function(topic, args) {
        getFormEditorApp().setCurrentlySelectedFormElement(args[0]);
        getViewModel().renewStructure();
        getViewModel().renderAbstractStageArea(false, false);
        getViewModel().refreshSelectedElementItemsBatch();
        getViewModel().addAbstractViewValidationResults();
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = placeholderDomElement
       *              args[1] = parentFormElementIdentifierPath
       *              args[2] = enclosingCompositeFormElement
       * @return void
       * @subscribe view/stage/abstract/dnd/change
       */
      getPublisherSubscriber().subscribe('view/stage/abstract/dnd/change', function(topic, args) {
        getViewModel().onAbstractViewDndChangeBatch(args[0], args[1], args[2]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = movedDomElement
       *              args[1] = movedFormElementIdentifierPath
       *              args[2] = previousFormElementIdentifierPath
       *              args[3] = nextFormElementIdentifierPath
       * @return void
       * @subscribe view/stage/abstract/dnd/update
       */
      getPublisherSubscriber().subscribe('view/stage/abstract/dnd/update', function(topic, args) {
        getViewModel().onAbstractViewDndUpdateBatch(args[0], args[1], args[2], args[3]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/viewModeButton/abstract/clicked
       */
      getPublisherSubscriber().subscribe('view/viewModeButton/abstract/clicked', function(topic, args) {
        if (getViewModel().getPreviewMode()) {
          getViewModel().setPreviewMode(false);
          getViewModel().renderAbstractStageArea();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/viewModeButton/preview/clicked
       */
      getPublisherSubscriber().subscribe('view/viewModeButton/preview/clicked', function(topic, args) {
        if (!getViewModel().getPreviewMode()) {
          getViewModel().setPreviewMode(true);
          getFormEditorApp().renderCurrentFormPage();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/paginationPrevious/clicked
       */
      getPublisherSubscriber().subscribe('view/paginationPrevious/clicked', function(topic, args) {
        getViewModel().selectPageBatch(getFormEditorApp().getCurrentlySelectedPageIndex() - 1);
        if (getViewModel().getPreviewMode()) {
          getFormEditorApp().renderCurrentFormPage();
        } else {
          getViewModel().renderAbstractStageArea();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/paginationNext/clicked
       */
      getPublisherSubscriber().subscribe('view/paginationNext/clicked', function(topic, args) {
        getViewModel().selectPageBatch(getFormEditorApp().getCurrentlySelectedPageIndex() + 1);
        if (getViewModel().getPreviewMode()) {
          getFormEditorApp().renderCurrentFormPage();
        } else {
          getViewModel().renderAbstractStageArea();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/stage/abstract/render/postProcess
       */
      getPublisherSubscriber().subscribe('view/stage/abstract/render/postProcess', function(topic, args) {
        getViewModel().renderUndoRedo();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/stage/preview/render/postProcess
       */
      getPublisherSubscriber().subscribe('view/stage/preview/render/postProcess', function(topic, args) {
        getViewModel().renderUndoRedo();
      });

      /* *********************************************************
       * Structure
       * ********************************************************/

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementIdentifierPath
       * @return void
       * @subscribe view/tree/node/clicked
       */
      getPublisherSubscriber().subscribe('view/tree/node/clicked', function(topic, args) {
        var oldPageIndex;
        if (getCurrentlySelectedFormElement().get('__identifierPath') !== args[0]) {
          oldPageIndex = getFormEditorApp().getCurrentlySelectedPageIndex();
          getFormEditorApp().setCurrentlySelectedFormElement(args[0]);
          if (oldPageIndex !== getFormEditorApp().getCurrentlySelectedPageIndex()) {
            getViewModel().renderAbstractStageArea();
          } else {
            getViewModel().renderAbstractStageArea(false);
          }
          getViewModel().renderPagination();
          getViewModel().renderInspectorEditors();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementIdentifierPath
       *              args[1] = newLabel
       * @return void
       * @subscribe view/tree/node/clicked
       */
      getPublisherSubscriber().subscribe('view/tree/node/changed', function(topic, args) {
        var formElement = getFormEditorApp().getFormElementByIdentifierPath(args[0]);
        formElement.set('label', args[1]);
        getViewModel().getStructure().setTreeNodeTitle(null, formElement);
        if(getCurrentlySelectedFormElement().get('__identifierPath') === args[0]) {
          getViewModel().renderInspectorEditors(args[0], false);
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/structure/root/selected
       */
      getPublisherSubscriber().subscribe('view/structure/root/selected', function(topic, args) {
        if (!getFormEditorApp().isRootFormElementSelected()) {
          getViewModel().addStructureRootElementSelection();
          getFormEditorApp().setCurrentlySelectedFormElement(getRootFormElement());
          getViewModel().renderAbstractStageArea();
          getViewModel().renewStructure();
          getViewModel().renderPagination();
          getViewModel().renderInspectorEditors();
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = targetEvent
       * @return void
       * @subscribe view/header/button/newPage/clicked
       */
      getPublisherSubscriber().subscribe('view/structure/button/newPage/clicked', function(topic, args) {
        if (getFormEditorApp().isRootFormElementSelected()) {
          getViewModel().selectPageBatch(0);
        }
        getViewModel().showInsertPagesModal(args[0]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = draggedFormElementIdentifierPath
       * @return void
       * @subscribe view/tree/dnd/stop
       */
      getPublisherSubscriber().subscribe('view/tree/dnd/stop', function(topic, args) {
        getFormEditorApp().setCurrentlySelectedFormElement(args[0]);
        getViewModel().renewStructure();
        getViewModel().renderPagination();
        getViewModel().renderAbstractStageArea();
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = placeholderDomElement
       *              args[1] = parentFormElementIdentifierPath
       *              args[2] = enclosingCompositeFormElement
       * @return void
       * @subscribe view/tree/dnd/change
       */
      getPublisherSubscriber().subscribe('view/tree/dnd/change', function(topic, args) {
        getViewModel().onStructureDndChangeBatch(args[0], args[1], args[2]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = movedDomElement
       *              args[1] = movedFormElementIdentifierPath
       *              args[2] = previousFormElementIdentifierPath
       *              args[3] = nextFormElementIdentifierPath
       * @return void
       * @subscribe view/tree/dnd/update
       */
      getPublisherSubscriber().subscribe('view/tree/dnd/update', function(topic, args) {
        getViewModel().onStructureDndUpdateBatch(args[0], args[1], args[2], args[3]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/structure/renew/postProcess
       */
      getPublisherSubscriber().subscribe('view/structure/renew/postProcess', function(topic, args) {
        getViewModel().addStructureValidationResults();
      });

      /* *********************************************************
       * Inspector
       * ********************************************************/

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = collectionElementIdentifier
       *              args[1] = collectionName
       *              args[2] = formElement
       * @return void
       * @subscribe view/inspector/removeCollectionElement/perform
       */
      getPublisherSubscriber().subscribe('view/inspector/removeCollectionElement/perform', function(topic, args) {
        getViewModel().removePropertyCollectionElement(args[0], args[1], args[2]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = collectionElementIdentifier
       *              args[1] = collectionName
       * @return void
       * @subscribe view/inspector/collectionElement/selected
       */
      getPublisherSubscriber().subscribe('view/inspector/collectionElement/new/selected', function(topic, args) {
        getViewModel().createAndAddPropertyCollectionElement(args[0], args[1]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = collectionElementIdentifier
       *              args[1] = collectionName
       * @return void
       * @subscribe view/inspector/collectionElement/selected
       */
      getPublisherSubscriber().subscribe('view/inspector/collectionElement/existing/selected', function(topic, args) {
        getViewModel().renderInspectorCollectionElementEditors(args[1], args[0]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = movedCollectionElementIdentifier
       *              args[1] = previousCollectionElementIdentifier
       *              args[2] = nextCollectionElementIdentifier
       *              args[3] = collectionName
       * @return void
       * @subscribe view/inspector/collectionElements/dnd/update
       * @throws 1477407673
       */
      getPublisherSubscriber().subscribe('view/inspector/collectionElements/dnd/update', function(topic, args) {
        if (args[2]) {
          getViewModel().movePropertyCollectionElement(args[0], 'before', args[2], args[3]);
        } else if (args[1]) {
          getViewModel().movePropertyCollectionElement(args[0], 'after', args[1], args[3]);
        } else {
          assert(false, 'Next element or previous element need to be set.', 1477407673);
        }
      });

      /* *********************************************************
       * Form element
       * ********************************************************/

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = propertyPath
       *              args[1] = value
       *              args[2] = oldValue
       *              args[3] = formElementIdentifierPath
       * @return void
       * @subscribe core/formElement/somePropertyChanged
       */
      getPublisherSubscriber().subscribe('core/formElement/somePropertyChanged', function(topic, args) {
        if ('renderables' !== args[0]) {
          if (!getFormEditorApp().isRootFormElementSelected() && 'label' === args[0]) {
            getViewModel().getStructure().setTreeNodeTitle();
          } else if (!getFormEditorApp().getUtility().isUndefinedOrNull(args[3]) && getRootFormElement().get('__identifierPath') === args[3]) {
            getViewModel().setStructureRootElementTitle();
            getViewModel().setStageHeadline();
          }

          if (getViewModel().getPreviewMode()) {
            getFormEditorApp().renderCurrentFormPage();
          } else {
            getViewModel().renderAbstractStageArea(false, false);
          }
          getViewModel().addStructureValidationResults();
        }

        getFormEditorApp().setUnsavedContent(true);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = parentFormElement
       * @return void
       * @subscribe view/formElement/removed
       */
      getPublisherSubscriber().subscribe('view/formElement/removed', function(topic, args) {
        getFormEditorApp().setCurrentlySelectedFormElement(args[0]);
        getViewModel().renewStructure();
        getViewModel().renderAbstractStageArea();
        getViewModel().renderPagination();
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = newFormElement
       * @return void
       * @subscribe view/formElement/inserted
       */
      getPublisherSubscriber().subscribe('view/formElement/inserted', function(topic, args) {
        getFormEditorApp().setCurrentlySelectedFormElement(args[0]);
        getViewModel().renewStructure();
        getViewModel().renderAbstractStageArea();
        getViewModel().renderPagination();
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = collectionElementIdentifier
       *              args[1] = collectionName
       *              args[2] = formElement
       *              args[3] = collectionElementConfiguration
       *              args[4] = referenceCollectionElementIdentifier
       * @return void
       * @subscribe view/collectionElement/new/added
       */
      getPublisherSubscriber().subscribe('view/collectionElement/new/added', function(topic, args) {
        getViewModel().renderInspectorEditors();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = movedCollectionElementIdentifier
       *              args[1] = previousCollectionElementIdentifier
       *              args[2] = nextCollectionElementIdentifier
       *              args[3] = collectionName
       * @return void
       * @subscribe view/collectionElement/moved
       * @throws 1477407673
       */
      getPublisherSubscriber().subscribe('view/collectionElement/moved', function(topic, args) {
        getViewModel().renderInspectorEditors(undefined, false);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = collectionElementIdentifier
       *              args[1] = collectionName
       *              args[2] = formElement
       * @return void
       * @subscribe view/collectionElement/removed
       */
      getPublisherSubscriber().subscribe('view/collectionElement/removed', function(topic, args) {
        getViewModel().renderInspectorEditors(undefined, false);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementType
       * @return void
       * @subscribe view/insertElements/perform/bottom
       */
      getPublisherSubscriber().subscribe('view/insertElements/perform/bottom', function(topic, args) {
        var lastRenderable;

        lastRenderable = getFormEditorApp().getLastTopLevelElementOnCurrentPage();
        if (!lastRenderable) {
          getViewModel().createAndAddFormElement(args[0], getFormEditorApp().getCurrentlySelectedPage());
        } else {
          if (
            !getFormEditorApp().getFormElementDefinition(lastRenderable, '_isTopLevelFormElement')
            && getFormEditorApp().getFormElementDefinition(lastRenderable, '_isCompositeFormElement')
          ) {
            getViewModel().createAndAddFormElement(args[0], getFormEditorApp().getCurrentlySelectedPage());
          } else {
            getViewModel().createAndAddFormElement(args[0], lastRenderable);
          }
        }
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementType
       * @return void
       * @publish view/formElement/inserted
       * @subscribe view/insertElements/perform/after
       */
      getPublisherSubscriber().subscribe('view/insertElements/perform/after', function(topic, args) {
        var newFormElement;
        newFormElement = getViewModel().createAndAddFormElement(args[0], undefined, true);
        newFormElement = getViewModel().moveFormElement(newFormElement, 'after', getFormEditorApp().getCurrentlySelectedFormElement());
        getPublisherSubscriber().publish('view/formElement/inserted', [newFormElement]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementType
       * @return void
       * @subscribe view/insertElements/perform/inside
       */
      getPublisherSubscriber().subscribe('view/insertElements/perform/inside', function(topic, args) {
        getViewModel().createAndAddFormElement(args[0]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementType
       * @return void
       * @subscribe view/insertElements/perform/after
       */
      getPublisherSubscriber().subscribe('view/insertPages/perform', function(topic, args) {
        getViewModel().createAndAddFormElement(args[0]);
      });

      /* *********************************************************
       * Modals
       * ********************************************************/

      /**
       * @private
       *
       * @param string
       * @param array
       * @return void
       * @subscribe view/modal/close/perform
       */
      getPublisherSubscriber().subscribe('view/modal/close/perform', function(topic, args) {
        getFormEditorApp().setUnsavedContent(false);
        getViewModel().closeEditor();
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElement
       * @return void
       * @subscribe view/modal/removeFormElement/perform
       */
      getPublisherSubscriber().subscribe('view/modal/removeFormElement/perform', function(topic, args) {
        getViewModel().removeFormElement(args[0]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = collectionElementIdentifier
       *              args[1] = collectionName
       *              args[2] = formElement
       * @return void
       * @subscribe view/modal/removeCollectionElement/perform
       */
      getPublisherSubscriber().subscribe('view/modal/removeCollectionElement/perform', function(topic, args) {
        getViewModel().removePropertyCollectionElement(args[0], args[1], args[2]);
      });

      /**
       * @private
       *
       * @param string
       * @param array
       *              args[0] = formElementIdentifierPath
       * @return void
       * @subscribe view/modal/validationErrors/element/clicked
       */
      getPublisherSubscriber().subscribe('view/modal/validationErrors/element/clicked', function(topic, args) {
        var oldPageIndex;
        if (getCurrentlySelectedFormElement().get('__identifierPath') !== args[0]) {
          oldPageIndex = getFormEditorApp().getCurrentlySelectedPageIndex();
          getFormEditorApp().setCurrentlySelectedFormElement(args[0]);

          if (getViewModel().getPreviewMode()) {
            getViewModel().setPreviewMode(false);
          }

          if (oldPageIndex !== getFormEditorApp().getCurrentlySelectedPageIndex()) {
            getViewModel().renderAbstractStageArea();
          } else {
            getViewModel().renderAbstractStageArea(false);
          }

          getViewModel().renderPagination();
          getViewModel().renderInspectorEditors();
        }
      });
    };

    /**
     * @public
     *
     * @param object
     * @param object
     * @return void
     */
    function bootstrap(formEditorApp, viewModel) {
      _formEditorApp = formEditorApp;
      _viewModel = viewModel;
      _helperSetup();
      _subscribeEvents();
    };

    /**
     * Implements the "Revealing Module Pattern".
     */
    return {
      /**
       * Publish the public methods.
       */
      bootstrap: bootstrap
    };
  })($, Helper);
});
