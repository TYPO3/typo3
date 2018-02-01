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
 * Module: TYPO3/CMS/Backend/ContextMenu
 * Javascript container used to load the context menu via AJAX
 * to render the result in a layer next to the mouse cursor
 */
define(['jquery', 'TYPO3/CMS/Backend/ContextMenuActions'], function($, ContextMenuActions) {

  /**
   *
   * @type {{mousePos: {X: null, Y: null}, delayContextMenuHide: boolean}}
   * @exports TYPO3/CMS/Backend/ContextMenu
   */
  var ContextMenu = {
    mousePos: {
      X: null,
      Y: null
    },
    delayContextMenuHide: false,
    record: {
      uid: null,
      table: null
    }
  };

  /**
   * Initialize events
   */
  ContextMenu.initializeEvents = function() {
    $(document).on('click contextmenu', '.t3js-contextmenutrigger', function(event) {
      // if there is an other "inline" onclick setting, context menu is not triggered
      // usually this is the case for the foldertree
      if ($(this).prop('onclick') && event.type === 'click') {
        return;
      }
      event.preventDefault();
      ContextMenu.show(
        $(this).data('table'),
        $(this).data('uid'),
        $(this).data('context'),
        $(this).data('iteminfo'),
        $(this).data('parameters')
      );
    });

    // register mouse movement inside the document
    $(document).on('mousemove', ContextMenu.storeMousePositionEvent);
  };

  /**
   * Main function, called from most context menu links
   *
   * @param {String} table Table from where info should be fetched
   * @param {(String|Number)} uid The UID of the item
   * @param {String} context Context of the item
   * @param {String} enDisItems Items to disable / enable
   * @param {String} addParams Additional params
   * @return void
   */
  ContextMenu.show = function(table, uid, context, enDisItems, addParams) {
    ContextMenu.record = null;
    ContextMenu.record = {table: table, uid: uid};

    var parameters = '';

    if (typeof table !== 'undefined') {
      parameters += 'table=' + encodeURIComponent(table);
    }
    if (typeof uid !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'uid=' + uid;
    }
    if (typeof context !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'context=' + context;
    }
    if (typeof enDisItems !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'enDisItems=' + enDisItems;
    }
    if (typeof addParams !== 'undefined') {
      parameters += (parameters.length > 0 ? '&' : '') + 'addParams=' + addParams;
    }
    this.fetch(parameters);
  };

  /**
   * Make the AJAX request
   *
   * @param {array} parameters Parameters sent to the server
   * @return void
   */
  ContextMenu.fetch = function(parameters) {
    var url = TYPO3.settings.ajaxUrls['contextmenu'];
    if (parameters) {
      url += ((url.indexOf('?') == -1) ? '?' : '&') + parameters;
    }
    $.ajax(url).done(function(response) {
      if (typeof response !== "undefined" && Object.keys(response).length > 0) {
        ContextMenu.populateData(response, 0);
      }
    });
  };

  /**
   * fills the context menu with content and displays it correctly
   * depending on the mouse position
   *
   * @param {array} items The data that will be put in the menu
   * @param {Number} level The depth of the context menu
   */
  ContextMenu.populateData = function(items, level) {
    this.initializeContextMenuContainer();

    level = parseInt(level, 10) || 0;
    var $obj = $('#contentMenu' + level);

    if ($obj.length && (level === 0 || $('#contentMenu' + (level - 1)).is(':visible'))) {
      var elements = ContextMenu.drawMenu(items, level);
      $obj.html('<div class="list-group">' + elements + '</div>');

      $('a.list-group-item', $obj).click(function(event) {
        event.preventDefault();

        if ($(this).hasClass('list-group-item-submenu')) {
          ContextMenu.openSubmenu(level, $(this));
          return;
        }

        var callbackName = $(this).data('callback-action');
        var callbackModule = $(this).data('callback-module');
        var clickItem = $(this);
        if (callbackModule) {
          require([callbackModule], function(callbackModule) {
            callbackModule[callbackName].bind(clickItem)(ContextMenu.record.table, ContextMenu.record.uid);
          });
        } else if (ContextMenuActions && ContextMenuActions[callbackName]) {
          ContextMenuActions[callbackName].bind(clickItem)(ContextMenu.record.table, ContextMenu.record.uid);
        } else {
          console.log('action: ' + callbackName + ' not found');
        }
        ContextMenu.hideAll();
      });

      $obj.css(ContextMenu.getPosition($obj)).show();
    }
  };

  ContextMenu.openSubmenu = function(level, $item) {
    var $obj = $('#contentMenu' + (level + 1)).html('');
    $item.next().find('.list-group').clone(true).appendTo($obj);
    $obj.css(ContextMenu.getPosition($obj)).show();
  };

  ContextMenu.getPosition = function($obj) {
    var x = this.mousePos.X;
    var y = this.mousePos.Y;
    var dimsWindow = {
      width: $(window).width() - 20, // saving margin for scrollbars
      height: $(window).height()
    };

    // dimensions for the context menu
    var dims = {
      width: $obj.width(),
      height: $obj.height()
    };

    var relative = {
      X: this.mousePos.X - $(document).scrollLeft(),
      Y: this.mousePos.Y - $(document).scrollTop()
    };

    // adjusting the Y position of the layer to fit it into the window frame
    // if there is enough space above then put it upwards,
    // otherwise adjust it to the bottom of the window
    if (dimsWindow.height - dims.height < relative.Y) {
      if (relative.Y > dims.height) {
        y -= (dims.height - 10);
      } else {
        y += (dimsWindow.height - dims.height - relative.Y);
      }
    }
    // adjusting the X position like Y above, but align it to the left side of the viewport if it does not fit completely
    if (dimsWindow.width - dims.width < relative.X) {
      if (relative.X > dims.width) {
        x -= (dims.width - 10);
      } else if ((dimsWindow.width - dims.width - relative.X) < $(document).scrollLeft()) {
        x = $(document).scrollLeft();
      } else {
        x += (dimsWindow.width - dims.width - relative.X);
      }
    }
    return {left: x + 'px', top: y + 'px'};
  };

  /**
   * fills the context menu with content and displays it correctly
   * depending on the mouse position
   *
   * @param {array} items The data that will be put in the menu
   * @param {Number} level The depth of the context menu
   */
  ContextMenu.drawMenu = function(items, level) {
    var elements = '';
    $.each(items, function(key, value) {
      if (value.type === 'item') {
        elements += ContextMenu.drawActionItem(value);
      } else if (value.type === 'divider') {
        elements += '<a class="list-group-item list-group-item-divider"></a>';
      } else if (value.type === 'submenu' || value.childItems) {
        elements += '<a class="list-group-item list-group-item-submenu"><span class="list-group-item-icon">' + value.icon + '</span> ' + value.label + '&nbsp;&nbsp;<span class="fa fa-caret-right"></span></a>';
        var childElements = ContextMenu.drawMenu(value.childItems, 1);
        elements += '<div class="context-menu contentMenu' + (level + 1) + '" style="display:none;"><div class="list-group">' + childElements + '</div></div>';

      }
    });
    return elements;
  };

  ContextMenu.drawActionItem = function(value) {
    var attributes = value.additionalAttributes || [];
    $attributesString = '';
    for (var attribute in attributes) {
      $attributesString += ' ' + attribute + '="' + attributes[attribute] + '"';
    }

    return '<a class="list-group-item"'
      + ' data-callback-action="' + value.callbackAction + '"'
      + $attributesString + '><span class="list-group-item-icon">' + value.icon + '</span> ' + value.label + '</a>';
  };
  /**
   * event handler function that saves the
   * actual position of the mouse
   * in the context menu object
   *
   * @param {Event} event The event object
   */
  ContextMenu.storeMousePositionEvent = function(event) {
    ContextMenu.mousePos.X = event.pageX;
    ContextMenu.mousePos.Y = event.pageY;
    ContextMenu.mouseOutFromMenu('#contentMenu0');
    ContextMenu.mouseOutFromMenu('#contentMenu1');
  };

  /**
   * hides a visible menu if the mouse has moved outside
   * of the object
   *
   * @param {Object} obj The object to hide
   */
  ContextMenu.mouseOutFromMenu = function(obj) {
    var $element = $(obj);

    if ($element.length > 0 && $element.is(':visible') && !this.within($element, this.mousePos.X, this.mousePos.Y)) {
      this.hide($element);
    } else if ($element.length > 0 && $element.is(':visible')) {
      this.delayContextMenuHide = true;
    }
  };

  /**
   *
   * @param {Object} $element
   * @param {Number} x
   * @param {Number} y
   * @returns {Boolean}
   */
  ContextMenu.within = function($element, x, y) {
    var offset = $element.offset();
    return (
      y >= offset.top &&
      y < offset.top + $element.height() &&
      x >= offset.left &&
      x < offset.left + $element.width()
    );
  };

  /**
   * hides a context menu
   *
   * @param {Object} obj The context menu object to hide
   */
  ContextMenu.hide = function(obj) {
    this.delayContextMenuHide = false;
    window.setTimeout(function() {
      if (!ContextMenu.delayContextMenuHide) {
        $(obj).hide();
      }
    }, 500);
  };

  /**
   * hides all context menus
   */
  ContextMenu.hideAll = function() {
    this.hide('#contentMenu0');
    this.hide('#contentMenu1');
  };

  /**
   * manipulates the DOM to add the divs needed for context menu the bottom of the <body>-tag
   */
  ContextMenu.initializeContextMenuContainer = function() {
    if ($('#contentMenu0').length === 0) {
      var code = '<div id="contentMenu0" class="context-menu"></div><div id="contentMenu1" class="context-menu" style="display: block;"></div>';
      $('body').append(code);
    }
  };

  ContextMenu.initializeEvents();

  return ContextMenu;
});
