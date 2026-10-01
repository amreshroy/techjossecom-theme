/**
 * TechJosse Commerce - front-end behaviour.
 *
 * Vanilla JavaScript, no dependencies. Provides:
 *  - slide-in panels (mobile menu, mini cart) and the category mega menu
 *  - AJAX live product search with keyboard support
 *  - AJAX add to cart on simple product pages
 *  - the cash on delivery quick order popup (shipping options by AJAX)
 *
 * @package TechJosse_Commerce
 */

( function () {
	'use strict';

	var data = window.techjossecomData || {};

	var strings = data.i18n || {
		searching: 'Searching...',
		noResults: 'No products found.',
		error: 'Something went wrong. Please try again.',
		required: 'Please fill in all required fields.'
	};

	/*
	 * The localised strings are the source, but a site still running a cached
	 * copy of an older script would hand back an object without the newer keys
	 * and leave a message reading "undefined". Filling the gap keeps the wording
	 * intact either way.
	 */
	strings = Object.assign( {
		chooseOptions: 'Please choose %s before adding this to your cart.'
	}, strings );

	/** Shorthand helpers. */
	function qs( selector, context ) {
		return ( context || document ).querySelector( selector );
	}

	function qsa( selector, context ) {
		return Array.prototype.slice.call( ( context || document ).querySelectorAll( selector ) );
	}

	/** Debounce a function. */
	function debounce( fn, wait ) {
		var timer;

		return function () {
			var args = arguments;
			var self = this;

			clearTimeout( timer );
			timer = setTimeout( function () {
				fn.apply( self, args );
			}, wait || 300 );
		};
	}

	/** Format a price using the store settings. */
	function formatPrice( amount ) {
		var price = data.priceFormat || {};
		var decimals = typeof price.decimals === 'number' ? price.decimals : 2;
		var fixed = ( Math.round( ( parseFloat( amount ) || 0 ) * Math.pow( 10, decimals ) ) / Math.pow( 10, decimals ) ).toFixed( decimals );
		var parts = fixed.split( '.' );
		var separator = price.thousandSeparator === undefined ? ',' : price.thousandSeparator;
		var decimalSeparator = price.decimalSeparator === undefined ? '.' : price.decimalSeparator;
		var whole = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, separator );
		var value = parts[ 1 ] ? whole + decimalSeparator + parts[ 1 ] : whole;
		var symbol = price.symbol || data.currencySymbol || '';

		switch ( price.position ) {
			case 'right':
				return value + symbol;
			case 'right_space':
				return value + ' ' + symbol;
			case 'left_space':
				return symbol + ' ' + value;
			default:
				return symbol + value;
		}
	}

	/* ------------------------------------------------------ slide panels -- */

	var overlay = qs( '[data-tj-overlay]' );
	var openPanels = [];

	function needsOverlay( element ) {
		return element.classList.contains( 'tj-drawer' ) || element.classList.contains( 'tj-mobile-menu' );
	}

	function showOverlay() {
		if ( ! overlay ) {
			return;
		}

		overlay.hidden = false;
		window.requestAnimationFrame( function () {
			overlay.classList.add( 'is-open' );
		} );
	}

	function hideOverlay() {
		document.body.classList.remove( 'tj-locked' );

		if ( ! overlay ) {
			return;
		}

		overlay.classList.remove( 'is-open' );

		// Restore the attribute once the fade-out finished, so the attribute
		// and the class never disagree. While it is invisible the overlay has
		// pointer-events: none, so it cannot block the page in the meantime.
		window.setTimeout( function () {
			if ( ! overlay.classList.contains( 'is-open' ) ) {
				overlay.hidden = true;
			}
		}, 220 );
	}

	function openPanel( id ) {
		var panel = typeof id === 'string' ? document.getElementById( id ) : id;

		if ( ! panel ) {
			return;
		}

		panel.hidden = false;

		window.requestAnimationFrame( function () {
			panel.classList.add( 'is-open' );
		} );

		if ( needsOverlay( panel ) ) {
			document.body.classList.add( 'tj-locked' );
			showOverlay();
		}

		if ( openPanels.indexOf( id ) === -1 ) {
			openPanels.push( id );
		}

		qsa( '[data-tj-toggle="' + id + '"]' ).forEach( function ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'true' );
		} );
	}

	function closePanel( id ) {
		var panel = typeof id === 'string' ? document.getElementById( id ) : id;

		if ( ! panel ) {
			return;
		}

		panel.classList.remove( 'is-open' );

		window.setTimeout( function () {
			if ( ! panel.classList.contains( 'is-open' ) ) {
				panel.hidden = true;
			}
		}, 260 );

		openPanels = openPanels.filter( function ( item ) {
			return item !== id;
		} );

		qsa( '[data-tj-toggle="' + id + '"]' ).forEach( function ( trigger ) {
			trigger.setAttribute( 'aria-expanded', 'false' );
		} );

		if ( ! openPanels.length ) {
			hideOverlay();
		}
	}

	function closeAllPanels() {
		openPanels.slice().forEach( function ( id ) {
			closePanel( id );
		} );
	}

	function isPanelOpen( id ) {
		var panel = document.getElementById( id );

		return !! panel && ! panel.hidden && panel.classList.contains( 'is-open' );
	}

	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '[data-tj-toggle]' );

		if ( toggle ) {
			event.preventDefault();

			var target = toggle.getAttribute( 'data-tj-toggle' );

			if ( isPanelOpen( target ) ) {
				closePanel( target );
			} else {
				openPanel( target );
			}

			return;
		}

		var closer = event.target.closest( '[data-tj-close]' );

		if ( closer ) {
			event.preventDefault();
			closePanel( closer.getAttribute( 'data-tj-close' ) );

			return;
		}

		var cartLink = event.target.closest( '[data-tj-open-cart]' );

		if ( cartLink ) {
			var drawer = document.getElementById( 'tj-mini-cart' );

			if ( drawer ) {
				event.preventDefault();
				openPanel( 'tj-mini-cart' );
			}
		}
	} );

	if ( overlay ) {
		overlay.addEventListener( 'click', closeAllPanels );
	}

	// The mini cart drawer is a transparent, full-viewport shell sitting above
	// the overlay, so a click on the dimmed area lands on the shell itself and
	// never reaches the overlay handler above. Treat a click on the bare shell
	// (the panel and its contents are children, so they do not match) as a
	// request to close.
	var panelShells = qsa( '.tj-drawer, .tj-mobile-menu' );

	document.addEventListener( 'click', function ( event ) {
		for ( var i = 0; i < panelShells.length; i++ ) {
			if ( event.target === panelShells[ i ] ) {
				closePanel( panelShells[ i ].id );

				return;
			}
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && openPanels.length ) {
			closeAllPanels();
		}
	} );

	// The category panel is a fly-out, so on a mouse it should follow the
	// pointer rather than wait for a second click. Clicking still works, so
	// touch and keyboard keep the behaviour they had.
	qsa( '.tj-catmenu' ).forEach( function ( catmenu ) {
		var trigger = qs( '[data-tj-toggle]', catmenu );

		if ( ! trigger ) {
			return;
		}

		var panelId = trigger.getAttribute( 'data-tj-toggle' );
		var closeTimer = null;

		function cancelClose() {
			if ( closeTimer ) {
				window.clearTimeout( closeTimer );
				closeTimer = null;
			}
		}

		catmenu.addEventListener( 'mouseenter', function () {
			cancelClose();
			openPanel( panelId );
		} );

		// The panel and the fly-out are positioned outside the button box, so the
		// pointer has to travel across the gap between them. A short delay keeps
		// the panel open while it crosses instead of snapping shut underneath.
		catmenu.addEventListener( 'mouseleave', function () {
			cancelClose();
			closeTimer = window.setTimeout( function () {
				closeTimer = null;
				closePanel( panelId );
			}, 180 );
		} );

		// Re-entering before the timer fires must not leave a stale close pending,
		// and moving the pointer away while a link is focused should still close.
		catmenu.addEventListener( 'focusin', function () {
			cancelClose();
			openPanel( panelId );
		} );

		catmenu.addEventListener( 'focusout', function ( event ) {
			if ( ! catmenu.contains( event.relatedTarget ) ) {
				closePanel( panelId );
			}
		} );
	} );

	// The nav bar is deliberately not sticky: it scrolls away with the page, so
	// nothing here has to measure the header height or watch the scroll
	// position. The header above it sticks on its own.

	// Mobile category accordion. A parent that has children renders a chevron
	// and its list folded, so the drawer starts compact; tapping the chevron
	// reveals the children and flips the arrow, tapping again folds them back.
	// aria-expanded carries the state so the arrow and the list cannot drift
	// apart, and each row folds independently of the others.
	qsa( '.tj-mobile-cat-toggle' ).forEach( function ( toggle ) {
		toggle.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			var list = document.getElementById( toggle.getAttribute( 'aria-controls' ) );

			if ( ! list ) {
				return;
			}

			var expanded = 'true' === toggle.getAttribute( 'aria-expanded' );

			toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			list.hidden = expanded;
		} );
	} );

	// The bottom bar search shortcut. It carries data-tj-toggle as well, so the
	// shared panel handler below does the opening, the Escape key and the
	// aria-expanded bookkeeping; this only has to move the caret into the field
	// that is actually visible on a phone.
	//
	// It used to focus the header input, which is present in the DOM at every
	// width but only displayed from 768px up, so on a phone the tap focused an
	// invisible field and looked like a dead button.
	qsa( '[data-tj-search-focus]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var bar = qs( '#' + button.getAttribute( 'aria-controls' ) );
			var field = bar ? qs( '[data-tj-search-input]', bar ) : null;

			if ( ! field ) {
				return;
			}

			// Let the bar finish sliding down first, otherwise the on-screen
			// keyboard can open against the pre-animation position.
			window.setTimeout( function () {
				field.focus();
			}, 240 );
		} );
	} );

	// Open the mini cart after an add to cart event triggered by WooCommerce.
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'added_to_cart', function () {
			openPanel( 'tj-mini-cart' );
		} );
	}
	/* ------------------------------------------------------ live search -- */

	// Every search box on the page is wired, not just the first one. The header
	// box, the drawer box and the mobile bar are separate elements, and binding
	// only the header one left the mobile search without suggestions.
	if ( data.liveSearch ) {
		var suggestionPanels = [];

		qsa( '[data-tj-search]' ).forEach( function ( searchBox ) {
			var searchInput = qs( '[data-tj-search-input]', searchBox );
			var results = qs( '[data-tj-search-results]', searchBox );

			if ( ! searchInput || ! results ) {
				return;
			}

			suggestionPanels.push( results );

			var activeIndex = -1;

			var search = debounce( function () {
				var term = searchInput.value.trim();

				if ( term.length < 2 ) {
					results.hidden = true;
					results.innerHTML = '';
					searchInput.setAttribute( 'aria-expanded', 'false' );
					return;
				}

				results.hidden = false;
				results.innerHTML = '<p class="tj-live-search-loading">' + strings.searching + '</p>';

				var body = new FormData();
				body.append( 'action', 'techjossecom_live_search' );
				body.append( 'nonce', data.searchNonce || '' );
				body.append( 'term', term );

				window.fetch( data.ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						body: body
					} )
					.then( function ( response ) {
						return response.json();
					} )
					.then( function ( payload ) {
						if ( ! payload || ! payload.success ) {
							results.innerHTML = '<p class="tj-live-search-empty">' + strings.error + '</p>';
							return;
						}

						results.innerHTML = payload.data.html || '<p class="tj-live-search-empty">' + strings.noResults + '</p>';
						activeIndex = -1;
						searchInput.setAttribute( 'aria-expanded', 'true' );
					} )
					.catch( function () {
						results.innerHTML = '<p class="tj-live-search-empty">' + strings.error + '</p>';
					} );
			}, 300 );

			searchInput.addEventListener( 'input', search );

			searchInput.addEventListener( 'focus', function () {
				if ( searchInput.value.trim().length >= 2 && results.innerHTML ) {
					results.hidden = false;
				}
			} );

			searchInput.addEventListener( 'keydown', function ( event ) {
				var items = qsa( '.tj-live-search-item', results );

				if ( ! items.length ) {
					return;
				}

				if ( 'ArrowDown' === event.key ) {
					event.preventDefault();
					activeIndex = ( activeIndex + 1 ) % items.length;
				} else if ( 'ArrowUp' === event.key ) {
					event.preventDefault();
					activeIndex = ( activeIndex - 1 + items.length ) % items.length;
				} else if ( 'Enter' === event.key && activeIndex > -1 ) {
					event.preventDefault();
					var link = qs( 'a', items[ activeIndex ] );

					if ( link ) {
						window.location.href = link.href;
					}

					return;
				} else {
					return;
				}

				items.forEach( function ( item, index ) {
					item.classList.toggle( 'is-active', index === activeIndex );
				} );
			} );

			// Clear the field and dismiss the suggestions, so the shopper can
			// start a different search without reloading the page.
			var clear = qs( '[data-tj-search-clear]', searchBox );

			if ( clear ) {
				clear.addEventListener( 'click', function () {
					searchInput.value = '';
					results.hidden = true;
					results.innerHTML = '';
					activeIndex = -1;
					searchInput.setAttribute( 'aria-expanded', 'false' );
					searchInput.focus();
				} );
			}
		} );

		// One listener for every box: a click anywhere outside a given search
		// box dismisses that box only.
		document.addEventListener( 'click', function ( event ) {
			suggestionPanels.forEach( function ( results ) {
				var box = results.closest( '[data-tj-search]' );

				if ( box && ! box.contains( event.target ) ) {
					results.hidden = true;
				}
			} );
		} );
	}

	/* ----------------------------------------------------- scrollers ---- */

	/*
	 * A section can ask for fewer items on a phone than on a wide screen. The
	 * same markup is sent to everybody, so the surplus items are marked here
	 * and the stylesheet hides them under the phone breakpoint. Doing it this
	 * way keeps one cached page correct for both sizes, which a user agent
	 * check on the server could not promise.
	 */
	qsa( '[data-tj-mobile-limit]' ).forEach( function ( list ) {
		var limit = parseInt( list.getAttribute( 'data-tj-mobile-limit' ), 10 );

		if ( ! limit || limit < 1 ) {
			return;
		}

		qsa( '> li', list ).forEach( function ( item, index ) {
			if ( index >= limit ) {
				item.classList.add( 'tj-over-mobile-limit' );
			}
		} );
	} );

	/*
	 * The arrows are bound once, on the whole page, and each one works out which
	 * track it belongs to from the section around it.
	 *
	 * They used to be looked up with a document wide query from inside the loop
	 * over the scrollers, which meant every arrow gained one listener per row on
	 * the page: pressing "next" under Best Deals also scrolled New Arrivals, and
	 * pressing it again on a third section would scroll all three at once. Worse,
	 * the buttons were wired to the track of whichever section was being set up,
	 * so on a page with more than one carousel the first row's arrows drove the
	 * last one. Resolving the track from the button's own section removes both
	 * problems, and is what lets a new homepage section be added later with no
	 * change to this file at all.
	 */

	/**
	 * The track a given arrow belongs to.
	 *
	 * The arrows are printed in the section heading, above the track rather than
	 * beside it, so the section that holds both is the link between them. A
	 * section with one row - every section on the site today - is a plain
	 * lookup. A section with several rows pairs them by order instead, so the
	 * first pair of arrows drives the first track and the second pair the
	 * second, rather than every arrow driving the first row.
	 *
	 * @param {Element} button One [data-tj-scroll] button.
	 * @return {Element|null}  The scrolling track, if there is one.
	 */
	var trackFor = function ( button ) {
		var owner     = button.closest( '.tj-section' ) || document;
		var scrollers = qsa( '[data-tj-scroller]', owner );

		if ( scrollers.length < 2 ) {
			return scrollers[0] || null;
		}

		var group  = button.closest( '[data-tj-scroll-buttons]' );
		var groups = qsa( '[data-tj-scroll-buttons]', owner );
		var index  = groups.indexOf( group );

		return scrollers[ index > -1 ? index : 0 ];
	};

	qsa( '[data-tj-scroll]' ).forEach( function ( button ) {
		var scroller = trackFor( button );
		var track = scroller ? ( qs( 'ul.products', scroller ) || scroller.firstElementChild ) : null;

		if ( ! track ) {
			return;
		}

		var step = function () {
			var first = track.firstElementChild;

			return first ? first.getBoundingClientRect().width + 14 : track.clientWidth;
		};

		button.addEventListener( 'click', function () {
			var direction = 'prev' === button.getAttribute( 'data-tj-scroll' ) ? -1 : 1;

			track.scrollBy( { left: direction * step(), behavior: 'smooth' } );
		} );
	} );

	qsa( '[data-tj-scroller]' ).forEach( function ( scroller ) {
		var track = qs( 'ul.products', scroller ) || scroller.firstElementChild;
		var section = scroller.closest( '.tj-section' ) || scroller.parentNode;

		if ( ! track || ! section ) {
			return;
		}

		var update = function () {
			var max = track.scrollWidth - track.clientWidth - 1;

			section.classList.toggle( 'is-at-start', track.scrollLeft <= 0 );
			section.classList.toggle( 'is-at-end', track.scrollLeft >= max );

			/*
			 * With every product visible there is nothing to scroll, so the
			 * arrows would sit there doing nothing. Hiding them is clearer
			 * than leaving two dead controls on the row.
			 */
			var canScroll = track.scrollWidth - track.clientWidth > 1;

			section.classList.toggle( 'has-no-overflow', ! canScroll );
		};

		track.addEventListener( 'scroll', update );
		window.addEventListener( 'resize', debounce( update, 150 ) );
		update();
	} );

	/* ----------------------------------------------------- hero slider ---- */

	/* The markup is a scroll-snap track, so a swipe already works with this
	   script switched off. Everything here is the enhancement layer: the
	   arrows, the dots, the auto rotation, and keeping the active dot in step
	   when the visitor scrolls the track by hand. */
	qsa( '[data-tj-slider]' ).forEach( function ( root ) {
		var track = qs( '[data-tj-slider-track]', root );

		if ( ! track ) {
			return;
		}

		var slides = qsa( '.tj-slider__slide', track );
		var dots   = qsa( '[data-tj-slider-dot]', root );
		var links  = qsa( '.tj-slider__link', track );
		var prev   = qs( '[data-tj-slider-prev]', root );
		var next   = qs( '[data-tj-slider-next]', root );
		var count  = slides.length;

		if ( count < 2 ) {
			return;
		}

		var index    = 0;
		var timer    = null;
		var autoplay = 'true' === root.getAttribute( 'data-tj-slider-autoplay' );
		var speed    = ( parseInt( root.getAttribute( 'data-tj-slider-speed' ), 10 ) || 5 ) * 1000;

		/** Scroll the track to one slide and mark it as the current one. */
		var show = function ( target ) {
			var slide = slides[ target ];

			if ( ! slide ) {
				return;
			}

			index = target;

			track.scrollTo( { left: slide.offsetLeft, behavior: 'smooth' } );

			dots.forEach( function ( dot, i ) {
				dot.classList.toggle( 'is-active', i === index );
				dot.setAttribute( 'aria-current', i === index ? 'true' : 'false' );
			} );

			/* A link on a banner that is currently off screen must not be
			   reachable by keyboard, or tabbing walks through invisible
			   targets. */
			links.forEach( function ( link, i ) {
				if ( i === index ) {
					link.removeAttribute( 'tabindex' );
				} else {
					link.setAttribute( 'tabindex', '-1' );
				}
			} );
		};

		var advance = function ( step ) {
			/* Wrapping rather than stopping at the ends keeps the banner
			   moving in a loop, which is what a promo strip is for. */
			show( ( index + step + count ) % count );
		};

		var stop = function () {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		};

		var start = function () {
			if ( ! autoplay || timer ) {
				return;
			}

			timer = window.setInterval( function () {
				advance( 1 );
			}, speed );
		};

		/** Restart the countdown so a manual move is not undone straight away. */
		var nudge = function () {
			stop();
			start();
		};

		/* Work out which slide the track is resting on after a manual swipe. */
		var sync = function () {
			var width  = track.clientWidth;
			var target = width ? Math.round( track.scrollLeft / width ) : 0;

			if ( target !== index && target >= 0 && target < count ) {
				show( target );
			}
		};

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				advance( -1 );
				nudge();
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				advance( 1 );
				nudge();
			} );
		}

		dots.forEach( function ( dot, i ) {
			dot.addEventListener( 'click', function () {
				show( i );
				nudge();
			} );
		} );

		/* Left and right arrows move between banners once the slider has
		   focus, which is the expected keyboard behaviour for a carousel. */
		root.addEventListener( 'keydown', function ( event ) {
			if ( 37 === event.key ) {
				advance( -1 );
				nudge();
			} else if ( 39 === event.key ) {
				advance( 1 );
				nudge();
			}
		} );

		track.addEventListener( 'scroll', debounce( sync, 90 ) );
		window.addEventListener( 'resize', debounce( sync, 150 ) );

		/* Hold still while the visitor is reading or tabbing, and while the
		   tab is in the background, so the banner never moves under them. */
		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );
		root.addEventListener( 'focusin', stop );
		root.addEventListener( 'focusout', start );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stop();
			} else {
				start();
			}
		} );

		show( 0 );
		start();
	} );

	/* ----------------------------------------------- AJAX add to cart ---- */

	function applyFragments( fragments ) {
		if ( ! fragments ) {
			return;
		}

		/*
		 * Where the shopper had scrolled to, per element.
		 *
		 * Replacing an element wholesale throws away its scroll position: the
		 * fresh node starts at the top however far down the list the reader
		 * had got. In the drawer that means changing the quantity on the
		 * last line jumps the whole list back to the first item, which is
		 * both disorienting and, on a row that is still on screen, looks
		 * like the edit was thrown away.
		 *
		 * The position is taken before anything is replaced and put back
		 * afterwards, on the replacement itself rather than on a node that
		 * no longer exists. Only elements that can actually scroll - a
		 * number that scrolls is either content taller than its box or a
		 * layout that lets it - are restored, so this costs nothing on the
		 * many fragments that are plain counts and buttons.
		 */
		var scrolls = [];

		Object.keys( fragments ).forEach( function ( selector ) {
			var html = fragments[ selector ];
			var targets = qsa( selector );

			if ( ! targets.length && selector.indexOf( '<' ) === -1 ) {
				return;
			}

			targets.forEach( function ( target ) {
				scrolls.push( { node: target, top: target.scrollTop, left: target.scrollLeft } );
				target.outerHTML = html;
			} );
		} );

		scrolls.forEach( function ( entry ) {
			var fresh = entry.node.parentNode
				? entry.node.parentNode.firstElementChild
				: null;

			if ( fresh && fresh !== entry.node && fresh.scrollHeight > fresh.clientHeight ) {
				fresh.scrollTop = entry.top;
				fresh.scrollLeft = entry.left;
			}
		} );

		// WooCommerce re-renders product cards inside the cart and the checkout,
		// so any Size / Color chips that just arrived need wiring up again.
		initVariationPickers();

		// The same goes for any quantity stepper the new markup brought with it.
		initQuantitySteppers();
	}

	/** Put a spinner on a button while its request is in flight. */
	function setButtonLoading( button, loading, label ) {
		if ( ! button ) {
			return;
		}

		button.classList.toggle( 'is-loading', !! loading );
		button.disabled = !! loading;

		if ( label ) {
			button.textContent = label;
		}
	}

	/* ---------------------------------------------- quantity -/+ stepper -- */

	/**
	 * Move a quantity input by its own step, staying inside its limits.
	 *
	 * The bounds are read off the input rather than hard coded, so a product
	 * that only sells in multiples of two, or one with a stock ceiling of
	 * five, steps the way WooCommerce would clamp the posted value anyway.
	 * An empty box counts as the minimum rather than as zero, which keeps
	 * "3 - 1" from turning a blank field into "0".
	 *
	 * The count is written to the input rather than to a separate variable,
	 * so what the shopper sees is what gets posted. That matters on a
	 * variable product, where add-to-cart-variation.js re-renders this same
	 * field when a different variation is chosen.
	 *
	 * @param {Element} input The quantity input.
	 * @param {number}  step  How far to move: -1 or 1.
	 * @return {void}
	 */
	function stepQuantity( input, step ) {
		var min = parseInt( input.getAttribute( 'min' ), 10 );
		var max = parseInt( input.getAttribute( 'max' ), 10 );
		var unit = parseInt( input.getAttribute( 'step' ), 10 );
		var current = parseInt( input.value, 10 );

		if ( isNaN( unit ) || unit < 1 ) {
			unit = 1;
		}

		if ( isNaN( current ) ) {
			current = isNaN( min ) ? 1 : min;
		}

		var next = current + ( step * unit );

		if ( ! isNaN( min ) && next < min ) {
			next = min;
		}

		// Only a positive max is a real ceiling; WooCommerce leaves the
		// attribute off entirely when a product is not stock managed.
		if ( ! isNaN( max ) && max > 0 && next > max ) {
			next = max;
		}

		input.value = String( next );

		/*
		 * WooCommerce and plugins watch this field for changes, so a stepper
		 * press has to look like typing to them. The event bubbles for
		 * listeners and jQuery alike.
		 */
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	/**
	 * Wire up every -/+ stepper on the page.
	 *
	 * Bound on the document, because the mini cart replaces its rows
	 * wholesale whenever the cart changes and a listener attached to a row
	 * would be thrown away with it.
	 *
	 * @return {void}
	 */
	function initQuantitySteppers() {
		qsa( '[data-tj-qty-step]' ).forEach( function ( button ) {
			if ( button.getAttribute( 'data-tj-ready' ) ) {
				return;
			}

			button.setAttribute( 'data-tj-ready', '1' );

			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				var step = parseInt( button.getAttribute( 'data-tj-qty-step' ), 10 ) || 0;

				if ( ! step ) {
					return;
				}

				// The input is the stepper's sibling, both inside .quantity.
				var input = qs( 'input', button.parentNode );

				if ( input ) {
					stepQuantity( input, step );
				}
			} );
		} );
	}

	/**
	 * Say which options a shopper still has to pick, in their own words.
	 *
	 * The label comes off the same markup the chips are built from, so a product
	 * with "Size" and "Color" asks for those two by name rather than for a
	 * generic "options". Answering in place is the point: the alternative was
	 * posting the form, which reloaded the page and left WooCommerce to say it
	 * in a banner the shopper had to scroll back up to find.
	 *
	 * @param {Element} form The single product add to cart form.
	 * @return {string} The message to show.
	 */
	function missingOptionMessage( form ) {
		var names = [];

		qsa( '.tj-var-attr', form ).forEach( function ( group ) {
			var selected = qs( '.tj-var-value.is-selected', group );
			var label = qs( '.tj-var-attr-name', group );

			if ( ! selected && label && label.textContent.trim() ) {
				names.push( label.textContent.trim() );
			}
		} );

		// No chips to read: fall back to the labels WooCommerce printed.
		if ( ! names.length ) {
			qsa( '.variations th.label, .variations label', form ).forEach( function ( label ) {
				if ( label.textContent.trim() ) {
					names.push( label.textContent.trim() );
				}
			} );
		}

		if ( ! names.length ) {
			return strings.chooseOptions;
		}

		return strings.chooseOptions.replace( '%s', names.join( ', ' ) );
	}

	/**
	 * The attribute values a shopper has chosen, ready to be posted.
	 *
	 * The chips are the theme's own controls and the <select> elements behind
	 * them hold the same choice, so both are read and the chips win: they are
	 * what the shopper can see and touch. The name is the full attribute name,
	 * "attribute_pa_size", which is both the <select>'s name and the key
	 * WooCommerce looks for when it validates an add.
	 *
	 * Posting these is not optional. A variation that leaves an attribute open
	 * stores an empty value for it, which WooCommerce filters out before it
	 * checks the add; with nothing posted to stand in for that value the add is
	 * refused with "Size is a required field" even though the shopper picked a
	 * size. Sending the choice is what closes that gap.
	 *
	 * @param {Element} form The single product add to cart form.
	 * @return {Array} List of { name, value } pairs.
	 */
	function collectChosenAttributes( form ) {
		var chosen = {};
		var order = [];

		// WooCommerce's own dropdowns, which the chips mirror onto.
		qsa( 'select[name^="attribute_"]', form ).forEach( function ( select ) {
			if ( ! select.value ) {
				return;
			}

			chosen[ select.name ] = select.value;
			order.push( select.name );
		} );

		// The chips, overwriting whatever the dropdowns currently hold.
		var picker = qs( '[data-tj-variation-picker]', form );

		if ( picker ) {
			qsa( '.tj-var-attr', picker ).forEach( function ( group ) {
				var key = group.getAttribute( 'data-attribute' );
				var active = qs( '.tj-var-value.is-selected', group );

				if ( ! key || ! active ) {
					return;
				}

				if ( ! Object.prototype.hasOwnProperty.call( chosen, key ) ) {
					order.push( key );
				}

				chosen[ key ] = active.getAttribute( 'data-value' );
			} );
		}

		return order.map( function ( name ) {
			return { name: name, value: chosen[ name ] };
		} );
	}

	/**
	 * Show a message next to the add to cart button.
	 *
	 * One notice at a time, and it is removed before the next one is shown, so
	 * a shopper choosing another size does not stack a second message under the
	 * first. It sits inside the form rather than at the top of the page, because
	 * that is where the control it is complaining about is.
	 *
	 * @param {Element} form    The single product add to cart form.
	 * @param {string}  message What to say.
	 * @return {void}
	 */
	function showProductNotice( form, message ) {
		if ( ! form || ! message ) {
			return;
		}

		clearProductNotice( form );

		var notice = document.createElement( 'p' );

		notice.className = 'tj-form-notice';
		notice.setAttribute( 'role', 'alert' );
		notice.textContent = message;

		form.appendChild( notice );
	}

	/**
	 * Remove the message the add to cart form is showing, if any.
	 *
	 * @param {Element} form The single product add to cart form.
	 * @return {void}
	 */
	function clearProductNotice( form ) {
		if ( ! form ) {
			return;
		}

		qsa( '.tj-form-notice', form ).forEach( function ( notice ) {
			notice.parentNode.removeChild( notice );
		} );
	}

	/**
	 * Add the product on the single product page to the cart over AJAX.
	 *
	 * This handles every product type, not just the simple ones. WooCommerce's
	 * own add-to-cart script is bound to the variation form and would submit the
	 * page as a normal POST, which is what produced the "has been added to your
	 * cart. View cart" bar and left the drawer shut. Binding on the document in
	 * the capture phase means this runs first and the form is never actually
	 * submitted, so that script never gets its turn.
	 */
	document.addEventListener(
		'submit',
		function ( event ) {
			var form = event.target;

			// Only the single product form; the cart and checkout keep theirs.
			if ( ! form || ! form.classList || ! form.classList.contains( 'cart' ) ) {
				return;
			}

			if ( ! form.closest( '.summary, .woocommerce-product-details' ) || ! data.ajaxUrl ) {
				return;
			}

			var button = qs( '.single_add_to_cart_button', form );
			var productId = qs( '[name="add-to-cart"]', form );

			if ( ! button || ! productId ) {
				return;
			}

			/*
			 * Read what is actually being added.
			 *
			 * WooCommerce's own script writes the hidden variation_id input
			 * when the real <select> elements change, and the Size / Color
			 * chips drive those selects, so it is normally already correct. The
			 * chips are consulted as well because they are the theme's own
			 * source of truth and do not depend on that script having run.
			 */
			var variation = qs( 'input[name="variation_id"]', form );
			var variationId = variation ? parseInt( variation.value, 10 ) || 0 : 0;

			if ( ! variationId ) {
				var picker = qs( '[data-tj-variation-picker]', form );
				var chosen = picker ? parseInt( picker.getAttribute( 'data-tj-selected-variation' ), 10 ) || 0 : 0;

				if ( ! chosen ) {
					var orderNow = qs( '.tj-order-now', form.closest( '.summary' ) || form );

					chosen = orderNow ? parseInt( orderNow.getAttribute( 'data-selected-variation' ), 10 ) || 0 : 0;
				}

				variationId = chosen;
			}

			var isVariable = !! qs( '.variations', form );

			/*
			 * The form is never submitted for real, in any case.
			 *
			 * This used to hand a variable product with nothing chosen yet back
			 * to the browser, which posted the empty form and reloaded the page.
			 * WooCommerce then answered with its "Size is a required field."
			 * notice, the shopper lost their place, and the drawer never opened
			 * even when the add itself had worked. Catching the submit here
			 * means an incomplete choice is answered in place instead.
			 */
			event.preventDefault();
			event.stopPropagation();

			// Say which option is still missing, and stop, without a reload.
			if ( isVariable && ! variationId ) {
				showProductNotice( form, missingOptionMessage( form ) );

				return;
			}

			var quantity = qs( '[name="quantity"]', form );
			var label = button.getAttribute( 'data-tj-label' ) || button.textContent;

			clearProductNotice( form );

			button.setAttribute( 'data-tj-label', label );
			setButtonLoading( button, true, strings.adding || '...' );

			var body = new FormData();

			/*
			 * The parent product id, plus the variation and the chosen
			 * attributes when this is a variable product.
			 *
			 * WooCommerce's own wc-ajax=add_to_cart endpoint is not used here.
			 * It takes a product id and a quantity and nothing else, and when
			 * handed a variation id it rebuilds the attributes from the
			 * variation's own stored values rather than from the shopper's
			 * choice. A variation that leaves an attribute open - an "Any"
			 * value - has nothing to rebuild from, so the add is refused with
			 * "Size is a required field" however the shopper fills the form in.
			 * Sending the selection itself is what makes those products work.
			 */
			body.append( 'action', 'techjossecom_add_to_cart' );
			body.append( 'nonce', data.cartNonce || '' );
			body.append( 'product_id', productId.value );
			body.append( 'quantity', quantity ? quantity.value : 1 );

			if ( variationId ) {
				body.append( 'variation_id', variationId );

				collectChosenAttributes( form ).forEach( function ( entry ) {
					body.append( 'variation[' + entry.name + ']', entry.value );
				} );
			}

			window.fetch( data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					/*
					 * A rejected add - out of stock, not purchasable, an option
					 * that has just sold out - is told to the shopper in place,
					 * with WooCommerce's own words rather than a generic one.
					 */
					if ( ! payload || ! payload.success ) {
						showProductNotice( form, ( payload && payload.data && payload.data.message ) || strings.error );
					} else {
						applyFragments( payload.data.fragments );
						openPanel( 'tj-mini-cart' );
					}
				} )
				.catch( function () {
					/*
					 * The request never came back, so there is nothing to say
					 * about the product.
					 *
					 * This used to fall back to posting the form for real. That
					 * reloaded the page, which is the one thing the drawer is
					 * built to avoid, and it happened on exactly the connection
					 * a shopper can least afford to lose. A message they can
					 * retry from is the honest answer.
					 */
					showProductNotice( form, strings.error );
				} )
				.finally( function () {
					setButtonLoading( button, false, label );
				} );
		},
		true
	);

	/* ------------------------------------------------ mini cart editing -- */

	/**
	 * Change a line in the mini cart and refresh the drawer in place.
	 *
	 * The drawer stays open throughout, which is the whole point: the page is
	 * never reloaded, so the shopper can keep editing without losing their
	 * place. A quantity of zero removes the line, which lets the cross and the
	 * stepper share one code path.
	 *
	 * @param {Element} row      The .tj-cart-item being changed.
	 * @param {number}  amount   Signed change to apply to the quantity.
	 * @param {boolean} isRemove True for the cross, false for a stepper button.
	 */
	function updateCartLine( row, amount, isRemove ) {
		var keys = ( row.getAttribute( 'data-tj-cart-keys' ) || '' ).split( ',' ).filter( Boolean );
		var current = parseInt( row.getAttribute( 'data-tj-cart-qty' ), 10 ) || 0;
		var max = parseInt( row.getAttribute( 'data-tj-cart-max' ), 10 ) || 0;
		var next = Math.max( 0, current + amount );

		/*
		 * These bounds belong to the stepper alone. The cross has to be allowed
		 * to reach zero, or the one control that removes a line is the one
		 * control that stops itself working: it arrives here with a negative
		 * amount and a next of zero, which is exactly what the lower bound
		 * rejects.
		 */
		if ( ! isRemove ) {
			if ( amount < 0 && next < 1 ) {
				return;
			}

			if ( amount > 0 && max > 0 && next > max ) {
				return;
			}
		}

		row.classList.add( 'is-busy' );

		var body = new FormData();

		body.append( 'action', 'techjossecom_cart_update' );
		body.append( 'nonce', data.cartNonce || '' );
		body.append( 'quantity', next );

		keys.forEach( function ( key ) {
			body.append( 'cart_item_keys[]', key );
		} );

		return window.fetch( data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success ) {
					return;
				}

				// The refreshed row carries the new total, so the number on
				// screen is never a guess: it comes back from the cart.
				applyFragments( payload.data.fragments );
			} )
			.catch( function () {} )
			.finally( function () {
				row.classList.remove( 'is-busy' );
			} );
	}

	// Delegated, because the cart fragments replace the drawer body wholesale
	// and any listener bound to a row would be thrown away on the next update.
	document.addEventListener( 'click', function ( event ) {
		var remove = event.target.closest( '[data-tj-cart-remove]' );
		var step = event.target.closest( '[data-tj-cart-step]' );

		if ( ! remove && ! step ) {
			return;
		}

		var row = ( remove || step ).closest( '[data-tj-cart-item]' );

		if ( ! row ) {
			return;
		}

		// Never let the remove link navigate: that is what reloaded the page
		// and closed the drawer.
		event.preventDefault();

		if ( remove ) {
			updateCartLine( row, -( parseInt( row.getAttribute( 'data-tj-cart-qty' ), 10 ) || 0 ), true );

			return;
		}

		updateCartLine( row, parseInt( step.getAttribute( 'data-tj-cart-step' ), 10 ) || 0, false );
	} );

	/* ------------------------------------------ variable product Size / Color -- */

	/**
	 * Whether a variation's own value for an attribute accepts a shopper's choice.
	 *
	 * WooCommerce records an "Any" option as an empty string rather than by
	 * leaving the key out of the map, so an empty value has to count as a
	 * wildcard exactly as a missing one does. A shirt sold in four colours but
	 * in one size stores "" for its size on every one of those variations;
	 * holding that blank against the chosen size made no combination ever fit
	 * a variation, and the chips reported the product as having no options.
	 *
	 * @param {Object} attributes A variation's attributes.
	 * @param {string} key        Attribute name, "attribute_pa_size".
	 * @param {string} value      The chosen value.
	 * @return {boolean} True when the variation can still be the one.
	 */
	function attributeAccepts( attributes, key, value ) {
		if ( ! attributes || ! Object.prototype.hasOwnProperty.call( attributes, key ) ) {
			return true;
		}

		var current = String( attributes[ key ] );

		return current === '' || current === String( value );
	}

	/**
	 * Find the variation a set of chosen attributes points at.
	 *
	 * A variation that leaves an attribute out of its map, or leaves it empty,
	 * accepts any value for it, which is how WooCommerce stores an "Any"
	 * option. When several variations match, an in stock one wins over an out
	 * of stock one.
	 *
	 * @param {Object} chosen Attribute name => chosen term slug.
	 * @param {Array}  list   Variations, as carried by the Order Now button.
	 * @return {Object|null} The matching variation, or null.
	 */
	function matchVariation( list, chosen ) {
		var keys = Object.keys( chosen );

		if ( ! keys.length ) {
			return null;
		}

		var found = null;

		list.forEach( function ( variation ) {
			var matches = keys.every( function ( key ) {
				return attributeAccepts( variation.attributes, key, chosen[ key ] );
			} );

			if ( matches && ( ! found || ( ! found.in_stock && variation.in_stock ) ) ) {
				found = variation;
			}
		} );

		return found;
	}

	/**
	 * Whether choosing a value can still lead to a purchasable variation, given
	 * the choices already made in the other attributes. Used to grey out
	 * combinations that do not exist, for example a "Red" in Large.
	 *
	 * @param {Array}  list   Variations.
	 * @param {Object} chosen Attribute name => chosen term slug.
	 * @param {string} key    Attribute currently being offered.
	 * @param {string} value  Candidate value for that attribute.
	 * @return {boolean} True when the combination is orderable.
	 */
	function valueIsAvailable( list, chosen, key, value ) {
		var probe = {};
		var keys;

		Object.keys( chosen ).forEach( function ( name ) {
			probe[ name ] = chosen[ name ];
		} );

		probe[ key ] = value;
		keys = Object.keys( probe );

		var ok = false;

		list.forEach( function ( variation ) {
			if ( ok || ! variation.in_stock ) {
				return;
			}

			var attributes = variation.attributes || {};

			ok = keys.every( function ( name ) {
				return attributeAccepts( attributes, name, probe[ name ] );
			} );
		} );

		return ok;
	}

	/**
	 * Wire up every Size / Color picker.
	 *
	 * Skips pickers it has already handled via a data attribute, so it is safe
	 * to call again after the mini cart fragments swap in - WooCommerce
	 * re-renders product cards in the cart and the checkout.
	 */
	function initVariationPickers() {
		qsa( '[data-tj-variation-picker]:not([data-tj-ready])' ).forEach( function ( picker ) {
			var card = picker.closest( '.tj-product' );
			var scope = card || picker.closest( '.summary' ) || picker.parentNode;
			var form = picker.closest( 'form.cart' );
			var orderNow = scope ? scope.querySelector( '.tj-order-now' ) : null;
			var priceEl = card ? card.querySelector( '.price' ) : null;
			var variations = [];

			picker.setAttribute( 'data-tj-ready', '1' );

			/*
			 * The picker carries its own list of variations, so the chips work
			 * whether or not a cash on delivery button is printed. That button
			 * used to be the only place the data came from, and the picker
			 * simply stood down without it - which left every chip click doing
			 * nothing, and left "Add to cart" posting an empty form.
			 */
			try {
				variations = JSON.parse( picker.getAttribute( 'data-tj-variations' ) || '[]' );
			} catch ( error ) {
				variations = [];
			}

			if ( ! variations.length && orderNow ) {
				try {
					variations = JSON.parse( orderNow.getAttribute( 'data-product-variations' ) || '[]' );
				} catch ( error ) {
					variations = [];
				}
			}

			if ( ! variations.length ) {
				return;
			}

			var groups = qsa( '.tj-var-attr', picker );
			var chosen = {};

			function read() {
				groups.forEach( function ( group ) {
					var key = group.getAttribute( 'data-attribute' );
					var active = qs( '.tj-var-value.is-selected', group );

					if ( active ) {
						chosen[ key ] = active.getAttribute( 'data-value' );
					} else {
						delete chosen[ key ];
					}
				} );
			}

			function paint() {
				groups.forEach( function ( group ) {
					var key = group.getAttribute( 'data-attribute' );

					qsa( '.tj-var-value', group ).forEach( function ( option ) {
						var value = option.getAttribute( 'data-value' );
						var possible = valueIsAvailable( variations, chosen, key, value );
						var selected = option.classList.contains( 'is-selected' );

						option.classList.toggle( 'is-unavailable', ! possible );
						option.disabled = ! possible && ! selected;
					} );
				} );
			}

			function notifySelect( select, value ) {
				if ( select.value === value ) {
					return;
				}

				select.value = value;

				// add-to-cart-variation.js is jQuery driven, so trigger through
				// jQuery when it is around and fall back to a native event.
				if ( window.jQuery ) {
					window.jQuery( select ).trigger( 'change' );
				} else {
					select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
			}

			/*
			 * Keep the add to cart button clickable.
			 *
			 * WooCommerce greys the button out until every option is chosen,
			 * and its own click handler for a greyed out button only shows an
			 * alert: it never cancels the click, so the form still posts itself
			 * and the page reloads with "Size is a required field." in a banner
			 * at the top. The chips already know whether the choice is
			 * complete, so the button is kept live and the answer is given in
			 * place instead - which is what a shopper expects from a control
			 * they can see and use.
			 */
			function refreshButton( match ) {
				var button = form ? qs( '.single_add_to_cart_button', form ) : null;

				if ( ! button ) {
					return;
				}

				button.disabled = false;
				button.classList.remove( 'disabled', 'wc-variation-selection-needed', 'wc-variation-is-unavailable' );

				// Kept in step with the real form so anything that reads it,
				// including a no-script fallback, agrees with the chips.
				var field = form ? qs( 'input[name="variation_id"]', form ) : null;

				if ( field ) {
					field.value = match ? String( match.id ) : '';
				}
			}

			function sync() {
				var match = matchVariation( variations, chosen );

				/*
				 * Record the choice on the picker itself.
				 *
				 * The add to cart handler reads this back when it works out what
				 * is being added, so the chips stay the single source of truth
				 * and the two can never drift apart. It is cleared to an empty
				 * string whenever the combination stops resolving, so a
				 * half-finished choice is never mistaken for a whole one.
				 */
				picker.setAttribute( 'data-tj-selected-variation', match ? String( match.id ) : '' );

				// Hand the choice to the cash on delivery popup, when there is one.
				if ( orderNow ) {
					if ( match ) {
						orderNow.setAttribute( 'data-selected-variation', String( match.id ) );
						orderNow.setAttribute( 'data-product-price', String( match.price ) );
					} else {
						orderNow.setAttribute( 'data-selected-variation', '' );
					}
				}

				if ( priceEl && match && match.price_html ) {
					priceEl.textContent = match.price_html;
				}

				/*
				 * Mirror the choice onto WooCommerce's real <select> elements.
				 *
				 * The key on a chip group is the full attribute name,
				 * "attribute_pa_size", which is already the select's name. The
				 * prefix used to be added a second time here, which made the
				 * lookup come back empty: the chips looked chosen, the form
				 * carried nothing, and "Add to cart" posted an empty
				 * variation. Taking the name as it stands is what keeps the
				 * hidden variation_id in step with the chips.
				 */
				if ( form ) {
					groups.forEach( function ( group ) {
						var key = group.getAttribute( 'data-attribute' );
						var select = key ? form.querySelector( 'select[name="' + key + '"]' ) : null;

						if ( select ) {
							notifySelect( select, chosen[ key ] || '' );
						}
					} );
				}

				refreshButton( match );

				picker.classList.toggle( 'is-complete', !! match );
			}

			groups.forEach( function ( group ) {
				qsa( '.tj-var-value', group ).forEach( function ( option ) {
					option.addEventListener( 'click', function ( event ) {
						event.preventDefault();
						event.stopPropagation();

						var same = option.classList.contains( 'is-selected' );

						qsa( '.tj-var-value', group ).forEach( function ( other ) {
							other.classList.remove( 'is-selected' );
							other.setAttribute( 'aria-pressed', 'false' );
						} );

						if ( ! same ) {
							option.classList.add( 'is-selected' );
							option.setAttribute( 'aria-pressed', 'true' );
						}

						read();
						paint();
						sync();
					} );
				} );
			} );

			// "Reset options" in WooCommerce clears the form behind the chips.
			if ( form ) {
				form.addEventListener( 'reset', function () {
					window.setTimeout( function () {
						groups.forEach( function ( group ) {
							qsa( '.tj-var-value', group ).forEach( function ( option ) {
								option.classList.remove( 'is-selected' );
								option.setAttribute( 'aria-pressed', 'false' );
							} );
						} );

						read();
						paint();
						sync();
					}, 0 );
				} );
			}

			// WooCommerce preselects attributes server side, so mirror whatever
			// the real <select> elements already hold onto the chips.
			if ( form ) {
				groups.forEach( function ( group ) {
					var key = group.getAttribute( 'data-attribute' );
					var select = key ? form.querySelector( 'select[name="' + key + '"]' ) : null;

					if ( ! select || ! select.value ) {
						return;
					}

					var pre = null;

					qsa( '.tj-var-value', group ).forEach( function ( option ) {
						if ( option.getAttribute( 'data-value' ) === select.value ) {
							pre = option;
						}
					} );

					if ( pre ) {
						pre.classList.add( 'is-selected' );
						pre.setAttribute( 'aria-pressed', 'true' );
					}
				} );
			}

			read();
			paint();
			sync();
		} );
	}

	initVariationPickers();
	initQuantitySteppers();

	/* --------------------------------------------- cash on delivery popup -- */

	var modal = document.getElementById( 'tj-cod-modal' );
	var freeLabel = strings.free || 'Free';
	var outOfStockLabel = strings.outOfStock || 'Out of stock';

	if ( modal && data.codEnabled && data.isWooCommerce ) {
		var els = {
			image: qs( '[data-tj-cod-image]', modal ),
			name: qs( '[data-tj-cod-name]', modal ),
			price: qs( '[data-tj-cod-price]', modal ),
			variationWrap: qs( '[data-tj-cod-variation-wrap]', modal ),
			variation: qs( '[data-tj-cod-variation]', modal ),
			qty: qs( '[data-tj-cod-qty]', modal ),
			shipping: qs( '[data-tj-cod-shipping]', modal ),
			productId: qs( '[data-tj-cod-product-id]', modal ),
			total: qs( '[data-tj-cod-total]', modal ),
			form: qs( '[data-tj-cod-form]', modal ),
			submit: qs( '[data-tj-cod-submit]', modal ),
			message: qs( '[data-tj-cod-message]', modal )
		};

		var state = {
			id: 0,
			price: 0,
			variations: [],
			options: [],
			shippingCost: 0,
			shippingLabel: '',
			methodId: '',
			instanceId: 0
		};

		function escapeHtml( value ) {
			var div = document.createElement( 'div' );
			div.textContent = ( value === undefined || value === null ) ? '' : String( value );

			return div.innerHTML;
		}

		function currentQuantity() {
			return Math.max( 1, parseInt( els.qty.value, 10 ) || 1 );
		}

		function findVariation( id ) {
			var found = null;

			state.variations.forEach( function ( variation ) {
				if ( String( variation.id ) === String( id ) ) {
					found = variation;
				}
			} );

			return found;
		}

		function currentVariation() {
			return state.variations.length ? findVariation( els.variation.value ) : null;
		}

		function unitPrice() {
			var variation = currentVariation();

			return variation ? parseFloat( variation.price ) || 0 : state.price;
		}

		function updateTotal() {
			els.total.textContent = formatPrice( unitPrice() * currentQuantity() + state.shippingCost );
		}

		function showMessage( text, type ) {
			els.message.textContent = text || '';
			els.message.className = 'tj-cod-message' + ( type ? ' is-' + type : '' );
		}

		function selectShipping( index ) {
			var option = state.options[ index ];

			if ( ! option ) {
				state.shippingCost = 0;
				state.shippingLabel = '';
				state.methodId = '';
				state.instanceId = 0;
				updateTotal();

				return;
			}

			state.shippingCost = parseFloat( option.cost ) || 0;
			state.shippingLabel = option.label || '';
			state.methodId = option.method_id || '';
			state.instanceId = parseInt( option.instance_id, 10 ) || 0;

			updateTotal();
		}

		function loadShipping() {
			els.shipping.innerHTML = '<p class="tj-cod-loading">' + ( strings.searching || '...' ) + '</p>';

			var body = new FormData();
			body.append( 'action', 'techjossecom_shipping' );
			body.append( 'nonce', data.codNonce || '' );
			body.append( 'product_id', state.id );
			body.append( 'quantity', currentQuantity() );

			window.fetch( data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					var options = payload && payload.success && payload.data ? payload.data.options || [] : [];

					state.options = options;

					if ( ! options.length ) {
						var notice = payload && payload.data && payload.data.notice ? payload.data.notice : strings.error;
						els.shipping.innerHTML = '<p class="tj-cod-loading">' + escapeHtml( notice ) + '</p>';
						selectShipping( -1 );

						return;
					}

					var html = '';

					options.forEach( function ( option, index ) {
						var cost = parseFloat( option.cost ) > 0 ? formatPrice( option.cost ) : freeLabel;

						html += '<label class="tj-cod-shipping-option">';
						html += '<input type="radio" name="tj_shipping_option" value="' + index + '"' + ( index === 0 ? ' checked' : '' ) + '>';
						html += '<span class="tj-cod-shipping-label">' + escapeHtml( option.label ) + '</span>';
						html += '<span class="tj-cod-shipping-cost">' + escapeHtml( cost ) + '</span>';
						html += '</label>';
					} );

					els.shipping.innerHTML = html;
					selectShipping( 0 );
				} )
				.catch( function () {
					els.shipping.innerHTML = '<p class="tj-cod-loading">' + strings.error + '</p>';
					selectShipping( -1 );
				} );
		}

		els.shipping.addEventListener( 'change', function ( event ) {
			if ( event.target.name === 'tj_shipping_option' ) {
				selectShipping( parseInt( event.target.value, 10 ) || 0 );
			}
		} );

		qsa( '[data-tj-qty-step]', modal ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var step = parseInt( button.getAttribute( 'data-tj-qty-step' ), 10 ) || 1;

				els.qty.value = Math.max( 1, currentQuantity() + step );
				loadShipping();
			} );
		} );

		els.qty.addEventListener( 'change', loadShipping );

		els.variation.addEventListener( 'change', function () {
			var variation = currentVariation();

			els.price.textContent = variation && variation.price_html ? variation.price_html : formatPrice( state.price );
			loadShipping();
		} );

		qsa( '.tj-order-now' ).forEach( function ( button ) {
			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				state.id = parseInt( button.getAttribute( 'data-product-id' ), 10 ) || 0;
				state.price = parseFloat( button.getAttribute( 'data-product-price' ) ) || 0;
				state.variations = [];

				try {
					var raw = button.getAttribute( 'data-product-variations' );
					state.variations = raw ? JSON.parse( raw ) : [];
				} catch ( error ) {
					state.variations = [];
				}

				var image = button.getAttribute( 'data-product-image' );

				els.productId.value = state.id;
				els.name.textContent = button.getAttribute( 'data-product-name' ) || '';
				els.price.textContent = formatPrice( state.price );
				els.qty.value = 1;

				if ( image ) {
					els.image.src = image;
					els.image.hidden = false;
				} else {
					els.image.hidden = true;
					els.image.removeAttribute( 'src' );
				}

				if ( state.variations.length ) {
					var options = state.variations.map( function ( variation ) {
						var label = variation.label + ( variation.in_stock ? '' : ' (' + outOfStockLabel + ')' );

						return '<option value="' + variation.id + '">' + escapeHtml( label ) + '</option>';
					} );

					els.variation.innerHTML = options.join( '' );
					els.variationWrap.hidden = false;

				// A Size / Color chosen on the product card or on the product page
				// is written onto the button by the variation picker, so open the
				// popup already on that option rather than making the shopper pick
				// the same thing twice.
				var preselected = button.getAttribute( 'data-selected-variation' );
				var preselectedVariation = preselected ? findVariation( preselected ) : null;

				if ( preselectedVariation ) {
					els.variation.value = String( preselectedVariation.id );
				}

				var shown = preselectedVariation || state.variations[ 0 ];

				if ( shown && shown.price_html ) {
					els.price.textContent = shown.price_html;
				}
				} else {
					els.variation.innerHTML = '';
					els.variationWrap.hidden = true;
				}

				showMessage( '' );
				openPanel( 'tj-cod-modal' );
				loadShipping();
			} );
		} );

		els.form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var formData = new FormData( els.form );
			var name = ( formData.get( 'name' ) || '' ).toString().trim();
			var phone = ( formData.get( 'phone' ) || '' ).toString().trim();
			var address = ( formData.get( 'address' ) || '' ).toString().trim();

			if ( ! name || ! phone || ! address ) {
				showMessage( strings.required, 'error' );

				return;
			}

			var variation = currentVariation();

			formData.append( 'action', 'techjossecom_cod_order' );
			formData.append( 'nonce', data.codNonce || '' );
			formData.append( 'product_id', state.id );
			formData.append( 'variation_id', variation ? variation.id : 0 );
			formData.append( 'quantity', currentQuantity() );
			formData.append( 'shipping_label', state.shippingLabel );
			formData.append( 'shipping_cost', state.shippingCost );
			formData.append( 'shipping_method_id', state.methodId );
			formData.append( 'shipping_instance_id', state.instanceId );

			var originalText = els.submit.textContent;

			modal.classList.add( 'is-loading' );
			els.submit.disabled = true;
			els.submit.textContent = strings.orderPlacing || '...';
			showMessage( '' );

			window.fetch( data.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					if ( payload && payload.success ) {
						showMessage( payload.data.message, 'success' );

						if ( payload.data.redirect ) {
							window.setTimeout( function () {
								window.location.href = payload.data.redirect;
							}, 1400 );
						}

						return;
					}

					showMessage( payload && payload.data && payload.data.message ? payload.data.message : strings.error, 'error' );
				} )
				.catch( function () {
					showMessage( strings.error, 'error' );
				} )
				.finally( function () {
					modal.classList.remove( 'is-loading' );
					els.submit.disabled = false;
					els.submit.textContent = originalText;
				} );
		} );
	}



}() );
