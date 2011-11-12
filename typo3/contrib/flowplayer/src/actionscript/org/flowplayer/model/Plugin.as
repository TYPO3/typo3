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
	import org.flowplayer.view.Flowplayer;	
	
	/**
	 * Plugin lifecycle interface that can be optionally implemented by plugins. 
	 * This interface provides plugins with:
	 * <ul>
	 * <li>The ability to interact with Flowplaeyr API.</li>
	 * <li>Optain the configuration specified for the plugin in the player's configuration.</li>
	 * <li>The plugin can provide a default configuration object.</li>
	 * </ul>
	 * 
	 * Lifecycle methods are invokek in following order: 1) onConfig(), 2) onLoad(), 3) getDefaultConfig().
	 */
	public interface Plugin {
		
		/**
		 * Provided plugins configuration properties. 
		 * This happens when the plugin SWF has been loaded but
		 * before it is added to the display list.
		 */
		function onConfig(configProps:PluginModel):void;

		/**
		 * Called when the player has been initialized. The interface is immediately ready to use, all
		 * other plugins have been loaded and initialized when this gets called.
		 *
		 * After this method has been called the plugin will be placed on the stage (on
		 * player's Panel).
		 */
		function onLoad(player:Flowplayer):void;

		/**
		 * Gets the default configuration to be used for this plugin. Called after onConfig() but
         * before onLoad()
		 * @return default configuration object, <code>null</code> if no defaults are available
		 */
		function getDefaultConfig():Object;
		
	}
}
