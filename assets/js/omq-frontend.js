/*!
 * Ofnoa Marquee — front-end engine.
 * Measures one content group, clones it enough times to fill the viewport and
 * derives a constant px/second duration. CSS does the actual animating.
 */
( function ( window, document ) {
	'use strict';

	var instances = [];
	var scrollState = { last: 0, dir: 1, timer: null, boosting: false };

	function num( value, fallback ) {
		var n = parseFloat( value );
		return isNaN( n ) ? fallback : n;
	}

	function reducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	function parseConfig( el ) {
		var cfg = {};
		try {
			cfg = JSON.parse( el.getAttribute( 'data-omq' ) || '{}' );
		} catch ( e ) {
			cfg = {};
		}
		return cfg;
	}

	function Instance( el ) {
		this.el = el;
		this.cfg = parseConfig( el );
		this.vertical = 1 === this.cfg.vertical;
		this.rows = [].slice.call( el.querySelectorAll( '.omq__row' ) );
		this.tracks = [];
		this.ready = false;
		this.raf = null;
	}

	Instance.prototype.clearClones = function () {
		var clones = this.el.querySelectorAll( '.omq__group[data-omq-clone]' );
		for ( var i = 0; i < clones.length; i++ ) {
			clones[ i ].parentNode.removeChild( clones[ i ] );
		}
	};

	Instance.prototype.build = function () {
		var self = this;
		var el = this.el;

		if ( el.classList.contains( 'omq--rm' ) && reducedMotion() ) {
			el.classList.add( 'is-ready', 'is-static' );
			return;
		}

		var cs = window.getComputedStyle( el );
		var baseSpeed = Math.max( 1, num( cs.getPropertyValue( '--omq-speed' ), 60 ) );
		var startDelay = num( cs.getPropertyValue( '--omq-delay' ), 0 ); // ms
		var vertical = this.vertical;

		this.clearClones();
		this.tracks = [];

		this.rows.forEach( function ( row, index ) {
			var track = row.querySelector( '.omq__track' );
			var group = track ? track.querySelector( '.omq__group' ) : null;
			var viewport = row.querySelector( '.omq__viewport' );
			if ( ! track || ! group || ! viewport ) {
				return;
			}

			var tcs = window.getComputedStyle( track );
			var gap = num( vertical ? tcs.rowGap : tcs.columnGap, 0 );
			var rect = group.getBoundingClientRect();
			var size = vertical ? rect.height : rect.width;

			if ( size < 1 ) {
				return;
			}

			// A theme rule forcing `img { width:100% }` turns a max-content track
			// into a runaway layout. Warn instead of animating something absurd.
			if ( size > 50000 && window.console && console.warn ) {
				console.warn(
					'[Ofnoa Marquee] Row measured ' + Math.round( size ) + 'px — a theme or builder rule is probably overriding the logo size. Check for `img { width:100%; height:auto }` rules applying to .omq__img.'
				);
			}

			var shift = size + gap;
			var vrect = viewport.getBoundingClientRect();
			var vsize = vertical ? vrect.height : vrect.width;
			var copies = Math.max( 2, Math.ceil( ( vsize + shift ) / shift ) + 1 );

			var frag = document.createDocumentFragment();
			for ( var c = 1; c < copies; c++ ) {
				var clone = group.cloneNode( true );
				clone.setAttribute( 'data-omq-clone', '1' );
				clone.setAttribute( 'aria-hidden', 'true' );
				var links = clone.querySelectorAll( 'a' );
				for ( var l = 0; l < links.length; l++ ) {
					links[ l ].setAttribute( 'tabindex', '-1' );
				}
				frag.appendChild( clone );
			}
			track.appendChild( frag );

			var speed = baseSpeed;
			if ( index > 0 && self.cfg.rowsSpeed ) {
				speed = baseSpeed * ( self.cfg.rowsSpeed / 100 );
			}
			speed = Math.max( 1, speed );

			var duration = Math.max( 0.4, shift / speed );
			var offset = index > 0 ? duration * ( ( self.cfg.rowsOffset || 0 ) / 100 ) : 0;

			track.style.setProperty( '--omq-shift', shift + 'px' );
			track.style.setProperty( '--omq-dur', duration + 's' );
			track.style.animationDelay = ( startDelay / 1000 - offset ) + 's';

			var base = ( window.getComputedStyle( track ).getPropertyValue( '--omq-adir' ) || 'normal' ).trim();

			self.tracks.push( {
				el: track,
				duration: duration,
				base: base || 'normal'
			} );
		} );

		el.classList.add( 'is-built' );

		if ( this.cfg.lazy ) {
			this.observe();
		} else {
			el.classList.add( 'is-ready' );
		}

		this.ready = true;
	};

	Instance.prototype.observe = function () {
		var el = this.el;
		if ( ! ( 'IntersectionObserver' in window ) ) {
			el.classList.add( 'is-ready' );
			return;
		}
		if ( this.io ) {
			this.io.disconnect();
		}
		this.io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						el.classList.add( 'is-ready' );
					} else {
						el.classList.remove( 'is-ready' );
					}
				} );
			},
			{ rootMargin: '120px 0px' }
		);
		this.io.observe( el );
	};

	Instance.prototype.setSpeedFactor = function ( factor ) {
		this.tracks.forEach( function ( t ) {
			t.el.style.animationDuration = ( t.duration / factor ) + 's';
		} );
	};

	Instance.prototype.flip = function ( flipped ) {
		this.tracks.forEach( function ( t ) {
			var dir = t.base;
			if ( flipped ) {
				dir = 'normal' === dir ? 'reverse' : 'normal';
			}
			t.el.style.setProperty( '--omq-adir', dir );
		} );
	};

	Instance.prototype.bind = function () {
		var self = this;
		var el = this.el;
		var cfg = this.cfg;

		if ( ! cfg.pauseHover && cfg.hoverSpeed && 100 !== cfg.hoverSpeed ) {
			el.addEventListener( 'mouseenter', function () {
				self.setSpeedFactor( cfg.hoverSpeed / 100 );
			} );
			el.addEventListener( 'mouseleave', function () {
				self.setSpeedFactor( 1 );
			} );
		}

		if ( cfg.clickPause ) {
			el.addEventListener( 'click', function ( event ) {
				if ( event.target.closest( 'a' ) ) {
					return;
				}
				el.classList.toggle( 'is-paused' );
			} );
		}
	};

	Instance.prototype.refresh = function () {
		this.build();
	};

	function waitForAssets( el, callback ) {
		var images = [].slice.call( el.querySelectorAll( 'img' ) );
		var pending = 0;
		var done = false;

		function finish() {
			if ( done ) {
				return;
			}
			done = true;
			callback();
		}

		images.forEach( function ( img ) {
			if ( img.complete && img.naturalWidth ) {
				return;
			}
			pending++;
			var onDone = function () {
				img.removeEventListener( 'load', onDone );
				img.removeEventListener( 'error', onDone );
				pending--;
				if ( pending <= 0 ) {
					finish();
				}
			};
			img.addEventListener( 'load', onDone );
			img.addEventListener( 'error', onDone );
		} );

		if ( pending <= 0 ) {
			finish();
		}

		// Safety net — never leave a marquee unbuilt.
		window.setTimeout( finish, 2500 );
	}

	function init( el ) {
		if ( ! el || el.omqInstance ) {
			return el ? el.omqInstance : null;
		}
		var inst = new Instance( el );
		el.omqInstance = inst;
		instances.push( inst );

		waitForAssets( el, function () {
			inst.build();
			inst.bind();
			observeResize( inst );
		} );

		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
				if ( inst.ready ) {
					inst.refresh();
				}
			} );
		}

		return inst;
	}

	function observeResize( inst ) {
		if ( ! ( 'ResizeObserver' in window ) ) {
			return;
		}
		var timer = null;
		var first = true;
		var ro = new ResizeObserver( function () {
			if ( first ) {
				first = false;
				return;
			}
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				inst.refresh();
			}, 200 );
		} );
		ro.observe( inst.el );
		inst.ro = ro;
	}

	function initAll( scope ) {
		var root = scope || document;
		var nodes = root.querySelectorAll( '.omq' );
		for ( var i = 0; i < nodes.length; i++ ) {
			init( nodes[ i ] );
		}
	}

	function onScroll() {
		var y = window.pageYOffset || document.documentElement.scrollTop;
		var dir = y > scrollState.last ? 1 : -1;
		scrollState.last = y;

		var needFlip = [];
		var needBoost = [];

		instances.forEach( function ( inst ) {
			if ( inst.cfg.reverseScroll ) {
				needFlip.push( inst );
			}
			if ( inst.cfg.scrollBoost && inst.cfg.scrollBoost > 100 ) {
				needBoost.push( inst );
			}
		} );

		if ( needFlip.length && dir !== scrollState.dir ) {
			scrollState.dir = dir;
			needFlip.forEach( function ( inst ) {
				inst.flip( dir < 0 );
			} );
		}

		if ( needBoost.length ) {
			if ( ! scrollState.boosting ) {
				scrollState.boosting = true;
				needBoost.forEach( function ( inst ) {
					inst.setSpeedFactor( inst.cfg.scrollBoost / 100 );
				} );
			}
			window.clearTimeout( scrollState.timer );
			scrollState.timer = window.setTimeout( function () {
				scrollState.boosting = false;
				needBoost.forEach( function ( inst ) {
					inst.setSpeedFactor( 1 );
				} );
			}, 220 );
		}
	}

	function ready( fn ) {
		if ( 'loading' !== document.readyState ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		initAll();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'orientationchange', function () {
			instances.forEach( function ( inst ) {
				inst.refresh();
			} );
		} );
	} );

	// Elementor / block editor friendly.
	window.addEventListener( 'elementor/frontend/init', function () {
		window.setTimeout( initAll, 50 );
	} );

	window.OfnoaMarquee = {
		init: init,
		initAll: initAll,
		instances: instances,
		refreshAll: function () {
			instances.forEach( function ( inst ) {
				inst.refresh();
			} );
		},
		destroy: function ( el ) {
			if ( ! el || ! el.omqInstance ) {
				return;
			}
			var inst = el.omqInstance;
			if ( inst.io ) {
				inst.io.disconnect();
			}
			if ( inst.ro ) {
				inst.ro.disconnect();
			}
			inst.clearClones();
			var i = instances.indexOf( inst );
			if ( i > -1 ) {
				instances.splice( i, 1 );
			}
			delete el.omqInstance;
		}
	};
} )( window, document );
