<?php
/**
 * Admin Logs Template
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
	<h1><?php esc_html_e( 'Menu Sync Logs', 'avro-multisite-menu-sync' ); ?></h1>

	<!-- Statistics Summary -->
	<div class="avro-stats-summary">
		<div class="avro-stat-item">
			<strong><?php esc_html_e( 'Total Syncs:', 'avro-multisite-menu-sync' ); ?></strong>
			<?php echo esc_html( number_format_i18n( $stats['total_syncs'] ) ); ?>
		</div>
		<div class="avro-stat-item success">
			<strong><?php esc_html_e( 'Successful:', 'avro-multisite-menu-sync' ); ?></strong>
			<?php echo esc_html( number_format_i18n( $stats['successful_syncs'] ) ); ?>
		</div>
		<div class="avro-stat-item error">
			<strong><?php esc_html_e( 'Failed:', 'avro-multisite-menu-sync' ); ?></strong>
			<?php echo esc_html( number_format_i18n( $stats['failed_syncs'] ) ); ?>
		</div>
		<div class="avro-stat-item">
			<strong><?php esc_html_e( 'Success Rate:', 'avro-multisite-menu-sync' ); ?></strong>
			<?php echo esc_html( number_format_i18n( $stats['success_rate'], 1 ) ); ?>%
		</div>
	</div>

	<!-- Filters -->
	<div class="avro-log-filters">
		<form method="get" action="">
			<input type="hidden" name="page" value="menu-sync-logs">
			
			<label for="status-filter"><?php esc_html_e( 'Status:', 'avro-multisite-menu-sync' ); ?></label>
			<select name="status" id="status-filter">
				<option value=""><?php esc_html_e( 'All', 'avro-multisite-menu-sync' ); ?></option>
				<option value="success" <?php selected( $status, 'success' ); ?>><?php esc_html_e( 'Success', 'avro-multisite-menu-sync' ); ?></option>
				<option value="error" <?php selected( $status, 'error' ); ?>><?php esc_html_e( 'Error', 'avro-multisite-menu-sync' ); ?></option>
			</select>
			
			<?php submit_button( __( 'Filter', 'avro-multisite-menu-sync' ), 'secondary', 'filter', false ); ?>
			
			<?php if ( ! empty( $status ) ) : ?>
				<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=menu-sync-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'Clear Filters', 'avro-multisite-menu-sync' ); ?>
				</a>
			<?php endif; ?>
		</form>
	</div>

	<!-- Logs Table -->
	<?php if ( ! empty( $logs ) ) : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 50px;"><?php esc_html_e( 'ID', 'avro-multisite-menu-sync' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Timestamp', 'avro-multisite-menu-sync' ); ?></th>
					<th><?php esc_html_e( 'Menu Name', 'avro-multisite-menu-sync' ); ?></th>
					<th><?php esc_html_e( 'Source Site', 'avro-multisite-menu-sync' ); ?></th>
					<th><?php esc_html_e( 'Target Site', 'avro-multisite-menu-sync' ); ?></th>
					<th style="width: 80px;"><?php esc_html_e( 'Items', 'avro-multisite-menu-sync' ); ?></th>
					<th style="width: 100px;"><?php esc_html_e( 'Status', 'avro-multisite-menu-sync' ); ?></th>
					<th><?php esc_html_e( 'Message', 'avro-multisite-menu-sync' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<?php
					$source_site = get_blog_details( $log['source_site_id'] );
					$target_site = get_blog_details( $log['target_site_id'] );
					$status_class = 'success' === $log['status'] ? 'avro-status-success' : 'avro-status-error';
					?>
					<tr>
						<td><?php echo esc_html( $log['id'] ); ?></td>
						<td>
							<abbr title="<?php echo esc_attr( $log['timestamp'] ); ?>">
								<?php echo esc_html( human_time_diff( strtotime( $log['timestamp'] ), current_time( 'timestamp' ) ) ); ?> 
								<?php esc_html_e( 'ago', 'avro-multisite-menu-sync' ); ?>
							</abbr>
						</td>
						<td><strong><?php echo esc_html( $log['menu_name'] ); ?></strong></td>
						<td>
							<?php if ( $source_site ) : ?>
								<?php echo esc_html( $source_site->blogname ); ?>
								<br><small class="avro-muted"><?php echo esc_html( $source_site->siteurl ); ?></small>
							<?php else : ?>
								<?php esc_html_e( 'Site', 'avro-multisite-menu-sync' ); ?> #<?php echo esc_html( $log['source_site_id'] ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $target_site ) : ?>
								<?php echo esc_html( $target_site->blogname ); ?>
								<br><small class="avro-muted"><?php echo esc_html( $target_site->siteurl ); ?></small>
							<?php else : ?>
								<?php esc_html_e( 'Site', 'avro-multisite-menu-sync' ); ?> #<?php echo esc_html( $log['target_site_id'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( $log['items_synced'] ) ); ?></td>
						<td>
							<span class="avro-status-badge <?php echo esc_attr( $status_class ); ?>">
								<?php echo esc_html( ucfirst( $log['status'] ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $log['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					$page_links = paginate_links( array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => __( '&laquo;', 'avro-multisite-menu-sync' ),
						'next_text' => __( '&raquo;', 'avro-multisite-menu-sync' ),
						'total'     => $total_pages,
						'current'   => $current_page,
					) );

					if ( $page_links ) {
						echo '<span class="displaying-num">' . 
						     sprintf(
							     /* translators: %s: total number of items */
							     esc_html( _n( '%s item', '%s items', $total_count, 'avro-multisite-menu-sync' ) ),
							     esc_html( number_format_i18n( $total_count ) )
						     ) . 
						     '</span>';
						echo '<span class="pagination-links">' . $page_links . '</span>';
					}
					?>
				</div>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<p><?php esc_html_e( 'No logs found.', 'avro-multisite-menu-sync' ); ?></p>
	<?php endif; ?>

	<!-- Actions -->
	<div class="avro-log-actions">
		<h3><?php esc_html_e( 'Log Management', 'avro-multisite-menu-sync' ); ?></h3>
		<p><?php esc_html_e( 'Logs are automatically cleaned up after 30 days. You can manually clean up old logs using WP-CLI:', 'avro-multisite-menu-sync' ); ?></p>
		<code>wp eval "Menu_Sync_Logger::cleanup_old_logs(30);"</code>
	</div>
</div>
