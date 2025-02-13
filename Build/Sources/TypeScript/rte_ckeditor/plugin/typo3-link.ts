import * as UI from '@ckeditor/ckeditor5-ui';
import * as Core from '@ckeditor/ckeditor5-core';
import * as Engine from '@ckeditor/ckeditor5-engine';
import * as Typing from '@ckeditor/ckeditor5-typing'
import * as Widget from '@ckeditor/ckeditor5-widget';
import * as Utils from '@ckeditor/ckeditor5-utils';
import * as Link from '@ckeditor/ckeditor5-link';
import { LinkUtils, LinkActionsView } from '@ckeditor/ckeditor5-link';
import { default as modalObject, ModalElement } from '@typo3/backend/modal';
import type { ViewAttributeElement, ViewElement, Schema, Writer } from '@ckeditor/ckeditor5-engine';
import type { GeneralHtmlSupport, DataFilter } from '@ckeditor/ckeditor5-html-support';
import type { GHSViewAttributes } from '@ckeditor/ckeditor5-html-support/src/utils';

const linkIcon = '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m11.077 15 .991-1.416a.75.75 0 1 1 1.229.86l-1.148 1.64a.748.748 0 0 1-.217.206 5.251 5.251 0 0 1-8.503-5.955.741.741 0 0 1 .12-.274l1.147-1.639a.75.75 0 1 1 1.228.86L4.933 10.7l.006.003a3.75 3.75 0 0 0 6.132 4.294l.006.004zm5.494-5.335a.748.748 0 0 1-.12.274l-1.147 1.639a.75.75 0 1 1-1.228-.86l.86-1.23a3.75 3.75 0 0 0-6.144-4.301l-.86 1.229a.75.75 0 0 1-1.229-.86l1.148-1.64a.748.748 0 0 1 .217-.206 5.251 5.251 0 0 1 8.503 5.955zm-4.563-2.532a.75.75 0 0 1 .184 1.045l-3.155 4.505a.75.75 0 1 1-1.229-.86l3.155-4.506a.75.75 0 0 1 1.045-.184z"/></svg>';

export const LINK_ALLOWED_ATTRIBUTES = ['href', 'title', 'class', 'target', 'rel'];

export function addLinkPrefix(attribute: string): string {
  const capitalizedAttribute = attribute.charAt(0).toUpperCase() + attribute.slice(1);
  return 'link' + capitalizedAttribute;
}

export function removeLinkPrefix(attribute: string): string {
  if (attribute.startsWith('link') && attribute.length >= 5) {
    return attribute.charAt(4).toLowerCase() + attribute.slice(5);
  }
  return attribute;
}

export interface Typo3LinkConfig {
  routeUrl: string;
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

export class Typo3TextView extends UI.View {
  declare public text: string | undefined;
  constructor(locale?: Utils.Locale) {
    super(locale);
    this.set('text', undefined);
    const bind = this.bindTemplate;
    this.setTemplate({
      tag: 'span',
      attributes: {
        class: ['ck', 'ck-linktext'],
        title: bind.to('text'),
      },
      children: [{ text: bind.to('text') }]
    });
  }
}

/**
 * Inspired by @ckeditor/ckeditor5-link/src/linkcommand.js
 */
export class Typo3LinkCommand extends Core.Command {
  public override value: string | undefined;
  public attrs: Record<string, string> = {};

  public override refresh(): void {
    const model = this.editor.model;
    const selection = model.document.selection;
    const selectedElement = selection.getSelectedElement() || Utils.first(selection.getSelectedBlocks());

    // A check for any integration that allows linking elements (e.g. `LinkImage`).
    // Currently the selection reads attributes from text nodes only. See #7429 and #7465.
    const sourceSelection = LinkUtils.isLinkableElement(selectedElement, model.schema) ? selectedElement : selection
    if (sourceSelection === selectedElement) {
      this.value = selectedElement.getAttribute('linkHref') as string;
      this.isEnabled = model.schema.checkAttribute(selectedElement, 'linkHref');
    } else {
      this.value = selection.getAttribute('linkHref') as string;
      this.isEnabled = model.schema.checkAttributeInSelection(selection, 'linkHref');
    }

    const htmlSupport: GeneralHtmlSupport = this.editor.plugins.get('GeneralHtmlSupport');
    const ghsAttributeName = htmlSupport.getGhsAttributeNameForElement('a');
    const attrs: Record<string, string> = {};
    for (const attribute of this.getLinkAttributesAllowedOnText(model.schema)) {
      if (attribute === 'linkHref') {
        continue;
      }

      if (attribute === ghsAttributeName) {
        const value: GHSViewAttributes = sourceSelection.getAttribute(attribute);
        if (value?.classes && value.classes.length !== 0) {
          attrs.class = value.classes.join(' ');
        }
      } else {
        const value = sourceSelection.getAttribute(attribute) as string | undefined;
        if (value !== undefined) {
          attrs[removeLinkPrefix(attribute)] = value;
        }
      }
    }
    this.attrs = attrs;
  }

  public override execute(href: string, linkAttr: Typo3LinkDict = {}): void {
    const model = this.editor.model;
    const selection = model.document.selection;

    model.change(writer => {
      // If selection is collapsed then update selected link or insert new one at the place of caret.
      if (selection.isCollapsed) {
        const position = selection.getFirstPosition();

        // When selection is inside text with `linkHref` attribute.
        if (selection.hasAttribute('linkHref')) {
          // Then update `linkHref` value.
          const linkRange = Typing.findAttributeRange(position, 'linkHref', selection.getAttribute('linkHref') as string, model);
          writer.setAttribute('linkHref', href, linkRange);
          for (const [attribute, value] of Object.entries(this.composeLinkAttributes(linkAttr))) {
            if (value !== null) {
              writer.setAttribute(attribute, value, linkRange);
            } else {
              writer.removeAttribute(attribute, linkRange);
            }
          }
          // Put the selection at the end of the updated link.
          writer.setSelection(writer.createPositionAfter(linkRange.end.nodeBefore));

        } else if (href !== '') {
          // If not then insert text node with `linkHref` attribute in place of caret.
          // However, since selection is collapsed, attribute value will be used as data for text node.
          // So, if `href` is empty, do not create text node.
          const attributes = Utils.toMap(selection.getAttributes() as any);
          attributes.set('linkHref', href);
          for (const [attribute, value] of Object.entries(this.composeLinkAttributes(linkAttr))) {
            if (value !== null) {
              attributes.set(attribute, value);
            }
          }
          const { end: positionAfter } = model.insertContent(writer.createText(href, attributes as any), position);
          // Put the selection at the end of the inserted link.
          // Using end of range returned from insertContent in case nodes with the same attributes got merged.
          writer.setSelection(positionAfter);
        }
        // Remove the `linkHref` attribute and all link decorators from the selection.
        // It stops adding a new content into the link element.
        this.removeLinkAttributesFromSelection(writer, this.getLinkAttributesAllowedOnText(model.schema));
      } else {
        // If selection has non-collapsed ranges, we change attribute on nodes inside those ranges
        // omitting nodes where the `linkHref` attribute is disallowed.
        const ranges = model.schema.getValidRanges((selection.getRanges() as unknown) as any[], 'linkHref');

        // But for the first, check whether the `linkHref` attribute is allowed on selected blocks (e.g. the "image" element).
        const allowedRanges = [];

        for (const element of selection.getSelectedBlocks()) {
          if (model.schema.checkAttribute(element, 'linkHref')) {
            allowedRanges.push(writer.createRangeOn(element));
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
          writer.setAttribute('linkHref', href, range);
          for (const [attribute, value] of Object.entries(this.composeLinkAttributes(linkAttr))) {
            if (value !== null) {
              writer.setAttribute(attribute, value, range);
            } else {
              writer.removeAttribute(attribute, range);
            }
          }
        }
      }
    });
  }

  private getLinkAttributesAllowedOnText(schema: Schema): Array<string> {
    const textAttributes = schema.getDefinition('$text').allowAttributes;
    return textAttributes.filter(attribute => attribute.startsWith('link') || attribute === 'htmlA');
  }

  private removeLinkAttributesFromSelection(writer: Writer, linkAttributes: Array<string>): void {
    writer.removeSelectionAttribute('linkHref');

    for (const attribute of linkAttributes) {
      writer.removeSelectionAttribute(attribute);
    }
  }

  private composeLinkAttributes(linkAttr: Typo3LinkDict): Record<string, GHSViewAttributes|string|null> {
    const attrs: Record<string, GHSViewAttributes|string> = {};
    for (const [attribute, value] of Object.entries(linkAttr.attrs)) {
      if (attribute === 'linkClass') {
        const htmlSupport: GeneralHtmlSupport = this.editor.plugins.get('GeneralHtmlSupport');
        const ghsAttributeName = htmlSupport.getGhsAttributeNameForElement('a');
        const selection = this.editor.model.document.selection;
        let htmlA: GHSViewAttributes;
        if (selection.hasAttribute(ghsAttributeName)) {
          htmlA = { ...(selection.getAttribute(ghsAttributeName) as GHSViewAttributes) };
        } else {
          htmlA = {};
        }
        const classes = value.replace(/\s+/g, ' ').trim();
        if (classes !== '') {
          htmlA.classes = classes.split(' ');
        } else if ('classes' in htmlA) {
          delete htmlA.classes;
        }
        attrs[ghsAttributeName] = Object.keys(htmlA).length !== 0 ? htmlA : null;
      } else {
        attrs[attribute] = value !== '' ? value : null;
      }
    }
    return attrs;
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
  public override refresh(): void {
    const model = this.editor.model;
    const selection = model.document.selection;
    const selectedElement = selection.getSelectedElement();

    if (LinkUtils.isLinkableElement(selectedElement, model.schema)) {
      this.isEnabled = model.schema.checkAttribute(selectedElement, 'linkHref');
    } else {
      this.isEnabled = model.schema.checkAttributeInSelection(selection, 'linkHref');
    }
  }

  public override execute(): void {
    const model = this.editor.model;
    const selection = model.document.selection;

    model.change(writer => {
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
        writer.removeAttribute('linkTitle', range);
        writer.removeAttribute('linkRel', range);
      }
    });
  }
}

export class Typo3LinkEditing extends Core.Plugin {
  static readonly pluginName = 'Typo3LinkEditing';

  init(): void {
    const editor = this.editor;
    // @todo: Why is this needed? Remove.
    (window as any).editor = editor;

    editor.model.schema.extend('$text', { allowAttributes: ['linkTitle', 'linkTarget', 'linkRel', 'linkDataRteError'] });

    const ghsDataFilter: DataFilter = editor.plugins.get('DataFilter');
    ghsDataFilter.loadAllowedConfig([{ name: 'a', classes: true }]);

    // linkDataRteError <=> data-rte-error
    // This is used for marking broken links (e.g. by linkvalidator) when editing in RTE.
    // Broken links are styled differently. This will not get persisted to the database.
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkDataRteError',
      view: (value: string|null, { writer }) => {
        const linkElement = writer.createAttributeElement('a', { 'data-rte-error': value }, { priority: 5 });
        writer.setCustomProperty('linkDataRteError', true, linkElement);
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { 'data-rte-error': true } },
      model: { key: 'linkDataRteError', value: (viewElement: ViewElement) => viewElement.getAttribute('data-rte-error') }
    });

    // linkTitle <=> title
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkTitle',
      view: (value: string|null, { writer }) => {
        const linkElement = writer.createAttributeElement('a', { title: value }, { priority: 5 });
        writer.setCustomProperty('linkTitle', true, linkElement);
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { title: true } },
      model: { key: 'linkTitle', value: (viewElement: ViewElement) => viewElement.getAttribute('title') }
    });
    // linkTarget <=> target
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkTarget',
      view: (value, { writer }) => {
        const linkElement = writer.createAttributeElement('a', { target: value }, { priority: 5 });
        writer.setCustomProperty('linkTarget', true, linkElement);
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { target: true } },
      model: { key: 'linkTarget', value: (viewElement: ViewElement) => viewElement.getAttribute('target') }
    });
    // linkRel <=> rel
    editor.conversion.for('downcast').attributeToElement({
      model: 'linkRel',
      view: (value, { writer }) => {
        const linkElement = writer.createAttributeElement('a', { rel: value }, { priority: 5 });
        writer.setCustomProperty('linkRel', true, linkElement);
        return linkElement;
      }
    });
    editor.conversion.for('upcast').elementToAttribute({
      view: { name: 'a', attributes: { rel: true } },
      model: { key: 'linkRel', value: (viewElement: ViewElement) => viewElement.getAttribute('rel') }
    });

    // overrides 'link' command, 'unlink' command is taken from CKEditor5's `LinkEditing`
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    editor.commands.add('link', new Typo3LinkCommand(editor));
    editor.commands.add('unlink', new Typo3UnlinkCommand(editor));
  }
}

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
export class Typo3LinkActionsView extends LinkActionsView {
  override _createPreviewButton(): Typo3TextView {
    const textView = new Typo3TextView(this.locale);
    const t = this.t;

    textView.bind('text').to(this, 'href', href => {
      return href || t('This link has no URL');
    });

    return textView;
  }
}

const VISUAL_SELECTION_MARKER_NAME = 'link-ui';

export class Typo3LinkUI extends Core.Plugin {
  static readonly pluginName = 'Typo3LinkUI';
  static readonly requires = [UI.ContextualBalloon];

  balloon: UI.ContextualBalloon;
  actionsView: Typo3LinkActionsView;

  init() {
    const editor = this.editor;
    editor.editing.view.addObserver(Engine.ClickObserver);

    this.actionsView = this.createActionsView();
    this.balloon = editor.plugins.get(UI.ContextualBalloon);

    this.createToolbarLinkButtons();
    this.enableUserBalloonInteractions();

    // Renders a fake visual selection marker on an expanded selection.
    editor.conversion.for('editingDowncast').markerToHighlight({
      model: VISUAL_SELECTION_MARKER_NAME,
      view: {
        classes: ['ck-fake-link-selection']
      }
    });

    // Renders a fake visual selection marker on a collapsed selection.
    editor.conversion.for('editingDowncast').markerToElement({
      model: VISUAL_SELECTION_MARKER_NAME,
      view: {
        name: 'span',
        classes: ['ck-fake-link-selection', 'ck-fake-link-selection_collapsed']
      }
    });
  }

  private createActionsView(): Typo3LinkActionsView {
    const editor = this.editor;
    const actionsView = new Typo3LinkActionsView(editor.locale);
    const linkCommand = editor.commands.get('link');
    const unlinkCommand = editor.commands.get('unlink');

    actionsView.bind('href').to(linkCommand, 'value');
    actionsView.editButtonView.bind('isEnabled').to(linkCommand);
    actionsView.unlinkButtonView.bind('isEnabled').to(unlinkCommand);

    // Open LinkBrowser after clicking on the "Edit" button.
    this.listenTo(actionsView, 'edit', () => {
      this.openLinkBrowser(editor);
    });

    // Execute unlink command after clicking on the "Unlink" button.
    this.listenTo(actionsView, 'unlink', () => {
      editor.execute('unlink');
      this.hideUI();
    });

    // Close the panel on esc key press when the **actions have focus**.
    actionsView.keystrokes.set('Esc', (data, cancel) => {
      this.hideUI();
      cancel();
    });

    return actionsView;
  }

  private createToolbarLinkButtons() {
    const editor = this.editor;
    const linkCommand = editor.commands.get('link');
    const t = editor.t;

    // Handle the `Ctrl+K` keystroke and show the panel.
    editor.keystrokes.set(LinkUtils.LINK_KEYSTROKE, (keyEvtData, cancel) => {
      // Prevent focusing the search bar in FF, Chrome and Edge. See https://github.com/ckeditor/ckeditor5/issues/4811.
      cancel();
      if (linkCommand.isEnabled) {
        this.showUI();
      }
    });

    // re-uses 'Link' plugin name -> original plugin 'Link' needs to be removed during runtime
    editor.ui.componentFactory.add('link', locale => {
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
  }

  private enableUserBalloonInteractions() {
    const viewDocument = this.editor.editing.view.document;

    this.listenTo(viewDocument, 'click', () => {
      const parentLink = this.getSelectedLinkElement();
      if (parentLink) {
        this.showUI();
      }
    });

    this.editor.keystrokes.set('Esc', (data, cancel) => {
      if (this.isUIVisible()) {
        this.hideUI();
        cancel();
      }
    });
  }

  private addActionsView(): void {
    if (this.areActionsInPanel()) {
      return;
    }

    this.balloon.add({
      view: this.actionsView,
      position: this.getBalloonPositionData()
    });
  }

  private hideUI(): void {
    if (!this.isUIInPanel()) {
      return;
    }

    const editor = this.editor;
    this.stopListening(editor.ui, 'update');
    this.stopListening(this.balloon, 'change:visibleView');
    editor.editing.view.focus();
    this.balloon.remove(this.actionsView);
    this.hideFakeVisualSelection();
  }

  private showUI(): void {
    if (!this.getSelectedLinkElement()) {
      this.showFakeVisualSelection();
      this.openLinkBrowser(this.editor);
    } else {
      this.addActionsView();
      this.balloon.showStack('main');
    }

    this.startUpdatingUI();
  }

  private startUpdatingUI(): void {
    const editor = this.editor;
    const viewDocument = editor.editing.view.document;

    let prevSelectedLink = this.getSelectedLinkElement();
    let prevSelectionParent = getSelectionParent();

    const update = () => {
      const selectedLink = this.getSelectedLinkElement();
      const selectionParent = getSelectionParent();

      if ((prevSelectedLink && !selectedLink) ||
        (!prevSelectedLink && selectionParent !== prevSelectionParent)) {
        this.hideUI();
      }
      else if (this.isUIVisible()) {
        this.balloon.updatePosition(this.getBalloonPositionData());
      }

      prevSelectedLink = selectedLink;
      prevSelectionParent = selectionParent;
    };

    function getSelectionParent() {
      return viewDocument.selection.focus.getAncestors()
        .reverse()
        .find(node => node.is('element'));
    }

    this.listenTo(editor.ui, 'update', update);
    this.listenTo(this.balloon, 'change:visibleView', update);
  }

  private areActionsInPanel(): boolean {
    return this.balloon.hasView(this.actionsView);
  }

  private areActionsVisible(): boolean {
    return this.balloon.visibleView === this.actionsView;
  }

  private isUIInPanel(): boolean {
    return this.areActionsInPanel();
  }

  private isUIVisible(): boolean {
    return this.areActionsVisible();
  }

  private getBalloonPositionData(): any {
    const view = this.editor.editing.view;
    const model = this.editor.model;
    const viewDocument = view.document;
    let target = null;

    if (model.markers.has(VISUAL_SELECTION_MARKER_NAME)) {
      // There are cases when we highlight selection using a marker (#7705, #4721).
      const markerViewElements = Array.from(this.editor.editing.mapper.markerNameToElements(VISUAL_SELECTION_MARKER_NAME));
      const newRange = view.createRange(
        view.createPositionBefore(markerViewElements[0]),
        view.createPositionAfter(markerViewElements[markerViewElements.length - 1])
      );

      target = view.domConverter.viewRangeToDom(newRange);
    } else {
      // Make sure the target is calculated on demand at the last moment because a cached DOM range
      // (which is very fragile) can desynchronize with the state of the editing view if there was
      // any rendering done in the meantime. This can happen, for instance, when an inline widget
      // gets unlinked.
      target = () => {
        const targetLink = this.getSelectedLinkElement();

        return targetLink ?
          // When selection is inside link element, then attach panel to this element.
          view.domConverter.mapViewToDom(targetLink) :
          // Otherwise attach panel to the selection.
          view.domConverter.viewRangeToDom(viewDocument.selection.getFirstRange());
      };
    }

    return { target };
  }

  private getSelectedLinkElement(): ViewAttributeElement | null {
    const view = this.editor.editing.view;
    const selection = view.document.selection;
    const selectedElement = selection.getSelectedElement();

    // The selection is collapsed or some widget is selected (especially inline widget).
    if (selection.isCollapsed || selectedElement && Widget.isWidget(selectedElement)) {
      return this.findLinkElementAncestor(selection.getFirstPosition());
    } else {
      // The range for fully selected link is usually anchored in adjacent text nodes.
      // Trim it to get closer to the actual link element.
      const range = selection.getFirstRange().getTrimmed();
      const startLink = this.findLinkElementAncestor(range.start);
      const endLink = this.findLinkElementAncestor(range.end);

      if (!startLink || startLink != endLink) {
        return null;
      }

      // Check if the link element is fully selected.
      if (view.createRangeIn(startLink).getTrimmed().isEqual(range)) {
        return startLink;
      } else {
        return null;
      }
    }
  }

  private showFakeVisualSelection(): void {
    const model = this.editor.model;

    model.change(writer => {
      const range = model.document.selection.getFirstRange();

      if (model.markers.has(VISUAL_SELECTION_MARKER_NAME)) {
        writer.updateMarker(VISUAL_SELECTION_MARKER_NAME, { range });
      } else {
        if (range.start.isAtEnd) {
          const startPosition = range.start.getLastMatchingPosition(
            ({ item }) => !model.schema.isContent(item),
            {
              startPosition: null,
              boundaries: range
            }
          );

          writer.addMarker(VISUAL_SELECTION_MARKER_NAME, {
            usingOperation: false,
            affectsData: false,
            range: writer.createRange(startPosition, range.end)
          });
        } else {
          writer.addMarker(VISUAL_SELECTION_MARKER_NAME, {
            usingOperation: false,
            affectsData: false,
            range
          });
        }
      }
    });
  }

  private hideFakeVisualSelection() {
    const model = this.editor.model;
    if (model.markers.has(VISUAL_SELECTION_MARKER_NAME)) {
      model.change(writer => {
        writer.removeMarker(VISUAL_SELECTION_MARKER_NAME);
      });
    }
  }

  private findLinkElementAncestor(position: any) {
    return position.getAncestors().find((ancestor: any) => LinkUtils.isLinkElement(ancestor));
  }

  private openLinkBrowser(editor: Core.Editor): void {
    const linkCommand = editor.commands.get('link') as unknown as Typo3LinkCommand;
    let additionalParameters = '';

    if (linkCommand.value) {
      additionalParameters += '&P[curUrl][url]=' + encodeURIComponent(linkCommand.value);
      for (const [attr, value] of Object.entries(linkCommand.attrs)) {
        additionalParameters += '&P[curUrl][' + encodeURIComponent(attr) + ']=' + encodeURIComponent(value);
      }
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

  private makeUrlFromModulePath(editor: Core.Editor, routeUrl: string, parameters: string) {
    return routeUrl
      + (routeUrl.indexOf('?') === -1 ? '?' : '&')
      + '&contentsLanguage=' + 'en'// editor.config.contentsLanguage
      + '&editorId=' + '123' // editor.id
      + (parameters ? parameters : '');
  }

  private openElementBrowser(editor: Core.Editor, title: string, url: string) {
    modalObject.advanced({
      type: modalObject.types.iframe,
      title: title,
      content: url,
      size: modalObject.sizes.large,
      callback: (currentModal: ModalElement) => {
        // Add the instance to the iframe itself
        currentModal.userData.editor = editor;
        currentModal.userData.selectionStartPosition = editor.model.document.selection.getFirstPosition();
        currentModal.userData.selectionEndPosition = editor.model.document.selection.getLastPosition();

        // @todo: is this used at all?
        // should maybe be a regular modal attribute then
        currentModal.querySelector('.t3js-modal-body')?.setAttribute('id', '123' /*editor.id*/);
      }
    });
  }
}

export class Typo3Link extends Core.Plugin {
  static readonly pluginName = 'Typo3Link';
  static readonly requires = ['GeneralHtmlSupport', Link.LinkEditing, Link.AutoLink, Typo3LinkEditing, Typo3LinkUI];
  static readonly overrides?: Array<typeof Core.Plugin> = [Link.Link];
}

declare module '@ckeditor/ckeditor5-core' {
  interface EditorConfig {
    typo3link?: Typo3LinkConfig;
  }
}

// Provided for backwards compatibility
export default Typo3Link;
