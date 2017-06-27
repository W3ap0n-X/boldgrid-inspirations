var BOLDGRID = BOLDGRID || {};
BOLDGRID.LIBRARY = BOLDGRID.LIBRARY || {};

( function ( $ ) {

	"use strict";

	var api, self;

	api = BOLDGRID.LIBRARY;

	api.PluginInstaller = {

		/**
		 * Is a process currently loading?
		 *
		 * @type {Boolean} Whether or not a process is currently loading.
		 */
		loading : false,

		/**
		 * Localized data.
		 *
		 * @type {Object} Data that has been localized through WordPress.
		 */
		i18n : _bglibPluginInstaller,

		/**
		 * Initialize SEO Headings Analysis.
		 *
		 * @since 1.0.0
		 */
		init : function () {
			$( document ).ready( self.onReady );
		},

		/**
		 * Sets up event listeners on document ready.
		 *
		 * @since 1.0.0
		 */
		onReady : function() {
			self._buttons();
			self._upgradeLinks();
		},

		/**
		 * Install a WordPress plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} el     The install button element.
		 * @param {string} plugin The plugin slug to install.
		 */
		install : function( el, plugin ) {
			$.ajax( {
				type: 'POST',
				url: self.i18n.ajaxUrl,
				data: {
					action: 'installation',
					plugin: plugin,
					nonce: self.i18n.nonce,
					dataType: 'json'
				},
				success: function( data ) {
					if ( data ) {
						if ( data.status === 'success' ) {
							el.attr( 'class', 'activate button button-primary' );
							el.html( self.i18n.activate );
							el.closest( '.plugin' )
								.find( '.installer-messages' )
								.attr( 'class', 'installer-messages notice updated-message notice-success notice-alt' )
								.find( 'p' )
								.text( data.message );
						} else {
							el.removeClass( 'installing' );
						}
					} else {
						el.removeClass( 'installing' );
					}
					self.loading = false;
				},
				error: function( xhr, status, error ) {
					console.log( status );
					el.removeClass( 'installing' );
					self.loading = false;
				}
			} );
		},

		/**
		 * Activate a WordPress plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} el     The activate button element.
		 * @param {string} plugin The plugin slug to activate.
		 */
		activate : function( el, plugin ) {
			$.ajax( {
				type: 'POST',
				url: self.i18n.ajaxUrl,
				data: {
					action: 'activation',
					plugin: plugin,
					nonce: self.i18n.nonce,
					dataType: 'json'
				},
				success: function( response ) {
					el.attr( 'class', 'installed button disabled' );
					el.html( self.i18n.installed );
					el.removeClass( 'installing' );
					el.closest( '.plugin' )
						.find( '.installer-messages' )
						.addClass( 'notice updated-message notice-success notice-alt' )
						.find( 'p' )
						.text( response.data.message );
					self.loading = false;
				},
				error: function( xhr, status, error ) {
					el.removeClass( 'installing' );
					el.closest( '.plugin' )
						.find( '.installer-messages' )
						.addClass( 'notice update-message notice-error notice-alt is-dismissible' )
						.find( 'p' )
						.text( error );
					self.loading = false;
				}
			} );
		},

		/**
		 * Upgrades a WordPress plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} el     The upgrade link element.
		 * @param {string} plugin The plugin slug to upgrade.
		 */
		upgrade : function( el, plugin, slug, title ) {
			$.ajax( {
				type : 'POST',
				url : self.i18n.ajaxUrl,

				data : {
					action : 'upgrade',
					nonce : self.i18n.nonce,
					plugin : plugin,
					slug : slug,
					title : title,
					dataType : 'json'
				},

				success : function( response ) {

					// Disable the install/activate button while performing upgrade.
					el.closest( 'a.button' ).attr( 'class', 'installed button disabled' );

					// Update success message.
					el.attr( 'class', 'installer-messages update-message updated-message notice inline notice-success notice-alt' )
						.find( 'p' )
						.text( response.data.message );

					// Clear loading process indicator.
					self.loading = false;
				},

				error : function( xhr, status, error ) {

					// Update error message.
					el.attr( 'class', 'installer-messages notice update-message notice-error notice-alt is-dismissible' )
						.find( 'p' )
						.text( error );

					// Clear loading process indicator.
					self.loading = false;
				}
			} );
		},

		_upgradeLinks : function() {
			$( '.update-link' ).on( 'click', function( e ) {
				var el, plugin, slug, title;

				el = $( this );
				plugin = el.data( 'plugin' );
				slug = el.data( 'slug' );

				e.preventDefault();

				// Ensure plugin doesn't have process running and button is enabled.
				if ( self.loading ) {
					return false;
				}

				el = el.closest( '.installer-messages' );
				title = el.next().find( '.plugin-title' ).text();

				// Remove any current messages displayed.
				el.addClass( 'updating-message' )
					.find( 'p' )
					.text( 'Updating...' );

				// Send ajax request to upgrade plugin.
				self.upgrade( el, plugin, slug, title );
			} );
		},

		/**
		 * Install/Activate buttons event handler.
		 *
		 *  @since 1.0.0
		 */
		_buttons : function() {
			$( '.bglib-plugin-installer' ).on( 'click', 'a.button', function( e ) {
				var el, plugin;

				el = $( this );
				plugin = el.data( 'slug' );

				e.preventDefault();

				// Ensure plugin doesn't have process running and button is enabled.
				if ( el.hasClass( 'disabled' ) || self.loading ) {
					return false;
				}

				// Remove any current messages displayed.
				el.closest( '.plugin' )
					.find( '.installer-messages' )
					.attr( 'class', 'installer-messages' )
					.find( 'p' )
					.text( '' );

				// Installation of plugins.
				if ( el.hasClass( 'install' ) ) {
					el.html( self.i18n.installing );
					el.attr( 'class', 'installing button disabled' );
					self.install( el, plugin );
				}

				// Activation of plugins.
				if ( el.hasClass( 'activate' ) ) {
					el.html( self.i18n.activating );
					el.attr( 'class', 'installing button disabled' );
					self.activate( el, plugin );
				}
			} );
		},
	};

	self = api.PluginInstaller;

})( jQuery );

BOLDGRID.LIBRARY.PluginInstaller.init();
