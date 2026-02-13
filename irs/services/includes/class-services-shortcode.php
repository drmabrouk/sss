<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Services_Shortcode {

	public function __construct() {
		add_shortcode( 'Services', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_ajax_submit_service_request', array( $this, 'handle_form_submission' ) );
		add_action( 'wp_ajax_nopriv_submit_service_request', array( $this, 'handle_form_submission' ) );
		add_action( 'wp_ajax_track_service_request', array( $this, 'handle_tracking_request' ) );
		add_action( 'wp_ajax_nopriv_track_service_request', array( $this, 'handle_tracking_request' ) );
		add_action( 'wp_ajax_get_member_data', array( $this, 'handle_get_member_data' ) );
		add_action( 'wp_ajax_nopriv_get_member_data', array( $this, 'handle_get_member_data' ) );
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'services-style', SERVICES_URL . 'public/style.css', array( 'dashicons' ), SERVICES_VERSION );
	}

	public function render_shortcode() {
		$services = Services_DB::get_services();
		$branches = Services_DB::get_branches();
		$categories = get_terms( array( 'taxonomy' => 'service_category', 'hide_empty' => true ) );
		ob_start();
		?>
		<div id="services-container" class="services-container" style="direction: rtl; text-align: right;">
			
			<div id="tracking-container" class="tracking-section gray-bg">
				<div class="tracking-header">
					<h3>تتبع طلبك</h3>
					<p>أدخل كود التأكيد الخاص بك لمتابعة حالة الطلب</p>
				</div>
				<form id="service-tracking-form" class="tracking-form">
					<input type="hidden" name="action" value="track_service_request">
					<div class="tracking-container-row">
						<input type="text" id="track_code_input" name="confirmation_code" required placeholder="أدخل كود التتبع الخاص بك" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
						<button type="submit" class="button primary wider-btn" id="track-btn">تتبع الآن</button>
					</div>
				</form>
				<p class="tracking-note-small">يمكنكم استخدام كود التتبع المكون من 13 رقماً لمعرفة حالة طلبكم والمرحلة التي وصل إليها حالياً.</p>
				<div id="tracking-response"></div>
			</div>

			<div class="services-layout-wrapper">
				<aside class="services-sidebar">
					<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<div class="services-filter">
							<h4 class="filter-title">تصنيفات الخدمات</h4>
							<button class="filter-btn active" data-filter="all">الكل</button>
							<?php foreach ( $categories as $cat ) : ?>
								<button class="filter-btn" data-filter="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</aside>

				<div id="services-list" class="services-grid">
					<?php foreach ( $services as $id => $service ) : ?>
						<?php 
							$cat_classes = !empty($service['categories']) ? implode(' ', $service['categories']) : '';
						?>
						<div class="service-box <?php echo esc_attr($cat_classes); ?>" data-type="<?php echo esc_attr( $id ); ?>" data-title="<?php echo esc_attr( $service['title'] ); ?>">
							<div class="service-box-icon">
								<span class="dashicons <?php echo esc_attr( !empty($service['icon']) ? $service['icon'] : 'dashicons-forms' ); ?>"></span>
							</div>
							<h3><?php echo esc_html( $service['title'] ); ?></h3>
							<button class="button secondary"><?php _e( 'طلب الخدمة', 'services' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div id="service-form-container" style="display: none;" class="submission-container">
				<div class="form-header">
					<h2 id="service-title"></h2>
					<button onclick="hideServiceForm()" class="button secondary"><?php _e( 'رجوع', 'services' ); ?> <span class="dashicons dashicons-arrow-left-alt"></span></button>
				</div>

				<div id="form-loading" style="display: none; text-align:center; padding:60px;">
					<span class="dashicons dashicons-update spin" style="font-size:50px; width:50px; height:50px; color:var(--ast-global-color-0, #007bff);"></span>
					<p style="margin-top:20px; font-size:1.2rem;">جاري استيراد بياناتك وتجهيز الطلب...</p>
				</div>

				<div id="confirm-data-container" style="display: none;">
					<div class="professional-confirmation">
						<div class="confirmation-header-area">
							<h3><span class="dashicons dashicons-id-alt"></span> استكمال بيانات الطلب</h3>
							<p>يرجى استكمال الحقول التالية (إن وجدت) والموافقة على الشروط لإرسال طلبكم.</p>
						</div>

						<div id="custom-fields-container" style="margin-bottom: 30px;"></div>
						
						<div id="service-specific-terms" class="service-specific-note" style="display:none;"></div>

						<div id="terms-container" class="terms-acceptance-box">
							<label>
								<input type="checkbox" id="agree-terms">
								<span>أقر أنا مقدم الطلب بصحة البيانات المدخلة والمسترجعة من حسابي، وأوافق على معالجة الطلب إلكترونياً.</span>
							</label>
						</div>

						<div id="confirmation-actions" class="confirmation-footer">
							<button class="button primary large" id="confirm-data-btn" disabled>تأكيد وإرسال الطلب الآن</button>
						</div>
					</div>
				</div>

				<div id="form-response"></div>
				<?php wp_nonce_field( 'submit_service_request', 'services_nonce' ); ?>
			</div>
		</div>

		<script>
		// Category Filtering
		document.querySelectorAll('.filter-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
				this.classList.add('active');
				var filter = this.getAttribute('data-filter');
				var boxes = document.querySelectorAll('.service-box');
				boxes.forEach(function(box) {
					if (filter === 'all' || box.classList.contains(filter)) {
						box.style.display = 'flex';
					} else {
						box.style.display = 'none';
					}
				});
			});
		});

		var currentServiceData = null;
		var isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;

		document.querySelectorAll('.service-box').forEach(function(box) {
			box.addEventListener('click', function() {
				if (!isLoggedIn) {
					showNotification('services-container', 'يرجى تسجيل الدخول أولاً لتتمكن من طلب هذه الخدمة.', 'error');
					return;
				}
				currentServiceData = {
					type: this.getAttribute('data-type'),
					title: this.getAttribute('data-title')
				};
				handleSmartRequestFlow();
			});
		});

		function handleSmartRequestFlow() {
			document.getElementById('services-list').style.display = 'none';
			document.querySelector('.services-filter').style.display = 'none';
			
			// Show container and loading state
			document.getElementById('service-form-container').style.display = 'block';
			document.getElementById('service-title').innerText = currentServiceData.title;
			document.getElementById('form-loading').style.display = 'block';
			document.getElementById('confirm-data-container').style.display = 'none';
			document.getElementById('form-response').innerHTML = '';
			
			// Auto lookup for logged in user
			handleLookup(null, true); 
		}

		var retrievedData = null;

		function handleLookup() {
			var formData = new FormData();
			formData.append('action', 'get_member_data');

			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					retrievedData = data.data.data;
					checkDataCompleteness(retrievedData);
				} else {
					// No data found found, checkDataCompleteness with empty object will trigger the profile notice
					checkDataCompleteness({});
				}
			});
		}

		function showDataConfirmation(data) {
			document.getElementById('form-loading').style.display = 'none';
			document.getElementById('confirm-data-container').style.display = 'block';
			
			var services = <?php echo json_encode($services); ?>;
			var service = services[currentServiceData.type];

			var customFieldsHtml = '';
			if (service.enable_complaint_title === '1') {
				customFieldsHtml += '<div class="custom-field-item" style="margin-bottom:20px;">' +
					'<label style="display:block; font-weight:bold; margin-bottom:8px;">عنوان الشكوى:</label>' +
					'<input type="text" id="custom_complaint_title" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;" placeholder="أدخل عنوان الشكوى هنا...">' +
					'</div>';
			}
			if (service.enable_complaint_details === '1') {
				customFieldsHtml += '<div class="custom-field-item" style="margin-bottom:20px;">' +
					'<label style="display:block; font-weight:bold; margin-bottom:8px;">تفاصيل الشكوى:</label>' +
					'<textarea id="custom_complaint_details" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;" rows="4" placeholder="أدخل تفاصيل الشكوى هنا..."></textarea>' +
					'</div>';
			}
			if (service.enable_notes === '1') {
				customFieldsHtml += '<div class="custom-field-item" style="margin-bottom:20px;">' +
					'<label style="display:block; font-weight:bold; margin-bottom:8px;">ملاحظات إضافية:</label>' +
					'<textarea id="custom_notes" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;" rows="3" placeholder="أدخل أي ملاحظات أخرى..."></textarea>' +
					'</div>';
			}
			
			document.getElementById('custom-fields-container').innerHTML = customFieldsHtml;
			
			// Show service specific terms if available
			var termsBox = document.getElementById('service-specific-terms');
			if (service.terms && service.terms.trim() !== '') {
				termsBox.innerHTML = '<div class="terms-content"><strong>تعليمات إضافية:</strong><br>' + service.terms.replace(/\n/g, '<br>') + '</div>';
				termsBox.style.display = 'block';
			} else {
				termsBox.style.display = 'none';
			}

			document.getElementById('agree-terms').checked = false;
			document.getElementById('confirm-data-btn').disabled = true;
			document.getElementById('confirm-data-btn').onclick = function() {
				autoSubmitRequest(data);
			};

			document.getElementById('agree-terms').onchange = function() {
				document.getElementById('confirm-data-btn').disabled = !this.checked;
			};
		}

		function checkDataCompleteness(data) {
			var availableFieldsMap = <?php echo json_encode(Services_DB::get_available_fields()); ?>;
			var services = <?php echo json_encode($services); ?>;
			var service = services[currentServiceData.type];
			var requestedKeys = service.fields || [];
			
			var missingEssential = [];
			
			requestedKeys.forEach(function(key) {
				var label = availableFieldsMap[key];
				if (!data[label] || data[label].trim() === '') {
					missingEssential.push(label);
				}
			});

			if (missingEssential.length > 0) {
				document.getElementById('form-loading').style.display = 'none';
				var responseContainer = document.getElementById('form-response');
				responseContainer.innerHTML = '<div class="notice-box error-notice">' +
					'<span class="dashicons dashicons-warning"></span>' +
					'<h3>بيانات ملفكم الشخصي غير مكتملة</h3>' +
					'<p>يتطلب هذا الطلب استكمال البيانات التالية في حسابكم: (' + missingEssential.join('، ') + ').</p>' +
					'<a href="/profile/" class="button primary">تحديث الملف الشخصي الآن</a>' +
					'</div>';
			} else {
				showDataConfirmation(data);
			}
		}

		function autoSubmitRequest(data) {
			var formData = new FormData();
			formData.append('action', 'submit_service_request');
			formData.append('service_type', currentServiceData.type);
			formData.append('services_nonce', document.querySelector('[name="services_nonce"]').value);
			formData.append('agree_terms', '1');
			
			var availableFieldsMap = <?php echo json_encode(Services_DB::get_available_fields()); ?>;
			var services = <?php echo json_encode($services); ?>;
			var service = services[currentServiceData.type];
			var requestedKeys = service.fields || [];

			requestedKeys.forEach(function(fieldKey) {
				var label = availableFieldsMap[fieldKey];
				if (data[label]) {
					formData.append(fieldKey, data[label]);
				}
			});

			if (document.getElementById('custom_complaint_title')) {
				formData.append('complaint_title', document.getElementById('custom_complaint_title').value);
			}
			if (document.getElementById('custom_complaint_details')) {
				formData.append('complaint_details', document.getElementById('custom_complaint_details').value);
			}
			if (document.getElementById('custom_notes')) {
				formData.append('notes', document.getElementById('custom_notes').value);
			}

			// Update UI to submission loading
			document.getElementById('confirm-data-container').style.display = 'none';
			document.getElementById('form-loading').style.display = 'block';
			document.getElementById('form-loading').querySelector('p').innerText = 'جاري إرسال الطلب وحفظه في النظام...';

			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(res => {
				document.getElementById('form-loading').style.display = 'none';
				if (res.success) {
					var successHtml = '<div class="success-confirmation-new">' +
						'<div class="success-header">' +
							'<h3><span class="dashicons dashicons-yes-alt"></span> ' + res.data.message + '</h3>' +
						'</div>' +
						'<div class="code-display-box">' +
							'<span class="code-label">كود التأكيد الخاص بك</span>' +
							'<div class="code-wrapper">' +
								'<strong id="confirmation-code-val">' + res.data.code + '</strong>' +
								'<button onclick="copyToClipboard(\'' + res.data.code + '\', this)" class="copy-btn" title="نسخ الكود">' +
									'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>' +
								'</button>' +
							'</div>' +
						'</div>' +
						'<div class="payment-info-box">' +
							'<h5><span class="dashicons dashicons-money-alt"></span> تعليمات إتمام الطلب والدفع:</h5>' +
							'<div class="payment-content">' + res.data.payment + '</div>' +
						'</div>' +
						'<p class="final-note">يرجى الاحتفاظ بالكود لتتبع حالة الطلب من خلال محرك التتبع في هذه الصفحة.</p>' +
						'</div>';
					document.getElementById('form-response').innerHTML = successHtml;
				} else {
					showNotification('form-response', res.data, 'error');
				}
			});
		}

		function copyToClipboard(text, btn) {
			navigator.clipboard.writeText(text).then(function() {
				var originalHtml = btn.innerHTML;
				btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
				setTimeout(function() {
					btn.innerHTML = originalHtml;
				}, 2000);
			});
		}


		function hideServiceForm() {
			document.getElementById('services-list').style.display = 'grid';
			document.querySelector('.services-filter').style.display = 'flex';
			document.getElementById('service-form-container').style.display = 'none';
		}

		function showNotification(containerId, message, type) {
			var container = document.getElementById(containerId);
			var icon = type === 'success' ? 'yes' : 'warning';
			container.innerHTML = '<div class="notification ' + type + '">' +
				'<span class="dashicons dashicons-' + icon + '"></span>' +
				'<div>' + message + '</div>' +
				'</div>';
			container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}

		// Auto-track if URL param present
		window.addEventListener('load', function() {
			var urlParams = new URLSearchParams(window.location.search);
			var trackCode = urlParams.get('track');
			if (trackCode) {
				document.getElementById('track_code_input').value = trackCode;
				document.getElementById('service-tracking-form').dispatchEvent(new Event('submit'));
			}
		});

		document.getElementById('service-tracking-form').addEventListener('submit', function(e) {
			e.preventDefault();
			var formData = new FormData(this);
			var responseContainer = document.getElementById('tracking-response');
			responseContainer.innerHTML = '<p style="text-align:center;">جاري البحث...</p>';

			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					responseContainer.innerHTML = data.data;
					responseContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				} else {
					showNotification('tracking-response', data.data, 'error');
				}
			});
		});

		</script>
		<?php
		return ob_get_clean();
	}

	public function handle_form_submission() {
		check_ajax_referer( 'submit_service_request', 'services_nonce' );

		if ( is_user_logged_in() && (!isset($_POST['agree_terms']) || $_POST['agree_terms'] !== '1') ) {
			wp_send_json_error( 'يجب الموافقة على الشروط والأحكام للمتابعة.' );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$service_type = sanitize_text_field( $_POST['service_type'] );
		$services = Services_DB::get_services();
		
		if ( ! isset( $services[ $service_type ] ) ) {
			wp_send_json_error( 'خدمة غير صالحة' );
		}

		$data = array();
		$labels = Services_DB::get_available_fields();

		// Validation: National ID (14 digits)
		if (isset($_POST['nid'])) {
			$national_id = sanitize_text_field($_POST['nid']);
			if (!preg_match('/^[0-9]{14}$/', $national_id)) {
				wp_send_json_error('رقم الهوية الوطنية يجب أن يكون 14 رقماً.');
			}
		}

		// Validation: Phone (starts with 010, 011, 012, 015 and followed by 8 digits)
		if (isset($_POST['phone'])) {
			$phone = sanitize_text_field($_POST['phone']);
			if (!preg_match('/^(010|011|012|015)[0-9]{8}$/', $phone)) {
				wp_send_json_error('رقم الهاتف غير صحيح. يجب أن يبدأ بـ 010 أو 011 أو 012 أو 015 ويتكون من 11 رقماً (أرقام فقط).');
			}
		}

		// Validation: Membership Number (min 2 digits)
		if (isset($_POST['member_no'])) {
			$membership_number = sanitize_text_field($_POST['member_no']);
			if (!preg_match('/^[0-9]{2,}$/', $membership_number)) {
				wp_send_json_error('رقم العضوية يجب أن يتكون من رقمين على الأقل (أرقام فقط).');
			}
		}


		foreach ( $labels as $key => $label ) {
			if ( isset( $_POST[ $key ] ) ) {
				$data[ $label ] = sanitize_text_field( $_POST[ $key ] );
				
				// Update user meta if logged in
				if (is_user_logged_in()) {
					$user_id = get_current_user_id();
					update_user_meta($user_id, $key, $data[$label]);
					
					// Technical Linkages for Saving to Core Profile
					if ($key === 'name') {
						wp_update_user(array('ID' => $user_id, 'display_name' => $data[$label]));
						update_user_meta($user_id, 'first_name', $data[$label]);
					}
					if ($key === 'email') {
						$u_data = get_userdata($user_id);
						if ($u_data && $data[$label] !== $u_data->user_email && !email_exists($data[$label])) {
							wp_update_user(array('ID' => $user_id, 'user_email' => $data[$label]));
						}
					}
					if ($key === 'phone') update_user_meta($user_id, 'billing_phone', $data[$label]);
					if ($key === 'address') update_user_meta($user_id, 'billing_address_1', $data[$label]);
				}
			} elseif ( isset( $_FILES[ $key ] ) && ! empty( $_FILES[ $key ]['name'] ) ) {
				$uploaded_file = wp_handle_upload( $_FILES[ $key ], array( 'test_form' => false ) );
				if ( ! isset( $uploaded_file['error'] ) ) {
					$data[ $label ] = $uploaded_file['url'];
				} else {
					wp_send_json_error( 'خطأ في رفع الملف (' . $label . '): ' . $uploaded_file['error'] );
				}
			}
		}

		// Handle Custom Fields
		if ( !empty( $_POST['complaint_title'] ) ) {
			$data['عنوان الشكوى'] = sanitize_text_field( $_POST['complaint_title'] );
		}
		if ( !empty( $_POST['complaint_details'] ) ) {
			$data['تفاصيل الشكوى'] = sanitize_textarea_field( $_POST['complaint_details'] );
		}
		if ( !empty( $_POST['notes'] ) ) {
			$data['ملاحظات'] = sanitize_textarea_field( $_POST['notes'] );
		}

		// Validation could be added here

		// Generate unique date-based confirmation code (DDMMYYYYHH + 3 digits)
		$date_prefix = date('dmY');
		$hour = date('H');
		$full_prefix = $date_prefix . $hour;
		
		$args = array(
			'post_type'      => 'service_request',
			'posts_per_page' => 1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			's'              => $full_prefix, // Search for today's requests in this hour
		);
		$last_posts = get_posts($args);
		$last_num = 0;
		if (!empty($last_posts)) {
			$last_code = $last_posts[0]->post_title;
			if (strpos($last_code, $full_prefix) === 0) {
				$last_num = (int) substr($last_code, strlen($full_prefix));
			}
		}
		
		$new_num = $last_num + 1;
		$confirmation_code = $full_prefix . str_pad($new_num, 3, '0', STR_PAD_LEFT);

		// Final safety check for uniqueness
		$exists = true;
		while ( $exists ) {
			$check_post = get_posts( array(
				'post_type'  => 'service_request',
				'title'      => $confirmation_code,
				'fields'     => 'ids',
				'limit'      => 1
			) );
			if ( empty( $check_post ) ) {
				$exists = false;
			} else {
				$new_num++;
				$confirmation_code = $full_prefix . str_pad($new_num, 3, '0', STR_PAD_LEFT);
			}
		}
		
		$post_id = wp_insert_post( array(
			'post_title'  => $confirmation_code,
			'post_type'   => 'service_request',
			'post_status' => 'publish',
		) );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( 'حدث خطأ أثناء حفظ الطلب' );
		}

		update_post_meta( $post_id, '_service_type', $service_type );
		update_post_meta( $post_id, '_service_data', $data );
		if ( isset($national_id) ) {
			update_post_meta( $post_id, '_national_id', $national_id );
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				update_user_meta( $user_id, 'nid', $national_id );
				update_user_meta( $user_id, '_services_last_data', $data );
				
				// Determine member type
				$m_num = get_user_meta($user_id, 'member_no', true);
				update_user_meta($user_id, 'member_type', (!empty($m_num) ? 'existing' : 'new'));
			}
		}
		update_post_meta( $post_id, '_confirmation_code', $confirmation_code );
		update_post_meta( $post_id, '_request_status', 'pending' );
		
		$statuses = Services_DB::get_statuses();
		update_post_meta( $post_id, '_status_history', array(
			array(
				'status' => 'pending',
				'label'  => $statuses['pending'],
				'date'   => current_time( 'mysql' ),
			)
		) );

		// Notify Admin
		$admin_email = get_option( 'services_admin_notification_email', get_option( 'admin_email' ) );
		$subject = 'طلب خدمة جديد: ' . $services[ $service_type ]['title'];
		$message = "تم استلام طلب جديد.\n\n";
		$message .= "كود التأكيد: " . $confirmation_code . "\n";
		$message .= "الخدمة: " . $services[ $service_type ]['title'] . "\n";
		$message .= "المقدم: " . $data['الاسم الكامل'] . "\n";
		$message .= "رقم الهاتف: " . $phone . "\n";
		$message .= "يمكنك مراجعة الطلب من لوحة التحكم.\n";
		wp_mail( $admin_email, $subject, $message );

		$payment_info = get_post_meta( $service_type, '_payment_info', true );
		if ( empty( $payment_info ) ) {
			$payment_info = 'سيتم التواصل معك لتحديد طريقة الدفع.';
		}

		wp_send_json_success( array(
			'message' => 'تم استلام طلبك بنجاح. يمكنك متابعة حالة طلبك في أي وقت باستخدام كود التأكيد الرقمي عبر محرك البحث الخاص بالتتبع الموجود في هذه الصفحة.',
			'code'    => $confirmation_code,
			'payment' => nl2br( esc_html( $payment_info ) ),
		) );
	}

	public function handle_get_member_data() {
		$national_id = isset($_POST['nid']) ? sanitize_text_field($_POST['nid']) : '';
		
		if ( is_user_logged_in() && empty($national_id) ) {
			$user_id = get_current_user_id();
			$user = get_userdata($user_id);
			$national_id = get_user_meta( $user_id, 'nid', true );
			
			$data = array();
			$field_map = Services_DB::get_available_fields();
			foreach ($field_map as $key => $label) {
				$val = get_user_meta($user_id, $key, true);
				
				// Technical Linkages / Fallbacks
				if (empty($val)) {
					if ($key === 'name') $val = $user->display_name;
					if ($key === 'email') $val = $user->user_email;
					if ($key === 'phone') $val = get_user_meta($user_id, 'billing_phone', true);
					if ($key === 'member_no') $val = get_user_meta($user_id, 'membership_no', true);
					if ($key === 'address') $val = get_user_meta($user_id, 'billing_address_1', true);
				}
				
				if (!empty($val)) $data[$label] = $val;
			}
			
			if (empty($national_id) && isset($data['رقم الهوية الوطنية'])) {
				$national_id = $data['رقم الهوية الوطنية'];
			}
			
			$data = array_filter($data);
			
			if ( !empty($data) ) {
				wp_send_json_success( array( 'data' => $data, 'nid' => $national_id, 'source' => 'user' ) );
			}
		}

		if ( empty($national_id) && !is_user_logged_in() ) {
			wp_send_json_error('يرجى إدخال رقم الهوية الوطنية.');
		}

		// Search in requests
		$args = array(
			'post_type'      => 'service_request',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => '_national_id',
					'value' => $national_id,
				),
			),
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);
		
		$query = new WP_Query($args);
		if ( $query->have_posts() ) {
			$post_id = $query->posts[0]->ID;
			$data = get_post_meta( $post_id, '_service_data', true );
			wp_send_json_success( array( 'data' => $data, 'nid' => $national_id, 'source' => 'request' ) );
		}

		wp_send_json_error('لم يتم العثور على بيانات سابقة لهذا الرقم.');
	}

	public function handle_tracking_request() {
		$code = sanitize_text_field( $_POST['confirmation_code'] );
		
		$args = array(
			'post_type'   => 'service_request',
			'title'       => $code,
			'post_status' => 'publish',
			'posts_per_page' => 1
		);
		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			wp_send_json_error( 'لم يتم العثور على طلب بهذا الكود. يرجى التأكد من الكود والمحاولة مرة أخرى.' );
		}

		$post = $posts[0];
		$service_key = get_post_meta( $post->ID, '_service_type', true );
		
		if ( is_numeric( $service_key ) ) {
			$service_title = get_the_title( $service_key );
		} else {
			$service_title = $service_key;
		}
		
		$status_key = get_post_meta( $post->ID, '_request_status', true );
		if ( ! $status_key ) $status_key = 'pending';
		$statuses = Services_DB::get_statuses();
		$status_label = isset( $statuses[ $status_key ] ) ? $statuses[ $status_key ] : $status_key;

		$data = get_post_meta( $post->ID, '_service_data', true );
		$history = get_post_meta( $post->ID, '_status_history', true );
		
		ob_start();
		?>
		<div class="tracking-result-card">
			<div class="tracking-result-header">
				<h4>تفاصيل الطلب: #<?php echo esc_html($code); ?></h4>
				<span class="status-badge <?php echo esc_attr($status_key); ?>"><?php echo esc_html( $status_label ); ?></span>
			</div>
			
			<div class="tracking-grid">
				<div class="tracking-info-section">
					<h5><span class="dashicons dashicons-admin-users"></span> بيانات مقدم الطلب</h5>
					<div class="info-row">
						<strong>الاسم:</strong> <span><?php echo esc_html($data['الاسم الكامل'] ?: '-'); ?></span>
					</div>
					<div class="info-row">
						<strong>رقم الهاتف:</strong> <span><?php echo esc_html($data['رقم الهاتف'] ?: '-'); ?></span>
					</div>
					<div class="info-row">
						<strong>الفرع المختص:</strong> <span><?php echo esc_html($data['فرع النقابة التابع له'] ?: '-'); ?></span>
					</div>
				</div>
				
				<div class="tracking-info-section">
					<h5><span class="dashicons dashicons-clipboard"></span> بيانات الخدمة</h5>
					<div class="info-row">
						<strong>نوع الخدمة:</strong> <span><?php echo esc_html($service_title); ?></span>
					</div>
					<div class="info-row">
						<strong>تاريخ التقديم:</strong> <span><?php echo get_the_date( 'Y-m-d H:i', $post->ID ); ?></span>
					</div>
				</div>

				<?php 
				$custom_fields_labels = array('عنوان الشكوى', 'تفاصيل الشكوى', 'ملاحظات');
				$has_custom = false;
				if (is_array($data)) {
					foreach($custom_fields_labels as $cf) { if(!empty($data[$cf])) $has_custom = true; }
				}
				
				if ($has_custom) : ?>
				<div class="tracking-info-section" style="grid-column: 1 / -1; margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
					<h5><span class="dashicons dashicons-edit"></span> بيانات إضافية مقدمة</h5>
					<?php foreach($custom_fields_labels as $cf) : if(!empty($data[$cf])) : ?>
						<div class="info-row">
							<strong><?php echo $cf; ?>:</strong> <span style="display:block; margin-top:5px; background:#f8fafc; padding:10px; border-radius:5px;"><?php echo nl2br(esc_html($data[$cf])); ?></span>
						</div>
					<?php endif; endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( is_array( $history ) && ! empty( $history ) ) : ?>
			<div class="tracking-history-section">
				<h5><span class="dashicons dashicons-backup"></span> سجل تحديثات الطلب</h5>
				<div class="timeline">
					<?php foreach ( array_reverse($history) as $entry ) : ?>
					<div class="timeline-item">
						<div class="timeline-dot"></div>
						<div class="timeline-content" style="flex-direction:column; align-items:flex-start;">
							<div style="display:flex; justify-content:space-between; width:100%;">
								<strong><?php echo esc_html($entry['label']); ?></strong>
								<small><?php echo esc_html( date_i18n( 'Y-m-d H:i', strtotime($entry['date']) ) ); ?></small>
							</div>
							<?php if ( ! empty($entry['comment']) ) : ?>
								<div class="status-comment" style="margin-top:5px; font-size:0.9em; color:#666; border-right:2px solid #ddd; padding-right:10px;">
									<?php echo esc_html($entry['comment']); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		
		wp_send_json_success( $html );
	}
}
