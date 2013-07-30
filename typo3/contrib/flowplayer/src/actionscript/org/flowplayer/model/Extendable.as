/*    
 *    Author: Anssi Piirainen, <api@iki.fi>
 *
 *    Copyright (c) 2011 Flowplayer Oy
 *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is licensed under the GPL v3 license with an
 *    Additional Term, see http://flowplayer.org/license_gpl.html
 */
package org.flowplayer.model {

    /**
     * An object that can be extended with custom properties.
     */
    public interface Extendable {

        /**
         * Sets an object containing the custom properties. Replaces all previous properties.
         * @param props
         */
        function set customProperties(props:Object):void;

        /**
         * Gets the object that contains all custom properties as it's properties.
         */
        function get customProperties():Object;

        /**
         * Sets a custom property.
         * @param name the name of the property to set
         * @param value the value for the property
         */
        function setCustomProperty(name:String, value:Object):void;

        /**
         * Gets the custom property with the specified name.
         *
         * @param name the name of the property to query
         * @return the value of the specified property, or <code>null</code> if the property is not found.
         */
        function getCustomProperty(name:String):Object;

        /**
         * Deletes the property with the specified name.
         * @param name
         */
        function deleteCustomProperty(name:String):void;
    }
}