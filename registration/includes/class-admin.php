<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Files_Admin {

	public function add_plugin_admin_menu() {
        $pending_reg = count( get_users( array( 'role' => 'pending_member' ) ) );
        $pending_upd = count( get_users( array( 'meta_key' => 'mf_pending_settings_update', 'compare' => 'EXISTS' ) ) );
        $total_pending = $pending_reg + $pending_upd;

        $menu_title = 'إدارة الحسابات';
        if ( $total_pending > 0 ) {
            $menu_title .= ' <span class="awaiting-mod update-plugins count-' . $total_pending . '"><span class="pending-count">' . $total_pending . '</span></span>';
        }

		add_menu_page(
			'إدارة الحسابات',
			$menu_title,
			'manage_options',
			'member-requests',
			array( $this, 'display_requests_page' ),
			'dashicons-admin-users',
			30
		);


        add_submenu_page(
            'member-requests',
            'إحصائيات ومراقبة',
            'الإحصائيات',
            'manage_options',
            'member-stats',
            array( $this, 'display_stats_page' )
        );

        $updates_title = 'تعديلات معلقة';
        if ( $pending_upd > 0 ) {
            $updates_title .= ' <span class="awaiting-mod update-plugins count-' . $pending_upd . '"><span class="pending-count">' . $pending_upd . '</span></span>';
        }

        add_submenu_page(
            'member-requests',
            'طلبات تعديل البيانات',
            $updates_title,
            'manage_options',
            'member-updates',
            array( $this, 'display_updates_page' )
        );

        add_submenu_page(
            'member-requests',
            'إضافة عضو جديد',
            'إضافة عضو',
            'manage_options',
            'member-add',
            array( $this, 'display_add_member_page' )
        );

        add_submenu_page(
            'member-requests',
            'استيراد الأعضاء',
            'استيراد (Excel)',
            'manage_options',
            'member-import-csv',
            array( $this, 'display_import_page' )
        );

        add_submenu_page(
            'member-requests',
            'إدارة المحافظات',
            'المحافظات',
            'manage_options',
            'member-governorates',
            array( $this, 'display_governorates_page' )
        );

        add_submenu_page(
            'member-requests',
            'إعدادات البريد',
            'إعدادات البريد',
            'manage_options',
            'member-email-settings',
            array( $this, 'display_email_settings_page' )
        );

        add_submenu_page(
            null,
            'تصدير البيانات',
            'تصدير',
            'manage_options',
            'member-export',
            array( $this, 'handle_export_print' )
        );
	}

    public function display_governorates_page() {
        if ( isset($_POST['mf_save_governorates']) ) {
            check_admin_referer('mf_governorates');
            $govs = sanitize_textarea_field($_POST['governorates_list']);
            update_option('mf_governorates_list', $govs);
            echo '<div class="updated"><p>تم حفظ قائمة المحافظات بنجاح.</p></div>';
        }

        $govs_list = get_option('mf_governorates_list', "القاهرة\nالجيزة\nالإسكندرية\nالقليوبية\nالدقهلية\nالغربية\nالشرقية\nالمنوفية\nدمياط\nكفر الشيخ\nالبحيرة\nالإسماعيلية\nبورسعيد\nالسويس\nمرسى مطروح\nشمال سيناء\nجنوب سيناء\nبني سويف\nالفيوم\nالمنيا\nأسيوط\nسوهاج\nقنا\nالأقصر\nأسوان\nالبحر الأحمر\nالوادي الجديد");

        ?>
        <div class="wrap" dir="rtl">
            <h1>إدارة المحافظات</h1>
            <p class="description">أدخل أسماء المحافظات، كل محافظة في سطر منفصل.</p>
            <form action="" method="post">
                <?php wp_nonce_field( 'mf_governorates' ); ?>
                <div class="postbox" style="padding: 20px; margin-top: 20px;">
                    <textarea name="governorates_list" rows="15" class="large-text" style="font-family: Arial; font-size: 14px;"><?php echo esc_textarea($govs_list); ?></textarea>
                </div>
                <p class="submit"><button type="submit" name="mf_save_governorates" class="button button-primary">حفظ القائمة</button></p>
            </form>
        </div>
        <?php
    }

    public function display_email_settings_page() {
        if ( isset($_POST['mf_save_email_settings']) ) {
            check_admin_referer('mf_email_settings');
            update_option('mf_email_otp_subject', sanitize_text_field($_POST['otp_subject']));
            update_option('mf_email_otp_body', sanitize_textarea_field($_POST['otp_body']));
            update_option('mf_email_approve_subject', sanitize_text_field($_POST['approve_subject']));
            update_option('mf_email_approve_body', sanitize_textarea_field($_POST['approve_body']));
            update_option('mf_email_note_subject', sanitize_text_field($_POST['note_subject']));
            update_option('mf_email_note_body', sanitize_textarea_field($_POST['note_body']));
            echo '<div class="updated"><p>تم حفظ إعدادات البريد بنجاح.</p></div>';
        }

        $otp_subject = get_option('mf_email_otp_subject', 'رمز التحقق الخاص بك - نظام الحسابات');
        $otp_body = get_option('mf_email_otp_body', "رمز التحقق الخاص بك هو: {otp}\n\nهذا الرمز صالح لمدة 10 دقائق لتسجيل الدخول إلى حسابك.");
        
        $approve_subject = get_option('mf_email_approve_subject', 'تم تفعيل حسابك بنجاح - نقابة الأعضاء');
        $approve_body = get_option('mf_email_approve_body', "مرحباً {name}، تم اعتماد عضويتك بنجاح في النقابة. رقم العضوية الخاص بك هو: {membership_no}");

        $note_subject = get_option('mf_email_note_subject', 'تنبيه جديد من إدارة النقابة');
        $note_body = get_option('mf_email_note_body', "لديك ملاحظة جديدة في حسابك:\n\n{notes}");

        ?>
        <div class="wrap" dir="rtl">
            <h1>إدارة قوالب البريد الإلكتروني</h1>
            <form action="" method="post">
                <?php wp_nonce_field( 'mf_email_settings' ); ?>
                
                <div class="postbox" style="padding: 20px; margin-top: 20px;">
                    <h2>قالب رمز التحقق (OTP)</h2>
                    <table class="form-table">
                        <tr>
                            <th>عنوان الرسالة</th>
                            <td><input type="text" name="otp_subject" value="<?php echo esc_attr($otp_subject); ?>" class="large-text"></td>
                        </tr>
                        <tr>
                            <th>محتوى الرسالة</th>
                            <td>
                                <textarea name="otp_body" rows="5" class="large-text"><?php echo esc_textarea($otp_body); ?></textarea>
                                <p class="description">استخدم الكود <code>{otp}</code> لإدراج الرمز.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="postbox" style="padding: 20px;">
                    <h2>قالب تفعيل الحساب</h2>
                    <table class="form-table">
                        <tr>
                            <th>عنوان الرسالة</th>
                            <td><input type="text" name="approve_subject" value="<?php echo esc_attr($approve_subject); ?>" class="large-text"></td>
                        </tr>
                        <tr>
                            <th>محتوى الرسالة</th>
                            <td>
                                <textarea name="approve_body" rows="5" class="large-text"><?php echo esc_textarea($approve_body); ?></textarea>
                                <p class="description">استخدم <code>{name}</code> للاسم و <code>{membership_no}</code> لرقم العضوية.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="postbox" style="padding: 20px;">
                    <h2>قالب تنبيهات الإدارة</h2>
                    <table class="form-table">
                        <tr>
                            <th>عنوان الرسالة</th>
                            <td><input type="text" name="note_subject" value="<?php echo esc_attr($note_subject); ?>" class="large-text"></td>
                        </tr>
                        <tr>
                            <th>محتوى الرسالة</th>
                            <td>
                                <textarea name="note_body" rows="5" class="large-text"><?php echo esc_textarea($note_body); ?></textarea>
                                <p class="description">استخدم الكود <code>{notes}</code> لإدراج الملاحظة.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit"><button type="submit" name="mf_save_email_settings" class="button button-primary">حفظ الإعدادات</button></p>
            </form>
        </div>
        <?php
    }

	public function display_requests_page() {
        $view = isset( $_GET['view'] ) ? $_GET['view'] : 'all';
        $user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;

        if ( $user_id ) {
            $this->display_member_review_page( $user_id );
            return;
        }

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $query_args = array();
        if ( $view == 'pending' ) {
            $query_args['role'] = 'pending_member';
        } elseif ( $view == 'approved' ) {
            $query_args['role'] = 'union_member';
        }
        
        if ( ! empty($search) ) {
            $query_args['search'] = '*' . $search . '*';
            $query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
            
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                array( 'key' => 'mf_membership_number', 'value' => $search, 'compare' => 'LIKE' ),
                array( 'key' => 'mf_national_id', 'value' => $search, 'compare' => 'LIKE' ),
                array( 'key' => 'mf_full_name', 'value' => $search, 'compare' => 'LIKE' )
            );
        }

		$users = get_users( $query_args );
		?>
		<div class="wrap" dir="rtl">
			<h1>إدارة الحسابات والأعضاء</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=member-requests" class="nav-tab <?php echo $view == 'all' ? 'nav-tab-active' : ''; ?>">الكل</a>
                <a href="?page=member-requests&view=pending" class="nav-tab <?php echo $view == 'pending' ? 'nav-tab-active' : ''; ?>">طلبات معلقة</a>
                <a href="?page=member-requests&view=approved" class="nav-tab <?php echo $view == 'approved' ? 'nav-tab-active' : ''; ?>">أعضاء معتمدون</a>
            </h2>

            <form method="get" style="margin: 20px 0; display: flex; gap: 10px;">
                <input type="hidden" name="page" value="member-requests">
                <input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="بحث بالاسم، الإيميل، الرقم القومي أو العضوية..." style="width: 350px;">
                <button type="submit" class="button">بحث</button>
            </form>

			<?php if ( isset( $_GET['msg'] ) ) : ?>
				<div class="updated"><p><?php echo esc_html( urldecode( $_GET['msg'] ) ); ?></p></div>
			<?php endif; ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>الاسم</th>
						<th>الرقم القومي</th>
						<th>البريد الإلكتروني</th>
						<th>نوع الطلب</th>
                        <th>الحالة</th>
						<th>إجراءات</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $users ) ) : ?>
						<?php foreach ( $users as $user ) : ?>
							<?php 
							$name = get_user_meta( $user->ID, 'name', true );
							$nid = get_user_meta( $user->ID, 'nid', true );
                            $m_type = get_user_meta( $user->ID, 'mf_member_type', true );
                            $is_pending = in_array('pending_member', (array)$user->roles);
							?>
							<tr>
								<td><strong><?php echo esc_html( $name ); ?></strong></td>
								<td><?php echo esc_html( $nid ); ?></td>
								<td><?php echo esc_html( $user->user_email ); ?></td>
                                <td>
                                    <?php if ($m_type === 'new') : ?>
                                        <span style="background: #e1f5fe; color: #01579b; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">عضو جديد</span>
                                    <?php else : ?>
                                        <span style="background: #f1f8e9; color: #33691e; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">عضو مقيد</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_pending) : ?>
                                        <span style="color: #f39c12; font-weight:bold;"><span class="dashicons dashicons-clock"></span> معلق</span>
                                    <?php else : ?>
                                        <span style="color: #27ae60; font-weight:bold;"><span class="dashicons dashicons-yes"></span> معتمد</span>
                                    <?php endif; ?>
                                </td>
								<td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?page=member-requests&user_id=<?php echo $user->ID; ?>" class="button <?php echo $is_pending ? 'button-primary' : ''; ?>"><?php echo $is_pending ? 'مراجعة واعتماد' : 'تعديل البيانات'; ?></a>
                                        <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=member-requests&action=delete&user_id='.$user->ID), 'mf_delete_user' ); ?>" class="button button-link-delete" onclick="return confirm('هل أنت متأكد من حذف هذا الحساب؟');">حذف</a>
                                    </div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="6">لا يوجد أعضاء في هذا القسم.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

    private function display_member_review_page( $user_id ) {
        $user = get_userdata( $user_id );
        $is_pending = in_array('pending_member', (array)$user->roles);
        $name = get_user_meta( $user_id, 'name', true );
        $nid  = get_user_meta( $user_id, 'nid', true );
        $m_no = get_user_meta( $user_id, 'member_no', true );
        $phone = get_user_meta( $user_id, 'phone', true );
        $notes = get_user_meta( $user_id, 'mf_admin_notes', true );
        $logs = Member_Files_Logger::get_logs( $user_id );
        $photo_id = get_user_meta( $user_id, 'mf_profile_photo', true );

        $m_type = get_user_meta( $user_id, 'mf_member_type', true );
        $university = get_user_meta( $user_id, 'university', true );
        $college = get_user_meta( $user_id, 'college', true );
        $department = get_user_meta( $user_id, 'department', true );
        $acad_special = get_user_meta( $user_id, 'acad_special', true );
        $degree = get_user_meta( $user_id, 'degree', true );
        $grad_year = get_user_meta( $user_id, 'grad_year', true );
        $union_branch = get_user_meta( $user_id, 'union_branch', true );
        $prof_level = get_user_meta( $user_id, 'prof_level', true );
        $prof_spec = get_user_meta( $user_id, 'prof_special', true );
        $address = get_user_meta( $user_id, 'address', true );
        $dob = get_user_meta( $user_id, 'dob', true );
        $m_rank_type = get_user_meta( $user_id, 'mf_membership_type', true );
        $gender = get_user_meta( $user_id, 'gender', true );
        $nationality = get_user_meta( $user_id, 'nationality', true );
        
        $res_country = get_user_meta( $user_id, 'res_country', true );
        $res_province = get_user_meta( $user_id, 'res_province', true );
        $res_city = get_user_meta( $user_id, 'res_city', true );

        $whatsapp = get_user_meta( $user_id, 'whatsapp', true );
        $facebook = get_user_meta( $user_id, 'facebook', true );
        $apply_license = get_user_meta( $user_id, 'apply_license', true );
        $practiced_before = get_user_meta( $user_id, 'practiced_before', true );
        $practice_years = get_user_meta( $user_id, 'practice_years', true );
        $work_location = get_user_meta( $user_id, 'work_location', true );
        $published_research = get_user_meta( $user_id, 'published_research', true );

        ?>
        <div class="wrap" dir="rtl">
            <h1 style="margin-bottom: 20px;"><?php echo $is_pending ? 'مراجعة طلب انضمام' : 'إدارة بيانات العضو'; ?>: <span style="color: #0073aa;"><?php echo esc_html( $name ); ?></span></h1>
            
            <div style="margin-bottom: 20px;">
                <a href="?page=member-requests" class="button button-secondary">العودة لقائمة الأعضاء</a>
                <?php if ($m_type === 'new') : ?>
                    <span style="background: #e1f5fe; color: #01579b; padding: 5px 12px; border-radius: 4px; font-weight: bold; margin-right: 10px;">طلب عضوية جديدة</span>
                <?php else : ?>
                    <span style="background: #f1f8e9; color: #33691e; padding: 5px 12px; border-radius: 4px; font-weight: bold; margin-right: 10px;">عضو مقيد سابقاً</span>
                <?php endif; ?>
            </div>

            <form action="" method="post">
                <?php wp_nonce_field( 'mf_edit_user', 'mf_edit_nonce' ); ?>
                <?php wp_nonce_field( 'mf_approve_user', 'mf_approve_nonce' ); ?>
                <?php wp_nonce_field( 'mf_admin_msg', 'mf_msg_nonce' ); ?>
                <?php wp_nonce_field( 'mf_notes_user', 'mf_notes_nonce' ); ?>
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            
                            <!-- Personal Info Section -->
                            <div class="postbox">
                                <h2 class="hndle">1. المعلومات الشخصية الأساسية</h2>
                                <div class="inside">
                                    <div style="display: flex; gap: 30px;">
                                        <div style="flex: 0 0 180px; text-align: center;">
                                            <label style="display: block; margin-bottom: 10px; font-weight: bold;">الصورة الشخصية</label>
                                            <?php if ( $photo_id ) : ?>
                                                <?php echo wp_get_attachment_image( $photo_id, 'medium', false, array('style' => 'width: 100%; height: auto; border-radius: 8px; border: 4px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);') ); ?>
                                            <?php else : ?>
                                                <div style="width: 100%; aspect-ratio: 1; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ccc;">
                                                    <span class="dashicons dashicons-admin-users" style="font-size: 60px; width: 60px; height: 60px;"></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                            <div class="form-group">
                                                <label>الاسم الكامل</label>
                                                <input type="text" name="name" value="<?php echo esc_attr($name); ?>" class="large-text" required>
                                            </div>
                                            <div class="form-group">
                                                <label>الرقم القومي (Username)</label>
                                                <input type="text" name="nid" value="<?php echo esc_attr($nid); ?>" class="large-text" required>
                                            </div>
                                            <div class="form-group">
                                                <label>تاريخ الميلاد</label>
                                                <input type="date" name="dob" value="<?php echo esc_attr($dob); ?>" class="large-text">
                                            </div>
                                            <div class="form-group">
                                                <label>الجنسية</label>
                                                <input type="text" name="nationality" value="<?php echo esc_attr($nationality); ?>" class="large-text">
                                            </div>
                                            <div class="form-group">
                                                <label>الجنس</label>
                                                <select name="gender" class="large-text">
                                                    <option value="ذكر" <?php selected($gender, 'ذكر'); ?>>ذكر</option>
                                                    <option value="أنثى" <?php selected($gender, 'أنثى'); ?>>أنثى</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>الصلاحية في النظام</label>
                                                <select name="user_role" class="large-text">
                                                    <?php wp_dropdown_roles( $user->roles[0] ); ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>نوع العضوية</label>
                                                <select name="membership_type" class="large-text">
                                                    <option value="عضوية عاملة" <?php selected($m_rank_type, 'عضوية عاملة'); ?>>عضوية عاملة</option>
                                                    <option value="عضوية منتسبة" <?php selected($m_rank_type, 'عضوية منتسبة'); ?>>عضوية منتسبة</option>
                                                    <option value="عضوية شرفية" <?php selected($m_rank_type, 'عضوية شرفية'); ?>>عضوية شرفية</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Academic Info Section -->
                            <div class="postbox">
                                <h2 class="hndle">2. المعلومات المهنية والأكاديمية</h2>
                                <div class="inside">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group">
                                            <label>رقم العضوية</label>
                                            <input type="text" name="member_no" value="<?php echo esc_attr($m_no); ?>" class="large-text">
                                        </div>

                                        <div class="form-group">
                                            <label>الدرجة العلمية</label>
                                            <select name="degree" class="large-text">
                                                <option value="">اختر الدرجة</option>
                                                <?php 
                                                $degrees = array('Undergraduate' => 'طالب', 'Bachelor' => 'بكالوريوس', 'Master' => 'ماجستير', 'Doctorate' => 'دكتوراه');
                                                foreach ($degrees as $val => $lbl) : ?>
                                                    <option value="<?php echo $val; ?>" <?php selected($degree, $val); ?>><?php echo $lbl; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>الجامعة</label>
                                            <input type="text" name="university" value="<?php echo esc_attr($university); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>الكلية</label>
                                            <input type="text" name="college" value="<?php echo esc_attr($college); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>القسم</label>
                                            <input type="text" name="department" value="<?php echo esc_attr($department); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>التخصص الأكاديمي</label>
                                            <input type="text" name="acad_special" value="<?php echo esc_attr($acad_special); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>فرع النقابة</label>
                                            <input type="text" name="union_branch" value="<?php echo esc_attr($union_branch); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>المستوى المهني</label>
                                            <select name="prof_level" class="large-text">
                                                <option value="">اختر المستوى</option>
                                                <?php 
                                                $levels = array('Practitioner' => 'ممارس', 'Assistant Specialist' => 'أخصائي مساعد', 'Specialist' => 'أخصائي', 'Consultant' => 'استشاري');
                                                foreach ($levels as $val => $lbl) : ?>
                                                    <option value="<?php echo $val; ?>" <?php selected($prof_level, $val); ?>><?php echo $lbl; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>التخصص المهني</label>
                                            <input type="text" name="prof_special" value="<?php echo esc_attr($prof_spec); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>سنة التخرج</label>
                                            <input type="text" name="grad_year" value="<?php echo esc_attr($grad_year); ?>" class="large-text">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Info Section -->
                            <div class="postbox">
                                <h2 class="hndle">3. معلومات التواصل والإقامة</h2>
                                <div class="inside">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group">
                                            <label>البريد الإلكتروني</label>
                                            <input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" class="large-text" required>
                                        </div>
                                        <div class="form-group">
                                            <label>رقم الهاتف</label>
                                            <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" class="large-text" required>
                                        </div>
                                        <div class="form-group">
                                            <label>رقم الواتساب</label>
                                            <input type="text" name="whatsapp" value="<?php echo esc_attr($whatsapp); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>رابط الفيسبوك</label>
                                            <input type="text" name="facebook" value="<?php echo esc_attr($facebook); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>دولة الإقامة</label>
                                            <input type="text" name="res_country" value="<?php echo esc_attr($res_country); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>محافظة الإقامة</label>
                                            <input type="text" name="res_province" value="<?php echo esc_attr($res_province); ?>" class="large-text">
                                        </div>
                                        <div class="form-group">
                                            <label>مدينة الإقامة</label>
                                            <input type="text" name="res_city" value="<?php echo esc_attr($res_city); ?>" class="large-text">
                                        </div>
                                        <div class="form-group" style="grid-column: span 2;">
                                            <label>العنوان بالكامل</label>
                                            <input type="text" name="address" value="<?php echo esc_attr($address); ?>" class="large-text">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Member Specific Section -->
                            <?php if ($m_type === 'new') : ?>
                            <div class="postbox">
                                <h2 class="hndle">4. معلومات مهنية إضافية (عضو جديد)</h2>
                                <div class="inside">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div class="form-group">
                                            <label>طلب ترخيص مزاولة المهنة</label>
                                            <input type="text" value="<?php echo $apply_license === 'yes' ? 'نعم' : 'لا'; ?>" class="large-text" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>مارس المهنة من قبل</label>
                                            <input type="text" value="<?php echo $practiced_before === 'yes' ? 'نعم' : 'لا'; ?>" class="large-text" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>عدد سنوات الممارسة</label>
                                            <input type="text" value="<?php echo esc_attr($practice_years); ?>" class="large-text" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>مكان العمل</label>
                                            <input type="text" value="<?php echo $work_location === 'inside' ? 'داخل مصر' : 'خارج مصر'; ?>" class="large-text" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>أبحاث منشورة</label>
                                            <input type="text" value="<?php echo $published_research === 'yes' ? 'نعم' : 'لا'; ?>" class="large-text" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Documents Section -->
                            <div class="postbox">
                                <h2 class="hndle">المستندات والمرفقات</h2>
                                <div class="inside">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                                        <?php 
                                        $docs = array(
                                            'academic_cert' => 'شهادة المؤهل',
                                            'membership_photo' => 'صورة الكارنيه',
                                            'id_photo' => 'صورة الهوية',
                                            'license_photo' => 'صورة الرخصة',
                                            'cv' => 'السيرة الذاتية',
                                            'passport' => 'جواز السفر'
                                        );
                                        foreach ($docs as $key => $label) :
                                            $url = get_user_meta($user_id, $key, true);
                                            if ($url) :
                                        ?>
                                            <div style="padding: 10px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                                                <div style="font-weight: bold; margin-bottom: 8px;"><?php echo $label; ?></div>
                                                <a href="<?php echo esc_url($url); ?>" target="_blank" class="button button-small">عرض الملف</a>
                                            </div>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity Log Section -->
                            <div class="postbox">
                                <h2 class="hndle">4. سجل النشاط والمراقبة</h2>
                                <div class="inside">
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead><tr><th>التاريخ</th><th>العملية</th><th>التفاصيل</th><th>IP</th></tr></thead>
                                        <tbody>
                                            <?php if (!empty($logs)) : foreach ( array_reverse($logs) as $log ) : ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i', $log['time']); ?></td>
                                                    <td><?php echo esc_html($log['action']); ?></td>
                                                    <td><?php echo esc_html($log['details']); ?></td>
                                                    <td><?php echo esc_html($log['ip']); ?></td>
                                                </tr>
                                            <?php endforeach; else: ?>
                                                <tr><td colspan="4">لا يوجد سجل نشاط حالياً.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>

                        <!-- Sidebar Actions -->
                        <div id="postbox-container-1" class="postbox-container">
                            <div class="postbox">
                                <h2 class="hndle">الإجراءات</h2>
                                <div class="inside">
                                    <div style="padding: 10px 0;">
                                        <?php if ( $is_pending ) : ?>
                                            <button type="submit" name="mf_approve_submit" class="button button-primary button-large" style="width: 100%; height: 46px; margin-bottom: 10px;">الموافقة وتفعيل العضوية</button>
                                            <p class="description" style="text-align: center;">سيتم إرسال بريد إلكتروني تلقائي للعضو عند التفعيل.</p>
                                        <?php else : ?>
                                            <button type="submit" name="mf_edit_submit" class="button button-primary button-large" style="width: 100%;">حفظ التعديلات</button>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <div style="text-align: center;">
                                        <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=member-requests&action=delete&user_id='.$user_id), 'mf_delete_user' ); ?>" style="color: #a00; text-decoration: none;" onclick="return confirm('هل أنت متأكد من حذف هذا الطلب؟');">حذف الحساب نهائياً</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Updates Box -->
                            <?php 
                            $pending_update = get_user_meta( $user_id, 'mf_pending_settings_update', true );
                            if ( $pending_update ) : ?>
                                <div class="postbox" style="border: 2px solid #ffb900;">
                                    <h2 class="hndle">طلب تعديل بيانات معلق</h2>
                                    <div class="inside">
                                        <p>طلب العضو تعديل بياناته. <a href="?page=member-updates">انقر هنا لمراجعة كافة الطلبات</a></p>
                                        <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=member-requests&user_id='.$user_id.'&action=approve_settings'), 'mf_approve_settings' ); ?>" class="button button-primary">موافقة سريعة وتحديث</a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Admin Messaging -->
                            <div class="postbox">
                                <h2 class="hndle">إرسال رسالة مباشرة</h2>
                                <div class="inside">
                                    <p class="description">ستصل الرسالة إلى صندوق بريد العضو في لوحة تحكمه.</p>
                                    <input type="text" name="msg_subject" placeholder="عنوان الرسالة" style="width:100%; margin-bottom:10px;">
                                    <textarea name="msg_content" rows="4" style="width:100%;" placeholder="محتوى الرسالة..."></textarea>
                                    <button type="submit" name="mf_send_message" class="button button-secondary" style="margin-top:10px; width: 100%;">إرسال الرسالة الآن</button>
                                </div>
                            </div>

                            <?php if ($m_type === 'new' && $is_pending) : ?>
                            <!-- Membership Stages (For New Members) -->
                            <div class="postbox" style="border: 1px solid #3498db;">
                                <h2 class="hndle" style="background: #3498db; color: #fff;">تحديث مرحلة الطلب (عضو جديد)</h2>
                                <div class="inside">
                                    <?php 
                                    $current_stage = (int) get_user_meta($user_id, 'mf_membership_stage', true) ?: 1;
                                    $stage_note = get_user_meta($user_id, 'mf_stage_note', true);
                                    ?>
                                    <p class="description">اختر المرحلة الحالية لطلب العضوية:</p>
                                    <select name="mf_membership_stage" style="width: 100%; margin-bottom: 15px;">
                                        <option value="1" <?php selected($current_stage, 1); ?>>1. مراجعة البيانات</option>
                                        <option value="2" <?php selected($current_stage, 2); ?>>2. انتظار المستندات الورقية</option>
                                        <option value="3" <?php selected($current_stage, 3); ?>>3. مراجعة المستندات الورقية</option>
                                        <option value="4" <?php selected($current_stage, 4); ?>>4. تفعيل الحساب والاعتماد</option>
                                    </select>
                                    
                                    <p class="description">رسالة/ملاحظة المرحلة (تظهر للعضو):</p>
                                    <textarea name="mf_stage_note" rows="3" style="width: 100%;" placeholder="مثلاً: يرجى إرسال أصل شهادة المؤهل بالبريد..."><?php echo esc_textarea($stage_note); ?></textarea>
                                    
                                    <button type="submit" name="mf_update_stage_submit" class="button button-primary" style="margin-top: 10px; width: 100%;">تحديث المرحلة والرسالة</button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Admin Notes -->
                            <div class="postbox">
                                <h2 class="hndle">ملاحظات إدارية (تنبيهات)</h2>
                                <div class="inside">
                                    <p class="description">ستظهر كشريط تنبيه في أعلى لوحة تحكم العضو.</p>
                                    <textarea name="admin_notes" rows="4" style="width:100%;" placeholder="أدخل الملاحظات هنا..."><?php echo esc_textarea($notes); ?></textarea>
                                    <button type="submit" name="mf_notes_submit" class="button button-secondary" style="margin-top:10px; width: 100%;">حفظ وإرسال تنبيه</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    public function display_updates_page() {
        $users = get_users( array(
            'meta_key' => 'mf_pending_settings_update',
            'compare'  => 'EXISTS'
        ) );
        ?>
        <div class="wrap" dir="rtl">
            <h1>طلبات تعديل البيانات الشخصية والمرفقات</h1>
            <p>توضح هذه الصفحة كافة طلبات تعديل البيانات المرسلة من قبل الأعضاء والتي تنتظر مراجعة الإدارة.</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="20%">العضو</th>
                        <th width="30%">البيانات الحالية</th>
                        <th width="35%">التعديلات المطلوبة (الجديدة)</th>
                        <th width="15%">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty($users) ) : foreach ( $users as $u ) : 
                        $pending = get_user_meta( $u->ID, 'mf_pending_settings_update', true );
                        $current_name = get_user_meta($u->ID, 'name', true);
                        $current_nid  = get_user_meta($u->ID, 'nid', true);
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($current_name); ?></strong><br>
                                <span class="description">الرقم القومي: <?php echo esc_html($current_nid); ?></span>
                            </td>
                            <td style="font-size: 12px; color: #666;">
                                <ul style="margin:0; padding:0; list-style:none;">
                                    <?php 
                                    foreach ( $pending as $k => $v ) : 
                                        if ($k === 'mf_profile_photo_id') continue;
                                        $label = isset(Member_Files::$profile_fields[$k]) ? Member_Files::$profile_fields[$k] : $k;
                                        $curr_val = get_user_meta($u->ID, $k, true);
                                        if (empty($curr_val)) $curr_val = '-';
                                        ?>
                                        <li><strong><?php echo $label; ?>:</strong> <?php 
                                            if (filter_var($curr_val, FILTER_VALIDATE_URL)) {
                                                echo '<a href="'.esc_url($curr_val).'" target="_blank">عرض المرفق الحالي</a>';
                                            } else {
                                                echo esc_html($curr_val);
                                            }
                                        ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td style="background: #f0f7fd; border-right: 3px solid #0073aa;">
                                <ul style="margin:0; padding:0; list-style:none;">
                                    <?php 
                                    foreach ( $pending as $k => $v ) : 
                                        if ( empty($v) ) continue;
                                        if ($k === 'mf_profile_photo_id') continue;
                                        $label = isset(Member_Files::$profile_fields[$k]) ? Member_Files::$profile_fields[$k] : $k;
                                        ?>
                                        <li style="margin-bottom: 5px;">
                                            <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 14px; color: #0073aa;"></span>
                                            <strong><?php echo $label; ?>:</strong> 
                                            <span style="color: #c0392b; font-weight:bold;">
                                                <?php 
                                                if (filter_var($v, FILTER_VALIDATE_URL)) {
                                                    echo '<a href="'.esc_url($v).'" target="_blank" style="color: #0073aa;">عرض المرفق الجديد</a>';
                                                } else {
                                                    echo esc_html($v);
                                                }
                                                ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=member-requests&user_id='.$u->ID.'&action=approve_settings'), 'mf_approve_settings' ); ?>" class="button button-primary">اعتماد التعديلات</a>
                                    <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=member-requests&action=reject_settings&user_id='.$u->ID), 'mf_reject_settings' ); ?>" class="button button-secondary" onclick="return confirm('هل أنت متأكد من رفض هذه التعديلات؟');">رفض الطلب</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px;">لا توجد طلبات تعديل معلقة حالياً.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function display_stats_page() {
        $all_members = get_users( array( 'role__in' => array('pending_member', 'union_member') ) );
        $approved_count = count( get_users( array( 'role' => 'union_member' ) ) );
        $pending_count = count( get_users( array( 'role' => 'pending_member' ) ) );
        
        $recent_logs = array();
        foreach($all_members as $u) {
            $user_logs = Member_Files_Logger::get_logs($u->ID);
            foreach($user_logs as $l) {
                $l['user_name'] = get_user_meta($u->ID, 'name', true);
                $recent_logs[] = $l;
            }
        }
        usort($recent_logs, function($a, $b) { return $b['time'] - $a['time']; });
        $recent_logs = array_slice($recent_logs, 0, 20);

        ?>
        <div class="wrap" dir="rtl">
            <h1>إحصائيات ومراقبة النظام</h1>
            
            <div class="mf-stats-cards" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
                <div class="card" style="background: #fff; padding: 20px; border-right: 4px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0">إجمالي الأعضاء</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo count($all_members); ?></p>
                </div>
                <div class="card" style="background: #fff; padding: 20px; border-right: 4px solid #2ecc71; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0">أعضاء معتمدون</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo $approved_count; ?></p>
                </div>
                <div class="card" style="background: #fff; padding: 20px; border-right: 4px solid #f1c40f; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0">طلبات معلقة</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo $pending_count; ?></p>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle" style="padding: 10px 15px;">آخر نشاطات الأعضاء</h2>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>الوقت</th><th>العضو</th><th>العملية</th><th>التفاصيل</th></tr></thead>
                        <tbody>
                            <?php foreach($recent_logs as $log): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', $log['time']); ?></td>
                                    <td><?php echo esc_html($log['user_name']); ?></td>
                                    <td><?php echo esc_html($log['action']); ?></td>
                                    <td><?php echo esc_html($log['details']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <a href="?page=member-export" class="button button-primary">تصدير تقرير الأعضاء (طباعة)</a>
        </div>
        <?php
    }

    public function display_add_member_page() {
        ?>
        <div class="wrap" dir="rtl">
            <h1>إضافة عضو جديد</h1>
            <div class="card" style="max-width: 800px; margin-top: 20px; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <form action="" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'mf_add_user_admin', 'mf_add_nonce' ); ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="grid-column: span 2; text-align: center; margin-bottom: 20px;">
                             <div style="width: 120px; height: 120px; background: #f1f5f9 url('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y') no-repeat center; background-size: cover; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; border: 2px dashed #cbd5e1;">
                             </div>
                             <input type="file" name="photo" accept="image/*">
                        </div>
                        
                        <div>
                            <input type="text" name="name" required placeholder="الاسم بالكامل" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <input type="email" name="email" required placeholder="البريد الإلكتروني" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <input type="text" name="nid" required pattern="[0-9]{14}" placeholder="الرقم القومي (14 رقم)" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <input type="text" name="member_no" required placeholder="رقم العضوية" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <input type="text" name="phone" placeholder="رقم الهاتف" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <input type="text" name="union_branch" placeholder="الفرع / المحافظة" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <input type="text" name="prof_special" placeholder="الدرجة المهنية / التخصص" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <select name="mf_gender" style="width:100%; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <option value="">الجنس</option>
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 30px; text-align: left;">
                        <button type="submit" name="mf_add_submit" class="button button-primary button-large" style="height: 46px; padding: 0 30px;">إضافة وتفعيل العضو الآن</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_export_print() {
        $users = get_users( array( 'role' => 'union_member' ) );
        ?>
        <div class="wrap" dir="rtl">
            <h1 class="no-print">تقرير أعضاء النقابة</h1>
            <button onclick="window.print();" class="button button-primary no-print">طباعة التقرير (PDF)</button>
            <style>
                @media print { .no-print { display: none; } }
                .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .report-table th, .report-table td { border: 1px solid #ddd; padding: 10px; text-align: right; }
                .report-table th { background: #f4f4f4; }
            </style>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الرقم القومي</th>
                        <th>رقم العضوية</th>
                        <th>الفرع</th>
                        <th>الدرجة المهنية</th>
                        <th>رقم الهاتف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'name', true)); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'nid', true)); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'member_no', true)); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'union_branch', true)); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'prof_special', true)); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'phone', true)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function display_import_page() {
        if ( isset($_POST['mf_import_submit']) ) {
            check_admin_referer('mf_import_members');
            if ( ! empty($_FILES['mf_csv']['tmp_name']) ) {
                $handle = fopen($_FILES['mf_csv']['tmp_name'], "r");
                $count = 0;
                // Skip header
                fgetcsv($handle);
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Expecting: Name, Email, NationalID, MemberNo, Phone, Branch, Rank
                    $name = $data[0];
                    $email = $data[1];
                    $nid = $data[2];
                    $mno = $data[3];
                    $phone = isset($data[4]) ? $data[4] : '';
                    $branch = isset($data[5]) ? $data[5] : '';
                    $rank = isset($data[6]) ? $data[6] : '';

                    if ( ! username_exists($nid) && ! email_exists($email) ) {
                        $uid = wp_insert_user( array(
                            'user_login' => $nid,
                            'user_email' => $email,
                            'user_pass'  => wp_generate_password(),
                            'role'       => 'union_member',
                            'display_name' => $name
                        ) );
                        if ( ! is_wp_error($uid) ) {
                            $import_data = array(
                                'name' => $name,
                                'nid'  => $nid,
                                'member_no' => $mno,
                                'phone' => $phone,
                                'union_branch' => $branch,
                                'prof_special' => $rank
                            );
                            Member_Files::update_user_data($uid, $import_data);
                            $count++;
                        }
                    }
                }
                fclose($handle);
                echo '<div class="updated"><p>تم استيراد ' . $count . ' عضو بنجاح.</p></div>';
            }
        }
        ?>
        <div class="wrap" dir="rtl">
            <h1>استيراد أعضاء من ملف Excel (CSV)</h1>
            <div class="card" style="max-width: 600px; padding: 20px;">
                <p>يرجى رفع ملف CSV يحتوي على الأعمدة التالية بالترتيب:<br>
                <strong>الاسم، البريد، الرقم القومي، رقم العضوية، الهاتف، الفرع، الدرجة المهنية</strong></p>
                <form action="" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'mf_import_members', '_wpnonce' ); ?>
                    <input type="file" name="mf_csv" accept=".csv" required>
                    <p class="submit"><button type="submit" name="mf_import_submit" class="button button-primary">بدء الاستيراد</button></p>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_member_actions() {
        $view = isset( $_GET['view'] ) ? $_GET['view'] : 'all';
        
        if ( isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['user_id']) ) {
            check_admin_referer('mf_delete_user');
            wp_delete_user( intval($_GET['user_id']) );
            wp_redirect( admin_url('admin.php?page=member-requests&view='.$view.'&msg='.urlencode('تم حذف العضو بنجاح')) );
            exit;
        }

        if ( isset($_GET['action']) && $_GET['action'] == 'reject_settings' && isset($_GET['user_id']) ) {
            check_admin_referer('mf_reject_settings');
            $user_id = intval($_GET['user_id']);
            delete_user_meta( $user_id, 'mf_pending_settings_update' );
            Member_Files_Logger::log( $user_id, 'رفض تعديل', 'تم رفض طلب تعديل البيانات الشخصية من قبل المسؤول' );
            wp_redirect( admin_url('admin.php?page=member-requests&msg='.urlencode('تم رفض التعديلات')) );
            exit;
        }
    }

    public function get_email_template($title, $content) {
        ob_start();
        ?>
        <div dir="rtl" style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <div style="background: #0073aa; padding: 30px; text-align: center; color: #fff;">
                    <h1 style="margin: 0; font-size: 24px;"><?php echo esc_html($title); ?></h1>
                </div>
                <div style="padding: 30px; line-height: 1.6; color: #333; text-align: right;">
                    <p><?php echo nl2br(esc_html($content)); ?></p>
                </div>
                <div style="background: #f9f9f9; padding: 20px; text-align: center; color: #777; font-size: 12px;">
                    © <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. جميع الحقوق محفوظة.
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

	public function handle_approval() {
        // Set email content type to HTML temporarily
        add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );

		// Existing approval
		if ( isset( $_POST['mf_approve_submit'] ) ) {
            if ( ! wp_verify_nonce( $_POST['mf_approve_nonce'], 'mf_approve_user' ) ) return;
            $user_id = intval( $_POST['user_id'] );
            if ( $user_id ) {
                $user = new WP_User( $user_id );
                $user->set_role( 'union_member' );
                
                $m_no = get_user_meta($user_id, 'member_no', true);
                $name = get_user_meta($user_id, 'name', true);
                
                $subject = get_option('mf_email_approve_subject', 'تم تفعيل حسابك بنجاح - نقابة الأعضاء');
                $body    = get_option('mf_email_approve_body', "مرحباً {name}، تم اعتماد عضويتك بنجاح في النقابة. رقم العضوية الخاص بك هو: {membership_no}");
                
                $body = str_replace('{name}', $name, $body);
                $body = str_replace('{membership_no}', $m_no, $body);

                $message = $this->get_email_template( $subject, $body );
                wp_mail( $user->user_email, $subject, $message );
                Member_Files_Logger::log( $user_id, 'تفعيل الحساب', 'تم تفعيل الحساب من قبل المسؤول' );
                wp_redirect( admin_url( 'admin.php?page=member-requests&msg=' . urlencode('تم التفعيل بنجاح') ) );
                exit;
            }
		}

        // Handle Add Member
        if ( isset( $_POST['mf_add_submit'] ) ) {
            if ( ! wp_verify_nonce( $_POST['mf_add_nonce'], 'mf_add_user_admin' ) ) return;
            $email = sanitize_email($_POST['email']);
            $nid   = sanitize_text_field($_POST['nid']);
            $name  = sanitize_text_field($_POST['name']);
            $user_id = wp_insert_user( array(
                'user_login' => $nid,
                'user_email' => $email,
                'user_pass'  => wp_generate_password(),
                'role'       => 'union_member',
                'display_name' => $name
            ) );
            if ( ! is_wp_error($user_id) ) {
                $add_data = array(
                    'name' => $name,
                    'nid'  => $nid,
                    'member_no' => sanitize_text_field($_POST['member_no']),
                    'gender' => sanitize_text_field($_POST['mf_gender']),
                    'phone'  => sanitize_text_field($_POST['phone']),
                    'union_branch' => sanitize_text_field($_POST['union_branch']),
                    'prof_special' => sanitize_text_field($_POST['prof_special'])
                );
                
                Member_Files::update_user_data($user_id, $add_data);
                
                if ( ! empty( $_FILES['photo']['name'] ) ) {
                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    require_once( ABSPATH . 'wp-admin/includes/file.php' );
                    require_once( ABSPATH . 'wp-admin/includes/media.php' );
                    $attachment_id = media_handle_upload( 'photo', 0 );
                    if ( ! is_wp_error( $attachment_id ) ) {
                        update_user_meta( $user_id, 'mf_profile_photo', $attachment_id );
                    }
                }

                Member_Files_Logger::log( $user_id, 'إنشاء حساب', 'تم إنشاء الحساب يدوياً من قبل المسؤول' );
                wp_redirect( admin_url( 'admin.php?page=member-requests&view=approved&msg=' . urlencode('تم إضافة العضو بنجاح') ) );
                exit;
            }
        }

        // Handle Admin Message
        if ( isset( $_POST['mf_send_message'] ) ) {
            if ( ! wp_verify_nonce( $_POST['mf_msg_nonce'], 'mf_admin_msg' ) ) return;
            $user_id = intval($_POST['user_id']);
            $messages = get_user_meta( $user_id, 'mf_user_messages', true ) ?: array();
            $messages[] = array(
                'time'    => time(),
                'subject' => sanitize_text_field($_POST['msg_subject']),
                'content' => sanitize_textarea_field($_POST['msg_content']),
                'read'    => false,
            );
            update_user_meta( $user_id, 'mf_user_messages', $messages );
            Member_Files_Logger::log( $user_id, 'رسالة إدارية', 'تم إرسال رسالة إلى العضو' );
            wp_redirect( add_query_arg( 'msg', urlencode('تم إرسال الرسالة'), admin_url('admin.php?page=member-requests&user_id='.$user_id) ) );
            exit;
        }

        // Handle Approve Settings Change
        if ( isset( $_GET['action'] ) && $_GET['action'] == 'approve_settings' ) {
            check_admin_referer('mf_approve_settings');
            $user_id = intval($_GET['user_id']);
            $pending = get_user_meta( $user_id, 'mf_pending_settings_update', true );
            if ( $pending ) {
                if ( isset($pending['mf_profile_photo_id']) ) {
                    update_user_meta( $user_id, 'mf_profile_photo', $pending['mf_profile_photo_id'] );
                    unset($pending['mf_profile_photo_id']);
                }
                
                Member_Files::update_user_data( $user_id, $pending );
                
                delete_user_meta( $user_id, 'mf_pending_settings_update' );
                Member_Files_Logger::log( $user_id, 'اعتماد تعديل', 'تم اعتماد تعديل البيانات الشخصية والمرفقات' );
            }
            wp_redirect( admin_url('admin.php?page=member-updates&msg='.urlencode('تم الاعتماد')) );
            exit;
        }

        // Handle Edit Member
        if ( isset( $_POST['mf_edit_submit'] ) ) {
            if ( ! wp_verify_nonce( $_POST['mf_edit_nonce'], 'mf_edit_user' ) ) return;
            $user_id = intval($_POST['user_id']);
            
            $data = array();
            foreach ( Member_Files::$profile_fields as $key => $label ) {
                if ( isset($_POST[$key]) ) {
                    $data[$key] = sanitize_text_field($_POST[$key]);
                }
            }

            $new_nid = isset($data['nid']) ? $data['nid'] : get_user_meta($user_id, 'nid', true);
            $old_user = get_userdata($user_id);
            
            // Update user_login if changed
            if ( $old_user->user_login !== $new_nid && !empty($new_nid) ) {
                if ( username_exists($new_nid) ) {
                    wp_redirect( add_query_arg( 'msg', urlencode('خطأ: الرقم القومي الجديد مسجل لمستخدم آخر.'), admin_url('admin.php?page=member-requests&user_id='.$user_id) ) );
                    exit;
                }
                global $wpdb;
                $wpdb->update(
                    $wpdb->users,
                    array( 'user_login' => $new_nid ),
                    array( 'ID' => $user_id )
                );
            }

            $user_data_update = array( 'ID' => $user_id );
            if (isset($data['email'])) $user_data_update['user_email'] = $data['email'];
            if (isset($data['name'])) $user_data_update['display_name'] = $data['name'];
            if (isset($_POST['user_role'])) $user_data_update['role'] = sanitize_text_field($_POST['user_role']);
            
            wp_update_user( $user_data_update );

            // Use the centralized update helper
            Member_Files::update_user_data( $user_id, $data );

            // Other fields not in profile_fields array but present in form
            if (isset($_POST['membership_type'])) update_user_meta( $user_id, 'mf_membership_type', sanitize_text_field( $_POST['membership_type'] ) );
            if (isset($_POST['qualifications'])) update_user_meta( $user_id, 'mf_qualifications', sanitize_textarea_field($_POST['qualifications']) );
            if (isset($_POST['requests_count'])) update_user_meta( $user_id, 'mf_requests_count', intval($_POST['requests_count']) );

            wp_redirect( admin_url( 'admin.php?page=member-requests&user_id='.$user_id.'&msg=' . urlencode('تم حفظ التعديلات') ) );
            exit;
        }

        // Handle Notes/Notifications
        if ( isset( $_POST['mf_notes_submit'] ) ) {
            if ( ! wp_verify_nonce( $_POST['mf_notes_nonce'], 'mf_notes_user' ) ) return;
            $user_id = intval($_POST['user_id']);
            $notes = sanitize_textarea_field($_POST['admin_notes']);
            update_user_meta( $user_id, 'mf_admin_notes', $notes );
            
            $user = get_userdata($user_id);
            $subject = get_option('mf_email_note_subject', 'تنبيه جديد من إدارة النقابة');
            $body    = get_option('mf_email_note_body', "لديك ملاحظة جديدة في حسابك:\n\n{notes}");
            $body    = str_replace('{notes}', $notes, $body);

            $email_content = $this->get_email_template($subject, $body);
            wp_mail( $user->user_email, $subject, $email_content );
            Member_Files_Logger::log( $user_id, 'إرسال تنبيه', 'أرسل المسؤول ملاحظة: ' . $notes );
            
            wp_redirect( admin_url( 'admin.php?page=member-requests&user_id='.$user_id.'&msg=' . urlencode('تم إرسال التنبيه بنجاح') ) );
            exit;
        }

        // Handle Membership Stage Update
        if ( isset( $_POST['mf_update_stage_submit'] ) ) {
            $user_id = intval($_POST['user_id']);
            update_user_meta( $user_id, 'mf_membership_stage', intval($_POST['mf_membership_stage']) );
            update_user_meta( $user_id, 'mf_stage_note', sanitize_textarea_field($_POST['mf_stage_note']) );
            
            Member_Files_Logger::log( $user_id, 'تحديث المرحلة', 'تم تحديث مرحلة الطلب إلى: ' . $_POST['mf_membership_stage'] );
            
            wp_redirect( admin_url( 'admin.php?page=member-requests&user_id='.$user_id.'&msg=' . urlencode('تم تحديث المرحلة بنجاح') ) );
            exit;
        }
	}
}
