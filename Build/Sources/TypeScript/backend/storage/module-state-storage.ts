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

interface CurrentState {
  identifier: string;
  treeIdentifier: string|null;
}

export interface ModuleStateUpdateEvent {
  state: CurrentState;
  oldState: CurrentState;
}

/**
 * Module State previous known as `fsMod` with the previous description:
 *
 * > Used in main modules with a frameset for submodules to keep the ID
 * > between modules Typically that is set by something like this in a
 * > Web>* sub module
 *
 * @exports @typo3/backend/storage/module-state-storage
 */
export class ModuleStateStorage {
  private static readonly prefix = 't3-module-state-';

  public static update(module: string, identifier: string|number): CurrentState
  {
    if (typeof identifier === 'number') {
      identifier = identifier.toString(10);
    } else if (typeof identifier !== 'string') {
      throw new SyntaxError('identifier must be of type string');
    }

    const oldState = ModuleStateStorage.current(module);
    const treeIdentifier = identifier === oldState.identifier ? oldState.treeIdentifier : null;
    const state = { identifier, treeIdentifier };

    ModuleStateStorage.commit(module, 'update', state);
    return state;
  }

  public static updateWithTreeIdentifier(module: string, identifier: string|number, treeIdentifier: string|number): CurrentState
  {
    if (typeof identifier === 'number') {
      identifier = identifier.toString(10);
    } else if (typeof identifier !== 'string') {
      throw new SyntaxError('identifier must be of type string');
    }

    if (typeof treeIdentifier === 'number') {
      treeIdentifier = treeIdentifier.toString(10);
    } else if (typeof treeIdentifier !== 'string') {
      throw new SyntaxError('treeIdentifier must be of type string');
    }

    const state = { identifier, treeIdentifier };
    ModuleStateStorage.commit(module, 'update-with-tree-identifier', state);
    return state;
  }

  public static updateWithCurrentMount(module: string, identifier: string|number)
  {
    ModuleStateStorage.update(module, identifier);
  }

  public static current(module: string): CurrentState {
    const state = {
      ...ModuleStateStorage.getInitialState(),
      ...(ModuleStateStorage.fetch(module) ?? {}),
    };
    return state;
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

  private static async commit(module: string, mode: string, state: CurrentState) {
    const oldState = ModuleStateStorage.current(module);
    sessionStorage.setItem(ModuleStateStorage.prefix + module, JSON.stringify(state));

    top.document.dispatchEvent(new CustomEvent<ModuleStateUpdateEvent>('typo3:module-state-storage:' + mode + ':' + module, {
      detail: {
        state,
        oldState,
      }
    }));
  }

  private static getInitialState(): CurrentState
  {
    return { identifier: '', treeIdentifier: null };
  }
}

// exposing `ModuleStateStorage`
window.ModuleStateStorage = ModuleStateStorage;
