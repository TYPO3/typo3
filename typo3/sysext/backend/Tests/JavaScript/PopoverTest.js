define(['jquery', 'bootstrap', 'TYPO3/CMS/Backend/Popover'], function($, bootstrap, Popover) {
  'use strict';

  describe('TYPO3/CMS/Backend/PopoverTest:', function() {
    /**
     * @test
     */
    describe('initialize', function() {
      var $body = $('body');
      var $element = $('<div data-toggle="popover">');
      $body.append($element);
      it('works with default selector', function() {
        Popover.initialize();
        expect($element[0].outerHTML).toBe('<div data-toggle="popover" data-original-title="" title=""></div>');
      });

      var $element2 = $('<div data-toggle="popover" data-title="foo">');
      $body.append($element2);
      it('works with default selector and title attribute', function() {
        Popover.initialize();
        expect($element2[0].outerHTML).toBe('<div data-toggle="popover" data-title="foo" data-original-title="" title=""></div>');
      });

      var $element3 = $('<div data-toggle="popover" data-content="foo">');
      $body.append($element3);
      it('works with default selector and content attribute', function() {
        Popover.initialize();
        expect($element3[0].outerHTML).toBe('<div data-toggle="popover" data-content="foo" data-original-title="" title=""></div>');
      });

      var $element4 = $('<div class="t3js-popover">');
      $body.append($element4);
      it('works with custom selector', function() {
        Popover.initialize('.t3js-popover');
        expect($element4[0].outerHTML).toBe('<div class="t3js-popover" data-original-title="" title=""></div>');
      });
    });

    describe('call setOptions', function() {
      var $body = $('body');
      var $element = $('<div class="t3js-test-set-options" data-title="foo-title" data-content="foo-content">');
      $body.append($element);
      it('can set title', function() {
        Popover.initialize('.t3js-test-set-options');
        expect($element.attr('data-title')).toBe('foo-title');
        expect($element.attr('data-content')).toBe('foo-content');
        expect($element.attr('data-original-title')).toBe('');
        expect($element.attr('title')).toBe('');
        Popover.setOptions($element, {
          'title': 'bar-title'
        });
        expect($element.attr('data-title')).toBe('foo-title');
        expect($element.attr('data-content')).toBe('foo-content');
        expect($element.attr('data-original-title')).toBe('bar-title');
        expect($element.attr('title')).toBe('');
      });
      var $element2 = $('<div class="t3js-test-set-options2" data-title="foo-title" data-content="foo-content">');
      $body.append($element2);
      it('can set content', function() {
        Popover.initialize('.t3js-test-set-options2');
        expect($element2.attr('data-title')).toBe('foo-title');
        expect($element2.attr('data-content')).toBe('foo-content');
        expect($element2.attr('data-original-title')).toBe('');
        expect($element2.attr('title')).toBe('');
        Popover.setOptions($element2, {
          'content': 'bar-content'
        });
        expect($element2.attr('data-title')).toBe('foo-title');
        expect($element2.attr('data-content')).toBe('bar-content');
        expect($element2.attr('data-original-title')).toBe('foo-title');
        expect($element2.attr('title')).toBe('');
      });
    });
  });
});
