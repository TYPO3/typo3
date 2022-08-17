import {UI, Core, Engine, Typing, Link, LinkUtils, Widget, Utils} from '@typo3/ckeditor5-bundle';
import {DoubleClickObserver} from '@typo3/rte-ckeditor/observer/double-click-observer';
import {default as modalObject, ModalElement} from '@typo3/backend/modal';
import type {EditorWithUI} from '@ckeditor/ckeditor5-core/src/editor/editorwithui';

// @todo in general: implement label translation handling via `editor.t()`
// @todo functionality: icons taken from @ckeditor/ckeditor5-link/theme/icons - add rollup SVG loader
const linkIcon = '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m11.077 15 .991-1.416a.75.75 0 1 1 1.229.86l-1.148 1.64a.748.748 0 0 1-.217.206 5.251 5.251 0 0 1-8.503-5.955.741.741 0 0 1 .12-.274l1.147-1.639a.75.75 0 1 1 1.228.86L4.933 10.7l.006.003a3.75 3.75 0 0 0 6.132 4.294l.006.004zm5.494-5.335a.748.748 0 0 1-.12.274l-1.147 1.639a.75.75 0 1 1-1.228-.86l.86-1.23a3.75 3.75 0 0 0-6.144-4.301l-.86 1.229a.75.75 0 0 1-1.229-.86l1.148-1.64a.748.748 0 0 1 .217-.206 5.251 5.251 0 0 1 8.503 5.955zm-4.563-2.532a.75.75 0 0 1 .184 1.045l-3.155 4.505a.75.75 0 1 1-1.229-.86l3.155-4.506a.75.75 0 0 1 1.045-.184z"/></svg>';
const unlinkIcon = '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m11.077 15 .991-1.416a.75.75 0 1 1 1.229.86l-1.148 1.64a.748.748 0 0 1-.217.206 5.251 5.251 0 0 1-8.503-5.955.741.741 0 0 1 .12-.274l1.147-1.639a.75.75 0 1 1 1.228.86L4.933 10.7l.006.003a3.75 3.75 0 0 0 6.132 4.294l.006.004zm5.494-5.335a.748.748 0 0 1-.12.274l-1.147 1.639a.75.75 0 1 1-1.228-.86l.86-1.23a3.75 3.75 0 0 0-6.144-4.301l-.86 1.229a.75.75 0 0 1-1.229-.86l1.148-1.64a.748.748 0 0 1 .217-.206 5.251 5.251 0 0 1 8.503 5.955zm-4.563-2.532a.75.75 0 0 1 .184 1.045l-3.155 4.505a.75.75 0 1 1-1.229-.86l3.155-4.506a.75.75 0 0 1 1.045-.184zm4.919 10.562-1.414 1.414a.75.75 0 1 1-1.06-1.06l1.414-1.415-1.415-1.414a.75.75 0 0 1 1.061-1.06l1.414 1.414 1.414-1.415a.75.75 0 0 1 1.061 1.061l-1.414 1.414 1.414 1.415a.75.75 0 0 1-1.06 1.06l-1.415-1.414z"/></svg>';

export const LINK_ALLOWED_ATTRIBUTES = ['href', 'title', 'class', 'target', 'rel'];

export function addLinkPrefix(attribute: string): string {
  const capitalizedAttribute = attribute.charAt(0).toUpperCase() +  attribute.slice(1);
  return 'link' + capitalizedAttribute;
}

export interface Typo3LinkDict {
  attrs?: {
    linkTitle?: string;
    linkClass?: string;
    linkTarget?: string;
    linkRel?: string;
  };
  linkText?: string;
}

/**
 * Inspired by @ckeditor/ckeditor5-link/src/linkcommand.js
 */
export class Typo3LinkCommand extends Core.Command {
  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const selectedElement = selection.getSelectedElement() || Utils.first(selection.getSelectedBlocks());

    // A check for any integration that allows linking elements (e.g. `LinkImage`).
    // Currently the selection reads attributes from text nodes only. See #7429 and #7465.
    if (LinkUtils.isLinkableElement( selectedElement, model.schema)) {
      this.value = selectedElement.getAttribute( 'linkHref' );
      this.isEnabled = model.schema.checkAttribute(selectedElement, 'linkHref');
    } else {
      this.value = selection.getAttribute('linkHref');
      this.isEnabled = model.schema.checkAttributeInSelection(selection, 'linkHref');
    }
  }

  execute(href: string, linkAttr: Typo3LinkDict = {}): void {
    const model = this.editor.model;
    const selection = model.document.selection;

    model.change(writer => {
      // If selection is collapsed then update selected link or insert new one at the place of caret.
      if (selection.isCollapsed) {
        const position = selection.getFirstPosition();

        // When selection is inside text with `linkHref` attribute.
        if (selection.hasAttribute( 'linkHref')) {
          // Then update `linkHref` value.
          const linkRange = Typing.findAttributeRange(position, 'linkHref', selection.getAttribute('linkHref') as string, model);
          writer.setAttribute('linkHref', href, linkRange);
          // apply `linkAttr`
          for (const [attribute, value] of Object.entries(linkAttr.attrs)) {
            writer.setAttribute(attribute, value, linkRange);
          }
          // Put the selection at the end of the updated link.
          writer.setSelection(writer.createPositionAfter(linkRange.end.nodeBefore));

        } else if (href !== '') {
          // If not then insert text node with `linkHref` attribute in place of caret.
          // However, since selection is collapsed, attribute value will be used as data for text node.
          // So, if `href` is empty, do not create text node.
          const attributes = Utils.toMap(selection.getAttributes() as any);
          attributes.set('linkHref', href);
          // apply `linkAttr`
          for (const [attribute, value] of Object.entries(linkAttr.attrs)) {
            attributes.set(attribute, value);
          }
          const { end: positionAfter } = model.insertContent(writer.createText(href, attributes as any), position);
          // Put the selection at the end of the inserted link.
          // Using end of range returned from insertContent in case nodes with the same attributes got merged.
          writer.setSelection(positionAfter);
        }
        // Remove the `linkHref` attribute and all link decorators from the selection.
        // It stops adding a new content into the link element.
        writer.removeSelectionAttribute('linkHref');
      } else {
        // If selection has non-collapsed ranges, we change attribute on nodes inside those ranges
        // omitting nodes where the `linkHref` attribute is disallowed.
        const ranges = model.schema.getValidRanges((selection.getRanges() as unknown) as any[], 'linkHref');

        // But for the first, check whether the `linkHref` attribute is allowed on selected blocks (e.g. the "image" element).
        const allowedRanges = [];

        for (const element of selection.getSelectedBlocks()) {
          if ( model.schema.checkAttribute( element, 'linkHref')) {
            allowedRanges.push( writer.createRangeOn(element));
          }
        }

        // Ranges that accept the `linkHref` attribute. Since we will iterate over `allowedRanges`, let's clone it.
        const rangesToUpdate = allowedRanges.slice();

        // For all selection ranges we want to check whether given range is inside an element that accepts the `linkHref` attribute.
        // If so, we don't want to propagate applying the attribute to its children.
        for (const range of ranges) {
          if (this.isRangeToUpdate(range, allowedRanges)) {
            rangesToUpdate.push(range);
          }
        }
        for (const range of rangesToUpdate) {
          writer.setAttribute( 'linkHref', href, range);
        }
      }
    });
  }

  private isRangeToUpdate(range: Engine.Range, allowedRanges: Engine.Range[]) {
    for (const allowedRange of allowedRanges) {
      // A range is inside an element that will have the `linkHref` attribute. Do not modify its nodes.
      if (allowedRange.containsRange(range)) {
        return false;
      }
    }
    return true;
  }
}

/**
 * Inspired by @ckeditor/ckeditor5-link/src/unlinkcommand.js
 */
export class Typo3UnlinkCommand extends Core.Command {
  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const selectedElement = selection.getSelectedElement();

    if (LinkUtils.isLinkableElement(selectedElement, model.schema)) {
      this.value = selectedElement.getAttribute( 'linkHref' );
      this.isEnabled = model.schema.checkAttribute(selectedElement, 'linkHref');
    } else {
      this.value = selection.getAttribute('linkHref');
      this.isEnabled = model.schema.checkAttributeInSelection(selection, 'linkHref');
    }
  }

  execute(): void {
    const model = this.editor.model;
    const selection = model.document.selection;

    model.change( writer => {
      // Get ranges to unlink.
      const rangesToUnlink = selection.isCollapsed
        ? [Typing.findAttributeRange(
          selection.getFirstPosition(),
          'linkHref',
          selection.getAttribute('linkHref') as string,
          model
        )]
        : model.schema.getValidRanges((selection.getRanges() as unknown) as any[], 'linkHref');
      // Remove `linkHref` attribute from specified ranges.
      for (const range of rangesToUnlink) {
        writer.removeAttribute('linkHref', range);
        writer.removeAttribute('linkTarget', range);
        writer.removeAttribute('linkClass', range);
        writer.removeAttribute('linkTitle', range);
        writer.removeAttribute('linkRel', range);
      }
    });
  }
}

export class Typo3LinkEditing extends Core.Plugin {
  static readonly pluginName = 'Typo3LinkEditing';

  init(): void {
    const editor = this.editor as EditorWithUI;
    (window as any).editor = editor;

    // @todo for whatever reason, `a.target` is not persisted
    // @todo YAML additionalAttributes is not implemented yet
    editor.model.schema.extend('$text', { allowAttributes: ['linkTitle', 'linkClass', 'linkTarget', 'linkRel'] });

    // linkTitle <=> title
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkTitle',
      view: (value, {writer}) => {
        const linkElement = writer.createAttributeElement( 'a', { title: value }, { priority: 5 } );
        writer.setCustomProperty( 'linkTitle', true, linkElement );
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { title: true }},
      model: { key: 'linkTitle', value: (viewElement) => viewElement.getAttribute('title') }
    });
    // linkClass <=> class
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkClass',
      view: (value: string, {writer}) => {
        const linkElement = writer.createAttributeElement( 'a', { class: value }, { priority: 5 } );
        writer.setCustomProperty( 'linkClass', true, linkElement );
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { title: true }},
      model: { key: 'linkClass', value: (viewElement) => viewElement.getAttribute('class') }
    });
    // linkTarget <=> target
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkTarget',
      view: (value, {writer}) => {
        const linkElement = writer.createAttributeElement( 'a', { target: value }, { priority: 5 } );
        writer.setCustomProperty( 'linkTarget', true, linkElement );
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { title: true }},
      model: { key: 'linkTarget', value: (viewElement) => viewElement.getAttribute('target') }
    });
    // linkRel <=> rel
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkRel',
      view: (value, {writer}) => {
        const linkElement = writer.createAttributeElement( 'a', { rel: value }, { priority: 5 } );
        writer.setCustomProperty( 'linkRel', true, linkElement );
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { title: true }},
      model: { key: 'linkRel', value: (viewElement) => viewElement.getAttribute('rel') }
    });

    // overrides 'link' command, 'unlink' command is taken from CKEditor5's `LinkEditing`
    editor.commands.add('link', new Typo3LinkCommand(editor));
    editor.commands.add('unlink', new Typo3UnlinkCommand(editor));
  }
}

export class Typo3LinkUI extends Core.Plugin {
  static readonly pluginName = 'Typo3LinkUI';

  init() {
    const editor = this.editor as EditorWithUI;
    editor.editing.view.addObserver(DoubleClickObserver);
    editor.editing.view.addObserver(Engine.ClickObserver);

    this.createToolbarLinkButtons();
  }

  private createToolbarLinkButtons() {
    const editor = this.editor as EditorWithUI;
    const linkCommand = editor.commands.get('link') as Typo3LinkCommand;
    const unlinkCommand = editor.commands.get('unlink') as Typo3UnlinkCommand;
    const t = editor.t;

    // Handle the `Ctrl+K` keystroke and show the panel.
    editor.keystrokes.set(LinkUtils.LINK_KEYSTROKE, (keyEvtData, cancel) => {
      // Prevent focusing the search bar in FF, Chrome and Edge. See https://github.com/ckeditor/ckeditor5/issues/4811.
      cancel();
      if (linkCommand.isEnabled) {
        this.showUI() ;
      }
    });

    // re-uses 'Link' plugin name -> original plugin 'Link' needs to be removed during runtime
    editor.ui.componentFactory.add('Typo3Link', locale => {
      const linkButton = new UI.ButtonView(locale);
      linkButton.isEnabled = true;
      linkButton.label = t('Link');
      linkButton.icon = linkIcon;
      linkButton.keystroke = LinkUtils.LINK_KEYSTROKE;
      linkButton.tooltip = true;
      linkButton.isToggleable = true;
      linkButton.bind('isEnabled').to(linkCommand, 'isEnabled');
      linkButton.bind('isOn').to(linkCommand, 'value', value => !!value);
      this.listenTo(linkButton, 'execute', () => this.showUI());
      return linkButton;
    });
    editor.ui.componentFactory.add('Typo3Unlink', locale => {
      const unlinkButton = new UI.ButtonView(locale);
      unlinkButton.isEnabled = true;
      unlinkButton.label = t( 'Unlink');
      unlinkButton.icon = unlinkIcon;
      unlinkButton.tooltip = true;
      unlinkButton.isToggleable = true;
      unlinkButton.bind('isEnabled').to(unlinkCommand, 'isEnabled');
      unlinkButton.bind('isOn').to(unlinkCommand, 'value', value => !!value);
      this.listenTo(unlinkButton, 'execute', () => unlinkCommand.execute());
      return unlinkButton;
    });
  }

  private showUI() {
    const element = this.getSelectedLinkElement();
    this.openLinkBrowser(this.editor as EditorWithUI, element);
  }

  private getSelectedLinkElement() {
    const view = this.editor.editing.view;
    const selection = view.document.selection;
    const selectedElement = selection.getSelectedElement();

    // The selection is collapsed or some widget is selected (especially inline widget).
    if (selection.isCollapsed || selectedElement && Widget.isWidget(selectedElement)) {
      return this.findLinkElementAncestor(selection.getFirstPosition() );
    } else {
      // The range for fully selected link is usually anchored in adjacent text nodes.
      // Trim it to get closer to the actual link element.
      const range = selection.getFirstRange().getTrimmed();
      const startLink = this.findLinkElementAncestor(range.start );
      const endLink = this.findLinkElementAncestor(range.end );

      if (!startLink || startLink != endLink ) {
        return null;
      }

      // Check if the link element is fully selected.
      if (view.createRangeIn(startLink ).getTrimmed().isEqual(range )) {
        return startLink;
      } else {
        return null;
      }
    }
  }

  private findLinkElementAncestor(position: any) {
    return position.getAncestors().find((ancestor: any) => LinkUtils.isLinkElement(ancestor) );
  }

  private openLinkBrowser(editor: EditorWithUI, element: any): void {
    // @todo copied from existing code... improve it
    if (!element) {
      // return;
    }
    let additionalParameters = '';
    if (element) {
      additionalParameters += '&P[curUrl][url]=' + encodeURIComponent(element.getAttribute('href'));
      ['target', 'class', 'title', 'rel'].forEach((attrName) => {
        const attrValue = element.getAttribute(attrName);
        if (attrValue) {
          additionalParameters += '&P[curUrl][' + attrName + ']=' +  encodeURIComponent(attrValue);
        }
      });
    }
    this.openElementBrowser(
      editor,
      'Link',
      this.makeUrlFromModulePath(
        editor,
        editor.config.get('typo3link')?.routeUrl,
        additionalParameters
      ));
  }

  private makeUrlFromModulePath(editor: EditorWithUI, routeUrl: string, parameters: string) {
    return routeUrl
      + (routeUrl.indexOf('?') === -1 ? '?' : '&')
      + '&contentsLanguage=' + 'en'// editor.config.contentsLanguage
      + '&editorId=' + '123' // editor.id
      + (parameters ? parameters : '');
  }

  private openElementBrowser(editor: EditorWithUI, title: string, url: string) {
    modalObject.advanced({
      type: modalObject.types.iframe,
      title: title,
      content: url,
      size: modalObject.sizes.large,
      callback: (currentModal: ModalElement) => {
        // Add the instance to the iframe itself
        currentModal.userData.ckeditor = editor;

        // @todo: is this used at all?
        // should maybe be a regular modal attribute then
        currentModal.querySelector('.t3js-modal-body')?.setAttribute('id', '123' /*editor.id*/);
      }
    });
  }
}

export default class Typo3Link extends Core.Plugin {
  static readonly pluginName = 'Typo3Link';
  static readonly requires = [Link.LinkEditing, Link.AutoLink, Typo3LinkEditing, Typo3LinkUI];
  static readonly overrides?: Array<typeof Core.Plugin> = [Link.Link];
}
