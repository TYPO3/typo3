import { Popover as BootstrapPopover } from 'bootstrap';
import Popover from '@typo3/backend/popover';

describe('TYPO3/CMS/Backend/PopoverTest:', () => {
  /**
   * @test
   */
  describe('initialize', () => {
    const element = document.createElement('div');
    element.dataset.bsToggle = 'popover';
    document.body.append(element);

    it('works with default selector', () => {
      Popover.initialize();
      expect(element.outerHTML).toBe('<div data-bs-toggle="popover"></div>');
    });

    const element2 = document.createElement('div');
    element2.dataset.bsToggle = 'popover';
    element2.dataset.title = 'foo';
    document.body.append(element2);
    it('works with default selector and title attribute', () => {
      Popover.initialize();
      expect(element2.outerHTML).toBe('<div data-bs-toggle="popover" data-title="foo" data-bs-title="foo"></div>');
    });

    const element3 = document.createElement('div');
    element3.dataset.bsToggle = 'popover';
    element3.dataset.bsContent = 'foo';
    document.body.append(element3);
    it('works with default selector and content attribute', () => {
      Popover.initialize();
      expect(element3.outerHTML).toBe('<div data-bs-toggle="popover" data-bs-content="foo"></div>');
    });

    const element4 = document.createElement('div');
    element4.classList.add('t3js-popover');
    document.body.append(element4);
    it('works with custom selector', () => {
      Popover.initialize('.t3js-popover');
      expect(element4.outerHTML).toBe('<div class="t3js-popover"></div>');
    });
  });

  describe('call setOptions', () => {
    const element = document.createElement('div');
    element.classList.add('t3js-test-set-options');
    element.dataset.title = 'foo-title';
    element.dataset.bsContent = 'foo-content';
    document.body.append(element);

    it('can set title', () => {
      Popover.initialize('.t3js-test-set-options');
      expect(element.getAttribute('data-title')).toBe('foo-title');
      expect(element.getAttribute('data-bs-content')).toBe('foo-content');
      Popover.setOptions(element, <BootstrapPopover.Options>{
        'title': 'bar-title'
      });
      expect(element.getAttribute('data-title')).toBe('foo-title');
      expect(element.getAttribute('data-bs-content')).toBe('foo-content');
      expect(element.getAttribute('data-bs-original-title')).toBe('bar-title');
    });

    const element2 = document.createElement('div');
    element2.classList.add('t3js-test-set-options2');
    element2.dataset.title = 'foo-title';
    element2.dataset.bsContent = 'foo-content';
    document.body.append(element2);

    it('can set content', () => {
      Popover.initialize('.t3js-test-set-options2');
      // Popover must be visible before the content can be updated manually via setOptions()
      Popover.show(element2);
      expect(element2.getAttribute('data-title')).toBe('foo-title');
      expect(element2.getAttribute('data-bs-content')).toBe('foo-content');
      Popover.setOptions(element2, <BootstrapPopover.Options>{
        'content': 'bar-content'
      });
      expect(element2.getAttribute('data-title')).toBe('foo-title');
      expect(element2.getAttribute('data-bs-content')).toBe('bar-content');
      expect(element2.getAttribute('data-bs-original-title')).toBe('foo-title');
    });
  });
});
