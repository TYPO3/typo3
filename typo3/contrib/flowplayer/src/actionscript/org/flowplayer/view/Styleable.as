/*    
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of FlowPlayer.
 *
 *    FlowPlayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    FlowPlayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with FlowPlayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.view {
	
	/**
	 * Interface for objects that support modifying their display style. 
	 */
	public interface Styleable {

		/**
		 * Notifies new css properties.
		 * 
		 * @param styleProps and object containing the new properties. The propertis contained by this
		 * object are added, if a specific object already exists it's overwritten. If the parameter is not specified
		 * returns the current style properties.
		 * @return void
		 */		
		function onBeforeCss(styleProps:Object = null):void;
		

		/**
		 * Sets/adds css style properties.
		 * 
		 * @param styleProps and object containing the new properties. The propertis contained by this
		 * object are added, if a specific object already exists it's overwritten. If the parameter is not specified
		 * returns the current style properties.
		 * @return the style props after setting the new properties
		 */		
		function css(styleProps:Object = null):Object;

		
		/**
		 * Notifies a css properties animation.
		 * 
		 * @param styleProps and object containing the properties to be animated. The propertis contained by this
		 * object are added, if a specific object already exists it's overwritten.
		 * @return void
		 */
		function onBeforeAnimate(styleProps:Object):void;

		/**
		 * Animates css properties.
		 * 
		 * @param styleProps and object containing the properties to be animated. The propertis contained by this
		 * object are added, if a specific object already exists it's overwritten.
		 * @return the style props after setting the new properties
		 */
		function animate(styleProps:Object):Object;
		
	}
}
