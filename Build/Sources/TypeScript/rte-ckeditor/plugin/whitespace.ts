import * as Core from '@ckeditor/ckeditor5-core';
import * as UI from '@ckeditor/ckeditor5-ui';
import * as Utils from '@ckeditor/ckeditor5-utils';
import * as Typing from '@ckeditor/ckeditor5-typing';
import type { InsertTextCommand } from '@ckeditor/ckeditor5-typing';

/**
 * CKEditor5 Whitespace Plugin
 *
 * Add support for non breaking spaces
 * - Make non breaking spaces visible
 * - Shortcut for adding non breaking space:
 *   - alt+shift+space on MacOS
 *   - ctrl+shift+space on all other Systems
 *
 * Add support for soft hyphen
 * - Make soft hyphens visible
 * - Register button for editor ui: softhyphen
 * - Shortcut for adding non breaking space:
 *   - alt+shift+dash on MacOS
 *   - ctrl+shift+dash on all other Systems
 */
export class Whitespace extends Core.Plugin {
  static readonly pluginName = 'Whitespace';
  static readonly requires = [ Typing.Typing ] as const;

  public init(): void {
    const editor = this.editor;
    const inputCommand: InsertTextCommand = editor.commands.get('insertText');

    // CKEditor should map Ctrl to Cmd on MacOs, but for
    // some reason the shortcut is blocked and cannot be
    // overwritten.
    //
    // For this reason we are using "Alt" as controlKey on MacOS.
    // All other System use "Ctrl" as controlKey.
    const controlKey = Utils.env.isMac ? 'Alt' : 'Ctrl';

    editor.ui.componentFactory.add('softhyphen', locale => {
      const button = new UI.ButtonView(locale);
      button.label = 'Soft-Hyphen';
      button.icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" xml:space="preserve"><path d="M4.25 3C3.082 4.683 2 6.917 2 10.026 2 13.083 3.114 15.282 4.25 17H3c-1.008-1.425-2-3.624-2-6.974.016-3.384.992-5.583 2-7.026h1.25zM17 3c1.008 1.443 1.984 3.642 2 7.026 0 3.35-.992 5.549-2 6.974h-1.25c1.136-1.718 2.25-3.917 2.25-6.974 0-3.11-1.082-5.343-2.25-7.026H17zM6 9h8v2H6z"/></svg>';
      button.keystroke = `${controlKey}+Shift+-`;
      button.tooltip = true;
      button.bind('isEnabled').to(inputCommand);
      button.on('execute', () => this.insertSoftHyphen());
      return button;
    });

    editor.keystrokes.set([controlKey, 'Shift', 189], (data, cancel) => {
      this.insertSoftHyphen();
      cancel();
    });

    editor.keystrokes.set([controlKey, 'Shift', 'Space'], (data, cancel) => {
      this.insertNonBreakingSpace();
      cancel();
    });

    editor.conversion.for('editingDowncast').add(downcastDispatcher => {
      downcastDispatcher.on('insert:$text', (evt, data, conversionApi) => {
        if (!conversionApi.consumable.consume(data.item, evt.name)) {
          return;
        }

        const viewWriter = conversionApi.writer;
        const chunks = data.item.data
          // Using a regex caputure group in order for the
          // split chars to be included in the `chunks` array
          .split(/([\u00AD\u00A0])/)
          .filter((value: string) => value !== '');
        let currentPosition = data.range.start;

        chunks.forEach((chunk: string) => {
          const text = chunk === '\u00AD' ? '-' : chunk;
          viewWriter.insert(
            conversionApi.mapper.toViewPosition(currentPosition),
            viewWriter.createText(text)
          );

          if (chunk === '\u00AD' || chunk === '\u00A0') {
            const className = chunk === '\u00AD' ? 'softhyphen' : 'nbsp';
            const id = Math.random().toString(16).slice(2);
            const wrapper = viewWriter.createAttributeElement('span', { class: `ck ck-${className}` }, { id });
            const wrapperRange = viewWriter.createRange(
              conversionApi.mapper.toViewPosition(currentPosition),
              conversionApi.mapper.toViewPosition(currentPosition.getShiftedBy(chunk.length))
            );
            viewWriter.wrap(wrapperRange, wrapper);
          }

          currentPosition = currentPosition.getShiftedBy(chunk.length);
        });
      }, { priority: 'high' });
    });
  }

  private insertNonBreakingSpace(): void {
    const editor = this.editor;

    editor.execute('insertText', { text: '\u00A0' });
    editor.editing.view.focus();
  }

  private insertSoftHyphen(): void {
    const editor = this.editor;

    editor.execute('insertText', { text: '\u00AD' });
    editor.editing.view.focus();
  }
}

// Provided for backwards compatibility
export default Whitespace;
