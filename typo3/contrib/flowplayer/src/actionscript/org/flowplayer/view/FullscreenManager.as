/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
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

package org.flowplayer.view {
    import flash.display.DisplayObject;

    import org.flowplayer.model.Clip;
	import org.flowplayer.model.Cloneable;
	import org.flowplayer.model.DisplayPluginModel;
	import org.flowplayer.model.DisplayProperties;
	import org.flowplayer.model.DisplayPropertiesImpl;
	import org.flowplayer.model.PlayerEvent;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.model.PluginModel;
	import org.flowplayer.util.Assert;
	import org.flowplayer.util.Log;
	import org.flowplayer.util.VersionUtil;
	import flash.display.Stage;
	import flash.display.StageDisplayState;
	import flash.events.FullScreenEvent;
	import flash.geom.Rectangle;	

	/**
	 * @author api
	 */
	internal class FullscreenManager {
		private var log:Log = new Log(this);
		private var _stage:Stage;
		private var _playlist:Playlist;
		private var _panel:Panel;
		private var _pluginRegistry:PluginRegistry;
		private var _animations:AnimationEngine;
		private var _screen:Screen;
		private var _screenNormalProperties:DisplayProperties;
		private var _playerEventDispatcher:PlayerEventDispatcher;

		public function FullscreenManager(stage:Stage, playlist:Playlist, panel:Panel, pluginRegistry:PluginRegistry, animations:AnimationEngine) {
			Assert.notNull(stage, "stage cannot be null");
			_stage = stage;
			_stage.addEventListener(FullScreenEvent.FULL_SCREEN, onFullScreen);
			_playlist = playlist;
			_panel = panel;
			_pluginRegistry = pluginRegistry;
			_screen = (pluginRegistry.getPlugin("screen") as DisplayProperties).getDisplayObject() as Screen;
			Assert.notNull(_screen, "got null screen from pluginRegistry");
			_screen.fullscreenManager = this;
			_animations = animations;
		}
		
		private function getFullscreenProperties():DisplayProperties {
			var model:DisplayPluginModel = _pluginRegistry.getPlugin("controls") as DisplayPluginModel;
            if (! model) return DisplayPropertiesImpl.fullSize("screen");

            var controls:DisplayObject = model.getDisplayObject();
			log.debug("controls.auotoHide " + controls["getAutoHide"]());

			if ( controls && ! controls["getAutoHide"]().enabled ) {
				log.debug("autoHiding disabled in fullscreen, calculating fullscreen display properties");
				var controlsHeight:Number = controls.height;
				var props:DisplayProperties = DisplayPropertiesImpl.fullSize("screen");
				props.bottom = controlsHeight;
				props.height =  ((_stage.stageHeight - controlsHeight) / _stage.stageHeight) * 100 + "%";
				return props;
			}
			return DisplayPropertiesImpl.fullSize("screen");
		}
        
		public function toggleFullscreen():void {
			log.debug("toggleFullsreen");
			if (isFullscreen) {
				exitFullscreen();
			} else {
				goFullscreen();
			}
		}
		
		private function exitFullscreen():void {
			log.info("exiting fullscreen");
			_stage.displayState = StageDisplayState.NORMAL;
		}

		private function goFullscreen():void {
			log.info("entering fullscreen");
			var clip:Clip = _playlist.current;
			initializeHwScaling(clip);
			_stage.displayState = StageDisplayState.FULL_SCREEN;
		}

		public function get isFullscreen():Boolean {
			log.debug("currently in fulscreen? " + (_stage.displayState == StageDisplayState.FULL_SCREEN));
			return _stage.displayState == StageDisplayState.FULL_SCREEN;
		}
		
		private function initializeHwScaling(clip:Clip):void {
            if (! _stage.hasOwnProperty("fullScreenSourceRect")) {
                log.info("hardware scaling not supported by this Flash version");
                return;
            }
			// accelerated and no stage video
			if (clip.useHWScaling) {
				_stage.fullScreenSourceRect = new Rectangle(0, 0,clip.originalWidth, clip.originalHeight);
				log.info("harware scaled fullscreen initialized with rectangle " + _stage.fullScreenSourceRect);
			} else {
				_stage.fullScreenSourceRect = null;
			}
		}
				
		private function onFullScreen(event:FullScreenEvent):void {
			// store the normal screen properties just prior to entering fullscreen so that the user's screen animations can be restored
			if (event.fullScreen) {
				_screenNormalProperties = Cloneable(_pluginRegistry.getPlugin("screen")).clone() as DisplayProperties;
			}
			_animations.animate(_screen, event.fullScreen ? getFullscreenProperties() : _screenNormalProperties, 0, function():void {_playerEventDispatcher.dispatchEvent(event.fullScreen ? PlayerEvent.fullscreen() : PlayerEvent.fullscreenExit());});
		}

		public function set playerEventDispatcher(playerEventDispatcher:PlayerEventDispatcher):void {
			_playerEventDispatcher = playerEventDispatcher;
		}
	}
}
