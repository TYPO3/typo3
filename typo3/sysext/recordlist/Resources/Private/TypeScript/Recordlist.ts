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

import * as $ from 'jquery';
import PersistentStorage = require('TYPO3/CMS/Backend/Storage/Persistent');
import Icons = require('TYPO3/CMS/Backend/Icons');

declare global {
  const T3_THIS_LOCATION: string;
  const editList: Function;
}

interface IconIdentifier {
  collapse: string;
  expand: string;
  editMultiple: string;
}
interface RecordlistIdentifier {
  entity: string;
  toggle: string;
  icons: IconIdentifier;
}

/**
 * Module: TYPO3/CMS/Recordlist/Recordlist
 * Usability improvements for the record list
 * @exports TYPO3/CMS/Recordlist/Recordlist
 */
class Recordlist {
  identifier: RecordlistIdentifier = {
    entity: '.t3js-entity',
    toggle: '.t3js-toggle-recordlist',
    icons: {
      collapse: 'actions-view-list-collapse',
      expand: 'actions-view-list-expand',
      editMultiple: '.t3js-record-edit-multiple',
    },
  };

  constructor() {
    $(document).on('click', this.identifier.toggle, this.toggleClick);
    $(document).on('click', this.identifier.icons.editMultiple, this.onEditMultiple);
  }

  public toggleClick = (e: JQueryEventObject): void => {
    e.preventDefault();

    const $me = $(e.currentTarget);
    const table = $me.data('table');
    const $target = $($me.data('target'));
    const isExpanded = $target.data('state') === 'expanded';
    const $collapseIcon = $me.find('.collapseIcon');
    const toggleIcon = isExpanded ? this.identifier.icons.expand : this.identifier.icons.collapse;

    Icons.getIcon(toggleIcon, Icons.sizes.small).done((icon: string): void => {
      $collapseIcon.html(icon);
    });

    // Store collapse state in UC
    let storedModuleDataList = {};

    if (PersistentStorage.isset('moduleData.list')) {
      storedModuleDataList = PersistentStorage.get('moduleData.list');
    }

    const collapseConfig: any = {};
    collapseConfig[table] = isExpanded ? 1 : 0;

    $.extend(true, storedModuleDataList, collapseConfig);
    PersistentStorage.set('moduleData.list', storedModuleDataList).done((): void => {
      $target.data('state', isExpanded ? 'collapsed' : 'expanded');
    });
  }

  /**
   * Handles editing multiple records.
   */
  public onEditMultiple = (event: JQueryEventObject): void => {
    event.preventDefault();
    let $tableContainer: JQuery;
    let tableName: string;
    let entityIdentifiers: string;
    let uri: string;
    let patterns: RegExpMatchArray;

    $tableContainer = $(event.currentTarget).closest('[data-table]');
    if ($tableContainer.length === 0) {
      return;
    }

    uri = $(event.currentTarget).data('uri');
    tableName = $tableContainer.data('table');
    entityIdentifiers = $tableContainer
      .find(this.identifier.entity + '[data-uid][data-table="' + tableName + '"]')
      .map((index: number, entity: Element): void => {
        return $(entity).data('uid');
      })
      .toArray()
      .join(',');

    patterns = uri.match(/{[^}]+}/g);
    $.each(patterns, (patternIndex: string, pattern: string) => {
      const expression: string = pattern.substr(1, pattern.length - 2);
      const pipes: Array<string> = expression.split(':');
      const name: string = pipes.shift();
      let value: string;

      switch (name) {
        case 'entityIdentifiers':
          value = entityIdentifiers;
          break;
        case 'T3_THIS_LOCATION':
          value = T3_THIS_LOCATION;
          break;
        default:
          return;
      }

      $.each(pipes, (pipeIndex: string, pipe: string): void => {
        if (pipe === 'editList') {
          value = editList(tableName, value);
        }
      });

      uri = uri.replace(pattern, value);
    });

    window.location.href = uri;
  }
}

export = new Recordlist();
