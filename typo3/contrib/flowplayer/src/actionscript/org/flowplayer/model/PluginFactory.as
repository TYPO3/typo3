/*    
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.model {

	/**
	 * An inteface for objects that create plugins. This is used when the plugin SWFs main class
	 * itself is not used as a plugin class. This is the case when the plugin is not a DisplayObject.
	 * The SWF main class is required to extend DisplayObject or any of it's subclasses and therefore
	 * it is not suitable for non-visual plugins that just implement logic. Providers are an example of
	 * non-visual plugins.
	 * 
	 * The SWF main class can implement this interface.
	 */
	public interface PluginFactory {

		/**
		 * A factory method to create the plugin. Player uses the plugin object returned by this method, instead
		 * of the factory object itself.
		 */
		function newPlugin():Object;
	}
}
