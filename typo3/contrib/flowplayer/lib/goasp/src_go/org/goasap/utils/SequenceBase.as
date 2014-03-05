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
	import flash.events.Event;
	import flash.utils.getQualifiedClassName;
	
	import org.goasap.PlayableBase;
	import org.goasap.errors.InstanceNotAllowedError;
	import org.goasap.events.GoEvent;
	import org.goasap.events.SequenceEvent;
	import org.goasap.interfaces.IPlayable;
	import org.goasap.managers.Repeater;	

	/**
	 * Dispatched when the sequence starts.
	 * @eventType org.goasap.events.START
	 */
	[Event(name="START", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when the sequence advances to its next step.
	 * @eventType org.goasap.events.SequenceEvent.ADVANCE
	 */
	[Event(name="ADVANCE", type="org.goasap.events.SequenceEvent")]

	/**
	 * Dispatched when the sequence is paused successfully.
	 * @eventType org.goasap.events.PAUSE
	 */
	[Event(name="PAUSE", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when the sequence is resumed successfully.
	 * @eventType org.goasap.events.RESUME
	 */
	[Event(name="RESUME", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched at the end the group if <code>repeater.cycles</code> is set to
	 * a value other than one, just before the sequence starts its next play cycle.
	 * @eventType org.goasap.events.CYCLE
	 */
	[Event(name="CYCLE", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when the sequence is manually stopped, which may also occur
	 * if one of its step instances is manually stopped outside the sequence.
	 * @eventType org.goasap.events.STOP
	 */
	[Event(name="STOP", type="org.goasap.events.GoEvent")]

	/**
	 * Dispatched when the sequence successfully finishes. (In SequenceCA this event
	 * is not fired until all custom-advanced steps have dispatched STOP or COMPLETE.)
	 * @eventType org.goasap.events.COMPLETE
	 */
	[Event(name="COMPLETE", type="org.goasap.events.GoEvent")]

	/**
	 * This base class should not be used directly, use it to build sequencing classes.
	 * 
	 * <p>When subclassing, follow the instructions in the comments of the protected
	 * methods to add a standard set of public getters and methods that work with the
	 * specific datatype of your SequenceStep subclass, if you create one. (This system
	 * is designed to work around the restrictiveness of overrides in AS3 which don't
	 * allow you to redefine datatypes.) See Sequence and SequenceCA for examples.</p>
	 * 
	 * @see Sequence
	 * @see SequenceCA
	 * 
	 * @author Moses Gunesch
	 */
	public class SequenceBase extends PlayableBase implements IPlayable {
		
		// -== Public Properties ==-
		
		/**
		 * The number of steps in the sequence.
		 */
		public function get length(): int {
			return (_steps ? _steps.length : 0);
		}
		
		/**
		 * The current play index of the sequence, starting a 0.
		 */
		public function get playIndex(): int {
			return _index;
		}

		/**
		 * Get or set the list of SequenceStep instances that defines the sequence. 
		 * 
		 * <p>
		 * When setting this property, each item must implement IPlayable that uses 
		 * PlayableBase play-state constants and dispatches STOP or COMPLETE when finished. 
		 * Each item is automatically wrapped in a SequenceStep if it is of any other IPlayable 
		 * type, such as a GoItem or PlayableGroup. Setting this property stops any sequence 
		 * play currently in progress.
		 * </p>
		 * @see #_getStepAt()
		 * @see #_getStepByID()
		 * @see #_getCurrentStep()
		 * @see #_getLastStep()
		 */
		public function get steps():Array {
			return _steps;
		}
		public function set steps(a:Array):void {
			if (_state!=STOPPED)
				stop();
			
			while (_steps.length > 0)
				_removeStepAt(_steps.length-1);
			
			for each (var item:Object in a)
				if (item is IPlayable)
					_addStep(item as IPlayable);
		}
		
		/**
		 * The sequence's Repeater instance, which may be used to make
		 * the sequence loop and play more than one time.
		 * 
		 * <p>The Repeater's cycles property can be set to an integer, or
		 * to Repeater.INFINITE or 0 to repeat indefinitely.</p> 
		 * 
		 * <pre>var seq:Sequence = new Sequence(tween1, tween2, tween3);
		 * seq.repeater.cycles = 2;
		 * seq.start();
		 * trace(seq.repeater.currentCycle); // output: 0
		 * 
		 * seq.skipTo(4); // moves to 2nd action in 2nd cycle
		 * trace(seq.repeater.currentCycle); // output: 1</pre>
		 * 
		 * <p>(The repeater property replaces the repeatCount and currentCount 
		 * parameters in earlier releases of SequenceBase).</p>
		 */
		public function get repeater(): Repeater {
			return _repeater;
		}
		
		
		// -== Protected Properties ==-
		
		/** @private */
		protected var _index: int = 0;
		
		/** @private */
		protected var _steps: Array;
		
		/** @private */
		protected var _repeater: Repeater;
		
		
		// -== Public Methods ==-
		
		/**
		 * Constructor.
		 * 
		 * @param items		Any number of IPlayable instances (e.g. LinearGo, PlayableGroup,
		 * 					SequenceStep) as separate arguments, or a single array of them.
		 */
		public function SequenceBase(...items) {
			super();
			var className:String = getQualifiedClassName(this);
			if (className.slice(className.lastIndexOf("::")+2) == "SequenceBase") {
				throw new InstanceNotAllowedError("SequenceBase");
			}
			_steps = new Array();
			if (items.length > 0) {
				steps = ((items[ 0 ] is Array) ? items[ 0 ] : items);
			}
			_repeater = new Repeater();
			_repeater.setParent(this);
		}
		
		// -== IPlayable implementation ==-
		
		/**
		 * Begins a sequence.
		 * 
		 * <p>If the group is active when this method is called, a <code>stop</code> call 
		 * is automated which will result in a GoEvent.STOP event being dispatched.</p>
		 * 
		 * @return Returns true unless there are no steps in the sequence.
		 */
		public function start() : Boolean {
			if (_steps.length==0)
				return false;
			stop();
			_state = PLAYING;
			_getCurrentStep().start();
			dispatchEvent(new GoEvent( GoEvent.START ));
			_playRetainer[ this ] = 1; // Developers - Important! Look up _playRetainer.
			return true;
		}

		/**
		 * Stops all activity and dispatches a GoEvent.STOP event.
		 * 
		 * @return Returns true unless sequence was already stopped.
		 */
		public function stop() : Boolean {
			if (_state==STOPPED || _steps.length==0)
				return false;
			_state = STOPPED;
			var stepState:String = _getCurrentStep().state; // TODO: this won't see the _trailingSteps state in SequenceCA
			_getCurrentStep().stop();
			if (_steps.length-_index > 1 || stepState!=STOPPED)
				dispatchEvent(new GoEvent( GoEvent.STOP ));
			else
				dispatchEvent(new GoEvent( GoEvent.COMPLETE ));
			_index = 0; 
			_repeater.reset();
			delete _playRetainer[ this ]; // Developers - Important! Look up _playRetainer.
			return true;
		}
		
		/**
		 * Pauses sequence play.
		 * 
		 * @return  Returns true unless sequence was unable to pause any children.
		 */
		public function pause() : Boolean {
			var prevState:String = _state;
			if (_state==STOPPED || _state==PAUSED)
				return false;
			_state = PAUSED;
			if (_getCurrentStep().pause()==false) {
				_state = prevState;
				return false;
			}
			dispatchEvent(new GoEvent( GoEvent.PAUSE ));
			return true;
		}
		
		/**
		 * Resumes previously-paused sequence play.
		 * 
		 * @return  Returns true unless sequence was unable to resume any children.
		 */
		public function resume() : Boolean {
			if (_state != PAUSED || _getCurrentStep().resume()==false) {
				return false;
			}
			_state = PLAYING;
			dispatchEvent(new GoEvent( GoEvent.RESUME));
			return true;
		}
		
		/**
		 * Stops the current step and skips to another step by sequence index.
		 * 
		 * @return Always returns true since the index is normalized to the sequence.
		 */
		public function skipTo(index : Number) : Boolean {
			_state = PLAYING;
			var prevIndex:int = _index;
			_index = _repeater.skipTo(_steps.length-1, index);
			if (_index==prevIndex) {
				(_getCurrentStep() as IPlayable).skipTo(0);
			}
			else {
				_steps[prevIndex].stop();  // _index is updated before this call so that onStepEvent ignores the item's STOP event.
				_getCurrentStep().start();
			}
			return true;
		}
		
		// -== Add hooks for these methods to your subclass like Sequence & SequenceCA ==-
		// These methods are broken out to allow subclasses to use exact typing for their SequenceStep class.
		
		/**
		 * Developers: Add a getter called <code>currentStep</code> to your subclass as in Sequence.
		 * 
		 * @return Developers: return the correct SequenceStep type for your subclass in your corresponding public method.
		 */
		protected function _getCurrentStep() : * {
			return (_steps.length==0 ? null : _steps[_index]);
		}
		
		/**
		 * Developers: Add a getter called <code>lastStep</code> to your subclass as in Sequence.
		 * 
		 * @return Developers: return the correct SequenceStep type for your subclass in your corresponding public method.
		 */
		protected function _getLastStep() : * {
			return (_steps.length==0 ? null : _steps[ _steps.length-1 ]);
		}
		/**
		 * Developers: Add a method called <code>getStepAt</code> to your subclass as in Sequence.
		 * 
		 * @param index	An array index starting at 0.
		 * @return		Developers: return the correct SequenceStep type for your subclass in your corresponding public method.
		 */
		protected function _getStepAt(index:int) : * {
			if (index >= _steps.length)
				return null;
			return (_steps[index] as SequenceStep);
		}
		
		/**
		 * Developers: Add a method called <code>getStepByID</code> to your subclass as in Sequence.
		 * 
		 * @param playableID	The step instance's playableID to search for.
		 * @return				 Developers: return the correct SequenceStep type for your subclass in your corresponding public method.
		 */
		protected function _getStepByID(playableID:*) : * {
			for each (var step:SequenceStep in _steps)
				if (step.playableID===playableID)
					return step;
			return null;
		}
		
		/**
		 * Developers: Add a method called <code>addStep</code> to your subclass as in Sequence.
		 * 
		 * <p>Drop the third parameter in your subclass' addStep method. Use it to be sure
		 * the correct type of wrapper is created, as in SequenceCA.</p> 
		 * 
		 * @param item			The playable item to add to the sequence.
		 * 						
		 * @param addToLastStep	If true is passed the item is added to the last
		 * 						existing SequenceStep in the steps array. This
		 * 						option should be used with individual items that
		 * 						you want added as children to the SequenceStep.
		 * 						If there are no steps yet this option ignored and
		 * 						a new step is created.
		 * 						
		 * @param stepTypeAsClass	Type for SequenceSteps. (Do not include this parameter in subclass addStep method.)
		 * 			
		 * @return New length of the steps array.
		 */
		protected function _addStep(item:IPlayable, addToLastStep:Boolean=false, stepTypeAsClass:*=null): int {
			if (item is SequenceStep && !addToLastStep) {
				return _addStepAt(_steps.length, item);
			}
			if (!stepTypeAsClass)
				stepTypeAsClass = SequenceStep;
			var step:SequenceStep = (addToLastStep && _steps.length > 0
									 ? (_steps.pop() as SequenceStep)
									 : new stepTypeAsClass() as SequenceStep);
			step.addChild(item);
			return _addStepAt(_steps.length, step, stepTypeAsClass); // adds listeners
		}
		
		/**
		 * Developers: Add a method called <code>addStep</code> to your subclass as in Sequence.
		 * 
		 * <p>Drop the third parameter in your subclass' addStep method. Use it to be sure
		 * the correct type of wrapper is created, as in SequenceCA.</p> 
		 * 
		 
		 * @param index			Position in the array starting at 0, or a negative 
		 * 						index like Array.splice.
		 * 					
		 * @param item			The playable item to splice into the sequence.
		 * 						
		 * @param stepTypeAsClass	Type for SequenceSteps. (Do not include this parameter in subclass addStep method.)
		 * 
		 * @return 				New length of the steps array.
		 */
		protected function _addStepAt(index:int, item:IPlayable, stepTypeAsClass:*=null): int {
			if (_state!=STOPPED)
				stop();
			if (!stepTypeAsClass)
				stepTypeAsClass = SequenceStep;
			var step:SequenceStep = (item is SequenceStep
									 ? item as SequenceStep
									 : new stepTypeAsClass(item) as SequenceStep);
			step.addEventListener(SequenceEvent.ADVANCE, onStepEvent, false, 0, true);
			step.addEventListener(GoEvent.STOP, onStepEvent, false, 0, true);
			_steps.splice(index, 0, step);
			return _steps.length;
		}

		/**
		 * Developers: Add a method called <code>addStep</code> to your subclass as in Sequence.
		 * 
		 * @param index	Position in the array starting at 0, or a negative 
		 * 				index like Array.splice.
		 * 					
		 * @return 		Developers: return the correct SequenceStep type for your subclass in your corresponding public method.
		 */
		protected function _removeStepAt(index:int) : * {
			if (_state!=STOPPED)
				stop();
			var step:SequenceStep = _steps.splice(index, 1) as SequenceStep;
			step.removeEventListener(SequenceEvent.ADVANCE, onStepEvent);
			step.removeEventListener(GoEvent.STOP, onStepEvent);
			return step;
		}
		
		// -== Protected Methods ==-
		
		/**
		 * @private
		 * Internal handler for step advance.
		 * 
		 * @param event		SequenceEvent dispatched by child item. 
		 */
		protected function onStepEvent(event : Event) : void {
			// A stop() call to the sequence results in step dispatching STOP, which would recurse here.
			if (_state==STOPPED || event.target!=_steps[_index])
				return;
			
			// Only occurs if the SequenceItem is manually stopped outside of this manager.
			if (event.type==GoEvent.STOP) {
				stop();
				return;
			}
			
			// Normal step advance
			if (event.type==SequenceEvent.ADVANCE) {
				if (_steps.length-_index == 1) {
					complete();
				}
				else {
					advance();
				}
			}
		}
		
		/**
		 * @private
		 * Internal handler for group completion.
		 */
		protected function advance() : void {
			if (_steps.length-_index > 1) {
				_index ++; // this changes currentStep value in following code
				_getCurrentStep().start();
			}
			dispatchEvent(new SequenceEvent( SequenceEvent.ADVANCE ));
		}
		
		/**
		 * @private
		 * Internal handler for group completion.
		 */
		protected function complete() : void {
			// order-sensitive
			if (_repeater.next()) {
				dispatchEvent(new GoEvent( GoEvent.CYCLE ));
				_index = 0;
				_getCurrentStep().start();
			}
			else {
				_index = _steps.length - 1;
				stop();
			}
		}
	}
}