import { html, LitElement, TemplateResult } from 'lit';
import { customElement, property, query } from 'lit/decorators';
import { CKEditor5, Core, WordCount } from '@typo3/ckeditor5-bundle';
import { SourceEditing } from '@ckeditor/ckeditor5-source-editing';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { Editor, PluginConstructor } from '@ckeditor/ckeditor5-core';
import { prefixAndRebaseCss } from '@typo3/rte-ckeditor/css-prefixer';

interface CKEditor5Config {
  // in TYPO3 always `items` property is used, skipping `string[]`
  toolbar?: { items: string[], shouldNotGroupWhenFull?: boolean };
  extraPlugins?: string[];
  removePlugins?: string[];
  importModules?: string[];
  contentsCss?: string[];
  style?: any;
  heading?: any;
  alignment?: any;
  width?: any;
  height?: any;
  readOnly?: boolean;
  language?: any;
  table?: any;
  ui?: any;
  htmlSupport?: any;

  wordCount?: any;
  typo3link?: any;
  debug?: boolean;
}

interface PluginModule {
  default?: PluginConstructor<Editor>;
}

interface FormEngineConfig {
  id?: string;
  name?: string;
  value?: string;
  validationRules?: any;
}

type Typo3Plugin = PluginConstructor<Editor> & {overrides?: PluginConstructor<Editor>[]};

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

  private readonly styleSheets: Map<CSSStyleSheet, true> = new Map();

  public connectedCallback(): void {
    super.connectedCallback();
    if (Array.isArray(this.options.contentsCss)) {
      for (const url of this.options.contentsCss) {
        this.prefixAndLoadContentsCss(url, this.getAttribute('id'));
      }
    }
  }

  public disconnectedCallback() {
    super.disconnectedCallback();
    document.adoptedStyleSheets = document.adoptedStyleSheets.filter(styleSheet => !this.styleSheets.has(styleSheet));
    this.styleSheets.clear();
  }

  protected firstUpdated(): void {
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
        const declaredPlugins = CKEditor5.builtinPlugins.concat(importedPlugins);
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
        if (this.options.ui) {
          config.ui = this.options.ui;
        }
        if (this.options.htmlSupport) {
          config.htmlSupport = this.options.htmlSupport;
        }

        CKEditor5
          .create(this.target, config)
          .then((editor: CKEditor5) => {
            this.applyEditableElementStyles(editor);
            this.handleWordCountPlugin(editor);
            this.applyReadOnly(editor);
            const sourceEditingPlugin = editor.plugins.get('SourceEditing') as SourceEditing;
            editor.model.document.on('change:data', (): void => {
              if(!sourceEditingPlugin.isSourceEditingMode) {
                editor.updateSourceElement()
              }
              this.target.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
            });

            if (this.options.debug) {
              import('@typo3/ckeditor5-inspector').then(({ CKEditorInspector }) => CKEditorInspector.attach(editor, { isCollapsed: true }));
            }
          });
      });
  }

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    // const renderRoot = this.attachShadow({mode: 'open'});
    return this;
  }

  protected render(): TemplateResult {
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

  private async prefixAndLoadContentsCss(url: string, fieldId: string): Promise<void> {
    let content: string;
    try {
      const response = await new AjaxRequest(url).get();
      content = await response.resolve();
    } catch {
      return;
    }
    // Prefix custom stylesheets with id of the container element and a required `.ck-content` selector
    // see https://ckeditor.com/docs/ckeditor5/latest/installation/advanced/content-styles.html
    const newParent = `#${fieldId} .ck-content`;
    const prefixedCss = prefixAndRebaseCss(content, url, newParent);

    const styleSheet = new CSSStyleSheet();
    await styleSheet.replace(
      prefixedCss
    );
    this.styleSheets.set(styleSheet, true);
    document.adoptedStyleSheets = [...document.adoptedStyleSheets, styleSheet];
  }

  private applyEditableElementStyles(editor: Core.Editor): void {
    const view = editor.editing.view;
    const styles: Record<string, any> = {
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
  private handleWordCountPlugin(editor: Core.Editor): void {
    if (editor.plugins.has('WordCount') && (this.options?.wordCount?.displayWords || this.options?.wordCount?.displayCharacters)) {
      const wordCountPlugin = editor.plugins.get('WordCount') as WordCount;
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
