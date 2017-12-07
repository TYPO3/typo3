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

import * as CodeMirror from 'cm/lib/codemirror';
import * as $ from 'jquery';

/**
 * Module: TYPO3/CMS/T3editor/T3editor
 * Renders CodeMirror into FormEngine
 * @exports TYPO3/CMS/T3editor/T3editor
 */
class T3editor {

  /**
   * @param {string} position
   * @param {string} label
   * @returns {HTMLElement}
   */
  public static createPanelNode(position: string, label: string): HTMLElement {
    const $panelNode = $('<div />', {
      class: 'CodeMirror-panel CodeMirror-panel-' + position,
      id: 'panel-' + position,
    }).append(
      $('<span />').text(label),
    );

    return $panelNode.get(0);
  }

  /**
   * The constructor, set the class properties default values
   */
  constructor() {
    this.initialize();
  }

  /**
   * Initializes CodeMirror on available texteditors
   */
  public findAndInitializeEditors(): void {
    $(document).find('textarea.t3editor').each(function(this: Element): void {
      const $textarea = $(this);

      if (!$textarea.prop('is_t3editor')) {
        const config = $textarea.data('codemirror-config');
        const modeParts = config.mode.split('/');
        const addons = $.merge([modeParts.join('/')], JSON.parse(config.addons));
        const options = JSON.parse(config.options);

        // load mode + registered addons
        require(addons, (): void => {
          const cm = CodeMirror.fromTextArea($textarea.get(0), {
            extraKeys: {
              'Ctrl-Alt-F': (codemirror: any): void => {
                codemirror.setOption('fullScreen', !codemirror.getOption('fullScreen'));
              },
              'Ctrl-Space': 'autocomplete',
              'Esc': (codemirror: any): void => {
                if (codemirror.getOption('fullScreen')) {
                  codemirror.setOption('fullScreen', false);
                }
              },
            },
            fullScreen: false,
            lineNumbers: true,
            lineWrapping: true,
            mode: modeParts[modeParts.length - 1],
          });

          // set options
          $.each(options, (key: string, value: any): void => {
            cm.setOption(key, value);
          });

          cm.addPanel(
            T3editor.createPanelNode('bottom', $textarea.attr('alt')),
            {
              position: 'bottom',
              stable: true,
            },
          );
        });

        $textarea.prop('is_t3editor', true);
      }
    });
  }

  /**
   * Initialize the events
   */
  public initialize(): void {
    $((): void => {
      this.findAndInitializeEditors();
    });
  }
}

// create an instance and return it
export = new T3editor();
