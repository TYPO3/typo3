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

import AjaxRequest from '@typo3/core/ajax/ajax-request';

type TypeId = string;

type PropertyName = string;

type PlainDocData = Record<TypeId, {
  extends: TypeId,
  name: TypeId,
  properties: Record<PropertyName, {
    name: PropertyName,
    type: string
  }>,
}>;

export class TsRefType {
  public readonly typeId: TypeId;
  public extends: TypeId;
  public properties: Record<PropertyName, TsRefProperty> & { clone?: () => void } = {};

  constructor(
    typeId: string,
    extendsTypeId: string | null,
    properties: Record<PropertyName, TsRefProperty>
  ) {
    this.typeId = typeId;
    this.extends = extendsTypeId;
    this.properties = properties;
  }
}

export class TsRefProperty {
  public readonly parentType: string;
  public readonly name: PropertyName;
  public readonly value: string;

  constructor(parentType: string, name: string, value: string) {
    this.parentType = parentType;
    this.name = name;
    this.value = value;
  }
}

export class TsRef {
  public typeTree: Record<TypeId, TsRefType> = {};
  public doc: PlainDocData = null;
  public cssClass: string;

  /**
   * Load available TypoScript reference
   */
  public async loadTsrefAsync(): Promise<void> {
    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.codeeditor_tsref).get();
    this.doc = await response.resolve();
    this.buildTree();
  }

  /**
   * Build the TypoScript reference tree
   */
  public buildTree(): void {
    for (const typeId of Object.keys(this.doc)) {
      const arr = this.doc[typeId];
      this.typeTree[typeId] = new TsRefType(
        typeId,
        arr.extends || undefined,
        Object.fromEntries(
          Object.entries(arr.properties).map(
            ([propName, property]) => [propName, new TsRefProperty(typeId, propName, property.type)]
          )
        )
      );
    }
    for (const typeId of Object.keys(this.typeTree)) {
      if (typeof this.typeTree[typeId].extends !== 'undefined') {
        this.addPropertiesToType(this.typeTree[typeId], this.typeTree[typeId].extends, 100);
      }
    }
  }

  /**
   * Adds properties to TypoScript types
   */
  public addPropertiesToType(
    addToType: TsRefType,
    addFromTypeNames: string,
    maxRecDepth: number
  ): void {
    if (maxRecDepth < 0) {
      throw 'Maximum recursion depth exceeded while trying to resolve the extends in the TSREF!';
    }
    const exts = addFromTypeNames.split(',');
    for (let i = 0; i < exts.length; i++) {
      // "Type 'array' which is used to extend 'undefined', was not found in the TSREF!"
      if (typeof this.typeTree[exts[i]] !== 'undefined') {
        if (typeof this.typeTree[exts[i]].extends !== 'undefined') {
          this.addPropertiesToType(this.typeTree[exts[i]], this.typeTree[exts[i]].extends, maxRecDepth - 1);
        }
        const properties = this.typeTree[exts[i]].properties;
        for (const propName in properties) {
          // only add this property if it was not already added by a supertype (subtypes override supertypes)
          if (typeof addToType.properties[propName] === 'undefined') {
            addToType.properties[propName] = properties[propName];
          }
        }
      }
    }
  }

  /**
   * Get properties from given TypoScript type id
   */
  public getPropertiesFromTypeId(tId: TypeId): Record<PropertyName, TsRefProperty> {
    if (typeof this.typeTree[tId] !== 'undefined') {
      // clone is needed to assure that nothing of the tsref is overwritten by user setup
      this.typeTree[tId].properties.clone = function() {
        const result = {} as Record<PropertyName, TsRefProperty>;
        for (const key of Object.keys(this)) {
          result[key] = new TsRefProperty(this[key].parentType, this[key].name, this[key].value);
        }
        return result;
      };
      return this.typeTree[tId].properties;
    }
    return {};
  }

  /**
   * Check if a property of a type exists
   */
  public typeHasProperty(typeId: TypeId, propertyName: PropertyName): boolean {
    return (
      typeof this.typeTree[typeId] !== 'undefined' &&
      typeof this.typeTree[typeId].properties[propertyName] !== 'undefined'
    );
  }

  /**
   * Get the type
   */
  public getType(typeId: TypeId): TsRefType {
    return this.typeTree[typeId];
  }

  /**
   * Check if type exists in the type tree
   */
  public isType(typeId: TypeId): boolean {
    return typeof this.typeTree[typeId] !== 'undefined';
  }
}
