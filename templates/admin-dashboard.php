<?php
/**
 * Admin Dashboard Template
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
	<h1><?php esc_html_e( 'Menu Sync Dashboard', 'avro-multisite-menu-sync' ); ?></h1>

	<?php settings_errors( 'avro_menu_sync' ); ?>

	<div class="avro-menu-sync-dashboard">
		<!-- Statistics Cards -->
		<div class="avro-stats-grid">
			<div class="avro-stat-card">
				<div class="avro-stat-icon">
					<span class="dashicons dashicons-update"></span>
				</div>
				<div class="avro-stat-content">
					<h3><?php echo esc_html( number_format_i18n( $stats['total_syncs'] ) ); ?></h3>
					<p><?php esc_html_e( 'Total Syncs', 'avro-multisite-menu-sync' ); ?></p>
				</div>
			</div>

			<div class="avro-stat-card success">
				<div class="avro-stat-icon">
					<span class="dashicons dashicons-yes-alt"></span>
				</div>
				<div class="avro-stat-content">
					<h3><?php echo esc_html( number_format_i18n( $stats['successful_syncs'] ) ); ?></h3>
					<p><?php esc_html_e( 'Successful', 'avro-multisite-menu-sync' ); ?></p>
				</div>
			</div>

			<div class="avro-stat-card error">
				<div class="avro-stat-icon">
					<span class="dashicons dashicons-warning"></span>
				</div>
				<div class="avro-stat-content">
					<h3><?php echo esc_html( number_format_i18n( $stats['failed_syncs'] ) ); ?></h3>
					<p><?php esc_html_e( 'Failed', 'avro-multisite-menu-sync' ); ?></p>
				</div>
			</div>

			<div class="avro-stat-card">
				<div class="avro-stat-icon">
					<span class="dashicons dashicons-menu"></span>
				</div>
				<div class="avro-stat-content">
					<h3><?php echo esc_html( number_format_i18n( $stats['total_items'] ) ); ?></h3>
					<p><?php esc_html_e( 'Items Synced', 'avro-multisite-menu-sync' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Configuration Status -->
		<div class="avro-config-status">
			<h2><?php esc_html_e( 'Configuration Status', 'avro-multisite-menu-sync' ); ?></h2>
			
			<table class="widefat">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Source Site:', 'avro-multisite-menu-sync' ); ?></strong></td>
						<td>
							<?php if ( $source_site ) : ?>
								<?php echo esc_html( $source_site->blogname ); ?> 
								<span class="avro-site-url">(<?php echo esc_html( $source_site->siteurl ); ?>)</span>
							<?php else : ?>
								<span class="avro-error"><?php esc_html_e( 'Not configured', 'avro-multisite-menu-sync' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Target Sites:', 'avro-multisite-menu-sync' ); ?></strong></td>
						<td>
							<?php if ( ! empty( $target_sites ) ) : ?>
								<strong><?php echo esc_html( count( $target_sites ) ); ?> 
								<?php esc_html_e( 'sites configured', 'avro-multisite-menu-sync' ); ?></strong>
								<ul style="margin-top: 8px; margin-left: 20px;">
									<?php foreach ( $target_sites as $target_site ) : ?>
										<li>
											<?php echo esc_html( $target_site->blogname ); ?>
											<span class="avro-site-url">(<?php echo esc_html( $target_site->siteurl ); ?>)</span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<span class="avro-error"><?php esc_html_e( 'No target sites configured', 'avro-multisite-menu-sync' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Sync Mode:', 'avro-multisite-menu-sync' ); ?></strong></td>
						<td>
							<?php 
							$sync_mode = $this->settings->get( 'sync_mode', 'manual' );
							echo esc_html( ucfirst( $sync_mode ) );
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Status:', 'avro-multisite-menu-sync' ); ?></strong></td>
						<td>
							<?php if ( $this->settings->is_enabled() ) : ?>
								<span class="avro-status-enabled"><?php esc_html_e( 'Enabled', 'avro-multisite-menu-sync' ); ?></span>
							<?php else : ?>
								<span class="avro-status-disabled"><?php esc_html_e( 'Disabled', 'avro-multisite-menu-sync' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ( empty( $source_site ) || empty( $target_sites ) ) : ?>
				<p class="avro-notice">
					<span class="dashicons dashicons-info"></span>
					<?php
					printf(
						/* translators: %s: settings page URL */
						__( 'Please <a href="%s">configure settings</a> before syncing menus.', 'avro-multisite-menu-sync' ),
						esc_url( network_admin_url( 'admin.php?page=menu-sync-settings' ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<!-- Available Menus -->
		<?php if ( $source_site && ! empty( $target_sites ) ) : ?>
			<div class="avro-menus-section">
				<h2><?php esc_html_e( 'Available Menus', 'avro-multisite-menu-sync' ); ?></h2>

				<?php if ( ! empty( $menus ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Menu Name', 'avro-multisite-menu-sync' ); ?></th>
								<th><?php esc_html_e( 'Items', 'avro-multisite-menu-sync' ); ?></th>
								<th><?php esc_html_e( 'Locations', 'avro-multisite-menu-sync' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'avro-multisite-menu-sync' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $menus as $menu ) : ?>
								<?php
								switch_to_blog( $source_site_id );
								$menu_items = wp_get_nav_menu_items( $menu->term_id );
								$item_count = $menu_items ? count( $menu_items ) : 0;
								
								// Get menu locations
								$locations = get_nav_menu_locations();
								$menu_locations = array();
								foreach ( $locations as $location => $assigned_menu_id ) {
									if ( absint( $assigned_menu_id ) === absint( $menu->term_id ) ) {
										$menu_locations[] = $location;
									}
								}
								restore_current_blog();
								?>
								<tr>
									<td><strong><?php echo esc_html( $menu->name ); ?></strong></td>
									<td><?php echo esc_html( number_format_i18n( $item_count ) ); ?></td>
									<td>
										<?php if ( ! empty( $menu_locations ) ) : ?>
											<?php echo esc_html( implode( ', ', $menu_locations ) ); ?>
										<?php else : ?>
											<span class="avro-muted"><?php esc_html_e( 'None', 'avro-multisite-menu-sync' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<button type="button" 
										        class="button button-primary avro-sync-menu" 
										        data-menu-id="<?php echo esc_attr( $menu->term_id ); ?>"
										        data-menu-name="<?php echo esc_attr( $menu->name ); ?>">
											<?php esc_html_e( 'Sync Now', 'avro-multisite-menu-sync' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p class="avro-bulk-actions">
						<button type="button" class="button button-secondary avro-sync-all-menus">
							<?php esc_html_e( 'Sync All Menus', 'avro-multisite-menu-sync' ); ?>
						</button>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'No menus found on source site.', 'avro-multisite-menu-sync' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Recent Activity -->
		<div class="avro-recent-activity">
			<h2><?php esc_html_e( 'Recent Activity', 'avro-multisite-menu-sync' ); ?></h2>

			<?php
			$recent_logs = $this->logger->get_logs( array( 'limit' => 5 ) );
			?>

			<?php if ( ! empty( $recent_logs ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time', 'avro-multisite-menu-sync' ); ?></th>
							<th><?php esc_html_e( 'Menu', 'avro-multisite-menu-sync' ); ?></th>
							<th><?php esc_html_e( 'Target Site', 'avro-multisite-menu-sync' ); ?></th>
							<th><?php esc_html_e( 'Status', 'avro-multisite-menu-sync' ); ?></th>
							<th><?php esc_html_e( 'Message', 'avro-multisite-menu-sync' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_logs as $log ) : ?>
							<?php
							$target_site = get_blog_details( $log['target_site_id'] );
							$status_class = 'success' === $log['status'] ? 'avro-status-success' : 'avro-status-error';
							?>
							<tr>
								<td><?php echo esc_html( human_time_diff( strtotime( $log['timestamp'] ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'ago', 'avro-multisite-menu-sync' ); ?></td>
								<td><?php echo esc_html( $log['menu_name'] ); ?></td>
								<td><?php echo $target_site ? esc_html( $target_site->blogname ) : esc_html( $log['target_site_id'] ); ?></td>
								<td><span class="<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $log['status'] ) ); ?></span></td>
								<td><?php echo esc_html( $log['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=menu-sync-logs' ) ); ?>" class="button">
						<?php esc_html_e( 'View All Logs', 'avro-multisite-menu-sync' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'No sync activity yet.', 'avro-multisite-menu-sync' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Sync Progress Modal -->
<div id="avro-sync-modal" class="avro-modal" style="display:none;">
	<div class="avro-modal-content">
		<h2><?php esc_html_e( 'Syncing Menu...', 'avro-multisite-menu-sync' ); ?></h2>
		<div class="avro-progress-bar">
			<div class="avro-progress-fill"></div>
		</div>
		<p class="avro-sync-status"></p>
	</div>
</div>
