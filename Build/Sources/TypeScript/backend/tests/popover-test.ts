import $ from 'jquery';
import {Popover as BootstrapPopover} from 'bootstrap';
import Popover from '@typo3/backend/popover';

describe('TYPO3/CMS/Backend/PopoverTest:', () => {
  /**
   * @test
   */
  describe('initialize', () => {
    const $body = $('body');
    const $element = $('<div data-bs-toggle="popover">');
    $body.append($element);
    it('works with default selector', () => {
      Popover.initialize();
      expect($element[0].outerHTML).toBe('<div data-bs-toggle="popover"></div>');
    });

    const $element2 = $('<div data-bs-toggle="popover" data-title="foo">');
    $body.append($element2);
    it('works with default selector and title attribute', () => {
      Popover.initialize();
      expect($element2[0].outerHTML).toBe('<div data-bs-toggle="popover" data-title="foo"></div>');
    });

    const $element3 = $('<div data-bs-toggle="popover" data-bs-content="foo">');
    $body.append($element3);
    it('works with default selector and content attribute', () => {
      Popover.initialize();
      expect($element3[0].outerHTML).toBe('<div data-bs-toggle="popover" data-bs-content="foo"></div>');
    });

    const $element4 = $('<div class="t3js-popover">');
    $body.append($element4);
    it('works with custom selector', () => {
      Popover.initialize('.t3js-popover');
      expect($element4[0].outerHTML).toBe('<div class="t3js-popover"></div>');
    });
  });

  describe('call setOptions', () => {
    const $body = $('body');
    const $element = $('<div class="t3js-test-set-options" data-title="foo-title" data-bs-content="foo-content">');
    $body.append($element);
    it('can set title', () => {
      Popover.initialize('.t3js-test-set-options');
      expect($element.attr('data-title')).toBe('foo-title');
      expect($element.attr('data-bs-content')).toBe('foo-content');
      Popover.setOptions($element, <BootstrapPopover.Options>{
        'title': 'bar-title'
      });
      expect($element.attr('data-title')).toBe('foo-title');
      expect($element.attr('data-bs-content')).toBe('foo-content');
      expect($element.attr('data-bs-original-title')).toBe('bar-title');
    });
    const $element2 = $('<div class="t3js-test-set-options2" data-title="foo-title" data-bs-content="foo-content">');
    $body.append($element2);

    it('can set content', () => {
      Popover.initialize('.t3js-test-set-options2');
      // Popover must be visible before the content can be updated manually via setOptions()
      Popover.show($element2);
      expect($element2.attr('data-title')).toBe('foo-title');
      expect($element2.attr('data-bs-content')).toBe('foo-content');
      Popover.setOptions($element2, <BootstrapPopover.Options>{
        'content': 'bar-content'
      });
      expect($element2.attr('data-title')).toBe('foo-title');
      expect($element2.attr('data-bs-content')).toBe('bar-content');
      expect($element2.attr('data-bs-original-title')).toBe('foo-title');
    });
  });
});
