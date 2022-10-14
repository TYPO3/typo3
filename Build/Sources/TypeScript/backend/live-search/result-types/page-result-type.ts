import {html, TemplateResult} from 'lit';
import {ResultItemInterface} from '@typo3/backend/live-search/element/result-item';
import LiveSearch from '@typo3/backend/toolbar/live-search';
import '@typo3/backend/live-search/element/provider/page-provider-result-item';

export function registerRenderer(type: string) {
  LiveSearch.addRenderer(type, (attributes: ResultItemInterface): TemplateResult => {
    return html`<typo3-backend-live-search-result-item-page-provider
      .icon="${attributes.icon}"
      .itemTitle="${attributes.itemTitle}"
      .typeLabel="${attributes.typeLabel}"
      .extraData="${attributes.extraData}">
    </typo3-backend-live-search-result-item-page-provider>`;
  });
}
