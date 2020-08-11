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

import CodeMirror from 'cm/lib/codemirror';
import $ from 'jquery';
import FormEngine = require('TYPO3/CMS/Backend/FormEngine');

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
   * Initialize the events
   */
  public initialize(): void {
    $((): void => {
      this.observeEditorCandidates();
    });
  }

  /**
   * Initializes CodeMirror on available texteditors
   */
  public observeEditorCandidates(): void {
    const observerOptions = {
      root: document.body
    };

    let observer = new IntersectionObserver((entries: IntersectionObserverEntry[]): void => {
      entries.forEach((entry: IntersectionObserverEntry): void => {
        if (entry.intersectionRatio > 0) {
          const $target = $(entry.target);
          if (!$target.prop('is_t3editor')) {
            this.initializeEditor($target);
          }
        }
      })
    }, observerOptions);

    document.querySelectorAll('textarea.t3editor').forEach((textarea: HTMLTextAreaElement): void => {
      observer.observe(textarea);
    });
  }

  private initializeEditor($textarea: JQuery): void {
    const config = $textarea.data('codemirror-config');
    const modeParts = config.mode.split('/');
    const addons = $.merge([modeParts.join('/')], JSON.parse(config.addons));
    const options = JSON.parse(config.options);

    // load mode + registered addons
    require(addons, (): void => {
      const cm = CodeMirror.fromTextArea($textarea.get(0), {
        extraKeys: {
          'Ctrl-F': 'findPersistent',
          'Cmd-F': 'findPersistent',
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

      // Mark form as changed if code editor content has changed
      cm.on('change', (): void => {
        FormEngine.Validation.markFieldAsChanged($textarea);
      });

      const bottomPanel = T3editor.createPanelNode('bottom', $textarea.attr('alt'));
      cm.addPanel(
        bottomPanel,
        {
          position: 'bottom',
          stable: false,
        },
      );

      // cm.addPanel() changes the height of the editor, thus we have to override it here again
      if ($textarea.attr('rows')) {
        const lineHeight = 18;
        const paddingBottom = 4;
        cm.setSize(null, parseInt($textarea.attr('rows'), 10) * lineHeight + paddingBottom + bottomPanel.getBoundingClientRect().height);
      } else {
        // Textarea has no "rows" attribute configured, don't limit editor in space
        cm.getWrapperElement().style.height = (document.body.getBoundingClientRect().height - cm.getWrapperElement().getBoundingClientRect().top - 80) + 'px';
        cm.setOption('viewportMargin', Infinity);
      }
    });

    $textarea.prop('is_t3editor', true);
  }
}

// create an instance and return it
export = new T3editor();
