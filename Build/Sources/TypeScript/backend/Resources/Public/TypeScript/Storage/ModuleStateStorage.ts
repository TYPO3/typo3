/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

interface StateChange {
  mount?: string;
  identifier: string;
  selected: boolean;
}

interface CurrentState {
  mount?: string;
  identifier: string;
  selection?: string
}

/**
 * Module State previous known as `fsMod` with the previous description:
 *
 * > Used in main modules with a frameset for submodules to keep the ID
 * > between modules Typically that is set by something like this in a
 * > Web>* sub module
 *
 * Reading from `fsMod` is still possible as fall-back, however write
 * access is only possible via static API in this `ModuleStateStorage` class.
 *
 * @exports TYPO3/CMS/Backend/Storage/ModuleStateStorage
 */
export class ModuleStateStorage {
  private static prefix = 't3-module-state-';

  public static update(module: string, identifier: string|number, selected: boolean, mount?: string|number)
  {
    if (typeof identifier === 'number') {
      identifier = identifier.toString(10);
    } else if (typeof identifier !== 'string') {
      throw new SyntaxError('identifier must be of type string');
    }
    if (typeof mount === 'number') {
      mount = mount.toString(10);
    } else if (typeof mount !== 'string' && typeof mount !== 'undefined') {
      throw new SyntaxError('mount must be of type string');
    }
    const state = ModuleStateStorage.assignProperties(
      {mount, identifier, selected} as StateChange,
      ModuleStateStorage.fetch(module)
    );
    ModuleStateStorage.commit(module, state);
  }

  public static updateWithCurrentMount(module: string, identifier: string|number, selected: boolean)
  {
    ModuleStateStorage.update(
      module,
      identifier,
      selected,
      ModuleStateStorage.current(module).mount
    )
  }

  public static current(module: string): CurrentState {
    return ModuleStateStorage.fetch(module) || ModuleStateStorage.createCurrentState();
  }

  public static purge(): void
  {
    Object.keys(sessionStorage)
      .filter((key: string) => key.startsWith(ModuleStateStorage.prefix))
      .forEach((key: string) => sessionStorage.removeItem(key));
  }

  private static fetch(module: string): CurrentState|null {
    const data = sessionStorage.getItem(ModuleStateStorage.prefix + module);
    if (data === null) {
      return null;
    }
    return JSON.parse(data);
  }

  private static commit(module: string, state: CurrentState) {
    sessionStorage.setItem(ModuleStateStorage.prefix + module, JSON.stringify(state));
  }

  private static assignProperties(change: StateChange, state: CurrentState|null): CurrentState
  {
    let target = Object.assign(ModuleStateStorage.createCurrentState(), state) as CurrentState;
    if (change.mount) {
      target.mount = change.mount;
    }
    if (change.identifier) {
      target.identifier = change.identifier;
    }
    if (change.selected) {
      target.selection = target.identifier;
    }
    return target;
  }

  private static createCurrentState(): CurrentState
  {
    return {mount: null, identifier: '', selection: null} as CurrentState;
  }
}

// exposing `ModuleStateStorage`
(window as any).ModuleStateStorage = ModuleStateStorage;

/**
 * Provides fallback handling for reading from `top.fsMod` directly.
 * + `top.fsMod.recentIds.web`
 * + `top.fsMod.navFrameHighlightedID.web`
 * + `top.fsMod.currentBank`
 *
 * @deprecated `top.fsMod` is deprecated, will be removed in TYPO3 v12.0
 */
if (!top.fsMod || !top.fsMod.isProxy) {
  const proxy = (aspect: string): CurrentState => {
    return new Proxy<CurrentState>({} as StateChange, {
      get(target: Object, p: PropertyKey): any {
        const prop = p.toString();
        const current = ModuleStateStorage.current(prop);
        if (aspect === 'identifier') {
          return current.identifier;
        }
        if (aspect === 'selection') {
          return current.selection;
        }
        return undefined;
      },
      set(target: Object, p: PropertyKey, value: any, receiver: any): boolean {
        throw new Error('Writing to fsMod is not possible anymore, use ModuleStateStorage instead.');
      }
    });
  }
  const fsMod = {
    isProxy: true,
    recentIds:{},
    navFrameHighlightedID: {},
    currentBank: '0'
  };
  /*
   */
  top.fsMod = new Proxy<Object>(fsMod, {
    get(target: Object, p: PropertyKey): any {
      const prop = p.toString();
      if (prop === 'isProxy') {
        return true;
      }
      console.warn('Reading from fsMod is deprecated, use ModuleStateStorage instead.');
      if (prop === 'recentIds') {
        return proxy('identifier');
      }
      if (prop === 'navFrameHighlightedID') {
        return proxy('selection');
      }
      if (prop === 'currentBank') {
        return ModuleStateStorage.current('web').mount;
      }
      return undefined;
    },
    set(target: Object, p: PropertyKey, value: any, receiver: any): boolean {
      throw new Error('Writing to fsMod is not possible anymore, use ModuleStateStorage instead.');
    }
  });
}
