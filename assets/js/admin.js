/**
 * Admin JavaScript
 *
 * @package Avro_Multisite_Menu_Sync
 * @since 1.0.0
 */

(function($) {
	'use strict';

	const AvroMenuSync = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Single menu sync
			$(document).on('click', '.avro-sync-menu', this.syncMenu.bind(this));
			
			// Sync all menus
			$(document).on('click', '.avro-sync-all-menus', this.syncAllMenus.bind(this));
		},

		/**
		 * Sync single menu
		 */
		syncMenu: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			const menuId = $button.data('menu-id');
			const menuName = $button.data('menu-name');
			
			// Confirm action
			if (!confirm(avroMenuSync.strings.confirm)) {
				return;
			}
			
			// Get target sites from settings
			const targetSites = this.getTargetSites();
			
			if (targetSites.length === 0) {
				alert(avroMenuSync.strings.selectSites);
				return;
			}
			
			// Show modal
			this.showModal(menuName);
			
			// Disable button
			$button.prop('disabled', true).addClass('avro-loading');
			
			// Perform AJAX request
			$.ajax({
				url: avroMenuSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'avro_menu_sync_manual_sync',
					nonce: avroMenuSync.nonce,
					menu_id: menuId,
					site_ids: targetSites
				},
				success: function(response) {
					if (response.success) {
						this.showSuccess(response.data.message);
					} else {
						this.showError(response.data.message || avroMenuSync.strings.error);
					}
				}.bind(this),
				error: function(xhr, status, error) {
					this.showError(avroMenuSync.strings.error + ': ' + error);
				}.bind(this),
				complete: function() {
					$button.prop('disabled', false).removeClass('avro-loading');
					this.hideModal();
				}.bind(this)
			});
		},

		/**
		 * Sync all menus
		 */
		syncAllMenus: function(e) {
			e.preventDefault();
			
			const $button = $(e.currentTarget);
			
			// Confirm action
			if (!confirm('Are you sure you want to sync all menus? This may take a while.')) {
				return;
			}
			
			// Get all menu IDs
			const menuIds = [];
			$('.avro-sync-menu').each(function() {
				menuIds.push($(this).data('menu-id'));
			});
			
			if (menuIds.length === 0) {
				alert('No menus found to sync.');
				return;
			}
			
			// Get target sites
			const targetSites = this.getTargetSites();
			
			if (targetSites.length === 0) {
				alert(avroMenuSync.strings.selectSites);
				return;
			}
			
			// Show modal
			this.showModal('All Menus');
			
			// Disable button
			$button.prop('disabled', true).addClass('avro-loading');
			
			// Sync each menu sequentially
			this.syncMenusSequentially(menuIds, targetSites, 0, $button);
		},

		/**
		 * Sync menus sequentially
		 */
		syncMenusSequentially: function(menuIds, targetSites, index, $button) {
			if (index >= menuIds.length) {
				// All done
				this.hideModal();
				$button.prop('disabled', false).removeClass('avro-loading');
				this.showSuccess('All menus synced successfully!');
				return;
			}
			
			const menuId = menuIds[index];
			const progress = Math.round(((index + 1) / menuIds.length) * 100);
			
			// Update progress
			this.updateProgress(progress, `Syncing menu ${index + 1} of ${menuIds.length}...`);
			
			// Sync this menu
			$.ajax({
				url: avroMenuSync.ajaxUrl,
				type: 'POST',
				data: {
					action: 'avro_menu_sync_manual_sync',
					nonce: avroMenuSync.nonce,
					menu_id: menuId,
					site_ids: targetSites
				},
				success: function(response) {
					// Continue to next menu
					this.syncMenusSequentially(menuIds, targetSites, index + 1, $button);
				}.bind(this),
				error: function(xhr, status, error) {
					// Log error but continue
					console.error('Failed to sync menu ' + menuId + ':', error);
					this.syncMenusSequentially(menuIds, targetSites, index + 1, $button);
				}.bind(this)
			});
		},

		/**
		 * Get target sites from current settings
		 */
		getTargetSites: function() {
			// First, try to get from checkboxes (on settings page)
			const sites = [];
			$('input[name="target_site_ids[]"]:checked').each(function() {
				sites.push($(this).val());
			});
			
			// If no checkboxes found (on dashboard), use localized data
			if (sites.length === 0 && typeof avroMenuSync !== 'undefined' && avroMenuSync.targetSites) {
				return avroMenuSync.targetSites;
			}
			
			return sites;
		},

		/**
		 * Show modal
		 */
		showModal: function(menuName) {
			const $modal = $('#avro-sync-modal');
			
			if ($modal.length === 0) {
				// Create modal if it doesn't exist
				$('body').append(`
					<div id="avro-sync-modal" class="avro-modal">
						<div class="avro-modal-content">
							<h2>${avroMenuSync.strings.syncing}</h2>
							<div class="avro-progress-bar">
								<div class="avro-progress-fill"></div>
							</div>
							<p class="avro-sync-status">Syncing ${menuName}...</p>
						</div>
					</div>
				`);
			} else {
				$modal.find('.avro-sync-status').text(`Syncing ${menuName}...`);
				$modal.show();
			}
		},

		/**
		 * Hide modal
		 */
		hideModal: function() {
			$('#avro-sync-modal').fadeOut(300);
		},

		/**
		 * Update progress
		 */
		updateProgress: function(percent, message) {
			$('#avro-sync-modal .avro-progress-fill').css('width', percent + '%');
			$('#avro-sync-modal .avro-sync-status').text(message);
		},

		/**
		 * Show success message
		 */
		showSuccess: function(message) {
			this.showNotice(message, 'success');
		},

		/**
		 * Show error message
		 */
		showError: function(message) {
			this.showNotice(message, 'error');
		},

		/**
		 * Show admin notice
		 */
		showNotice: function(message, type) {
			const $notice = $('<div>')
				.addClass('notice notice-' + type + ' is-dismissible')
				.html('<p>' + message + '</p>');
			
			$('.wrap h1').after($notice);
			
			// Make dismissible
			$(document).trigger('wp-updates-notice-added');
			
			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		AvroMenuSync.init();
	});

})(jQuery);
