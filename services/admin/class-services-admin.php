<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Services_Admin {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );
		add_filter( 'manage_service_request_posts_columns', array( $this, 'set_custom_columns' ) );
		add_action( 'manage_service_request_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'add_admin_filters' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_menu', array( $this, 'add_pending_count_bubble' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_head', array( $this, 'admin_styles' ) );
		add_action( 'wp_ajax_print_service_request', array( $this, 'handle_quick_print' ) );
	}

	public function admin_styles() {
		echo '<style>
			:root {
				--print-font-size: 14px;
				--print-margin: 7mm;
			}
			@media print {
				@page {
					size: A4 portrait;
					margin: var(--print-margin);
				}
				body {
					background: #fff !important;
					font-size: var(--print-font-size) !important;
					font-family: var(--ast-global-font-family, "Segoe UI", Tahoma, sans-serif) !important;
				}
				/* Hide non-printable elements more effectively to remove blank page */
				#adminmenumain, #wpadminbar, #screen-meta, #screen-meta-links, #wpfooter, .no-print, .updated, .error, .notice {
					display: none !important;
				}
				#wpcontent, #wpbody-content, #wpbody, #wpwrap {
					margin: 0 !important;
					padding: 0 !important;
					width: 100% !important;
					float: none !important;
				}
				.print-footer {
					position: fixed;
					bottom: 0;
					left: 0;
					right: 0;
					padding: 10mm 10mm;
					background: #fdfdfd !important;
					border-top: 3px solid #333 !important;
					display: block !important;
					text-align: center;
					z-index: 9999;
				}
				.print-content-wrapper {
					padding-bottom: 50mm; /* Space for fixed footer */
				}
				#postbox-container-1 { display: none !important; }
				#postbox-container-2 { width: 100% !important; }
				.postbox:not(.service-details-meta-box) { display: none !important; }
				
				.service-details-meta-box {
					border: none !important;
					background: transparent !important;
					box-shadow: none !important;
					width: 100% !important;
					padding: 0 !important;
					margin: 0 !important;
				}
				.service-details-meta-box .postbox-header, .service-details-meta-box .handle-actions {
					display: none !important;
				}
				
				.print-header, .print-footer, .print-watermark {
					display: block !important;
				}
				.only-print {
					display: inline !important;
				}
				
				.print-table {
					border: 1.5px solid #000 !important;
					width: 100% !important;
				}
				.print-table tr {
					border-bottom: 1px solid #000 !important;
					page-break-inside: avoid;
				}
				.print-table td {
					border-left: 1px solid #000 !important;
					padding: 8px 12px !important;
					border-top: 1px solid #000 !important;
				}
				.barcode-placeholder {
					filter: grayscale(1);
				}
				.document-note p {
					margin: 0;
					text-align: justify;
				}
			}
			.only-print { display: none; }
			
			/* Print Config Panel */
			#print-config-panel {
				background: #fff;
				border: 1px solid #ccd0d4;
				padding: 15px;
				margin-bottom: 20px;
				border-radius: 8px;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				display: flex;
				gap: 20px;
				align-items: center;
				flex-wrap: wrap;
			}
			.config-item { display: flex; align-items: center; gap: 8px; }
		</style>';
		
		echo '<script>
			function updatePrintConfig() {
				const fontSize = document.getElementById("print-font-size").value;
				const margin = document.getElementById("print-margin-val").value;
				document.documentElement.style.setProperty("--print-font-size", fontSize + "px");
				document.documentElement.style.setProperty("--print-margin", margin + "mm");
			}
		</script>';
	}

	public function add_pending_count_bubble() {
		global $menu;
		$pending_count = wp_count_posts( 'service_request' )->publish; 
		// Actually, I should count those with _request_status == 'pending'
		$args = array(
			'post_type'  => 'service_request',
			'meta_query' => array(
				array(
					'key'   => '_request_status',
					'value' => 'pending',
				),
			),
		);
		$query = new WP_Query( $args );
		$count = $query->found_posts;

		if ( $count > 0 && is_array($menu) ) {
			foreach ( $menu as $key => $value ) {
				if ( isset($value[2]) && $value[2] == 'services-management' ) {
					$menu[$key][0] .= " <span class='update-plugins count-$count'><span class='plugin-count'>" . number_format_i18n($count) . "</span></span>";
					break;
				}
			}
		}
	}

	public function add_settings_page() {
		add_menu_page(
			'إدارة الخدمات',
			'نظام الخدمات',
			'manage_options',
			'services-management',
			null,
			'dashicons-clipboard',
			25
		);

		add_submenu_page(
			'services-management',
			'تصنيفات الخدمات',
			'تصنيفات الخدمات',
			'manage_options',
			'edit-tags.php?taxonomy=service_category&post_type=service_item'
		);

		add_submenu_page(
			'services-management',
			'الإحصائيات',
			'الإحصائيات',
			'manage_options',
			'services-stats',
			array( $this, 'stats_page_html' )
		);

		add_submenu_page(
			'services-management',
			'الإعدادات العامة',
			'الإعدادات العامة',
			'manage_options',
			'services-settings',
			array( $this, 'settings_page_html' )
		);
	}

	public function register_settings() {
		register_setting( 'services_settings_group', 'services_admin_notification_email' );
		register_setting( 'services_settings_group', 'services_union_name' );
		register_setting( 'services_settings_group', 'services_union_phone' );
		register_setting( 'services_settings_group', 'services_union_email' );
		register_setting( 'services_settings_group', 'services_print_contact' );
		register_setting( 'services_settings_group', 'services_print_logo' );
	}

	public function stats_page_html() {
		$period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : 'all';
		$stats  = Services_DB::get_stats( $period );
		$statuses = Services_DB::get_statuses();

		$period_label = 'كل الوقت';
		if ( $period === 'today' ) $period_label = 'اليوم';
		if ( $period === 'week' ) $period_label = 'هذا الأسبوع';
		if ( $period === 'month' ) $period_label = 'هذا الشهر';
		?>
		<div class="wrap" style="direction: rtl;">
			<h1>مركز الإحصائيات - <?php echo esc_html( $period_label ); ?></h1>
			
			<h2 class="nav-tab-wrapper">
				<a href="?page=services-stats&period=all" class="nav-tab <?php echo $period === 'all' ? 'nav-tab-active' : ''; ?>">الكل</a>
				<a href="?page=services-stats&period=today" class="nav-tab <?php echo $period === 'today' ? 'nav-tab-active' : ''; ?>">اليوم</a>
				<a href="?page=services-stats&period=week" class="nav-tab <?php echo $period === 'week' ? 'nav-tab-active' : ''; ?>">هذا الأسبوع</a>
				<a href="?page=services-stats&period=month" class="nav-tab <?php echo $period === 'month' ? 'nav-tab-active' : ''; ?>">هذا الشهر</a>
			</h2>

			<div class="stats-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
				<div class="stats-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; text-align: center; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin-top: 0;">إجمالي الطلبات</h3>
					<span style="font-size: 2.5rem; font-weight: bold; color: #0073aa;"><?php echo number_format_i18n( $stats['total'] ); ?></span>
				</div>
				<div class="stats-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; text-align: center; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin-top: 0; color: #4caf50;">تم الانتهاء</h3>
					<span style="font-size: 2.5rem; font-weight: bold; color: #4caf50;"><?php echo number_format_i18n( $stats['completed'] ); ?></span>
				</div>
				<div class="stats-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; text-align: center; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin-top: 0; color: #ff9800;">قيد الانتظار</h3>
					<span style="font-size: 2.5rem; font-weight: bold; color: #ff9800;"><?php echo number_format_i18n( $stats['pending'] ); ?></span>
				</div>
				<div class="stats-card" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; text-align: center; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin-top: 0; color: #2196f3;">جاري المعالجة</h3>
					<span style="font-size: 2.5rem; font-weight: bold; color: #2196f3;"><?php echo number_format_i18n( $stats['processing'] ); ?></span>
				</div>
			</div>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 40px;">
				<div class="stats-section">
					<h3>توزيع الحالات</h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>الحالة</th>
								<th>العدد</th>
								<th>النسبة</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $statuses as $key => $label ) : 
								$count = $stats[ $key ];
								$percent = $stats['total'] > 0 ? round( ( $count / $stats['total'] ) * 100, 1 ) : 0;
							?>
								<tr>
									<td><strong><?php echo esc_html( $label ); ?></strong></td>
									<td><?php echo number_format_i18n( $count ); ?></td>
									<td><?php echo $percent; ?>%</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="stats-section">
					<h3>حسب نوع الخدمة</h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>الخدمة</th>
								<th>العدد</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							if ( empty( $stats['by_service'] ) ) : ?>
								<tr><td colspan="2">لا توجد بيانات لهذه الفترة.</td></tr>
							<?php else :
								arsort($stats['by_service']);
								foreach ( $stats['by_service'] as $service_title => $count ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $service_title ); ?></strong></td>
									<td><?php echo number_format_i18n( $count ); ?></td>
								</tr>
							<?php endforeach; 
							endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	public function settings_page_html() {
		?>
		<div class="wrap" style="direction: rtl;">
			<h1>الإعدادات العامة لنظام الخدمات</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'services_settings_group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">بريد الإشعارات (المدير)</th>
						<td>
							<input type="email" name="services_admin_notification_email" value="<?php echo esc_attr( get_option( 'services_admin_notification_email', get_option('admin_email') ) ); ?>" class="regular-text">
							<p class="description">البريد الذي ستصل إليه إشعارات الطلبات الجديدة.</p>
						</td>
					</tr>
				</table>

				<hr>
				<h2>إعدادات الطباعة</h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">اسم النقابة/الاتحاد</th>
						<td>
							<input type="text" name="services_union_name" value="<?php echo esc_attr( get_option( 'services_union_name' ) ); ?>" class="regular-text">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">رقم الهاتف (للطباعة)</th>
						<td>
							<input type="text" name="services_union_phone" value="<?php echo esc_attr( get_option( 'services_union_phone' ) ); ?>" class="regular-text">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">البريد الإلكتروني (للطباعة)</th>
						<td>
							<input type="email" name="services_union_email" value="<?php echo esc_attr( get_option( 'services_union_email' ) ); ?>" class="regular-text">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">بيانات التواصل الإضافية</th>
						<td>
							<textarea name="services_print_contact" rows="3" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'services_print_contact' ) ); ?></textarea>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">شعار العلامة المائية (Watermark)</th>
						<td>
							<input type="text" name="services_print_logo" value="<?php echo esc_attr( get_option( 'services_print_logo' ) ); ?>" class="regular-text">
							<p class="description">أدخل رابط (URL) لشعار النقابة ليظهر كعلامة مائية في خلفية الطباعة.</p>
						</td>
					</tr>
				</table>

				<div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h2>نصيحة للإدارة</h2>
					<p>يمكنك الآن إدارة الخدمات (إضافة، تعديل، حذف، أو إخفاء) من خلال قائمة <strong>إدارة الخدمات</strong>.</p>
					<p>كما يمكنك إدارة الفروع بشكل منفصل من خلال قائمة <strong>إدارة الفروع</strong>.</p>
					<p>استخدم الكود المختصر <code>[Services]</code> في أي صفحة لعرض الخدمات.</p>
				</div>

				<?php submit_button( 'حفظ الإعدادات' ); ?>
			</form>
		</div>
		<?php
	}

	public function add_meta_boxes() {
		// Request Meta Boxes
		add_meta_box(
			'service_request_details',
			'تفاصيل الطلب',
			array( $this, 'render_details_meta_box' ),
			'service_request',
			'normal',
			'high'
		);
		add_meta_box(
			'service_request_status',
			'حالة الطلب',
			array( $this, 'render_status_meta_box' ),
			'service_request',
			'side',
			'default'
		);

		// Service Item Meta Boxes
		add_meta_box(
			'service_item_settings',
			'إعدادات الخدمة',
			array( $this, 'render_service_item_meta_box' ),
			'service_item',
			'normal',
			'high'
		);
	}

	public function render_service_item_meta_box( $post ) {
		$icon = get_post_meta( $post->ID, '_service_icon', true );
		$payment = get_post_meta( $post->ID, '_payment_info', true );
		$hidden = get_post_meta( $post->ID, '_service_hidden', true );
		$selected_fields = get_post_meta( $post->ID, '_service_fields', true ) ?: array();
		$service_terms = get_post_meta( $post->ID, '_service_terms', true );
		$enable_complaint_title = get_post_meta( $post->ID, '_enable_complaint_title', true );
		$enable_complaint_details = get_post_meta( $post->ID, '_enable_complaint_details', true );
		$enable_notes = get_post_meta( $post->ID, '_enable_notes', true );

		wp_nonce_field( 'save_service_item', 'service_item_nonce' );
		$available_fields = Services_DB::get_available_fields();
		?>
		<table class="form-table">
			<tr>
				<th>البيانات المطلوب سحبها</th>
				<td>
					<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
						<?php foreach ($available_fields as $key => $label) : ?>
							<label style="display: block; margin-bottom: 5px;">
								<input type="checkbox" name="service_fields[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected_fields)); ?>>
								<?php echo esc_html($label); ?>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="description">اختر البيانات التي سيتم سحبها من ملف العضو وعرضها في صفحة التأكيد لهذا الطلب.</p>
				</td>
			</tr>
			<tr>
				<th>أيقونة الخدمة (Dashicon)</th>
				<td>
					<input type="text" name="service_icon" value="<?php echo esc_attr($icon); ?>" class="regular-text">
					<p class="description">أدخل اسم الأيقونة من <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank">Dashicons</a> (مثلاً: dashicons-id-alt)</p>
				</td>
			</tr>
			<tr>
				<th>تعليمات الدفع</th>
				<td>
					<textarea name="payment_info" rows="4" cols="50" class="large-text"><?php echo esc_textarea($payment); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>تعليمات/شروط خاصة بالخدمة</th>
				<td>
					<textarea name="service_terms" rows="4" cols="50" class="large-text" placeholder="تظهر هذه النصوص في صفحة التأكيد قبل الإرسال..."><?php echo esc_textarea($service_terms); ?></textarea>
					<p class="description">نصوص تظهر للمستخدم خصيصاً لهذه الخدمة في مرحلة المراجعة.</p>
				</td>
			</tr>
			<tr>
				<th>حقول إضافية للخدمة</th>
				<td>
					<label style="display:block; margin-bottom:5px;"><input type="checkbox" name="enable_complaint_title" value="1" <?php checked($enable_complaint_title, '1'); ?>> تفعيل حقل "عنوان الشكوى"</label>
					<label style="display:block; margin-bottom:5px;"><input type="checkbox" name="enable_complaint_details" value="1" <?php checked($enable_complaint_details, '1'); ?>> تفعيل حقل "تفاصيل الشكوى"</label>
					<label style="display:block; margin-bottom:5px;"><input type="checkbox" name="enable_notes" value="1" <?php checked($enable_notes, '1'); ?>> تفعيل حقل "ملاحظات"</label>
					<p class="description">هذه الحقول تظهر للمستخدم لإدخال بيانات إضافية مستقلة عن البيانات المسترجعة.</p>
				</td>
			</tr>
			<tr>
				<th>إخفاء الخدمة</th>
				<td>
					<label><input type="checkbox" name="service_hidden" value="1" <?php checked($hidden, '1'); ?>> إخفاء هذه الخدمة من الموقع</label>
				</td>
			</tr>
		</table>
		<?php
	}

	public function render_status_meta_box( $post ) {
		$current_status = get_post_meta( $post->ID, '_request_status', true );
		if ( ! $current_status ) {
			$current_status = 'pending';
		}
		$statuses = Services_DB::get_statuses();
		wp_nonce_field( 'save_service_status', 'service_status_nonce' );
		?>
		<p><label>الحالة:</label></p>
		<select name="service_request_status" class="widefat" style="margin-bottom: 10px;">
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_status, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p><label>ملاحظة التحديث (اختياري):</label></p>
		<textarea name="service_request_status_comment" class="widefat" rows="3"></textarea>
		<?php
	}

	public function save_meta_box_data( $post_id ) {
		// Save Service Item Meta
		if ( isset( $_POST['service_item_nonce'] ) && wp_verify_nonce( $_POST['service_item_nonce'], 'save_service_item' ) ) {
			update_post_meta( $post_id, '_service_icon', sanitize_text_field( $_POST['service_icon'] ) );
			update_post_meta( $post_id, '_payment_info', sanitize_textarea_field( $_POST['payment_info'] ) );
			update_post_meta( $post_id, '_service_terms', sanitize_textarea_field( $_POST['service_terms'] ) );
			update_post_meta( $post_id, '_enable_complaint_title', isset( $_POST['enable_complaint_title'] ) ? '1' : '0' );
			update_post_meta( $post_id, '_enable_complaint_details', isset( $_POST['enable_complaint_details'] ) ? '1' : '0' );
			update_post_meta( $post_id, '_enable_notes', isset( $_POST['enable_notes'] ) ? '1' : '0' );
			update_post_meta( $post_id, '_service_hidden', isset( $_POST['service_hidden'] ) ? '1' : '0' );
			
			$fields = isset($_POST['service_fields']) ? (array) $_POST['service_fields'] : array();
			update_post_meta( $post_id, '_service_fields', array_map('sanitize_text_field', $fields) );
		}

		if ( ! isset( $_POST['service_status_nonce'] ) || ! wp_verify_nonce( $_POST['service_status_nonce'], 'save_service_status' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( isset( $_POST['service_request_status'] ) ) {
			$new_status = sanitize_text_field( $_POST['service_request_status'] );
			$old_status = get_post_meta( $post_id, '_request_status', true );
			
			$status_comment = isset( $_POST['service_request_status_comment'] ) ? sanitize_textarea_field( $_POST['service_request_status_comment'] ) : '';
			
			if ( $new_status !== $old_status || !empty($status_comment) ) {
				update_post_meta( $post_id, '_request_status', $new_status );
				
				$history = get_post_meta( $post_id, '_status_history', true );
				if ( ! is_array( $history ) ) {
					$history = array();
				}
				
				$statuses = Services_DB::get_statuses();
				$history[] = array(
					'status'  => $new_status,
					'label'   => isset( $statuses[ $new_status ] ) ? $statuses[ $new_status ] : $new_status,
					'comment' => $status_comment,
					'date'    => current_time( 'mysql' ),
				);
				update_post_meta( $post_id, '_status_history', $history );
			}
		}
	}

	public function handle_quick_print() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'غير مسموح' );
		$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
		$post = get_post($request_id);
		if ( !$post || $post->post_type !== 'service_request' ) wp_die( 'طلب غير صالح' );

		echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">';
		echo '<title>طباعة طلب - ' . get_the_title($post->ID) . '</title>';
		$this->admin_styles();
		echo '<style>
			@media print { .no-print { display: none !important; } }
			body { 
				padding: 20px; 
				font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
			}
		</style>';
		echo '</head><body onload="window.print();">';
		$this->render_details_meta_box($post, true);
		echo '</body></html>';
		exit;
	}

	public function render_details_meta_box( $post, $is_quick_print = false ) {
		$data = get_post_meta( $post->ID, '_service_data', true );
		$service_key = get_post_meta( $post->ID, '_service_type', true );
		$confirmation_code = get_the_title($post->ID);
		$status_key = get_post_meta( $post->ID, '_request_status', true ) ?: 'pending';
		$statuses = Services_DB::get_statuses();
		$status_label = isset($statuses[$status_key]) ? $statuses[$status_key] : $status_key;
		$history = get_post_meta( $post->ID, '_status_history', true );
		
		if ( is_numeric( $service_key ) ) {
			$service_post = get_post( $service_key );
			$service_title = $service_post ? $service_post->post_title : 'خدمة غير معروفة';
		} else {
			$service_title = $service_key;
		}

		$union_name = get_option('services_union_name', 'اسم النقابة');
		$union_phone = get_option('services_union_phone', 'رقم الهاتف');
		$union_email = get_option('services_union_email', 'البريد الإلكتروني');
		$contact_details = get_option('services_print_contact', 'بيانات التواصل');
		$watermark = get_option('services_print_logo');

		echo '<div style="direction: rtl;" class="service-details-meta-box">';
		
		if ( !$is_quick_print ) {
			echo '<div class="no-print" id="print-config-panel">
					<div class="config-item">
						<strong>إعدادات الطباعة:</strong>
					</div>
					<div class="config-item">
						<label>حجم الخط:</label>
						<input type="number" id="print-font-size" value="16" min="10" max="24" onchange="updatePrintConfig()" style="width:60px;"> px
					</div>
					<div class="config-item">
						<label>الهوامش:</label>
						<input type="number" id="print-margin-val" value="10" min="5" max="40" onchange="updatePrintConfig()" style="width:60px;"> mm
					</div>
					<button type="button" class="button button-primary" onclick="window.print();">
						<span class="dashicons dashicons-printer" style="margin-top:4px;"></span> طباعة الآن
					</button>
				  </div>';
		}

		// Print Header
		echo '<div class="print-header" style="display:none; width:100%;">';
		echo '<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #333; padding-bottom:10px; margin-bottom:20px;">';
		echo '<div><h2 style="margin:0;">' . esc_html($union_name) . '</h2><p style="margin:5px 0 0;">نموذج طلب خدمة إلكتروني</p></div>';
		if ($watermark) {
			echo '<div><img src="' . esc_url($watermark) . '" style="max-height:80px;"></div>';
		}
		echo '</div></div>';

		echo '<div class="print-content-wrapper">';
		if ($watermark) {
			echo '<div class="print-watermark" style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); opacity:0.1; z-index:-1;">';
			echo '<img src="' . esc_url($watermark) . '" style="max-width:400px;">';
			echo '</div>';
		}

		echo '<div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px dashed #ccc; padding-bottom:8px;">';
		echo '<div><strong>كود الطلب:</strong> ' . esc_html($confirmation_code) . '</div>';
		echo '<div><strong>الحالة الحالية:</strong> <span class="status-badge ' . esc_attr($status_key) . '">' . esc_html($status_label) . '</span></div>';
		echo '<div><strong>التاريخ:</strong> ' . get_the_date('Y/m/d', $post->ID) . '</div>';
		echo '</div>';

		echo '<h3 style="text-align:center; background:#f4f4f4; padding:8px; margin: 15px 0; border:1px solid #ddd; font-size: 1.2em;">' . esc_html( $service_title ) . '</h3>';
		
		if ( is_array( $data ) ) {
			echo '<table class="widefat striped print-table" style="width:100%; border-collapse:collapse; table-layout: fixed; margin-bottom: 20px;">';
			
			// Custom Grouped Rows
			$grouped_rows = array(
				array('الاسم الكامل', 'الرقم القومي'),
				array('رقم العضوية', 'تاريخ انتهاء العضوية')
			);
			
			$processed_keys = array();
			
			foreach ( $grouped_rows as $row_keys ) {
				echo '<tr>';
				foreach ( $row_keys as $key ) {
					$val = isset($data[$key]) ? $data[$key] : '-';
					echo '<td style="padding:8px 12px; width:20%; background:#fafafa; font-weight:bold; border:1px solid #000;">' . esc_html($key) . '</td>';
					echo '<td style="padding:8px 12px; width:30%; border:1px solid #000;">' . esc_html($val) . '</td>';
					$processed_keys[] = $key;
				}
				echo '</tr>';
			}

			// Remaining fields
			foreach ( $data as $label => $value ) {
				if ( in_array($label, $processed_keys) ) continue;
				
				echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:8px 12px; width:30%; background:#fafafa; font-weight:bold; overflow: hidden; word-wrap: break-word; border:1px solid #000;">' . esc_html( $label ) . '</td><td colspan="3" style="padding:8px 12px; overflow: hidden; word-wrap: break-word; border:1px solid #000;">';
				if ( filter_var($value, FILTER_VALIDATE_URL) && preg_match('/\.(jpg|jpeg|png|gif|pdf|doc|docx)$/i', $value) ) {
					if ( preg_match('/\.(jpg|jpeg|png|gif)$/i', $value) ) {
						echo '<div class="file-preview"><a href="' . esc_url($value) . '" target="_blank"><img src="' . esc_url($value) . '" style="max-width: 250px; display: block; margin-bottom: 5px;"> عرض الملف</a></div>';
					} else {
						echo '<a href="' . esc_url($value) . '" target="_blank" class="button no-print">عرض المستند</a><span class="only-print">مستند مرفق (راجع لوحة التحكم)</span>';
					}
				} else {
					echo esc_html( $value );
				}
				echo '</td></tr>';
			}
			echo '</table>';
		}

		if ( is_array($history) && !empty($history) ) {
			echo '<div class="history-section" style="margin-top: 20px;">';
			echo '<h4 style="border-right: 4px solid #333; padding-right: 10px; margin-bottom: 10px; font-size:1.1em;">سجل حالات الطلب والتعليقات:</h4>';
			echo '<table style="width:100%; border-collapse: collapse; border:1px solid #000;">';
			echo '<thead style="background:#f9f9f9;"><tr><th style="border:1px solid #000; padding:8px;">الحالة</th><th style="border:1px solid #000; padding:8px;">التاريخ</th><th style="border:1px solid #000; padding:8px;">ملاحظات</th></tr></thead>';
			echo '<tbody>';
			foreach ($history as $entry) {
				echo '<tr>';
				echo '<td style="border:1px solid #000; padding:8px;">' . esc_html($entry['label']) . '</td>';
				echo '<td style="border:1px solid #000; padding:8px;">' . esc_html(date_i18n('Y-m-d H:i', strtotime($entry['date']))) . '</td>';
				echo '<td style="border:1px solid #000; padding:8px;">' . esc_html($entry['comment'] ?: '-') . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table></div>';
		}

		// Document Note
		echo '<div class="document-note" style="margin-top: 25px; font-size: 0.8em; line-height: 1.5; border-top: 1px dashed #ccc; padding-top: 10px;">';
		echo '<p>تم إنشاء هذا النموذج إلكترونياً، وهو وثيقة رسمية مقدمة ومولدة بواسطة العضو. يمكن الرجوع إليها في أي وقت خلال عام واحد من تاريخ التقديم باستخدام مرجع الكود الفريد عبر موقعنا الإلكتروني.</p>';
		echo '</div>';

		// Approval and QR Section
		echo '<div style="display:flex; justify-content:center; align-items:center; margin-top: 30px; direction: rtl;">';
		echo '<div class="union-approval-section" style="text-align: center;">';
		echo '<strong style="display:block; margin-bottom:8px; font-size: 1.1em;">تتبع حالة الطلب</strong>';
		$tracking_url = home_url('/');
		$qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($tracking_url . '?track=' . $confirmation_code);
		echo '<div class="qr-code" style="margin: 0 auto 10px;"><img src="' . esc_url($qr_api) . '" width="120" height="120" style="display:block; margin:0 auto;"></div>';
		echo '<div style="font-family: monospace; font-size: 1.2rem; letter-spacing: 2px; font-weight: bold;">' . esc_html($confirmation_code) . '</div>';
		echo '</div>';
		echo '</div>';

		// Print Footer (Fixed)
		echo '<div class="print-footer" style="display:none;">';
		echo '<div style="display:flex; justify-content:space-around; align-items:center; font-weight:bold; font-size:1.1em; border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:10px;">';
		echo '<div>هاتف: ' . esc_html($union_phone) . '</div>';
		echo '<div>بريد إلكتروني: ' . esc_html($union_email) . '</div>';
		echo '</div>';
		echo '<div style="font-size:0.95em; line-height:1.4;">';
		echo wpautop(esc_html($contact_details));
		echo '</div>';
		echo '</div>';

		echo '</div>'; // print-content-wrapper
		echo '</div>'; // service-details-meta-box
	}

	public function set_custom_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['applicant_name'] = 'اسم مقدم الطلب';
		$new_columns['title'] = 'كود التأكيد';
		$new_columns['service_type'] = 'نوع الخدمة';
		$new_columns['phone'] = 'رقم الهاتف';
		$new_columns['membership_no'] = 'رقم العضوية';
		$new_columns['status'] = 'الحالة';
		$new_columns['print'] = 'الطباعة';
		$new_columns['date'] = $columns['date'];
		return $new_columns;
	}

	public function custom_column_content( $column, $post_id ) {
		$data = get_post_meta( $post_id, '_service_data', true );
		switch ( $column ) {
			case 'print':
				$print_url = admin_url('admin-ajax.php?action=print_service_request&request_id=' . $post_id);
				echo '<a href="' . $print_url . '" target="_blank" class="button button-small"><span class="dashicons dashicons-printer" style="margin-top:4px;"></span> طباعة سريعة</a>';
				break;
			case 'applicant_name':
				echo isset( $data['الاسم الكامل'] ) ? esc_html( $data['الاسم الكامل'] ) : '-';
				break;
			case 'membership_no':
				echo isset( $data['رقم العضوية'] ) ? esc_html( $data['رقم العضوية'] ) : '-';
				break;
			case 'phone':
				echo isset( $data['رقم الهاتف'] ) ? esc_html( $data['رقم الهاتف'] ) : '-';
				break;
			case 'service_type':
				$service_key = get_post_meta( $post_id, '_service_type', true );
				if ( is_numeric( $service_key ) ) {
					echo esc_html( get_the_title( $service_key ) );
				} else {
					echo esc_html( $service_key );
				}
				break;
			case 'status':
				$status = get_post_meta( $post_id, '_request_status', true );
				if ( ! $status ) $status = 'pending';
				$statuses = Services_DB::get_statuses();
				$colors = array(
					'pending'    => '#ff9800',
					'processing' => '#2196f3',
					'completed'  => '#4caf50',
					'cancelled'  => '#f44336',
				);
				$color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#999';
				echo '<span style="background:' . $color . '; color:#fff; padding:3px 8px; border-radius:3px; font-size:0.9em;">' . esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status ) . '</span>';
				break;
		}
	}

	public function add_admin_filters() {
		global $typenow;
		if ( $typenow === 'service_request' ) {
			// Service Type Filter
			$services = Services_DB::get_services();
			$current_service = isset( $_GET['service_type_filter'] ) ? $_GET['service_type_filter'] : '';
			?>
			<select name="service_type_filter">
				<option value=""><?php _e( 'كل الخدمات', 'services' ); ?></option>
				<?php foreach ( $services as $id => $service ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current_service, $id ); ?>>
						<?php echo esc_html( $service['title'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			
			<?php
			// Status Filter
			$statuses = Services_DB::get_statuses();
			$current_status = isset( $_GET['status_filter'] ) ? $_GET['status_filter'] : '';
			?>
			<select name="status_filter">
				<option value=""><?php _e( 'كل الحالات', 'services' ); ?></option>
				<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_status, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}
}

// To make the filter actually work, we need a pre_get_posts hook, but I'll add that in a moment or here.
add_action( 'pre_get_posts', function( $query ) {
	if ( is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === 'service_request' ) {
		$meta_query = array();

		if ( ! empty( $_GET['service_type_filter'] ) ) {
			$meta_query[] = array(
				'key'   => '_service_type',
				'value' => $_GET['service_type_filter'],
			);
		}

		if ( ! empty( $_GET['status_filter'] ) ) {
			$meta_query[] = array(
				'key'   => '_request_status',
				'value' => $_GET['status_filter'],
			);
		}

		if ( ! empty( $meta_query ) ) {
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'AND';
			}
			$query->set( 'meta_query', $meta_query );
		}
	}
} );
