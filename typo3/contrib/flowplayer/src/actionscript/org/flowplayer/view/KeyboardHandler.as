/*    
 *    Author: Anssi Piirainen, <api@iki.fi>
 *
 *    Copyright (c) 2009-2011 Flowplayer Oy
 *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is licensed under the GPL v3 license with an
 *    Additional Term, see http://flowplayer.org/license_gpl.html
 */
package org.flowplayer.view {
    import flash.display.Stage;
    import flash.events.KeyboardEvent;
    import flash.ui.Keyboard;

    import flash.utils.Dictionary;

    import org.flowplayer.model.Clip;
    import org.flowplayer.model.PlayerEvent;
    import org.flowplayer.model.Status;
    import org.flowplayer.util.Log;

    public class KeyboardHandler {
	
        private var log:Log = new Log(this);
        private var _player:Flowplayer;
		private var _keyboardShortcutsEnabled:Boolean;
				
        private var _handlers:Dictionary = new Dictionary();
		
		public function set player(p:Flowplayer):void
		{
			_player = p;
		}

        public function KeyboardHandler(stage:Stage, enteringFullscreenCb:Function) {
			_keyboardShortcutsEnabled = true;
			
           addKeyListener(Keyboard.SPACE, function(event:KeyboardEvent):void {
                _player.toggle();
            });

            /*
             * Volume control
             */
            var volumeUp:Function = function(event:KeyboardEvent):void {
                var volume:Number = _player.volume;
                volume += 10;
                log.debug("setting volume to " + volume);
                _player.volume = volume > 100 ? 100 : volume;
            };
            addKeyListener(Keyboard.UP, volumeUp);
            addKeyListener(75, volumeUp);

            var volumeDown:Function = function(event:KeyboardEvent):void {
                log.debug("down");
                var volume:Number = _player.volume;
                volume -= 10;
                log.debug("setting volume to " + volume);
                _player.volume = volume < 0 ? 0 : volume;
            };
            addKeyListener(Keyboard.DOWN, volumeDown);
            addKeyListener(74, volumeDown);

            addKeyListener(77, function(event:KeyboardEvent):void {
                _player.muted = ! _player.muted; 
            });

            /*
             * Jump seeking
             */
            var jumpseek:Function = function(forwards:Boolean = true):void {
//                if (! _player.isPlaying()) return;
                var status:Status = _player.status;
                if (! status) return;
                var time:Number = status.time;
                var clip:Clip = _player.playlist.current;
                if (! clip) return;

                var targetTime:Number = time + (forwards ? 0.1 : -0.1) * clip.duration;
                if (targetTime < 0) {
                    targetTime = 0;
                }
                if (targetTime > (status.allowRandomSeek ? clip.duration : (status.bufferEnd - clip.bufferLength))) {
                    targetTime = status.allowRandomSeek ? clip.duration : (status.bufferEnd - clip.bufferLength - 5);
                }
                _player.seek(targetTime);
            };

			var jumpforward:Function  = function(event:KeyboardEvent):void { if ( ! event.ctrlKey ) jumpseek(); };
			var jumpbackward:Function = function(event:KeyboardEvent):void { if ( ! event.ctrlKey ) jumpseek(false); };
            addKeyListener(Keyboard.RIGHT, 	jumpforward);
			addKeyListener(76, 				jumpforward); 
			addKeyListener(Keyboard.LEFT, 	jumpbackward); 
			addKeyListener(72, 				jumpbackward);  
     

            stage.addEventListener(KeyboardEvent.KEY_DOWN,
                    function(event:KeyboardEvent):void {
                        log.debug("keyDown: " + event.keyCode);
                        if ( enteringFullscreenCb() ) return;
						if ( ! isKeyboardShortcutsEnabled() ) return;
                        if (_player.dispatchBeforeEvent(PlayerEvent.keyPress(event.keyCode))) {
                            _player.dispatchEvent(PlayerEvent.keyPress(event.keyCode));
                            if (_handlers[event.keyCode] != null) {
								for ( var i:int = 0; i < _handlers[event.keyCode].length; i++)
                                	_handlers[event.keyCode][i](event);
                            }
                        }
                    });
        }

		public function addKeyListener(keyCode:uint, func:Function):void
		{
			if ( _handlers[keyCode] == null )
				_handlers[keyCode] = [];
				
			_handlers[keyCode].push(func);
		}
		
		public function removeKeyListener(keyCode:uint, func:Function):void
		{
			if ( _handlers[keyCode] == null )
				return;
			
			if ( _handlers[keyCode].indexOf(func) == -1 )
				return;
				
			var handlers:Array = [];
			for ( var i:int; i < _handlers[keyCode].length; i++ )
				if ( _handlers[keyCode][i] != func )
					handlers.push(_handlers[keyCode][i]);
					
			_handlers[keyCode] = handlers;
		}
		
		public function isKeyboardShortcutsEnabled():Boolean
		{
			return _keyboardShortcutsEnabled;
		}
		
		public function setKeyboardShortcutsEnabled(enabled:Boolean):void
		{
			_keyboardShortcutsEnabled = enabled;
		}

    }
}