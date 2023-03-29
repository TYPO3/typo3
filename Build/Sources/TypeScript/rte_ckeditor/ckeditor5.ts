import { html, LitElement, TemplateResult } from 'lit';
import { customElement, property, query } from 'lit/decorators';
import { CKEditor5, Core } from '@typo3/ckeditor5-bundle';
import type { PluginInterface } from '@ckeditor/ckeditor5-core/src/plugin';
import type { EditorWithUI } from '@ckeditor/ckeditor5-core/src/editor/editorwithui';

interface CKEditor5Config {
  // in TYPO3 always `items` property is used, skipping `string[]`
  toolbar?: { items: string[], shouldNotGroupWhenFull?: boolean };
  extraPlugins?: string[];
  removePlugins?: string[];
  importModules?: string[];
  style?: any;
  heading?: any;
  alignment?: any;
  width?: any;
  height?: any;
  readOnly?: boolean;
  language?: any;
  table?: any;

  wordCount?: any;
  typo3link?: any;
  debug?: boolean;
}

interface PluginModule {
  default?: PluginInterface;
}

interface FormEngineConfig {
  id?: string;
  name?: string;
  value?: string;
  validationRules?: any;
}

type Typo3Plugin = PluginInterface & {overrides?: Typo3Plugin[]};

/**
 * Module: @typo3/rte_ckeditor/ckeditor5
 *
 * @example
 * <typo3-rte-ckeditor-ckeditor5
 *    options="[JSON]"
 *    form-engine="[JSON]"
 *    style-src?="/uri/for/custom.css">
 * </typo3-rte-ckeditor-ckeditor5>
 */
@customElement('typo3-rte-ckeditor-ckeditor5')
export class CKEditor5Element extends LitElement {
  @property({ type: Object }) options?: CKEditor5Config = {};
  @property({ type: Object, attribute: 'form-engine' }) formEngine?: FormEngineConfig = {};

  @query('textarea') target: HTMLElement;

  public constructor() {
    super();
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // const renderRoot = this.attachShadow({mode: 'open'});
    return this;
  }

  public render(): TemplateResult {
    return html`
      <textarea
        id="${this.formEngine.id}"
        name="${this.formEngine.name}"
        class="form-control"
        rows="18"
        data-formengine-validation-rules="${this.formEngine.validationRules}"
        >${this.formEngine.value}</textarea>
    `;
  }

  firstUpdated(): void
  {
    if (!(this.target instanceof HTMLElement)) {
      throw new Error('No rich-text content target found.');
    }

    const importModules = this.options.importModules || [];
    // @todo import error handling (module not found)
    const importPromises = importModules.map((name: string) => import(name));
    // when all modules have been imported
    Promise.all(importPromises)
      .then((modules: PluginModule[]) => {
        const importedPlugins = modules
          // @todo warning when no default export was used
          .filter((module) => module.default)
          .map((module) => module.default);
        // all declared plugins (builtinPlugins + importedPlugins)
        const declaredPlugins = (CKEditor5.builtinPlugins as PluginInterface<Core.Plugin>[]).concat(importedPlugins);
        // plugins that were overridden by other custom plugin implementations
        const overriddenPlugins = [].concat(...declaredPlugins
          .filter((plugin: Typo3Plugin) => plugin.overrides?.length > 0)
          .map((plugin: Typo3Plugin) => plugin.overrides));
        // plugins, without those that have been overridden
        const plugins = declaredPlugins.filter((plugin: Typo3Plugin) => !overriddenPlugins.includes(plugin));

        const toolbar = this.options.toolbar;

        const config = {
          // link.defaultProtocol: 'https://'
          // @todo use complete `config` later - currently step-by-step only
          toolbar,
          plugins,
          typo3link: this.options.typo3link || null,
          // alternative, purge from `plugins` (classes) above already (probably better)
          removePlugins: this.options.removePlugins || [],
        } as any;
        if (this.options.language) {
          config.language = this.options.language;
        }
        if (this.options.style) {
          config.style = this.options.style;
        }
        if (this.options.wordCount) {
          config.wordCount = this.options.wordCount;
        }
        if (this.options.table) {
          config.table = this.options.table;
        }
        if (this.options.heading) {
          config.heading = this.options.heading;
        }
        if (this.options.alignment) {
          config.alignment = this.options.alignment;
        }

        CKEditor5
          .create(this.target, config)
          .then((editor: CKEditor5) => {
            this.applyEditableElementStyles(editor);
            this.handleWordCountPlugin(editor);
            this.applyReadOnly(editor);
            editor.model.document.on('change:data', (): void => {
              editor.updateSourceElement();
              this.target.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
            });

            if (this.options.debug) {
              window.CKEditorInspector.attach(editor, { isCollapsed: true });
            }
          });
      });
  }

  private applyEditableElementStyles(editor: EditorWithUI): void {
    const view = editor.editing.view;
    const styles: any = {
      'min-height': this.options.height,
      'min-width': this.options.width,
    };
    Object.keys(styles).forEach((key) => {
      let assignment: any = styles[key];
      if (!assignment) {
        return;
      }
      if (isFinite(assignment) && !Number.isNaN(parseFloat(assignment))) {
        assignment += 'px';
      }
      view.change((writer) => {
        writer.setStyle(key, assignment, view.document.getRoot());
      });
    });
  }

  /**
   * see https://ckeditor.com/docs/ckeditor5/latest/features/word-count.html
   */
  private handleWordCountPlugin(editor: EditorWithUI): void {
    if (editor.plugins.has('WordCount') && (this.options?.wordCount?.displayWords || this.options?.wordCount?.displayCharacters)) {
      const wordCountPlugin = editor.plugins.get('WordCount');
      this.renderRoot.appendChild(wordCountPlugin.wordCountContainer);
    }
  }

  /**
   * see https://ckeditor.com/docs/ckeditor5/latest/features/read-only.html
   * does not work with types yet. so the editor is added with "any".
   */
  private applyReadOnly(editor: any): void {
    if (this.options.readOnly) {
      editor.enableReadOnlyMode('typo3-lock');
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-rte-ckeditor-ckeditor5': CKEditor5Element;
  }
}
