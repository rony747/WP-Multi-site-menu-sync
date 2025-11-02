<?php
/**
 * Admin Settings Template
 *
 * @package Avro_Multisite_Menu_Sync
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Menu Sync Settings', 'avro-multisite-menu-sync' ); ?></h1>

	<?php settings_errors( 'avro_menu_sync' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'avro_menu_sync_save_settings', 'avro_menu_sync_settings_nonce' ); ?>

		<table class="form-table">
			<!-- Plugin Status -->
			<tr>
				<th scope="row">
					<label for="enabled"><?php esc_html_e( 'Plugin Status', 'avro-multisite-menu-sync' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" 
						       name="enabled" 
						       id="enabled" 
						       value="1" 
						       <?php checked( $current_settings['enabled'], true ); ?>>
						<?php esc_html_e( 'Enable menu synchronization', 'avro-multisite-menu-sync' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Uncheck to temporarily disable all sync operations.', 'avro-multisite-menu-sync' ); ?>
					</p>
				</td>
			</tr>

			<!-- Source Site -->
			<tr>
				<th scope="row">
					<label for="source_site_id"><?php esc_html_e( 'Source Site', 'avro-multisite-menu-sync' ); ?></label>
				</th>
				<td>
					<select name="source_site_id" id="source_site_id" class="regular-text" required>
						<option value=""><?php esc_html_e( '-- Select Source Site --', 'avro-multisite-menu-sync' ); ?></option>
						<?php foreach ( $available_sites as $site ) : ?>
							<option value="<?php echo esc_attr( $site->blog_id ); ?>" 
							        <?php selected( $current_settings['source_site_id'], $site->blog_id ); ?>>
								<?php echo esc_html( $site->blogname ); ?> (<?php echo esc_html( $site->siteurl ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select the site from which menus will be synced.', 'avro-multisite-menu-sync' ); ?>
					</p>
				</td>
			</tr>

			<!-- Target Sites -->
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Target Sites', 'avro-multisite-menu-sync' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Target Sites', 'avro-multisite-menu-sync' ); ?></span>
						</legend>
						
						<div class="avro-site-checkboxes">
							<?php foreach ( $available_sites as $site ) : ?>
								<?php
								$site_id = absint( $site->blog_id );
								$target_ids = is_array( $current_settings['target_site_ids'] ) ? array_map( 'absint', $current_settings['target_site_ids'] ) : array();
								$is_checked = in_array( $site_id, $target_ids, true );
								?>
								<label>
									<input type="checkbox" 
									       name="target_site_ids[]" 
									       value="<?php echo esc_attr( $site_id ); ?>"
									       <?php checked( $is_checked, true ); ?>>
									<?php echo esc_html( $site->blogname ); ?>
									<span class="avro-site-url">(<?php echo esc_html( $site->siteurl ); ?>)</span>
								</label><br>
							<?php endforeach; ?>
						</div>

						<p class="description">
							<?php esc_html_e( 'Select sites that will receive synced menus. You can select multiple sites.', 'avro-multisite-menu-sync' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>

			<!-- Sync Mode -->
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Sync Mode', 'avro-multisite-menu-sync' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Sync Mode', 'avro-multisite-menu-sync' ); ?></span>
						</legend>
						
						<label>
							<input type="radio" 
							       name="sync_mode" 
							       value="auto" 
							       <?php checked( $current_settings['sync_mode'], 'auto' ); ?>>
							<strong><?php esc_html_e( 'Automatic', 'avro-multisite-menu-sync' ); ?></strong>
							<p class="description">
								<?php esc_html_e( 'Menus sync automatically when saved on the source site.', 'avro-multisite-menu-sync' ); ?>
							</p>
						</label>
						<br>
						
						<label>
							<input type="radio" 
							       name="sync_mode" 
							       value="manual" 
							       <?php checked( $current_settings['sync_mode'], 'manual' ); ?>>
							<strong><?php esc_html_e( 'Manual', 'avro-multisite-menu-sync' ); ?></strong>
							<p class="description">
								<?php esc_html_e( 'Menus sync only when you manually trigger sync from the dashboard.', 'avro-multisite-menu-sync' ); ?>
							</p>
						</label>
					</fieldset>
				</td>
			</tr>

			<!-- Conflict Resolution -->
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Conflict Resolution', 'avro-multisite-menu-sync' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Conflict Resolution', 'avro-multisite-menu-sync' ); ?></span>
						</legend>
						
						<label>
							<input type="radio" 
							       name="conflict_resolution" 
							       value="override" 
							       <?php checked( $current_settings['conflict_resolution'], 'override' ); ?>>
							<strong><?php esc_html_e( 'Override', 'avro-multisite-menu-sync' ); ?></strong>
							<p class="description">
								<?php esc_html_e( 'Replace existing menus completely with synced version.', 'avro-multisite-menu-sync' ); ?>
							</p>
						</label>
						<br>
						
						<label>
							<input type="radio" 
							       name="conflict_resolution" 
							       value="skip" 
							       <?php checked( $current_settings['conflict_resolution'], 'skip' ); ?>>
							<strong><?php esc_html_e( 'Skip', 'avro-multisite-menu-sync' ); ?></strong>
							<p class="description">
								<?php esc_html_e( 'Keep existing menus, do not sync if menu already exists.', 'avro-multisite-menu-sync' ); ?>
							</p>
						</label>
						<br>
						
						<label>
							<input type="radio" 
							       name="conflict_resolution" 
							       value="merge" 
							       <?php checked( $current_settings['conflict_resolution'], 'merge' ); ?>>
							<strong><?php esc_html_e( 'Merge', 'avro-multisite-menu-sync' ); ?></strong>
							<p class="description">
								<?php esc_html_e( 'Update existing menu items, preserve items not in source menu.', 'avro-multisite-menu-sync' ); ?>
							</p>
						</label>
					</fieldset>
				</td>
			</tr>

			<!-- Additional Options -->
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Additional Options', 'avro-multisite-menu-sync' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Additional Options', 'avro-multisite-menu-sync' ); ?></span>
						</legend>
						
						<label>
							<input type="checkbox" 
							       name="sync_menu_locations" 
							       value="1" 
							       <?php checked( $current_settings['sync_menu_locations'], true ); ?>>
							<?php esc_html_e( 'Sync menu location assignments', 'avro-multisite-menu-sync' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Assign synced menus to the same theme locations as source site.', 'avro-multisite-menu-sync' ); ?>
						</p>
						<br>
						
						<label>
							<input type="checkbox" 
							       name="preserve_custom_fields" 
							       value="1" 
							       <?php checked( $current_settings['preserve_custom_fields'], true ); ?>>
							<?php esc_html_e( 'Preserve custom fields on target sites', 'avro-multisite-menu-sync' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Keep custom menu item fields that exist on target sites.', 'avro-multisite-menu-sync' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'avro-multisite-menu-sync' ), 'primary', 'avro_menu_sync_save_settings' ); ?>
	</form>

	<!-- Information Box -->
	<div class="avro-info-box">
		<h3><?php esc_html_e( 'How It Works', 'avro-multisite-menu-sync' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Select a source site where you maintain your master menus.', 'avro-multisite-menu-sync' ); ?></li>
			<li><?php esc_html_e( 'Choose target sites that should receive synced menus.', 'avro-multisite-menu-sync' ); ?></li>
			<li><?php esc_html_e( 'Configure sync mode (automatic or manual) and conflict resolution.', 'avro-multisite-menu-sync' ); ?></li>
			<li><?php esc_html_e( 'Save settings and perform initial sync from the dashboard.', 'avro-multisite-menu-sync' ); ?></li>
			<li><?php esc_html_e( 'Menus will sync according to your settings when updated.', 'avro-multisite-menu-sync' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'Important Notes', 'avro-multisite-menu-sync' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'The plugin maps menu items by slug. Ensure matching content exists on target sites.', 'avro-multisite-menu-sync' ); ?></li>
			<li><?php esc_html_e( 'If a referenced page/post is not found on target site, it will be converted to a custom link.', 'avro-multisite-menu-sync' ); ?></li>
			<li><?php esc_html_e( 'Always test on a staging environment before using in production.', 'avro-multisite-menu-sync' ); ?></li>
			<li><?php esc_html_e( 'Check the logs regularly to monitor sync operations and catch any issues.', 'avro-multisite-menu-sync' ); ?></li>
		</ul>
	</div>
</div>
