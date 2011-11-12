/**
 * Copyright (c) 2007 Moses Gunesch
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package org.goasap.utils {
	import flash.utils.Dictionary;
	
	import org.goasap.PlayableBase;
	import org.goasap.events.GoEvent;
	import org.goasap.interfaces.IPlayable;
	import org.goasap.managers.Repeater;	

	/**
	 * Dispatched when the group starts.
	 * @eventType org.goasap.events.START
	 */
	[Event(name="START", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when the group is paused successfully.
	 * @eventType org.goasap.events.PAUSE
	 */
	[Event(name="PAUSE", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when the group is resumed successfully.
	 * @eventType org.goasap.events.RESUME
	 */
	[Event(name="RESUME", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched at the end the group if <code>repeater.cycles</code> is set to
	 * a value other than one, just before the group starts its next play cycle.
	 * @eventType org.goasap.events.CYCLE
	 */
	[Event(name="CYCLE", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched if the group is manually stopped.
	 * @eventType org.goasap.events.STOP
	 */
	[Event(name="STOP", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched after all children have dispatched a STOP or COMPLETE event.
	 * @eventType org.goasap.events.COMPLETE
	 */
	[Event(name="COMPLETE", type="org.goasap.events.GoEvent")]


	/**
	 * Batch-play a set of items and receive an event when all of them have finished.
	 * 
	 * <p>PlayableGroup accepts any IPlayable for its children, which can include
	 * tweens, other groups, sequences and so forth. The group listens for both 
	 * GoEvent.STOP and GoEvent.COMPLETE events from its children, either of which 
	 * are counted toward group completion.</p>
	 * 
	 * <p>The <code>repeater</code> property of PlayableGroup allows you to loop play 
	 * any number of times, or indefinitely by setting its cycles to Repeater.INFINITE. 
	 * GoEvent.CYCLE is dispatched on each loop and GoEvent.COMPLETE when finished.
	 * Other events dispatched include the GoEvent types START, STOP, PAUSE, and RESUME.</p>
	 * 
	 * @author Moses Gunesch
	 */
	public class PlayableGroup extends PlayableBase implements IPlayable {
		
		// -== Public Properties ==-
		
		/**
		 * Get or set the children array. Only IPlayable items are stored. Note that 
		 * unlike the methods <code>addChild</code> and <code>removeChild</code>, 
		 * setting this property will stop any group play currently in progress.
		 */
		public function get children():Array {
			var a:Array = [];
			for (var item:Object in _children)
				a.push(item);
			return a;
		}
		public function set children(a:Array):void {
			if (_listeners > 0)
				stop();
			for each (var item:Object in a)
				if (item is IPlayable)
					addChild(item as IPlayable);
		}
		
		/**
		 * The groups's Repeater instance, which may be used to make
		 * it loop and play more than one time.
		 * 
		 * <p>The Repeater's cycles property can be set to an integer, or
		 * to Repeater.INFINITE or 0 to repeat indefinitely.</p> 
		 * 
		 * <pre>
		 * var group:PlayableGroup = new PlayableGroup(tween1, tween2, tween3);
		 * group.repeater.cycles = 2;
		 * group.start();
		 * trace(group.repeater.currentCycle); // output: 0
		 * </pre>
		 */
		public function get repeater(): Repeater {
			return _repeater;
		}
		
		/**
		 * Determines the number of children currently being monitored 
		 * for completion by the group.
		 */
		public function get listenerCount() : uint {
			return _listeners;
		}

		// -== Protected Properties ==-
		
		/** @private */
		protected var _children: Dictionary = new Dictionary();
		
		/** @private */
		protected var _listeners: uint = 0;
		
		/** @private */
		protected var _repeater: Repeater;
		
		// -== Public Methods ==-
		
		/**
		 * Constructor.
		 * 
		 * @param items	Any number of IPlayable items as separate arguments, 
		 * 					or a single array of them.
		 */
		public function PlayableGroup(...items) {
			super();
			if (items.length > 0)
				this.children = ((items[ 0 ] is Array) ? items[ 0 ] : items);
			_repeater = new Repeater();
			_repeater.setParent(this);
		}
		
		/**
		 * Searches for a child with the specified playableID.
		 * 
		 * @param playableID	The item playableID to search for.
		 * @param deepSearch	If child is not found in the group, this option runs a 
		 * 						recursive search on any children that are PlayableGroup.
		 * @return				The SequenceStep with the matching playableID.
		 */
		public function getChildByID(playableID:*, deepSearch:Boolean=true):IPlayable {
			for (var item:Object in _children)
				if ((item as IPlayable).playableID===playableID)
					return (item as IPlayable);
			if (deepSearch) {
				for (item in _children) {
					if (item is PlayableGroup) {
						var match:IPlayable = ((item as PlayableGroup).getChildByID(playableID, true));
						if (match) { return (match as IPlayable); }
					}
				}
			}
			return null;
		}
		
		/**
		 * Adds a single IPlayable to the children array (duplicates are rejected) and
		 * syncs up the group and child play-states based on various conditions.
		 * 
		 * <p>If both the group and the item being added are STOPPED, the item is simply
		 * added to the children list.</p>
		 * 
		 * <p>If both items are PAUSED or PLAYING (including PLAYING_DELAY for children), 
		 * the child is actively added to the group during play and will be monitored for 
		 * completion along with others.</p>
		 * 
		 * <p>In other cases where the child's state mismatches the group's state, there
		 * are several behaviors available. Normally if the second parameter <code>adoptChildState</code>
		 * is left false, the child's mismatched state will be updated to match the group's
		 * state. This can result in it being stopped, paused, or started/resumed and monitored 
		 * for completion along with other children. Passing true for <code>adoptChildState</code> 
		 * results in updating the group's state to match the child's. This option could be used, for 
		 * example, if you wanted to build a group of already-playing items without disrupting their
		 * play cycle with a start() call to the group.</p>
		 * 
		 * @param item				Any instance that implements IPlayable and uses PlayableBase's play-state constants.
		 * @param adoptChildState	Makes this group change its play-state to match the state of the new child.
		 * @return Success.
		 */
		public function addChild(item:IPlayable, adoptChildState:Boolean=false): Boolean {
			if (_children[ item ])
				return false;
			if (item.state!=_state) { // Resolve an obvious mismatched play state...
									  // Normally states are both STOPPED, so the following ugliness is rarely used.
				var primary:IPlayable = (adoptChildState ? item : this);
				var primaryPlaying:Boolean = (primary.state==PLAYING || primary.state==PLAYING_DELAY);
				var secondary:IPlayable = (adoptChildState ? this : item);
				var secondaryPlaying:Boolean = (secondary.state==PLAYING || secondary.state==PLAYING_DELAY);
				if ( !(primaryPlaying && secondaryPlaying) ) // Less obvious, but treat PLAYING_DELAY & PLAYING as "playing."
				{
					switch (primary.state) {
						case STOPPED:
							secondary.stop();
							break;
						case PAUSED: // This case works either way. Both START & PAUSE events will result.
							if (secondary.state==STOPPED)
								secondary.start();
							secondary.pause();
							break;
						case PLAYING: 
						case PLAYING_DELAY:
							if (secondary.state==PAUSED)
								secondary.resume();
							else if (secondary.state==STOPPED) {
								if (adoptChildState) {
									_state = PLAYING; // Group adopts child playing state
									dispatchEvent(new GoEvent( GoEvent.START));
								} 
								else {
									secondary.start();
								}
							}
							break;
					}
				}
			}
			// Saved until after possible play-state changes. Now we can base listening on this group's state.
			_children[ item ] = false;
			if (_state!=STOPPED)
				listenTo(item);
			return true;
		}
		
		/**
		 * Removes a single IPlayable from the children array.
		 * 
		 * <p>Note that if play is in progress when a child is added it does not
		 * interrupt play and the child is monitored for completion along with
		 * others.</p>
		 * 
		 * @param item		Any instance that implements IPlayable and uses PlayableBase's play-state constants.
		 * @return Success.
		 */
		public function removeChild(item:IPlayable): Boolean {
			var v:* = _children[ item ];
			if (v===null)
				return false;
			if (v===true)
				unListenTo( item );
			delete _children[ item ];
			return true;
		}
		
		/**
		 * Test whether any child has a particular play state, based on 
		 * the int constants in the PlayableBase class.
		 * 
		 * <pre>
		 * // Example: resume a paused group
		 * if ( myGroup.anyChildHasState(PlayableBase.PAUSED) ) {
		 *     myGroup.resume();
		 * }
		 * </pre>
		 */
		public function anyChildHasState(state:String): Boolean {
			for (var item:Object in _children)
				if ((item as IPlayable).state==state)
					return true;
			return false;
		}
		
		// -== IPlayable implementation ==-
		
		/**
		 * Calls start on all children. 
		 * 
		 * <p>If the group is active when this method is called, a <code>stop</code> call 
		 * is automated which will result in a GoEVent.STOP event being dispatched.</p>
		 * 
		 * @return Returns true if any child in the group starts successfully.
		 */
		public function start() : Boolean {
			stop();
			var r:Boolean = false;
			for (var item:Object in _children) {
				var started:Boolean = (item as IPlayable).start();
				if (started)
					listenTo(item as IPlayable);
				r = (started || r);
			}
			if (!r) return false; // all starts failed
			_state = PLAYING;
			dispatchEvent(new GoEvent( GoEvent.START));
			_playRetainer[ this ] = 1; // Developers - Important! Look up _playRetainer.
			return true;
		}

		/**
		 * If the group is active, this method stops all child items and 
		 * dispatches a GoEvent.STOP event.
		 * 
		 * @return Returns true only if all children in the group stop successfully.
		 */
		public function stop() : Boolean {
			if (_state == STOPPED)
				return false;
			_state = STOPPED;
			_repeater.reset();
			delete _playRetainer[ this ]; // Developers - Important! Look up _playRetainer.
			if (_listeners==0) {
				dispatchEvent(new GoEvent( GoEvent.COMPLETE ));
				return true;
			}
			var r:Boolean = true;
			for (var item:Object in _children) {
				unListenTo(item as IPlayable);
				r = ((item as IPlayable).stop() && r);
			}
			dispatchEvent(new GoEvent( GoEvent.STOP ));
			return r;
		}
		
		/**
		 * Calls <code>pause</code> on all children.
		 * 
		 * @return  Returns true only if all playing children in the group paused successfully
		 * 			and at least one child was paused.
		 */
		public function pause() : Boolean {
			if (_state!= PLAYING)
				return false;
			var r:Boolean = true;
			var n:uint = 0;
			for (var item:Object in _children) {
				var success:Boolean = (item as IPlayable).pause();
				if (success) n++;
				r = (r && success);
			}
			if (n>0) {
				_state = PAUSED; // state should reflect that at least one item was paused,
								  // while return value may indicate that not all pause calls succeeded.
				dispatchEvent(new GoEvent( GoEvent.PAUSE ));
			}
			return (n>0 && r);
		}
		
		/**
		 * Calls <code>resume</code> on all children.
		 * 
		 * @return	Returns true only if all paused children in the group resumed successfully
		 * 			and at least one child was resumed.
		 */
		public function resume() : Boolean {
			if (_state!= PAUSED)
				return false;
			var r:Boolean = true;
			var n:uint = 0;
			for (var item:Object in _children) {
				var success:Boolean = (item as IPlayable).resume();
				if (success) n++;
				r = (r && success);
			}
			if (n>0) {
				_state = PLAYING; // state should reflect that at least one item was resumed,
								  // while return value may indicate that not all resume calls succeeded.
				dispatchEvent(new GoEvent( GoEvent.RESUME ));
			}
			return (n>0 && r);
		}
		
		/**
		 * Calls <code>skipTo</code> on all children.
		 * 
		 * @return	Returns true only if all children in the group skipTo the position successfully
		 * 			and at least one child was affected.
		 */
		public function skipTo(position : Number) : Boolean {
			var r:Boolean = true;
			var n:uint = 0;
			position = _repeater.skipTo(_repeater.cycles, position); // TODO: TEST
			for (var item:Object in _children) {
				r = ((item as IPlayable).skipTo(position) && r);
				listenTo(item as IPlayable);
				n++;
			}
			_state = (r ? PLAYING : STOPPED);
			return (n>0 && r);
		}
		
		// -== Protected Methods ==-
		
		/**
		 * @private
		 * Internal handler for item completion.
		 * @param event		GoEvent dispatched by child item. 
		 */
		protected function onItemEnd(event:GoEvent) : void {
			unListenTo(event.target as IPlayable);
			if (_listeners==0) {
				complete();
			}
		}
		
		/**
		 * @private
		 * Internal handler for group completion.
		 */
		protected function complete() : void {
			if (_repeater.next()) {
				dispatchEvent(new GoEvent( GoEvent.CYCLE ));
				for (var item:Object in _children) {
					var started:Boolean = (item as IPlayable).start();
					if (started)
						listenTo(item as IPlayable);
				}
			}
			else {
				stop();
			}
		}

		/**
		 * @private
		 * Internal. Listen for item completion, keeping tight track of listeners.
		 * @param item	Any instance that extends IPlayable (IPlayable itself should not be used directly).
		 */
		protected function listenTo(item:IPlayable) : void {
			if (_children[ item ] === false) {
				item.addEventListener(GoEvent.STOP, onItemEnd, false, 0, true);
				item.addEventListener(GoEvent.COMPLETE, onItemEnd, false, 0, true);
				_children[ item ] = true;
				_listeners++;
			}
		}
		
		/**
		 * @private
		 * Internal. Stop listening for item completion.
		 * @param item	Any instance that extends IPlayable (IPlayable itself should not be used directly).
		 * @return Number of completion listeners remaining.
		 */
		protected function unListenTo(item:IPlayable) : void {
			if (_children[ item ] === true) {
				item.removeEventListener(GoEvent.STOP, onItemEnd);
				item.removeEventListener(GoEvent.COMPLETE, onItemEnd);
				_children[ item ] = false;
				_listeners--;
			}
		}
	}
}