<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Files_Login {

	public function render_login_form() {
		ob_start();
		$step = isset( $_GET['step'] ) ? $_GET['step'] : '1';
		$national_id = isset( $_GET['nid'] ) ? sanitize_text_field( $_GET['nid'] ) : '';
		?>
		<div class="member-files-container registration-box-v3" dir="rtl">
			<h2 class="form-title">تسجيل الدخول</h2>
            <div class="form-subtitle">
                <a href="?action=register">ليس لديك حساب؟ تسجيل جديد</a>
            </div>

			<?php if ( isset( $_GET['login_error'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( urldecode( $_GET['login_error'] ) ); ?></p></div>
			<?php endif; ?>

			<?php if ( $step == '1' ) : ?>
				<form action="" method="post" class="member-files-form login-form-pro">
					<?php wp_nonce_field( 'member_files_login_step1', 'mf_login_nonce' ); ?>
					<div class="form-group">
						<input type="text" name="mf_national_id" id="mf_national_id" placeholder="أدخل الرقم القومي (14 رقم)" required pattern="[0-9]{14}">
					</div>
					<div class="form-actions">
						<button type="submit" name="mf_login_request_otp">دخول آمن عبر رمز التحقق</button>
					</div>
				</form>
			<?php else : ?>
				<form action="" method="post" class="member-files-form login-form-pro">
					<?php wp_nonce_field( 'member_files_login_step2', 'mf_login_nonce' ); ?>
					<input type="hidden" name="mf_national_id" value="<?php echo esc_attr( $national_id ); ?>">
					<div class="form-group">
						<p class="otp-notice"><span class="dashicons dashicons-email-alt"></span> تم إرسال الرمز لبريدك الإلكتروني.</p>
						<input type="text" name="mf_otp" id="mf_otp" placeholder="أدخل رمز التحقق (OTP)" required>
					</div>
					<div class="form-actions">
						<button type="submit" name="mf_login_verify_otp">التحقق والدخول</button>
					</div>
				</form>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_login_request() {
		if ( ! isset( $_POST['mf_login_request_otp'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['mf_login_nonce'], 'member_files_login_step1' ) ) {
			return;
		}

		$national_id = sanitize_text_field( $_POST['mf_national_id'] );
		$user = get_user_by( 'login', $national_id );

		if ( ! $user ) {
			wp_redirect( add_query_arg( array( 'action' => 'login', 'login_error' => urlencode( 'عذراً، الرقم القومي المدخل غير صحيح أو غير مسجل في النظام.' ) ), wp_get_referer() ) );
			exit;
		}

        // Check if pending
        if ( in_array( 'pending_member', (array) $user->roles ) ) {
            wp_redirect( add_query_arg( array( 'action' => 'login', 'login_error' => urlencode( 'طلبك قيد المراجعة حالياً من قبل الإدارة. سيصلك بريد إلكتروني فور تفعيل الحساب.' ) ), wp_get_referer() ) );
			exit;
        }

        // Check if approved (union_member)
        if ( ! in_array( 'union_member', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
            wp_redirect( add_query_arg( array( 'action' => 'login', 'login_error' => urlencode( 'عذراً، لم يتم تفعيل هذا الحساب بعد. يرجى التواصل مع الدعم الفني.' ) ), wp_get_referer() ) );
			exit;
        }

		// Generate OTP
		$otp = rand( 100000, 999999 );
		update_user_meta( $user->ID, 'mf_login_otp', $otp );
		update_user_meta( $user->ID, 'mf_login_otp_time', time() );

		// Send email using professional template
        $admin = new Member_Files_Admin();
        $subject = get_option('mf_email_otp_subject', 'رمز التحقق الخاص بك - نظام الحسابات');
        $body    = get_option('mf_email_otp_body', "رمز التحقق الخاص بك هو: {otp}\n\nهذا الرمز صالح لمدة 10 دقائق لتسجيل الدخول إلى حسابك.");
        $body    = str_replace('{otp}', $otp, $body);

		$message = $admin->get_email_template(
            'رمز التحقق (OTP)',
            $body
        );
        add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
		wp_mail( $user->user_email, $subject, $message );

		wp_redirect( add_query_arg( array( 'action' => 'login', 'step' => '2', 'nid' => $national_id ), wp_get_referer() ) );
		exit;
	}

    public function set_html_content_type() {
        return 'text/html';
    }

	public function handle_otp_verification() {
		if ( ! isset( $_POST['mf_login_verify_otp'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['mf_login_nonce'], 'member_files_login_step2' ) ) {
			return;
		}

		$national_id = sanitize_text_field( $_POST['mf_national_id'] );
		$otp_entered = sanitize_text_field( $_POST['mf_otp'] );

		$user = get_user_by( 'login', $national_id );

		if ( ! $user ) {
			wp_redirect( add_query_arg( array( 'action' => 'login', 'login_error' => urlencode( 'خطأ في النظام.' ) ), wp_get_referer() ) );
			exit;
		}

		$stored_otp = get_user_meta( $user->ID, 'mf_login_otp', true );
		$otp_time   = get_user_meta( $user->ID, 'mf_login_otp_time', true );

		if ( $stored_otp == $otp_entered && ( time() - $otp_time ) < 600 ) {
			// Success
			delete_user_meta( $user->ID, 'mf_login_otp' );
			delete_user_meta( $user->ID, 'mf_login_otp_time' );

			wp_set_auth_cookie( $user->ID );
            Member_Files_Logger::log( $user->ID, 'تسجيل دخول', 'تم تسجيل الدخول عبر OTP' );
            update_user_meta( $user->ID, 'mf_last_login', time() );
			wp_redirect( remove_query_arg( array( 'action', 'step', 'nid', 'login_error' ), wp_get_referer() ) );
			exit;
		} else {
			wp_redirect( add_query_arg( array( 'action' => 'login', 'step' => '2', 'nid' => $national_id, 'login_error' => urlencode( 'رمز التحقق غير صحيح أو منتهي الصلاحية.' ) ), wp_get_referer() ) );
			exit;
		}
	}
}
