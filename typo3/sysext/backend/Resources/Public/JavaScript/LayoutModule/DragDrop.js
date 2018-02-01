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
 * Module: TYPO3/CMS/Backend/LayoutModule/DragDrop
 * this JS code does the drag+drop logic for the Layout module (Web => Page)
 * based on jQuery UI
 */
define(['jquery', 'jquery-ui/droppable'], function($) {
  'use strict';

  /**
   *
   * @type {{contentIdentifier: string, dragIdentifier: string, dragHeaderIdentifier: string, dropZoneIdentifier: string, columnIdentifier: string, validDropZoneClass: string, dropPossibleHoverClass: string, addContentIdentifier: string, originalStyles: string}}
   * @exports TYPO3/CMS/Backend/LayoutModule/DragDrop
   */
  var DragDrop = {
    contentIdentifier: '.t3js-page-ce',
    dragIdentifier: '.t3-page-ce-dragitem',
    dragHeaderIdentifier: '.t3js-page-ce-draghandle',
    dropZoneIdentifier: '.t3js-page-ce-dropzone-available',
    columnIdentifier: '.t3js-page-column',
    validDropZoneClass: 'active',
    dropPossibleHoverClass: 't3-page-ce-dropzone-possible',
    addContentIdentifier: '.t3js-page-new-ce',
    clone: true,
    originalStyles: ''
  };

  /**
   * initializes Drag+Drop for all content elements on the page
   */
  DragDrop.initialize = function() {
    $(DragDrop.contentIdentifier).draggable({
      handle: DragDrop.dragHeaderIdentifier,
      scope: 'tt_content',
      cursor: 'move',
      distance: 20,
      addClasses: 'active-drag',
      revert: 'invalid',
      zIndex: 100,
      start: function(evt, ui) {
        DragDrop.onDragStart($(this));
      },
      stop: function(evt, ui) {
        DragDrop.onDragStop($(this));
      }
    });

    $(DragDrop.dropZoneIdentifier).droppable({
      accept: this.contentIdentifier,
      scope: 'tt_content',
      tolerance: 'pointer',
      over: function(evt, ui) {
        DragDrop.onDropHoverOver($(ui.draggable), $(this));
      },
      out: function(evt, ui) {
        DragDrop.onDropHoverOut($(ui.draggable), $(this));
      },
      drop: function(evt, ui) {
        DragDrop.onDrop($(ui.draggable), $(this), evt);
      }
    });
  };

  /**
   * called when a draggable is selected to be moved
   * @param $element a jQuery object for the draggable
   * @private
   */
  DragDrop.onDragStart = function($element) {
    // Add css class for the drag shadow
    DragDrop.originalStyles = $element.get(0).style.cssText;
    $element.children(DragDrop.dragIdentifier).addClass('dragitem-shadow');
    $element.append('<div class="ui-draggable-copy-message">' + TYPO3.lang['dragdrop.copy.message'] + '</div>');
    // Hide create new element button
    $element.children(DragDrop.dropZoneIdentifier).addClass('drag-start');
    $element.closest(DragDrop.columnIdentifier).removeClass('active');

    $element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).hide();
    $element.find(DragDrop.dropZoneIdentifier).hide();

    // make the drop zones visible
    $(DragDrop.dropZoneIdentifier).each(function() {
      var $me = $(this);
      if ($me.parent().find('.icon-actions-document-new').length) {
        $me.addClass(DragDrop.validDropZoneClass);
      } else {
        $me.closest(DragDrop.contentIdentifier).find('> ' + DragDrop.addContentIdentifier + ', > > ' + DragDrop.addContentIdentifier).show();
      }
    });
  };

  /**
   * called when a draggable is released
   * @param $element a jQuery object for the draggable
   * @private
   */
  DragDrop.onDragStop = function($element) {
    // Remove css class for the drag shadow
    $element.children(DragDrop.dragIdentifier).removeClass('dragitem-shadow');
    // Show create new element button
    $element.children(DragDrop.dropZoneIdentifier).removeClass('drag-start');
    $element.closest(DragDrop.columnIdentifier).addClass('active');
    $element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).show();
    $element.find(DragDrop.dropZoneIdentifier).show();
    $element.find('.ui-draggable-copy-message').remove();

    // Reset inline style
    $element.get(0).style.cssText = DragDrop.originalStyles;

    $(DragDrop.dropZoneIdentifier + '.' + DragDrop.validDropZoneClass).removeClass(DragDrop.validDropZoneClass);
  };

  /**
   * adds CSS classes when hovering over a dropzone
   * @param $draggableElement
   * @param $droppableElement
   * @private
   */
  DragDrop.onDropHoverOver = function($draggableElement, $droppableElement) {
    if ($droppableElement.hasClass(DragDrop.validDropZoneClass)) {
      $droppableElement.addClass(DragDrop.dropPossibleHoverClass);
    }
  };

  /**
   * removes the CSS classes after hovering out of a dropzone again
   * @param $draggableElement
   * @param $droppableElement
   * @private
   */
  DragDrop.onDropHoverOut = function($draggableElement, $droppableElement) {
    $droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
  };

  /**
   * this method does the whole logic when a draggable is dropped on to a dropzone
   * sending out the request and afterwards move the HTML element in the right place.
   *
   * @param $draggableElement
   * @param $droppableElement
   * @param {Event} evt the event
   * @private
   */
  DragDrop.onDrop = function($draggableElement, $droppableElement, evt) {
    var newColumn = DragDrop.getColumnPositionForElement($droppableElement);

    $droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
    var $pasteAction = typeof $draggableElement === 'number';

    // send an AJAX requst via the AjaxDataHandler
    var contentElementUid = $pasteAction ? $draggableElement : parseInt($draggableElement.data('uid'));
    if (contentElementUid > 0) {
      var parameters = {};
      // add the information about a possible column position change
      var targetFound = $droppableElement.closest(DragDrop.contentIdentifier).data('uid');
      // the item was moved to the top of the colPos, so the page ID is used here
      var targetPid = 0;
      if (typeof targetFound === 'undefined') {
        // the actual page is needed
        targetPid = $('[data-page]').first().data('page');
      } else {
        // the negative value of the content element after where it should be moved
        targetPid = 0 - parseInt(targetFound);
      }
      var language = parseInt($droppableElement.closest('[data-language-uid]').data('language-uid'));
      var colPos = 0;
      if (targetPid !== 0) {
        colPos = newColumn;
      }
      parameters['cmd'] = {tt_content: {}};
      parameters['data'] = {tt_content: {}};
      var copyAction = (evt && evt.originalEvent.ctrlKey || $droppableElement.hasClass('t3js-paste-copy'));
      if (copyAction) {
        parameters['cmd']['tt_content'][contentElementUid] = {
          copy: {
            action: 'paste',
            target: targetPid,
            update: {
              colPos: colPos,
              sys_language_uid: language
            }
          }
        };
        DragDrop.ajaxAction($droppableElement, $draggableElement, parameters, copyAction, $pasteAction);
      } else {
        parameters['data']['tt_content'][contentElementUid] = {
          colPos: colPos,
          sys_language_uid: language
        };
        if ($pasteAction) {
          parameters = {
            CB: {
              paste: 'tt_content|' + targetPid,
              update: {
                colPos: colPos,
                sys_language_uid: language
              }
            }
          };
        } else {
          parameters['cmd']['tt_content'][contentElementUid] = {move: targetPid};
        }
        // fire the request, and show a message if it has failed
        DragDrop.ajaxAction($droppableElement, $draggableElement, parameters, copyAction, $pasteAction);
      }
    }
  };

  /**
   * this method does the actual AJAX request for both, the move and the copy action.
   *
   * @param $droppableElement
   * @param $draggableElement
   * @param parameters
   * @param $copyAction
   * @param $pasteAction
   * @private
   */
  DragDrop.ajaxAction = function($droppableElement, $draggableElement, parameters, $copyAction, $pasteAction) {
    require(['TYPO3/CMS/Backend/AjaxDataHandler'], function(DataHandler) {
      DataHandler.process(parameters).done(function(result) {
        if (!result.hasErrors) {
          // insert draggable on the new position
          if (!$pasteAction) {
            if (!$droppableElement.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
              $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.dropZoneIdentifier));
            } else {
              $draggableElement.detach().css({top: 0, left: 0})
                .insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
            }
          }
          if ($('.t3js-page-lang-column').length || $copyAction || $pasteAction) {
            self.location.reload(true);
          }
        }
      });
    });
  };

  /**
   * returns the next "upper" container colPos parameter inside the code
   * @param $element
   * @return int|null the colPos
   */
  DragDrop.getColumnPositionForElement = function($element) {
    var $columnContainer = $element.closest('[data-colpos]');
    if ($columnContainer.length && $columnContainer.data('colpos') !== 'undefined') {
      return $columnContainer.data('colpos');
    } else {
      return false;
    }
  };

  $(DragDrop.initialize);
  return DragDrop;
});
