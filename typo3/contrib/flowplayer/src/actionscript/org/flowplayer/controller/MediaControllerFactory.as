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

package org.flowplayer.controller {
	import org.flowplayer.model.ClipError;	
	import org.flowplayer.config.Config;	
	import org.flowplayer.model.ClipEventType;	
	import org.flowplayer.view.PlayerEventDispatcher;	
	import org.flowplayer.util.Log;	
	
	import flash.utils.Dictionary;
	
	import org.flowplayer.flow_internal;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.ClipType;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.model.ProviderModel;	

	/**
	 * @author anssi
	 */
	internal class MediaControllerFactory {

        private var log:Log = new Log(this);
        private var _streamProviderController:MediaController;
        private var _inStreamController:MediaController;

        private var _imageController:ImageController;
        private var _inStreamImageController:ImageController;

		private static var _instance:MediaControllerFactory;
		private var _volumeController:VolumeController;
        private var _providers:Dictionary;
		private var _playerEventDispatcher:PlayerEventDispatcher;
		private var _config:Config;
		private var _loader:ResourceLoader;

		use namespace flow_internal;
		
		public function MediaControllerFactory(providers:Dictionary, playerEventDispatcher:PlayerEventDispatcher, config:Config, loader:ResourceLoader) {
            _providers = providers;
			_instance = this;
			_playerEventDispatcher = playerEventDispatcher;
			_volumeController = new VolumeController(_playerEventDispatcher);
			_config = config;
			_loader = loader;
		}

		flow_internal function getMediaController(clip:Clip, playlist:Playlist):MediaController {
			var clipType:ClipType = clip.type;
			//allow for chromeless swf video players to be treated as video
			if (clipType == ClipType.VIDEO || clipType == ClipType.AUDIO || clipType == ClipType.API) {
				return getStreamProviderController(playlist, clip.isInStream);
			}
			if (clipType == ClipType.IMAGE) {
				return getImageController(playlist, clip.isInStream);
			}
			throw new Error("No media controller found for clip type " + clipType);
			return null;
		}
		
		flow_internal function getVolumeController():VolumeController {
			return _volumeController;
		}
		
		private function getStreamProviderController(playlist:Playlist, inStream:Boolean = false):MediaController {
            if (inStream) {
                if (! _inStreamController) {
                    _inStreamController = new StreamProviderController(this, getVolumeController(), _config, playlist);
                }
                return _inStreamController;
            }
            
			if (!_streamProviderController) {
				_streamProviderController = new StreamProviderController(this, getVolumeController(), _config, playlist);
            }
            return _streamProviderController;
		}
		
		private function getImageController(playlist:Playlist, inStream:Boolean = false):MediaController {
            if (inStream) {
                if (! _inStreamImageController) {
                    _inStreamImageController = new ImageController(_loader, getVolumeController(), playlist);
                }
                return _inStreamImageController;
            }

			if (!_imageController)
				_imageController = new ImageController(_loader, getVolumeController(), playlist);
			return _imageController;
		}

		internal function addProvider(provider:ProviderModel):void {
			_providers[provider.name] = provider.pluginObject;
		}
		
		public function getProvider(clip:Clip):StreamProvider {
			var provider:StreamProvider = _providers[clip.provider];
			if (! provider) {
                for (var key:String in _providers) {
                    log.debug("found provider " + key);
                }
				clip.dispatchError(ClipError.PROVIDER_NOT_LOADED, "Provider '" + clip.provider + "' " + getInstreamProviderErrorMsg(clip));
				return null;
			}
			provider.volumeController = getVolumeController();
			return provider;
		}

        private function getInstreamProviderErrorMsg(clip:Clip):String {
            if (! clip.isInStream) return "";
            return "(if this instream clip was started using play() you need to explicitly load/configure provider '" + clip.provider + "' before calling play())";
        }
	}
}
