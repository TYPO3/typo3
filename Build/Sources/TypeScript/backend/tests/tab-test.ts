import '@typo3/backend/tab.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/tab', () => {
  afterEach(() => {
    document.body.replaceChildren();
  });

  it('creates the scroll buttons of a tab scroller once when it is moved', async () => {
    const container = document.createElement('div');
    container.innerHTML = `
      <typo3-backend-tab-scroller>
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item"><button type="button" class="nav-link active" role="tab" data-typo3-tab="#tab-test-1">One</button></li>
        </ul>
      </typo3-backend-tab-scroller>
      <div class="tab-content">
        <div class="tab-pane active" id="tab-test-1"></div>
      </div>
    `;
    document.body.append(container);
    const scroller = container.querySelector('typo3-backend-tab-scroller')!;

    container.append(scroller);

    expect(scroller.querySelectorAll(':scope > .nav-tabs-scroll').length).to.equal(2);
  });
});
