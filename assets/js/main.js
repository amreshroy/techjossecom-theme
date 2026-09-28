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

	// Scroll the topbar away, then pin the nav bar directly under the header.
	// The offset is the measured header height rather than a fixed number: the
	// header grows and shrinks with the logo, the hotline and the cart badge.
	var header = qs( '.tj-header' );
	var navBar = qs( '.tj-nav-bar' );

	if ( header && navBar ) {
		var stickyOffset = function () {
			var height = header.getBoundingClientRect().height;

			document.documentElement.style.setProperty( '--tj-header-h', height + 'px' );
		};

		stickyOffset();

		// Re-measure when the header resizes, so a wrapped hotline or a larger
		// logo cannot leave the nav bar overlapping the header.
		if ( window.ResizeObserver ) {
			new window.ResizeObserver( stickyOffset ).observe( header );
		}

		window.addEventListener( 'resize', debounce( stickyOffset, 150 ) );

		var stuck = function () {
			navBar.classList.toggle( 'is-stuck', window.scrollY > 0 );
		};

		stuck();
		window.addEventListener( 'scroll', stuck, { passive: true } );
	}

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

	// Highlight the phone number of the search shortcut in the bottom bar.
	qsa( '[data-tj-search-focus]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var field = qs( '.tj-search--header .tj-search-input' );

			if ( ! field ) {
				field = qs( '.tj-search--mobile .tj-search-input' );
				openPanel( 'tj-mobile-menu' );
			}

			if ( field ) {
				window.setTimeout( function () {
					field.focus();
				}, 120 );
			}
		} );
	} );

	// Open the mini cart after an add to cart event triggered by WooCommerce.
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'added_to_cart', function () {
			openPanel( 'tj-mini-cart' );
		} );
	}
	/* ------------------------------------------------------ live search -- */

	var searchBox = qs( '.tj-search--header' ) || qs( '.tj-search' );

	if ( searchBox && data.liveSearch ) {
		var searchInput = qs( '[data-tj-search-input]', searchBox );
		var results = qs( '[data-tj-search-results]', searchBox );
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

		document.addEventListener( 'click', function ( event ) {
			if ( ! searchBox.contains( event.target ) ) {
				results.hidden = true;
			}
		} );
	}

	/* ----------------------------------------------------- scrollers ---- */

	qsa( '[data-tj-scroller]' ).forEach( function ( scroller ) {
		var track = qs( 'ul.products', scroller ) || scroller.firstElementChild;
		var section = scroller.closest( '.tj-section' ) || scroller.parentNode;

		if ( ! track || ! section ) {
			return;
		}

		var step = function () {
			var first = track.firstElementChild;

			return first ? first.getBoundingClientRect().width + 14 : track.clientWidth;
		};

		qsa( '[data-tj-scroll]', section ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var direction = 'prev' === button.getAttribute( 'data-tj-scroll' ) ? -1 : 1;

				track.scrollBy( { left: direction * step(), behavior: 'smooth' } );
			} );
		} );

		var update = function () {
			var max = track.scrollWidth - track.clientWidth - 1;

			section.classList.toggle( 'is-at-start', track.scrollLeft <= 0 );
			section.classList.toggle( 'is-at-end', track.scrollLeft >= max );
		};

		track.addEventListener( 'scroll', update );
		window.addEventListener( 'resize', debounce( update, 150 ) );
		update();
	} );

	/* ----------------------------------------------- AJAX add to cart ---- */

	function applyFragments( fragments ) {
		if ( ! fragments ) {
			return;
		}

		Object.keys( fragments ).forEach( function ( selector ) {
			var html = fragments[ selector ];
			var targets = qsa( selector );

			if ( ! targets.length && selector.indexOf( '<' ) === -1 ) {
				return;
			}

			targets.forEach( function ( target ) {
				target.outerHTML = html;
			} );
		} );

		// WooCommerce re-renders product cards inside the cart and the checkout,
		// so any Size / Color chips that just arrived need wiring up again.
		initVariationPickers();
	}

	var cartForm = qs( 'form.cart' );

	if ( cartForm && ! qs( '.variations', cartForm ) && data.wcAjaxUrl ) {
		cartForm.addEventListener( 'submit', function ( event ) {
			var button = qs( '.single_add_to_cart_button', cartForm );
			var productId = qs( '[name="add-to-cart"]', cartForm );
			var quantity = qs( '[name="quantity"]', cartForm );

			if ( ! button || ! productId ) {
				return;
			}

			event.preventDefault();

			var originalText = button.textContent;

			button.classList.add( 'loading' );
			button.disabled = true;
			button.textContent = strings.adding || '...';

			var body = new FormData();
			body.append( 'product_id', productId.value );
			body.append( 'quantity', quantity ? quantity.value : 1 );

			window.fetch( data.wcAjaxUrl.replace( '%%endpoint%%', 'add_to_cart' ), {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					applyFragments( payload && payload.fragments );
					openPanel( 'tj-mini-cart' );
				} )
				.catch( function () {
					cartForm.submit();
				} )
				.finally( function () {
					button.classList.remove( 'loading' );
					button.disabled = false;
					button.textContent = originalText;
				} );
		} );
	}
	/* ------------------------------------------ variable product Size / Color -- */

	/**
	 * Find the variation a set of chosen attributes points at.
	 *
	 * A variation that leaves an attribute out of its map accepts any value for
	 * it, which is how WooCommerce stores an "Any" option. When several
	 * variations match, an in stock one wins over an out of stock one.
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
			var attributes = variation.attributes || {};
			var matches = keys.every( function ( key ) {
				var value = attributes[ key ];

				return value === undefined || String( value ) === String( chosen[ key ] );
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
				var current = attributes[ name ];

				return current === undefined || String( current ) === String( probe[ name ] );
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

			if ( ! orderNow ) {
				return;
			}

			try {
				variations = JSON.parse( orderNow.getAttribute( 'data-product-variations' ) || '[]' );
			} catch ( error ) {
				variations = [];
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

			function sync() {
				var match = matchVariation( variations, chosen );

				// Hand the choice to the cash on delivery popup.
				if ( match ) {
					orderNow.setAttribute( 'data-selected-variation', String( match.id ) );
					orderNow.setAttribute( 'data-product-price', String( match.price ) );
				} else {
					orderNow.setAttribute( 'data-selected-variation', '' );
				}

				if ( priceEl && match && match.price_html ) {
					priceEl.textContent = match.price_html;
				}

				// Mirror onto WooCommerce's real <select> elements so the add to
				// cart form submits a variation on the product page.
				if ( form ) {
					groups.forEach( function ( group ) {
						var key = group.getAttribute( 'data-attribute' );
						var select = form.querySelector( 'select[name="attribute_' + key + '"]' );

						if ( select ) {
							notifySelect( select, chosen[ key ] || '' );
						}
					} );
				}

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
					var select = form.querySelector( 'select[name="attribute_' + key + '"]' );

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
