import $ from 'jquery';
import Icons from '@typo3/backend/icons';

describe('TYPO3/CMS/Backend/IconsTest:', () => {
  /**
   * @test
   */
  describe('tests for Icons object', () => {
    it('has all sizes', () => {
      expect(Icons.sizes.small).toBe('small');
      expect(Icons.sizes.default).toBe('default');
      expect(Icons.sizes.large).toBe('large');
      expect(Icons.sizes.overlay).toBe('overlay');
    });
    it('has all states', () => {
      expect(Icons.states.default).toBe('default');
      expect(Icons.states.disabled).toBe('disabled');
    });
    it('has all markupIdentifiers', () => {
      expect(Icons.markupIdentifiers.default).toBe('default');
      expect(Icons.markupIdentifiers.inline).toBe('inline');
    });
  });

  /**
   * @test
   */
  describe('tests for Icons::getIcon', () => {
    beforeEach(() => {
      spyOn(Icons, 'getIcon');
      Icons.getIcon('test', Icons.sizes.small, null, Icons.states.default, Icons.markupIdentifiers.default);
    });

    it('tracks that the spy was called', () => {
      expect(Icons.getIcon).toHaveBeenCalled();
    });
    it('tracks all the arguments of its calls', () => {
      expect(Icons.getIcon).toHaveBeenCalledWith('test', Icons.sizes.small, null, Icons.states.default, Icons.markupIdentifiers.default);
    });
    xit('works get icon from remote server');
  });

  /**
   * @test
   */
  describe('tests for Icons::putInCache', () => {
    it('works for simply identifier and markup', () => {
      const promise = new Promise<void>((reveal) => reveal());
      (Icons as any).putInPromiseCache('foo', promise);
      expect((Icons as any).getFromPromiseCache('foo')).toBe(promise);
      expect((Icons as any).isPromiseCached('foo')).toBe(true);
    });
  });

  /**
   * @test
   */
  describe('tests for Icons::getFromPromiseCache', () => {
    it('return undefined for uncached promise', () => {
      expect((Icons as any).getFromPromiseCache('bar')).not.toBeDefined();
      expect((Icons as any).isPromiseCached('bar')).toBe(false);
    });
  });
});
;
