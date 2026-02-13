<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Files_Profile {

    public function ajax_send_email_otp() {
        check_ajax_referer( 'mf_update_settings', 'nonce' );
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        
        $otp = sprintf( '%06d', mt_rand( 0, 999999 ) );
        set_transient( 'mf_email_update_otp_' . $user_id, $otp, 10 * MINUTE_IN_SECONDS );

        $subject = 'رمز تأكيد تغيير البريد الإلكتروني';
        $message = 'رمز التحقق الخاص بك لتغيير البريد الإلكتروني هو: ' . $otp;
        
        wp_mail( $user->user_email, $subject, $message );
        
        wp_send_json_success( array( 'msg' => 'تم إرسال الرمز بنجاح.' ) );
    }

	public function render_profile() {
		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );
		
        $data = array();
        foreach ( Member_Files::$profile_fields as $key => $label ) {
            $data[$key] = get_user_meta( $user_id, $key, true );
        }

		$national_id = $data['nid'];
        $membership_no = $data['member_no'];
        
        // New status logic
        $is_pending = in_array('pending_member', (array)$user->roles);
        $is_active = ( !$is_pending && !empty($membership_no) );

        if ($is_pending) {
            return $this->render_pending_status_page($user_id, $data);
        }

        if ($is_active) {
            $status_label = 'عضوية نشطة';
            $status_class = 'active';
        } else {
            $status_label = 'عضوية غير نشطة';
            $status_class = 'inactive';
        }

        $full_name   = $data['name'];
        $phone       = $data['phone'];
        $requests_count = get_user_meta( $user_id, 'mf_requests_count', true ) ?: 0;
        $branch      = $data['union_branch'];

        $profile_photo_id = get_user_meta( $user_id, 'mf_profile_photo', true );
        $license_file_id  = get_user_meta( $user_id, 'license_photo', true );
        $admin_notes      = get_user_meta( $user_id, 'mf_admin_notes', true );
        $messages         = get_user_meta( $user_id, 'mf_user_messages', true ) ?: array();
        $unread_count = 0;
        foreach ($messages as $m) {
            if (!$m['read']) $unread_count++;
        }

        // Auto-mark as read if on mailbox tab
        if ( $unread_count > 0 ) {
            $updated_msgs = array();
            foreach ($messages as $m) {
                $m['read'] = true;
                $updated_msgs[] = $m;
            }
            update_user_meta( $user_id, 'mf_user_messages', $updated_msgs );
        }

        $all_logs = Member_Files_Logger::get_logs( $user_id );
        $logs = array_slice( array_reverse($all_logs), 0, 10 );

        // Integrated Data
        $member_records    = $this->get_integrated_data( 'member_record', $national_id );
        $member_licenses   = $this->get_integrated_data( 'member_license', $national_id );
        $member_institutions = $this->get_integrated_data( 'member_institution', $national_id );
        $member_payments   = $this->get_integrated_data( 'member_payment', $national_id );

        // Collect all documents for sidebar
        $sidebar_docs = array();
        $doc_keys = array('photo', 'id_photo', 'cv', 'passport', 'membership_photo', 'license_photo');
        foreach ($doc_keys as $key) {
            $val = get_user_meta($user_id, $key, true);
            if ($val) {
                $sidebar_docs[$key] = array(
                    'label' => Member_Files::$profile_fields[$key],
                    'url'   => $val
                );
            }
        }

		ob_start();
		?>
		<div class="mf-dashboard" dir="rtl">
            <?php if ( isset( $_GET['update_pending'] ) ) : ?>
                <div class="mf-floating-notification">
                    <div class="mf-floating-content">
                        <span class="dashicons dashicons-info"></span>
                        <p>تحديثك الأخير قيد المراجعة. بمجرد الموافقة، ستظهر التغييرات فوراً.</p>
                    </div>
                </div>
                <script>
                    setTimeout(function() {
                        var notification = document.querySelector('.mf-floating-notification');
                        if (notification) {
                            notification.classList.add('fade-out');
                            setTimeout(function() { notification.remove(); }, 500);
                        }
                    }, 5000);
                </script>
            <?php endif; ?>
            <aside class="mf-sidebar">
                <div class="mf-user-card">
                    <div class="mf-avatar-wrapper enlarged">
                        <div class="mf-avatar">
                            <?php if ( $profile_photo_id ) : ?>
                                <?php echo wp_get_attachment_image( $profile_photo_id, 'medium' ); ?>
                            <?php else : ?>
                                <div class="mf-no-avatar" style="background-image: url('https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y');"></div>
                            <?php endif; ?>
                        </div>
                        <div class="mf-avatar-edit-btn" title="تحديث الصورة" onclick="window.switchTab('settings')">
                            <span class="dashicons dashicons-edit"></span>
                        </div>
                    </div>
                    <div class="mf-user-meta-top">
                        <h3><?php echo esc_html( $full_name ); ?></h3>
                    </div>
                    <p class="mf-membership-tag"><?php echo esc_html( $national_id ); ?></p>
                </div>
                <nav class="mf-nav">
                    <a href="#overview" class="active" data-tab="overview"><span class="dashicons dashicons-dashboard"></span> نظرة عامة</a>
                    <?php if ( ! empty($member_records) ) : ?>
                        <a href="#records" data-tab="records"><span class="dashicons dashicons-id-alt"></span> العضوية</a>
                    <?php endif; ?>
                    <?php if ( ! empty($member_licenses) ) : ?>
                        <a href="#licenses" data-tab="licenses"><span class="dashicons dashicons-media-text"></span> رخصة مزاولة المهنة</a>
                    <?php endif; ?>
                    <?php if ( ! empty($member_institutions) ) : ?>
                        <a href="#institutions" data-tab="institutions"><span class="dashicons dashicons-building"></span> ترخيص المنشأة</a>
                    <?php endif; ?>
                    <a href="#payments" data-tab="payments"><span class="dashicons dashicons-cart"></span> المدفوعات</a>
                    <a href="#documents" data-tab="documents"><span class="dashicons dashicons-paperclip"></span> المستندات</a>
                    <a href="#activity" data-tab="activity"><span class="dashicons dashicons-list-view"></span> سجل النشاط</a>
                </nav>

                <?php if ( ! empty($sidebar_docs) ) : ?>
                <div class="mf-sidebar-section">
                    <h4 class="mf-sidebar-title">المستندات المرفوعة</h4>
                    <div class="mf-sidebar-docs-list">
                        <?php foreach ($sidebar_docs as $key => $doc) : ?>
                            <a href="<?php echo esc_url($doc['url']); ?>" target="_blank" class="mf-doc-btn">
                                <span class="dashicons dashicons-media-document"></span>
                                <?php echo esc_html($doc['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>

            <main class="mf-content">
                <header class="mf-dashboard-header">
                    <div class="mf-header-actions">
                        <a href="<?php echo $is_active ? home_url('/digital-services/') : '#'; ?>" 
                           class="mf-btn-header services-btn <?php echo $is_active ? '' : 'disabled'; ?>"
                           <?php echo !$is_active ? 'onclick="return false;" title="هذه الخدمة متاحة للأعضاء النشطين فقط"' : ''; ?>>
                           <span class="dashicons dashicons-admin-site"></span> الخدمات الرقمية
                        </a>
                        <a href="#mailbox" data-tab="mailbox" class="mf-btn-header inbox-btn">
                            <span class="dashicons dashicons-email"></span> البريد الوارد
                            <?php if ($unread_count > 0) : ?>
                                <span class="mf-notif-dot"></span>
                            <?php endif; ?>
                        </a>
                        <a href="#settings" data-tab="settings" class="mf-btn-header settings-btn"><span class="dashicons dashicons-admin-generic"></span> الإعدادات</a>
                        <a href="<?php echo wp_logout_url( get_permalink() ); ?>" class="mf-btn-header logout-btn"><span class="dashicons dashicons-exit"></span> تسجيل الخروج</a>
                    </div>
                </header>

                <?php if ( $admin_notes ) : ?>
                    <div class="mf-admin-notice">
                        <strong>تنبيه من الإدارة:</strong>
                        <p><?php echo esc_html( $admin_notes ); ?></p>
                    </div>
                <?php endif; ?>

                <section id="overview" class="mf-tab-content active">
                    <div class="mf-badge-section">
                        <div class="mf-meta-badge clickable-card <?php echo $is_active ? 'has-data' : 'no-data'; ?>" <?php echo $is_active ? 'onclick="window.switchTab(\'records\')"' : ''; ?>>
                            <div class="badge-icon"><span class="dashicons dashicons-awards"></span></div>
                            <div class="badge-content">
                                <span>حالة العضوية</span>
                                <strong><?php echo esc_html($status_label); ?></strong>
                            </div>
                        </div>
                        <div class="mf-meta-badge clickable-card <?php echo !empty($member_licenses) ? 'has-data' : 'no-data'; ?>" <?php echo !empty($member_licenses) ? 'onclick="window.switchTab(\'licenses\')"' : ''; ?>>
                            <div class="badge-icon"><span class="dashicons dashicons-businessman"></span></div>
                            <div class="badge-content">
                                <span>رخصة مزاولة المهنة</span>
                                <strong><?php echo !empty($member_licenses) ? 'ترخيص ساري' : 'غير متوفر'; ?></strong>
                            </div>
                        </div>
                        <div class="mf-meta-badge clickable-card <?php echo !empty($member_institutions) ? 'has-data' : 'no-data'; ?>" <?php echo !empty($member_institutions) ? 'onclick="window.switchTab(\'institutions\')"' : ''; ?>>
                            <div class="badge-icon"><span class="dashicons dashicons-store"></span></div>
                            <div class="badge-content">
                                <span>ترخيص المنشأة</span>
                                <strong><?php 
                                    if (!empty($member_institutions)) {
                                        echo count($member_institutions) == 1 ? esc_html(get_post_meta($member_institutions[0]->ID, '_mf_inst_name', true) ?: 'منشأة واحدة') : count($member_institutions) . ' منشآت';
                                    } else {
                                        echo 'لا يوجد';
                                    }
                                ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="mf-card">
                        <h3>المعلومات الشخصية</h3>
                        <div class="mf-info-grid">
                            <?php 
                            $personal_fields = array('name', 'nid', 'dob', 'nationality', 'gender', 'bio');
                            foreach ($personal_fields as $f) : 
                                if ( empty($data[$f]) ) continue;
                                ?>
                                <div class="mf-info-item">
                                    <span><?php echo Member_Files::$profile_fields[$f]; ?></span>
                                    <strong><?php echo esc_html( $data[$f] ?: '-' ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mf-card">
                        <h3>المعلومات المهنية والأكاديمية</h3>
                        <div class="mf-info-grid">
                            <!-- Row 1 -->
                            <div class="mf-info-item">
                                <span><?php echo Member_Files::$profile_fields['member_no']; ?></span>
                                <strong><?php echo esc_html( $data['member_no'] ?: '-' ); ?></strong>
                            </div>
                            <div class="mf-info-item">
                                <span><?php echo Member_Files::$profile_fields['prof_level']; ?></span>
                                <strong><?php echo esc_html( $data['prof_level'] ?: '-' ); ?></strong>
                            </div>
                            <!-- Row 2 -->
                            <div class="mf-info-item">
                                <span><?php echo Member_Files::$profile_fields['union_branch']; ?></span>
                                <strong><?php echo esc_html( $data['union_branch'] ?: '-' ); ?></strong>
                            </div>
                            <div class="mf-info-item">
                                <span><?php echo Member_Files::$profile_fields['prof_special']; ?></span>
                                <strong><?php echo esc_html( $data['prof_special'] ?: '-' ); ?></strong>
                            </div>
                            <!-- Other academic fields -->
                            <?php 
                            $other_pro_fields = array('degree', 'university', 'college', 'department', 'acad_special', 'grad_year');
                            foreach ($other_pro_fields as $f) : 
                                if ( empty($data[$f]) ) continue;
                                ?>
                                <div class="mf-info-item">
                                    <span><?php echo Member_Files::$profile_fields[$f]; ?></span>
                                    <strong><?php echo esc_html( $data[$f] ?: '-' ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mf-card">
                        <h3>معلومات التواصل</h3>
                        <div class="mf-info-grid">
                            <?php 
                            $contact_fields = array('res_country', 'res_province', 'res_city', 'address', 'phone', 'phone_alt', 'email');
                            foreach ($contact_fields as $f) : 
                                if ( empty($data[$f]) ) continue;
                                ?>
                                <div class="mf-info-item">
                                    <span><?php echo Member_Files::$profile_fields[$f]; ?></span>
                                    <strong><?php echo esc_html( $data[$f] ?: '-' ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mf-card">
                        <h3>إحصائيات سريعة</h3>
                        <div class="mf-stats-grid">
                            <div class="mf-stat-box">
                                <span class="mf-stat-value"><?php echo count($logs); ?></span>
                                <span class="mf-stat-label">إجمالي العمليات</span>
                            </div>
                            <div class="mf-stat-box">
                                <span class="mf-stat-value"><?php echo count($messages); ?></span>
                                <span class="mf-stat-label">الرسائل الواردة</span>
                            </div>
                            <div class="mf-stat-box">
                                <span class="mf-stat-value"><?php echo $requests_count; ?></span>
                                <span class="mf-stat-label">طلبات إضافية</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="records" class="mf-tab-content">
                    <div class="mf-card">
                        <h3>بيانات العضوية</h3>
                        <?php if ( ! empty( $member_records ) ) : ?>
                            <div class="mf-table-wrapper">
                            <table class="mf-table">
                                <thead>
                                    <tr>
                                        <th>رقم العضوية</th>
                                        <th>الفرع</th>
                                        <th>المؤهل</th>
                                        <th>تاريخ الانتهاء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $member_records as $post ) : ?>
                                        <?php 
                                        $expiry = get_post_meta( $post->ID, '_mf_expiry_date', true ); 
                                        $is_expired = strtotime($expiry) < time();
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_member_num', true ) ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_sub_syndicate', true ) ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_qualification', true ) ); ?></td>
                                            <td>
                                                <span class="mf-badge <?php echo $is_expired ? 'expired' : 'active'; ?>">
                                                    <?php echo esc_html( $expiry ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php else : ?>
                            <p>لا توجد بيانات عضوية مسجلة حالياً.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="licenses" class="mf-tab-content">
                    <div class="mf-card">
                        <h3>رخصة مزاولة المهنة</h3>
                        <?php if ( ! empty( $member_licenses ) ) : ?>
                            <div class="mf-table-wrapper">
                            <table class="mf-table">
                                <thead>
                                    <tr>
                                        <th>رقم الترخيص</th>
                                        <th>الرتبة</th>
                                        <th>التخصص</th>
                                        <th>جهة الإصدار</th>
                                        <th>تاريخ الانتهاء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $member_licenses as $post ) : ?>
                                        <?php 
                                        $expiry = get_post_meta( $post->ID, '_mf_expiry_date', true ); 
                                        $is_expired = strtotime($expiry) < time();
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_license_num', true ) ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_prof_rank', true ) ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_prof_spec', true ) ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_issue_authority', true ) ); ?></td>
                                            <td>
                                                <span class="mf-badge <?php echo $is_expired ? 'expired' : 'active'; ?>">
                                                    <?php echo esc_html( $expiry ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php else : ?>
                            <p>لا توجد تراخيص مهنية مسجلة.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="institutions" class="mf-tab-content">
                    <div class="mf-card">
                        <h3>ترخيص المنشأة</h3>
                        <?php if ( ! empty( $member_institutions ) ) : ?>
                            <div class="mf-table-wrapper">
                            <table class="mf-table">
                                <thead>
                                    <tr>
                                        <th>رقم المنشأة</th>
                                        <th>السجل التجاري</th>
                                        <th>الرقم الضريبي</th>
                                        <th>تاريخ الانتهاء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $member_institutions as $post ) : ?>
                                        <?php 
                                        $expiry = get_post_meta( $post->ID, '_mf_expiry_date', true ); 
                                        $is_expired = strtotime($expiry) < time();
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_inst_num', true ) ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_comm_reg', true ) ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_tax_id', true ) ); ?></td>
                                            <td>
                                                <span class="mf-badge <?php echo $is_expired ? 'expired' : 'active'; ?>">
                                                    <?php echo esc_html( $expiry ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php else : ?>
                            <p>لا توجد منشآت مسجلة باسمك.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="mailbox" class="mf-tab-content">
                    <div class="mf-card">
                        <h3>صندوق الرسائل والاشعارات</h3>
                        <p class="mf-mailbox-notice" style="background: #fff9e6; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; border-right: 4px solid #ffcc00;">
                            <span class="dashicons dashicons-info" style="font-size: 18px; margin-left: 5px;"></span>
                            تنبيه: يتم حذف الرسائل والإشعارات تلقائياً بعد مرور 30 يوماً على استلامها.
                        </p>
                        <?php 
                        $messages = get_user_meta( $user_id, 'mf_user_messages', true ) ?: array();
                        if ( ! empty( $messages ) ) : ?>
                            <div class="mf-message-list">
                                <?php foreach ( array_reverse($messages) as $msg ) : ?>
                                    <div class="mf-message-item <?php echo $msg['read'] ? '' : 'unread'; ?>">
                                        <div class="msg-header">
                                            <strong><?php echo esc_html($msg['subject']); ?></strong>
                                            <span><?php echo date('Y-m-d H:i', $msg['time']); ?></span>
                                        </div>
                                        <div class="msg-body">
                                            <?php echo nl2br(esc_html($msg['content'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p>لا توجد رسائل جديدة حالياً.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="payments" class="mf-tab-content">
                    <div class="mf-card">
                        <h3>سجل المدفوعات والرسوم</h3>
                        <?php if ( ! empty( $member_payments ) ) : ?>
                            <div class="mf-table-wrapper">
                            <table class="mf-table">
                                <thead>
                                    <tr>
                                        <th>النوع</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>الإيصال</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $member_payments as $post ) : ?>
                                        <?php 
                                        $status = get_post_meta( $post->ID, '_mf_payment_status', true ); 
                                        $type = get_post_meta( $post->ID, '_mf_payment_type', true );
                                        $type_label = '';
                                        switch($type) {
                                            case 'membership': $type_label = 'اشتراك عضوية'; break;
                                            case 'license': $type_label = 'رسوم ترخيص'; break;
                                            case 'institution': $type_label = 'رسوم منشأة'; break;
                                        }
                                        ?>
                                        <tr class="payment-row <?php echo $status; ?>">
                                            <td><?php echo esc_html( $type_label ); ?></td>
                                            <td><?php echo esc_html( get_post_meta( $post->ID, '_mf_amount', true ) ); ?> ج.م</td>
                                            <td>
                                                <span class="mf-badge <?php echo $status; ?>">
                                                    <?php echo $status == 'paid' ? 'تم الدفع' : 'مطلوب الدفع'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($status == 'paid') : ?>
                                                    <div class="payment-receipt-btn" onclick="window.print()">
                                                        <span class="dashicons dashicons-printer"></span> إيصال دفع
                                                    </div>
                                                <?php else : ?>
                                                    <?php echo esc_html( get_post_meta( $post->ID, '_mf_trans_id', true ) ?: '-' ); ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php else : ?>
                            <p>لا توجد عمليات دفع مسجلة.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="documents" class="mf-tab-content">
                    <div class="mf-card">
                        <h3>المستندات المصنفة</h3>
                        
                        <div class="mf-doc-group">
                            <h4><span class="dashicons dashicons-id"></span> ملفات العضوية</h4>
                            <div class="mf-file-list">
                                <?php 
                                $membership_files = get_user_meta($user_id, 'mf_docs_membership', true) ?: array();
                                if (!empty($membership_files)) : foreach($membership_files as $fid) : ?>
                                    <div class="mf-file-card">
                                        <span><?php echo get_the_title($fid); ?></span>
                                        <a href="<?php echo wp_get_attachment_url($fid); ?>" target="_blank" class="mf-btn-secondary">تحميل</a>
                                    </div>
                                <?php endforeach; else: echo "<p class='empty-msg'>لا يوجد ملفات</p>"; endif; ?>
                            </div>
                        </div>

                        <div class="mf-doc-group">
                            <h4><span class="dashicons dashicons-portfolio"></span> ملفات تراخيص المهنة</h4>
                            <div class="mf-file-list">
                                <?php if ( $license_file_id ) : ?>
                                    <div class="mf-file-card">
                                        <span>الرخصة المهنية الأساسية</span>
                                        <a href="<?php echo wp_get_attachment_url( $license_file_id ); ?>" target="_blank" class="mf-btn-secondary">تحميل</a>
                                    </div>
                                <?php endif; ?>
                                <?php 
                                $license_files = get_user_meta($user_id, 'mf_docs_license', true) ?: array();
                                if (!empty($license_files)) : foreach($license_files as $fid) : ?>
                                    <div class="mf-file-card">
                                        <span><?php echo get_the_title($fid); ?></span>
                                        <a href="<?php echo wp_get_attachment_url($fid); ?>" target="_blank" class="mf-btn-secondary">تحميل</a>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>

                        <div class="mf-doc-group">
                            <h4><span class="dashicons dashicons-store"></span> ملفات تراخيص المنشآت</h4>
                            <div class="mf-file-list">
                                <?php 
                                $inst_files = get_user_meta($user_id, 'mf_docs_institution', true) ?: array();
                                if (!empty($inst_files)) : foreach($inst_files as $fid) : ?>
                                    <div class="mf-file-card">
                                        <span><?php echo get_the_title($fid); ?></span>
                                        <a href="<?php echo wp_get_attachment_url($fid); ?>" target="_blank" class="mf-btn-secondary">تحميل</a>
                                    </div>
                                <?php endforeach; else: echo "<p class='empty-msg'>لا يوجد ملفات</p>"; endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="activity" class="mf-tab-content">
                    <div class="mf-card">
                        <h3>سجل النشاط (آخر 10 عمليات)</h3>
                        <div class="mf-table-wrapper">
                        <table class="mf-table">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>العملية</th>
                                    <th>التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $logs as $log ) : ?>
                                    <tr>
                                        <td><?php echo date( 'Y-m-d H:i', $log['time'] ); ?></td>
                                        <td><?php echo esc_html( $log['action'] ); ?></td>
                                        <td><?php echo esc_html( $log['details'] ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </section>

                <section id="settings" class="mf-tab-content">
                    <?php if ( isset( $_GET['upload_success'] ) ) : ?>
                        <div class="notice notice-success"><p>تم تحديث الصورة الشخصية بنجاح.</p></div>
                    <?php endif; ?>
                    <?php 
                    $pending_update = get_user_meta( $user_id, 'mf_pending_settings_update', true );
                    if ( $pending_update ) : ?>
                        <div class="mf-card mf-pending-update-card" style="border: 2px solid #3498db; background: #f0f7fd;">
                            <h3 style="color: #2980b9;"><span class="dashicons dashicons-clock"></span> طلب تعديل معلق حالياً</h3>
                            <p>لقد قمت بإرسال طلب لتعديل بياناتك، وهو قيد المراجعة حالياً من قبل الإدارة. البيانات المرسلة:</p>
                            <div class="mf-info-grid">
                                <?php foreach ( $pending_update as $k => $v ) : 
                                    if ( empty($v) ) continue;
                                    $label = isset(Member_Files::$profile_fields[$k]) ? Member_Files::$profile_fields[$k] : $k;
                                    ?>
                                    <div class="mf-info-item">
                                        <span><?php echo $label; ?></span>
                                        <strong>
                                            <?php if ( filter_var($v, FILTER_VALIDATE_URL) && preg_match('/\.(jpg|jpeg|png|gif|pdf)$/i', $v) ) : ?>
                                                <a href="<?php echo esc_url($v); ?>" target="_blank">عرض المرفق</a>
                                            <?php else : ?>
                                                <?php echo esc_html($v); ?>
                                            <?php endif; ?>
                                        </strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top: 15px; font-size: 13px; color: #7f8c8d;">
                                * سيتم تحديث بياناتك تلقائياً فور موافقة الإدارة على الطلب.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mf-card">
                        <h3>تعديل بيانات الحساب</h3>
                        <p class="mf-settings-note">ملاحظة: سيتم مراجعة التعديلات من قبل الإدارة قبل تطبيقها.</p>
                        
                        <?php if ( isset( $_GET['update_pending'] ) ) : ?>
                            <div class="notice notice-info"><p>تم إرسال طلب التعديل للمراجعة.</p></div>
                        <?php endif; ?>

                        <form action="" method="post" enctype="multipart/form-data" class="mf-form-v2">
                            <?php wp_nonce_field( 'mf_update_settings', 'mf_settings_nonce' ); ?>
                            
                            <h4 class="mf-settings-section-title">المعلومات الشخصية</h4>
                            <div class="mf-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                                <?php 
                                $edit_personal = array('name', 'nid', 'dob', 'nationality', 'gender', 'bio');
                                foreach ($edit_personal as $f) : 
                                    // Fields that cannot be edited after approval
                                    $is_locked = ( !$is_pending && in_array($f, array('name', 'dob', 'nationality')) );
                                    $is_readonly = ($f === 'nid' || $is_locked);
                                    ?>
                                    <div class="mf-form-group">
                                        <label><?php echo Member_Files::$profile_fields[$f]; ?></label>
                                        <?php if ($f === 'bio') : ?>
                                            <textarea name="bio" rows="3"><?php echo esc_textarea($data[$f]); ?></textarea>
                                        <?php elseif ($f === 'gender') : ?>
                                            <select name="gender">
                                                <option value="">الجنس</option>
                                                <option value="ذكر" <?php selected($data[$f], 'ذكر'); ?>>ذكر</option>
                                                <option value="أنثى" <?php selected($data[$f], 'أنثى'); ?>>أنثى</option>
                                            </select>
                                        <?php else : ?>
                                            <input type="<?php echo $f == 'dob' ? 'date' : 'text'; ?>" 
                                                   name="<?php echo $f; ?>" 
                                                   value="<?php echo esc_attr($data[$f]); ?>"
                                                   <?php echo $is_readonly ? 'readonly style="background: #f5f5f5; cursor: not-allowed;" title="لا يمكن تعديل هذا الحقل"' : ''; ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <h4 class="mf-settings-section-title">المعلومات المهنية والأكاديمية</h4>
                            <div class="mf-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                                <?php 
                                $edit_pro = array('member_no', 'prof_level', 'union_branch', 'prof_special', 'degree', 'university', 'college', 'department', 'acad_special', 'grad_year');
                                foreach ($edit_pro as $f) : 
                                    // Logic for locked fields
                                    $is_pro_locked = (!$is_pending && in_array($f, array('degree', 'prof_special', 'prof_level')));
                                    $is_readonly = ($f === 'member_no' || $is_pro_locked);
                                    ?>
                                    <div class="mf-form-group">
                                        <label><?php echo Member_Files::$profile_fields[$f]; ?></label>
                                        <?php if ($f === 'degree') : ?>
                                            <?php if ($is_readonly) : ?>
                                                <input type="text" value="<?php echo esc_attr($data[$f]); ?>" readonly style="background: #f5f5f5; cursor: not-allowed;">
                                                <input type="hidden" name="degree" value="<?php echo esc_attr($data[$f]); ?>">
                                            <?php else: ?>
                                                <select name="degree">
                                                    <option value="">الدرجة العلمية</option>
                                                    <?php 
                                                    $degrees = array('Undergraduate' => 'طالب', 'Bachelor' => 'بكالوريوس', 'Master' => 'ماجستير', 'Doctorate' => 'دكتوراه');
                                                    foreach ($degrees as $val => $lbl) : ?>
                                                        <option value="<?php echo $val; ?>" <?php selected($data[$f], $val); ?>><?php echo $lbl; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        <?php elseif ($f === 'prof_level') : ?>
                                            <?php if ($is_readonly) : ?>
                                                <input type="text" value="<?php echo esc_attr($data[$f]); ?>" readonly style="background: #f5f5f5; cursor: not-allowed;">
                                                <input type="hidden" name="prof_level" value="<?php echo esc_attr($data[$f]); ?>">
                                            <?php else: ?>
                                                <select name="prof_level">
                                                    <option value="">المستوى المهني</option>
                                                    <?php 
                                                    $levels = array('Practitioner' => 'ممارس', 'Assistant Specialist' => 'أخصائي مساعد', 'Specialist' => 'أخصائي', 'Consultant' => 'استشاري');
                                                    foreach ($levels as $val => $lbl) : ?>
                                                        <option value="<?php echo $val; ?>" <?php selected($data[$f], $val); ?>><?php echo $lbl; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <input type="text" 
                                                   name="<?php echo $f; ?>" 
                                                   value="<?php echo esc_attr($data[$f]); ?>"
                                                   <?php echo $is_readonly ? 'readonly style="background: #f5f5f5; cursor: not-allowed;"' : ''; ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <h4 class="mf-settings-section-title">معلومات التواصل</h4>
                            <div class="mf-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                                <?php 
                                $edit_contact = array('res_country', 'res_province', 'res_city', 'address', 'phone', 'phone_alt');
                                foreach ($edit_contact as $f) : ?>
                                    <div class="mf-form-group">
                                        <label><?php echo Member_Files::$profile_fields[$f]; ?></label>
                                        <input type="text" name="<?php echo $f; ?>" value="<?php echo esc_attr($data[$f]); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mf-email-update-section" style="background: #f8fafc; padding: 25px; border-radius: 12px; margin-top: 30px; border: 1px dashed #cbd5e1;">
                                <h4 style="margin-top: 0;">تعديل وتأكيد البريد الإلكتروني</h4>
                                <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">لتغيير بريدك الإلكتروني، سيتم إرسال رمز تحقق (OTP) إلى بريدك الحالي لتأكيد العملية.</p>
                                <div class="mf-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div class="mf-form-group">
                                        <label>البريد الإلكتروني الجديد</label>
                                        <input type="email" name="new_email" placeholder="example@mail.com">
                                    </div>
                                    <div class="mf-form-group">
                                        <label>رمز التحقق (OTP)</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="email_otp" placeholder="000000" style="flex: 1;">
                                            <button type="button" id="mf-send-email-otp" class="mf-btn-secondary" style="white-space: nowrap;">إرسال الرمز</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h3>المرفقات والمستندات</h3>
                            <div class="mf-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="mf-form-group">
                                    <label>صورة شخصية</label>
                                    <input type="file" name="photo">
                                </div>
                                <?php 
                                $file_fields = array('id_photo', 'cv', 'passport', 'membership_photo', 'license_photo');
                                foreach ($file_fields as $f) : ?>
                                    <div class="mf-form-group">
                                        <label><?php echo Member_Files::$profile_fields[$f]; ?></label>
                                        <input type="file" name="<?php echo $f; ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="submit" name="mf_submit_settings_change" class="mf-btn-primary" style="margin-top:20px;">إرسال طلب التعديل</button>
                        </form>
                    </div>
                </section>
            </main>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Tab switching logic
            window.switchTab = function(tabId) {
                if (!tabId || tabId === 'none') return;

                // Remove active class from all links and content sections
                $('.mf-nav a, .mf-btn-header, .mf-tab-content').removeClass('active');
                
                // Add active class to corresponding links and targeted content
                $('[data-tab="' + tabId + '"]').addClass('active');
                $('#' + tabId).addClass('active');
                
                // Update URL hash without jumping
                if (history.pushState) {
                    history.pushState(null, null, '#' + tabId);
                } else {
                    location.hash = '#' + tabId;
                }
                
                // Scroll to top of content for better UX on mobile
                if (window.innerWidth < 768) {
                    $('html, body').animate({
                        scrollTop: $(".mf-content").offset().top - 20
                    }, 500);
                }
            };

            // Global event handler for data-tab clicks
            $(document).on('click', '[data-tab]', function(e) {
                var target = $(this);
                if (target.hasClass('logout-btn') || (target.attr('href') && target.attr('href').indexOf('logout') !== -1)) return;
                
                var tabId = target.data('tab');
                if (tabId && tabId !== 'none') {
                    e.preventDefault();
                    window.switchTab(tabId);
                }
            });

            // Handle initial hash in URL
            var hash = window.location.hash.substring(1);
            if (hash && hash !== '') {
                window.switchTab(hash);
            }

            $('#mf-send-email-otp').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('جاري الإرسال...');
                
                $.post(
                    '<?php echo admin_url('admin-ajax.php'); ?>',
                    {
                        action: 'mf_send_email_otp',
                        nonce: '<?php echo wp_create_nonce("mf_update_settings"); ?>'
                    },
                    function(response) {
                        if(response.success) {
                            alert(response.data.msg);
                            btn.text('تم الإرسال');
                        } else {
                            alert('حدث خطأ أثناء الإرسال.');
                            btn.prop('disabled', false).text('إعادة الإرسال');
                        }
                    }
                );
            });
        });
        </script>
		<?php
		return ob_get_clean();
	}

    private function render_pending_status_page($user_id, $data) {
        $admin_notes = get_user_meta( $user_id, 'mf_admin_notes', true );
        $is_new = (get_user_meta($user_id, 'mf_member_type', true) === 'new');
        
        ob_start();
        ?>
        <div class="mf-dashboard pending-mode" dir="rtl">
            <div class="mf-pending-container">
                <div class="mf-pending-header">
                    <div class="status-icon"><span class="dashicons dashicons-clock"></span></div>
                    <h2>طلبك قيد المراجعة حالياً</h2>
                    <p class="status-msg">شكراً لتسجيلك. يقوم فريقنا حالياً بمراجعة بياناتك ومرفقاتك المقدمة.</p>
                </div>

                <?php if ($admin_notes) : ?>
                    <div class="mf-admin-notes-box">
                        <h4><span class="dashicons dashicons-warning"></span> ملاحظات من الإدارة:</h4>
                        <div class="notes-content"><?php echo nl2br(esc_html($admin_notes)); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($is_new) : ?>
                    <div class="mf-postal-notice" style="background: #eef2ff; border: 1px solid #c7d2fe; padding: 20px; border-radius: 15px; margin-top: 30px; text-align: right;">
                        <h4 style="color: #4338ca; margin-top: 0;"><span class="dashicons dashicons-email-alt"></span> تعليمات إرسال المستندات الورقية:</h4>
                        <p style="font-size: 14px; color: #3730a3;">يجب إرسال أصول المستندات عبر البريد المصري السريع إلى عنوان النقابة التالي:</p>
                        <strong style="display: block; font-size: 16px; margin: 10px 0; color: #1e1b4b; background: #fff; padding: 10px; border-radius: 8px;">43 عمارات العبور، رابعة العدوية، مدينة نصر، القاهرة</strong>
                        <p style="font-size: 13px; color: #4338ca; margin-bottom: 0;">* يرجى كتابة الرقم القومي واسمك بالكامل بوضوح على المظروف من الخارج.</p>
                    </div>

                    <div class="mf-steps-map">
                        <h3>نظام تتبع طلب العضوية:</h3>
                        <?php 
                        $current_stage = (int) get_user_meta($user_id, 'mf_membership_stage', true) ?: 1;
                        $stage_note = get_user_meta($user_id, 'mf_stage_note', true);
                        
                        $stages = array(
                            1 => 'مراجعة البيانات',
                            2 => 'انتظار المستندات',
                            3 => 'مراجعة المستندات',
                            4 => 'تفعيل الحساب'
                        );
                        ?>
                        <div class="map-grid four-stages">
                            <?php foreach ($stages as $num => $label) : 
                                $status_class = '';
                                if ($num < $current_stage) $status_class = 'completed';
                                elseif ($num == $current_stage) $status_class = 'active';
                                ?>
                                <div class="map-item <?php echo $status_class; ?>">
                                    <span class="dot"></span>
                                    <label><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($stage_note) : ?>
                            <div class="mf-stage-note-box">
                                <strong><span class="dashicons dashicons-email-alt"></span> رسالة المرحلة الحالية:</strong>
                                <p><?php echo nl2br(esc_html($stage_note)); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mf-account-under-review" style="margin-top: 30px; padding: 40px; border: 2px dashed #cbd5e1; border-radius: 20px; background: #fff; text-align: center;">
                        <div style="font-size: 50px; color: #3498db; margin-bottom: 20px;"><span class="dashicons dashicons-clock" style="font-size: 50px; width: 50px; height: 50px;"></span></div>
                        <p style="font-size: 22px; color: #1e293b; font-weight: 800; margin-bottom: 15px;">طلبك قيد المراجعة حالياً من قبل الإدارة</p>
                        <p style="color: #64748b; font-size: 16px; line-height: 1.8;">ستصلك رسالة بريد إلكتروني لتفعيل حسابك فور الانتهاء من مراجعة البيانات.</p>
                        <div style="display: inline-block; margin-top: 25px; padding: 12px 25px; background: #ebf8ff; border: 1px solid #bee3f8; border-radius: 10px; font-weight: bold; color: #2b6cb0;">
                            الحالة: جاري التدقيق والاعتماد
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mf-pending-actions">
                    <a href="<?php echo wp_logout_url( get_permalink() ); ?>" class="button button-secondary">تسجيل الخروج</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_integrated_data( $post_type, $national_id ) {
        if ( ! $national_id ) return array();
        
        return get_posts(array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_mf_national_id',
                    'value'   => $national_id,
                    'compare' => '='
                )
            )
        ));
    }

    public function handle_profile_updates() {
        if ( empty($_POST) ) {
            return;
        }

        $user_id = get_current_user_id();
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Handle categorized doc upload (Categorized docs remain immediate as they are supplementary)
        if ( ! empty( $_FILES['mf_new_doc']['name'] ) && isset($_POST['mf_upload_nonce']) ) {
            if ( ! wp_verify_nonce( $_POST['mf_upload_nonce'], 'mf_upload_files' ) ) return;
            $type = sanitize_text_field($_POST['mf_doc_type']);
            $attachment_id = media_handle_upload( 'mf_new_doc', 0 );
            if ( ! is_wp_error( $attachment_id ) ) {
                $meta_key = 'mf_docs_' . $type;
                $docs = get_user_meta( $user_id, $meta_key, true ) ?: array();
                $docs[] = $attachment_id;
                update_user_meta( $user_id, $meta_key, $docs );
                Member_Files_Logger::log( $user_id, 'رفع مستند', 'تم رفع مستند جديد في قسم ' . $type );
                wp_redirect( add_query_arg( 'upload_success', '1', remove_query_arg( 'upload_success' ) ) );
                exit;
            }
        }

        // Handle settings change request (Core data and Photo go to Pending)
        if ( isset($_POST['mf_submit_settings_change']) && isset($_POST['mf_settings_nonce']) ) {
            if ( ! wp_verify_nonce( $_POST['mf_settings_nonce'], 'mf_update_settings' ) ) return;
            
            $pending_changes = array();
            
            // Text fields
            foreach ( Member_Files::$profile_fields as $key => $label ) {
                if ( isset($_POST[$key]) ) {
                    $pending_changes[$key] = sanitize_text_field($_POST[$key]);
                }
            }

            // File fields (all go to pending)
            $file_keys = array('cv', 'id_photo', 'passport', 'membership_photo', 'license_photo');
            foreach ($file_keys as $key) {
                if ( ! empty($_FILES[$key]['name']) ) {
                    $attachment_id = media_handle_upload( $key, 0 );
                    if ( ! is_wp_error( $attachment_id ) ) {
                        $pending_changes[$key] = wp_get_attachment_url($attachment_id);
                    }
                }
            }

            // Handle special photo in settings
            if ( ! empty($_FILES['photo']['name']) ) {
                $attachment_id = media_handle_upload( 'photo', 0 );
                if ( ! is_wp_error( $attachment_id ) ) {
                    $pending_changes['mf_profile_photo_id'] = $attachment_id;
                    $pending_changes['personal_photo'] = wp_get_attachment_url($attachment_id);
                }
            }

            // Handle email update specifically if OTP is provided
            if ( !empty($_POST['new_email']) && !empty($_POST['email_otp']) ) {
                $saved_otp = get_transient( 'mf_email_update_otp_' . $user_id );
                if ( $saved_otp && $saved_otp === sanitize_text_field($_POST['email_otp']) ) {
                    wp_update_user( array( 'ID' => $user_id, 'user_email' => sanitize_email($_POST['new_email']) ) );
                    delete_transient( 'mf_email_update_otp_' . $user_id );
                    Member_Files_Logger::log( $user_id, 'تحديث البريد', 'تم تغيير البريد الإلكتروني بنجاح.' );
                } else {
                    wp_redirect( add_query_arg( 'update_error', 'otp_invalid', remove_query_arg('update_pending') ) );
                    exit;
                }
            }

            update_user_meta( $user_id, 'mf_pending_settings_update', $pending_changes );
            Member_Files_Logger::log( $user_id, 'طلب تعديل', 'تم إرسال طلب لتعديل البيانات الشخصية والمهنية' );
            wp_redirect( add_query_arg( 'update_pending', '1', remove_query_arg('update_pending') ) );
            exit;
        }


    }
}
