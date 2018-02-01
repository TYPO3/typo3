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

var Tree, DragDrop;

/**
 * Module: TYPO3/CMS/Backend/LegacyTree
 * JavaScript RequireJS module in use for legacy trees, used
 * in FolderTree, Element Browser PageTree and
 * Element Browser FolderTree
 * note that this should not be used (thus, declared as anonymous
 * UMD module)
 * @exports TYPO3/CMS/Backend/LegacyTree
 */
define(['jquery'], function($) {

  DragDrop = {
    dragID: null,
    table: null	// can be "pages" or "folders", needed for doing the changes when dropping
  };

  DragDrop.dragElement = function(event, $element) {
    event.preventDefault();
    var $container = $element.parent().parent();
    var elementID = $container.prop('id');
    elementID = elementID.substring(elementID.indexOf('_') + 1);
    DragDrop.dragID = DragDrop.getIdFromEvent(event);

    if (!DragDrop.dragID) {
      return false;
    }

    if (!elementID) {
      elementID = DragDrop.dragID;
    }

    if ($('#dragIcon').length === 0) {
      $('body').append('<div id="dragIcon" style="display: none;">&nbsp;</div>');
    }

    $('#dragIcon').html($container.find('.dragIcon').html() + $container.find('.dragTitle').children(':first').text());

    document.onmouseup = function(event) {
      DragDrop.cancelDragEvent(event);
    };

    document.onmousemove = function(event) {
      DragDrop.mouseMoveEvent(event);
    };
  };

  DragDrop.dropElement = function(event) {
    var dropID = DragDrop.getIdFromEvent(event);
    if ((DragDrop.dragID) && (DragDrop.dragID !== dropID)) {
      var dragID = DragDrop.dragID;
      var table = DragDrop.table;
      var parameters = 'table=' + table + '-drag' +
        '&uid=' + dragID +
        '&dragDrop=' + table +
        '&srcId=' + dragID +
        '&dstId=' + dropID;
      require(['TYPO3/CMS/Backend/ContextMenu'], function(ContextMenu) {
        ContextMenu.record = {table: decodeURIComponent(table), uid: decodeURIComponent(dragID)};
        ContextMenu.fetch(parameters);
      });
    }
    DragDrop.cancelDragEvent();
    return false;
  };

  DragDrop.cancelDragEvent = function(event) {
    DragDrop.dragID = null;
    if ($('#dragIcon').length && $('#dragIcon').is(':visible')) {
      $('#dragIcon').hide();
    }

    document.onmouseup = null;
    document.onmousemove = null;
  };

  DragDrop.mouseMoveEvent = function(event) {
    if (!event) {
      event = window.event;
    }
    $('#dragIcon').css({
      left: (event.x + 5) + 'px',
      top: (event.y - 5) + 'px'
    }).show();
  };

  DragDrop.getIdFromEvent = function(event) {
    var obj = event.currentTarget;
    while (obj.id == false && obj.parentNode) {
      obj = obj.parentNode;
    }
    return obj.id.substring(obj.id.indexOf('_') + 1);
  };

  Tree = {
    ajaxID: 'sc_alt_file_navframe_expandtoggle',
    frameSetModule: null,
    activateDragDrop: true,
    highlightClass: 'active',
    pageID: 0,

    // reloads a part of the page tree (useful when "expand" / "collapse")
    load: function(params, isExpand, obj, scopeData, scopeHash) {
      var $obj = $(obj);
      var $parentNode = $(obj).parent().parent();

      // immediately collapse the subtree and change the plus to a minus when collapsing
      // without waiting for the response
      if (!isExpand) {
        $parentNode.find('ul:first').remove();
        var $pm = $obj.parent().find('.pm:first');
        if ($pm.length) {
          $pm.get().onclick = null;
          var src = $pm.children(':first').prop('src');
          src = src.replace(/minus/, 'plus');
          $pm.children('first').prop('src', src);
        }
      } else {
        $obj.css({cursor: 'wait'});
      }
      $.ajax({
        url: TYPO3.settings.ajaxUrls[this.ajaxID],
        data: {
          PM: params,
          scopeData: scopeData,
          scopeHash: scopeHash
        }
      }).done(function(data) {
        // the parent node needs to be overwritten, not the object
        $parentNode.replaceWith(data);
        Tree.reSelectActiveItem();
      });
    },

    // does the complete page refresh (previously known as "_refresh_nav()")
    refresh: function() {
      var r = new Date();
      // randNum is useful so pagetree does not get cached in browser cache when refreshing
      var loc = window.location.href.replace(/&randNum=\d+|#.*/g, '');
      var addSign = loc.indexOf('?') > 0 ? '&' : '?';
      window.location = loc + addSign + 'randNum=' + r.getTime();
    },

    // attaches the events to the elements needed for the drag and drop (for the titles and the icons)
    registerDragDropHandlers: function() {
      if (!Tree.activateDragDrop) {
        return;
      }

      $('.list-tree-root').on('mousedown', '.dragTitle, .dragIcon', function(evt) {
        DragDrop.dragElement(evt, $(this));
      }).on('mouseup', '.dragTitle, .dragIcon', function(evt) {
        DragDrop.dropElement(evt, $(this));
      });
    },

    // selects the activated item again, in case it collapsed and got expanded again
    reSelectActiveItem: function() {
      if (!top.fsMod) {
        return;
      }
      var $activeItem = $('#' + top.fsMod.navFrameHighlightedID[this.frameSetModule]);
      if ($activeItem.length) {
        $activeItem.addClass(Tree.highlightClass);
        Tree.extractPageIdFromTreeItem($activeItem.prop('id'));
      }
    },

    // highlights an active list item in the page tree and registers it to the top-frame
    // used when loading the page for the first time
    highlightActiveItem: function(frameSetModule, highlightID) {
      Tree.frameSetModule = frameSetModule;
      Tree.extractPageIdFromTreeItem(highlightID);

      // Remove all items that are already highlighted
      var $obj = $('#' + top.fsMod.navFrameHighlightedID[frameSetModule]);
      if ($obj.length) {
        $obj.removeClass(Tree.highlightClass);
      }

      // Set the new item
      top.fsMod.navFrameHighlightedID[frameSetModule] = highlightID;
      $('#' + highlightID).addClass(Tree.highlightClass);
    },

    //extract pageID from the given id (pagesxxx_y_z where xxx is the ID)
    extractPageIdFromTreeItem: function(highlightID) {
      if (highlightID) {
        Tree.pageID = highlightID.split('_')[0].substring(5);
      }
    }
  };

  return Tree;
});
