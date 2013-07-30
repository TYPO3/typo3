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
	import org.flowplayer.config.Config;
	import org.flowplayer.flow_internal;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.ClipEvent;
	import org.flowplayer.model.ClipType;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.model.ProviderModel;
	import org.flowplayer.model.State;
	import org.flowplayer.model.Status;
	import org.flowplayer.util.Log;
	import org.flowplayer.view.PlayerEventDispatcher;
	
	import flash.utils.Dictionary;		
	
	use namespace flow_internal;

	/**
	 * PlayListController is responsible in moving the playback within the clips in the playList.
	 * It does this by delegating to the the PlayStates.
	 * 
	 * @author anssi
	 */
	public class PlayListController {

		private var log:Log;
		private var _playList:Playlist;
		private var _state:PlayState;
        private var _providers:Dictionary;
		private var _config:Config;
		private var _loader:ResourceLoader;

		public function PlayListController(playList:Playlist, providers:Dictionary, config:Config, loader:ResourceLoader) {
			log = new Log(this);
			_playList = playList;
			_providers = providers;
			_config = config;
			_loader = loader;
		}

        flow_internal function get streamProvider():StreamProvider {
            return _state.streamProvider;
        }

		flow_internal function set playerEventDispatcher(playerEventDispatcher:PlayerEventDispatcher):void {
			PlayState.initStates(_playList, this, _providers, playerEventDispatcher, _config, _loader);
		}

		flow_internal function setPlaylist(clips:Array):void {
            if (getState() != State.WAITING) {
                close(false);
            }
			_playList.replaceClips2(clips);
		}

		flow_internal function get playlist():Playlist {
			return _playList;
		}


		flow_internal function rewind():Clip {
			log.info("rewind()");
			setPlayState(PlayState.waitingState);
			_playList.toIndex(firstNonSplashClip());
			_state.play();
			return _playList.current;
		}

		private function firstNonSplashClip():Number {
			var clips:Array = _playList.clips;
			for (var i:Number = 0; i < clips.length; i++) {
				var clip:Clip = clips[i];
				if (clip.type == ClipType.IMAGE && clip.duration > 0) {
					return i;
				}
				if (clip.type == ClipType.IMAGE && i < clips.length - 1) {
					var nextClip:Clip = clips[i+1] as Clip;
					if (nextClip.type == ClipType.AUDIO && nextClip.image) {
						// this is a splash image for the next audio clip
						nextClip.autoPlayNext = true;
						return i;
					}
				}
				if (clip.type == ClipType.VIDEO || clip.type == ClipType.AUDIO) {
					return i;
				}
			}
			return 0;
		}

		flow_internal function playClips(clips:Array):void {
			replacePlaylistAndPlay(clips);
		}

        flow_internal function playInstream(clip:Clip):void {
            _state.pause();
            playlist.setInStreamClip(clip);
            setPlayState(PlayState.waitingState);            
            _state.play();
        }

        flow_internal function switchStream(clip:Clip, netStreamPlayOption:Object = null):void {
            _state.switchStream(netStreamPlayOption);
        }

		flow_internal function play(clip:Clip = null, clipIndex:Number = -1):Clip {
			log.debug("play() " + clip + ", " + clipIndex);
			if (clip || clipIndex >= 0) {
				return playClip(clip, clipIndex);
			} else if (! _playList.hasNext() && status.ended) {
				return rewind();
			}
			_state.play();
			return _playList.current;
		}
		
		private function playClip(clip:Clip = null, clipIndex:Number = undefined):Clip {
			if (clip) {
				replacePlaylistAndPlay(clip);
				return clip;
			}
			if (clipIndex >= 0) {
/*
				if (clipIndex == _playList.currentIndex && getState() != State.WAITING) {
					log.debug("play(): already playing this clip, returning");
					return _playList.current;
				}
*/
				_state.stop();
				if (_playList.toIndex(clipIndex) == null) {
					log.error("There is no clip at index " + clipIndex + ", cannot play");
					return _playList.current;
				}
				_state.play();
			}
			return _playList.current;	
		}

		flow_internal function startBuffering():Clip {
			_state.startBuffering();
			return _playList.current;
		}

		flow_internal function stopBuffering():Clip {
			_state.stopBuffering();
			return _playList.current;
		}
		
		flow_internal function next(obeyClipPlaySettings:Boolean, silent:Boolean = false, skipPreAndPostroll:Boolean = true):Clip {
			if (!_playList.hasNext(skipPreAndPostroll)) return _playList.current;
			return moveTo(_playList.next, obeyClipPlaySettings, silent, skipPreAndPostroll);
		}

		flow_internal function previous(skipPreAndPostroll:Boolean = true):Clip {
			if (!_playList.hasPrevious(skipPreAndPostroll)) return _playList.current;
			
			if (currentIsAudioWithSplash() && _playList.currentIndex >= 3) {
				_state.stop();
				_playList.toIndex(_playList.currentIndex - 2);
				_state.play();
				return _playList.current;
			}
			
			return moveTo(_playList.previous, false, false, skipPreAndPostroll);
		}
		
		private function currentIsAudioWithSplash():Boolean {
			return _playList.current.type == ClipType.AUDIO && _playList.current.image 
				&& _playList.previousClip && _playList.previousClip.type == ClipType.IMAGE;
		}

		flow_internal function moveTo(advanceFunction:Function, obeyClipPlaySettings:Boolean, silent:Boolean, skipPreAndPostroll:Boolean = true):Clip {
			var stateBeforeStopping:State = getState();
			
			log.debug("moveTo() current state is " + _state);
			
			if (silent) {
				_state.stop(true, true);
				setPlayState(PlayState.waitingState);
			} else {
				_state.stop();
			}

			// now we can move to next/previous in the playList
			var clip:Clip = advanceFunction(skipPreAndPostroll) as Clip;
			log.info("moved in playlist, current clip is " + _playList.current + ", next clip is " + clip);
			
			log.debug("moved in playlist, next clip autoPlay " + clip.autoPlay + ", autoBuffering " + clip.autoBuffering);
			if (obeyClipPlaySettings) {
				log.debug("obeying clip autoPlay & autoBuffeing");
				// autoPlayNext is used when rewinding
                log.debug("autoPlayNext? " + clip.autoPlayNext + ", autoPlay? " + clip.autoPlay + ", autoBuffering? " + clip.autoBuffering);
                if (clip.autoPlayNext) {
					clip.autoPlayNext = false;
					_state.play();
				} else if (clip.autoPlay) {
					_state.play();
				} else if (clip.autoBuffering) {
                    if (clip.type == ClipType.IMAGE && clip.autoBuffering) {
                        _state.play();
                    }else {
                        _state.startBuffering();
                    }
				}
			} else {
				log.debug("not obeying playlist settings");
				if (stateBeforeStopping == State.PAUSED || stateBeforeStopping == State.WAITING) {
					_state.startBuffering();
				} else { 
					_state.play();
				}
			}
			
			return clip;
		}
		
		flow_internal function pause(silent:Boolean = false):Clip {
            log.debug("pause(), silent? " + silent);
			_state.pause(silent);
			return _playList.current;
		}
		
		flow_internal function resume(silent:Boolean = false):Clip {
            log.debug("resume(), silent? " + silent);
			_state.resume(silent);
			return _playList.current;
		}
		
		flow_internal function stop(silent:Boolean = false):Clip {
			if (silent) {
				setPlayState(PlayState.waitingState);
			} else {
				if (_state) {
					_state.stop();
				}
			}
			if (! _playList) return null;
			return _playList.current;
		}
		
		flow_internal function close(silent:Boolean):void {
			_state.close(silent);
		}
		
		flow_internal function seekTo(seconds:Number, silent:Boolean = false):Clip {
			log.debug("seekTo " + seconds + ", silent? " + silent);
			if (seconds >= 0) {
				_state.seekTo(seconds, silent);
			} else {
				log.warn("seekTo was called with seconds value " + seconds);
			}
			return _playList.current;
		}
		
		flow_internal function getState():State {
			if (! _state) return null;
			return _state.state;
		}

		flow_internal function getPlayState():PlayState {
			return _state;
		}

		flow_internal function setPlayState(state:PlayState):void {
			log.debug("moving to state " + state);
			if (_state)
				_state.active = false;
			_state = state;
			_state.active = true;
		}
		
		flow_internal function isInState(state:PlayState):Boolean {
			return _state == state;
		}
		
		flow_internal function get muted():Boolean {
			return _state.muted;
		}
		
		flow_internal function set muted(value:Boolean):void {
			_state.muted = value;
		}

		flow_internal function set volume(volume:Number):void {
			_state.volume = volume;
		}

		flow_internal function get volume():Number {
			if (! _state) return 0;
			return _state.volume;
		}
		
		flow_internal function get status():Status {
			return _state.status;
		}

        flow_internal function addConnectionCallback(name:String, listener:Function):void {
            addCallback(name, listener, "addConnectionCallback");
        }

        flow_internal function addStreamCallback(name:String, listener:Function):void {
            addCallback(name, listener, "addStreamCallback");
        }

        private function addCallback(name:String, listener:Function, registerFuncName:String):void {
            for each (var obj:Object in _providers) {
                log.debug("provider" + obj);
                var provider:StreamProvider = obj as StreamProvider;
                provider[registerFuncName](name, listener);
            }
        }

		private function replacePlaylistAndPlay(clips:Object):void {
			stop();
			if (clips is Clip) {
				_playList.replaceClips(clips as Clip);
			} else {
				_playList.replaceClips2(clips as Array);
			}
			play();
		}
		
		flow_internal function addProvider(provider:ProviderModel):void {
			PlayState.addProvider(provider);
		}
    }
}
