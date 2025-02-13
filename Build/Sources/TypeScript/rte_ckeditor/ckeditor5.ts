import { html, LitElement, TemplateResult } from 'lit';
import { customElement, property, queryAssignedElements } from 'lit/decorators';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { prefixAndRebaseCss } from '@typo3/rte-ckeditor/css-prefixer';
import { ClassicEditor } from '@ckeditor/ckeditor5-editor-classic';
import type { Editor, EditorConfig, PluginConstructor } from '@ckeditor/ckeditor5-core';
import type { WordCount, WordCountConfig } from '@ckeditor/ckeditor5-word-count';
import type { SourceEditing } from '@ckeditor/ckeditor5-source-editing';
import type { GeneralHtmlSupportConfig } from '@ckeditor/ckeditor5-html-support';
import type { TypingConfig } from '@ckeditor/ckeditor5-typing';

type PluginModuleDescriptor = {
  module: string,
  exports: string[],
};

type CKEditor5Config = Omit<EditorConfig, 'toolbar'> & {
  // in TYPO3 always `items` property is used, skipping `string[]`
  toolbar?: { items: string[], shouldNotGroupWhenFull?: boolean };
  importModules?: Array<string|PluginModuleDescriptor>;
  removeImportModules?: Array<string|PluginModuleDescriptor>;
  contentsCss?: string[];
  width?: string|number;
  height?: string|number;
  readOnly?: boolean;
  debug?: boolean;
}

type Typo3Plugin = PluginConstructor<Editor> & {overrides?: PluginConstructor<Editor>[]};
type PluginModule = Record<string, Typo3Plugin>;

const defaultPlugins: PluginModuleDescriptor[] = [
  { module: '@ckeditor/ckeditor5-block-quote', exports: ['BlockQuote'] },
  { module: '@ckeditor/ckeditor5-essentials', exports: ['Essentials'] },
  { module: '@ckeditor/ckeditor5-find-and-replace', exports: ['FindAndReplace'] },
  { module: '@ckeditor/ckeditor5-heading', exports: ['Heading'] },
  { module: '@ckeditor/ckeditor5-indent', exports: ['Indent'] },
  { module: '@ckeditor/ckeditor5-link', exports: ['Link'] },
  { module: '@ckeditor/ckeditor5-list', exports: ['List'] },
  { module: '@ckeditor/ckeditor5-paragraph', exports: ['Paragraph'] },
  { module: '@ckeditor/ckeditor5-clipboard', exports: ['PastePlainText'] },
  { module: '@ckeditor/ckeditor5-paste-from-office', exports: ['PasteFromOffice'] },
  { module: '@ckeditor/ckeditor5-remove-format', exports: ['RemoveFormat'] },
  { module: '@ckeditor/ckeditor5-table', exports: ['Table', 'TableToolbar', 'TableProperties', 'TableCellProperties', 'TableCaption'] },
  { module: '@ckeditor/ckeditor5-typing', exports: ['TextTransformation'] },
  { module: '@ckeditor/ckeditor5-source-editing', exports: ['SourceEditing'] },
  { module: '@ckeditor/ckeditor5-alignment', exports: ['Alignment'] },
  { module: '@ckeditor/ckeditor5-style', exports: ['Style'] },
  { module: '@ckeditor/ckeditor5-html-support', exports: ['GeneralHtmlSupport'] },
  { module: '@ckeditor/ckeditor5-basic-styles', exports: ['Bold', 'Italic', 'Subscript', 'Superscript', 'Strikethrough', 'Underline'] },
  { module: '@ckeditor/ckeditor5-special-characters', exports: ['SpecialCharacters', 'SpecialCharactersEssentials'] },
  { module: '@ckeditor/ckeditor5-horizontal-line', exports: ['HorizontalLine'] },
];

/**
 * Module: @typo3/rte_ckeditor/ckeditor5
 *
 * @example
 * <typo3-rte-ckeditor-ckeditor5
 *    options="[JSON]"
 * ></typo3-rte-ckeditor-ckeditor5>
 */
@customElement('typo3-rte-ckeditor-ckeditor5')
export class CKEditor5Element extends LitElement {

  @property({ type: Object }) options?: CKEditor5Config = {};

  @queryAssignedElements({ slot: 'textarea' }) target: HTMLElement[];

  private readonly styleSheets: Map<CSSStyleSheet, true> = new Map();

  public override connectedCallback(): void {
    super.connectedCallback();
    this.prefixAndLoadContentsCss();
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    document.adoptedStyleSheets = document.adoptedStyleSheets.filter(styleSheet => !this.styleSheets.has(styleSheet));
    this.styleSheets.clear();
  }

  protected override firstUpdated(): void {
    if (this.target[0] instanceof HTMLTextAreaElement) {
      this.initCKEditor();
    } else {
      this.renderRoot.querySelector('slot[name="textarea"]').addEventListener('slotchange', () => this.initCKEditor(), { once: true });
    }
  }

  protected async initCKEditor(): Promise<void> {
    if (!(this.target[0] instanceof HTMLTextAreaElement)) {
      throw new Error('No rich-text <textarea> content target found.');
    }

    const {
      // options handled by this wrapper
      importModules,
      removeImportModules,
      width,
      height,
      readOnly,
      debug,

      // options forwarded to CKEditor5
      toolbar,
      placeholder,
      htmlSupport,
      wordCount,
      typo3link,
      removePlugins,
      ...otherOptions
    } = this.options;

    if ('extraPlugins' in otherOptions) {
      // Drop CKEditor4 style extraPlugins which we do not support for CKEditor5
      // as this string-based list of plugin names works only for bundled plugins.
      // `config.importModules` is used for CKEditor5 instead
      delete otherOptions.extraPlugins;
    }
    if ('contentsCss' in otherOptions) {
      // Consumed in connectedCallback
      delete otherOptions.contentsCss;
    }

    const plugins = await this.resolvePlugins(defaultPlugins, importModules, removeImportModules);

    const config: EditorConfig = {
      licenseKey: 'GPL',
      ...otherOptions,
      // link.defaultProtocol: 'https://'
      toolbar,
      plugins,
      placeholder,
      wordCount,
      typo3link: typo3link || null,
      removePlugins: removePlugins || [],
    };

    if (htmlSupport !== undefined) {
      config.htmlSupport = convertPseudoRegExp(htmlSupport) as GeneralHtmlSupportConfig;
    }

    if (config?.typing?.transformations !== undefined) {
      // Implement variant of CKEditor's native buildQuotesRegExp() method.
      // This allows to convert a 'pattern' sub-object into the proper object.
      config.typing.transformations = convertPseudoRegExp(config.typing.transformations) as TypingConfig['transformations'];
    }

    ClassicEditor
      .create(this.target[0], config)
      .then((editor: ClassicEditor) => {
        this.applyEditableElementStyles(editor, width, height);
        this.handleWordCountPlugin(editor, wordCount);
        this.applyReadOnly(editor, readOnly);
        if (editor.plugins.has('SourceEditing')) {
          const sourceEditingPlugin = editor.plugins.get('SourceEditing') as SourceEditing;
          editor.model.document.on('change:data', (): void => {
            if (!sourceEditingPlugin.isSourceEditingMode) {
              editor.updateSourceElement()
            }
            this.target[0].dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
          });
        }

        if (debug) {
          import('@ckeditor/ckeditor5-inspector').then(({ default: CKEditorInspector }) => CKEditorInspector.attach(editor, { isCollapsed: true }));
        }
      });
  }

  protected override render(): TemplateResult {
    return html`
      <slot name="textarea"></slot>
      <slot></slot>
    `;
  }

  private async resolvePlugins(
    defaultPlugins: Array<PluginModuleDescriptor>,
    importModulesOption: Array<string|PluginModuleDescriptor>|undefined,
    removeImportModulesOption: Array<string|PluginModuleDescriptor>|undefined
  ): Promise<Array<PluginConstructor<Editor>>> {
    const removeImportModules: Array<PluginModuleDescriptor> = normalizeImportModules(removeImportModulesOption || []);
    const importModules: Array<PluginModuleDescriptor> = normalizeImportModules([
      ...defaultPlugins,
      ...(importModulesOption || []),
    ]).map((moduleDescriptor: PluginModuleDescriptor) => {
      const { module } = moduleDescriptor;
      let { exports } = moduleDescriptor;
      for (const toRemove of removeImportModules) {
        if (toRemove.module === module) {
          exports = exports.filter(el => !toRemove.exports.includes(el));
        }
      }
      return { module, exports };
    });

    const pluginModules: Array<{module: PluginModule, exports: string[]}> = await Promise.all(
      importModules
        .map(async (moduleDescriptor: PluginModuleDescriptor): Promise<{module: PluginModule, exports: string[]}> => {
          try {
            return {
              module: await import(moduleDescriptor.module) as PluginModule,
              exports: moduleDescriptor.exports,
            }
          } catch (e) {
            console.error(`Failed to load CKEditor5 module ${moduleDescriptor.module}`, e);
            return {
              module: null,
              exports: []
            }
          }
        })
    );

    const declaredPlugins: Array<Typo3Plugin> = [];
    pluginModules.forEach(({ module, exports }) => {
      for (const exportName of exports) {
        if (exportName in module) {
          declaredPlugins.push(module[exportName]);
        } else {
          console.error(`CKEditor5 plugin export "${exportName}" not available in`, module);
        }
      }
    });

    // plugins that were overridden by other custom plugin implementations
    const overriddenPlugins = declaredPlugins
      .filter(plugin => plugin.overrides?.length > 0)
      .map(plugin => plugin.overrides)
      .flat(1);

    // plugins, without those that have been overridden
    return declaredPlugins
      .filter(plugin => !overriddenPlugins.includes(plugin as PluginConstructor<Editor>));
  }

  private async prefixAndLoadContentsCss(): Promise<void> {
    if (!Array.isArray(this.options.contentsCss)) {
      return;
    }
    const styleSheetStates = await Promise.allSettled(
      this.options.contentsCss.map(url => this.prefixContentsCss(url, this.getAttribute('id')))
    );
    const styleSheets = styleSheetStates
      .map(state => state.status === 'fulfilled' ? state.value : null)
      .filter(v => v !== null);
    styleSheets.forEach(styleSheet => this.styleSheets.set(styleSheet, true));
    document.adoptedStyleSheets = [...document.adoptedStyleSheets, ...styleSheets];
  }

  private async prefixContentsCss(url: string, fieldId: string): Promise<CSSStyleSheet> {
    let content: string;
    try {
      const response = await new AjaxRequest(url).get();
      content = await response.resolve();
    } catch (e) {
      console.error(`Failed to fetch CSS content for CKEditor5 prefixing: "${url}"`, e);
      throw new Error();
    }
    // Prefix custom stylesheets with id of the container element and a required `.ck-content` selector
    // see https://ckeditor.com/docs/ckeditor5/latest/installation/advanced/content-styles.html
    const newParent = `#${fieldId} .ck-content`;
    const prefixedCss = prefixAndRebaseCss(content, url, newParent);

    const styleSheet = new CSSStyleSheet();
    await styleSheet.replace(
      prefixedCss
    );
    return styleSheet;
  }

  private applyEditableElementStyles(editor: Editor, width: string|number|undefined, height: string|number|undefined): void {
    const view = editor.editing.view;
    const styles: Record<string, string|number|undefined> = {
      'min-height': height,
      'min-width': width,
    };
    Object.keys(styles).forEach((key) => {
      const _assignment: string|number = styles[key];
      if (!_assignment) {
        return;
      }
      let assignment: string;
      if (typeof _assignment === 'number' || !Number.isNaN(Number(assignment))) {
        assignment = `${_assignment}px`;
      } else {
        assignment = _assignment
      }
      view.change((writer) => {
        writer.setStyle(key, assignment, view.document.getRoot());
      });
    });
  }

  /**
   * see https://ckeditor.com/docs/ckeditor5/latest/features/word-count.html
   */
  private handleWordCountPlugin(editor: Editor, wordCount: WordCountConfig|undefined): void {
    if (editor.plugins.has('WordCount') && (wordCount?.displayWords || wordCount?.displayCharacters)) {
      const wordCountPlugin = editor.plugins.get('WordCount') as WordCount;
      this.appendChild(wordCountPlugin.wordCountContainer);
    }
  }

  /**
   * see https://ckeditor.com/docs/ckeditor5/latest/features/read-only.html
   */
  private applyReadOnly(editor: Editor, readOnly: boolean): void {
    if (readOnly) {
      editor.enableReadOnlyMode('typo3-lock');
    }
  }
}

type RecurseMapInput = Record<string, unknown>|Array<unknown>|unknown;
type PseudoRegExp = {
  pattern: string,
  flags?: string
};

function walkObj(data: RecurseMapInput, proc: (value: unknown) => unknown|null): RecurseMapInput {
  if (typeof data === 'object') {
    if (Array.isArray(data)) {
      return data.map((element: RecurseMapInput) => proc(element) ?? walkObj(element, proc));
    }
    const newData: Record<string, unknown> = {};
    for (const [key, value] of Object.entries(data)) {
      newData[key] = proc(value) ?? walkObj(value, proc);
    }
    return newData;
  }
  return data;
}

function convertPseudoRegExp(data: RecurseMapInput): RecurseMapInput {
  return walkObj(data, (entry: PseudoRegExp | unknown): RegExp | null => {
    if (typeof entry === 'object' && 'pattern' in entry && typeof entry.pattern === 'string') {
      const pseudoRegExp = entry as PseudoRegExp;
      return new RegExp(pseudoRegExp.pattern, pseudoRegExp.flags || undefined);
    }
    return null;
  });
}

function normalizeImportModules(modules: Array<string|PluginModuleDescriptor>): Array<PluginModuleDescriptor> {
  return modules.map(moduleDescriptor => {
    if (typeof moduleDescriptor === 'string') {
      return {
        module: moduleDescriptor,
        exports: [ 'default' ],
      }
    }
    return moduleDescriptor;
  });
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-rte-ckeditor-ckeditor5': CKEditor5Element;
  }
}
