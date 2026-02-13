<?php

if (!defined('ABSPATH')) {
    exit;
}

function member_files_add_meta_boxes() {
    // Member Meta Box
    add_meta_box('mf_member_details', 'بيانات العضو التفصيلية', 'member_files_member_callback', 'member_record', 'normal', 'high');
    
    // License Meta Box
    add_meta_box('mf_license_details', 'بيانات الترخيص المهني', 'member_files_license_callback', 'member_license', 'normal', 'high');
    
    // Institution Meta Box
    add_meta_box('mf_institution_details', 'بيانات ترخيص المؤسسة', 'member_files_institution_callback', 'member_institution', 'normal', 'high');
    
    // Payment Meta Box
    add_meta_box('mf_payment_details', 'تفاصيل المدفوعات والرسوم', 'member_files_payment_callback', 'member_payment', 'normal', 'high');

    // Notes Meta Box for Member
    add_meta_box('mf_member_notes', 'ملاحظات إضافية', 'member_files_notes_callback', 'member_record', 'side', 'default');
}
add_action('add_meta_boxes', 'member_files_add_meta_boxes');

function member_files_member_callback($post) {
    wp_nonce_field('member_files_save_meta', 'member_files_nonce');
    $national_id = get_post_meta($post->ID, '_mf_national_id', true);
    $member_num = get_post_meta($post->ID, '_mf_member_num', true);
    $sub_syndicate = get_post_meta($post->ID, '_mf_sub_syndicate', true);
    $expiry_date = get_post_meta($post->ID, '_mf_expiry_date', true);
    
    $phone = get_post_meta($post->ID, '_mf_phone', true);
    $email = get_post_meta($post->ID, '_mf_email', true);
    $address = get_post_meta($post->ID, '_mf_address', true);
    $qualification = get_post_meta($post->ID, '_mf_qualification', true);
    ?>
    <div class="mf-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <p>
            <label>الرقم القومي (المفتاح الرئيسي):</label><br>
            <input type="text" name="mf_national_id" value="<?php echo esc_attr($national_id); ?>" class="widefat" required>
        </p>
        <p>
            <label>رقم العضوية:</label><br>
            <input type="text" name="mf_member_num" value="<?php echo esc_attr($member_num); ?>" class="widefat">
        </p>
        <p>
            <label>النقابة الفرعية:</label><br>
            <input type="text" name="mf_sub_syndicate" value="<?php echo esc_attr($sub_syndicate); ?>" class="widefat">
        </p>
        <p>
            <label>تاريخ انتهاء العضوية:</label><br>
            <input type="date" name="mf_expiry_date" value="<?php echo esc_attr($expiry_date); ?>" class="widefat">
        </p>
        <p>
            <label>رقم الهاتف:</label><br>
            <input type="text" name="mf_phone" value="<?php echo esc_attr($phone); ?>" class="widefat">
        </p>
        <p>
            <label>البريد الإلكتروني:</label><br>
            <input type="email" name="mf_email" value="<?php echo esc_attr($email); ?>" class="widefat">
        </p>
        <p>
            <label>العنوان:</label><br>
            <input type="text" name="mf_address" value="<?php echo esc_attr($address); ?>" class="widefat">
        </p>
        <p>
            <label>المؤهل العلمي:</label><br>
            <input type="text" name="mf_qualification" value="<?php echo esc_attr($qualification); ?>" class="widefat">
        </p>
    </div>
    <?php
}

function member_files_license_callback($post) {
    wp_nonce_field('member_files_save_meta', 'member_files_nonce');
    $national_id = get_post_meta($post->ID, '_mf_national_id', true);
    $license_num = get_post_meta($post->ID, '_mf_license_num', true);
    $reg_num = get_post_meta($post->ID, '_mf_reg_num', true);
    $prof_rank = get_post_meta($post->ID, '_mf_prof_rank', true);
    $prof_spec = get_post_meta($post->ID, '_mf_prof_spec', true);
    $expiry_date = get_post_meta($post->ID, '_mf_expiry_date', true);
    
    $issue_authority = get_post_meta($post->ID, '_mf_issue_authority', true);
    ?>
    <div class="mf-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <p>
            <label>الرقم القومي للعضو:</label><br>
            <input type="text" name="mf_national_id" value="<?php echo esc_attr($national_id); ?>" class="widefat" required>
        </p>
        <p>
            <label>رقم الرخصة (الباركود):</label><br>
            <input type="text" name="mf_license_num" value="<?php echo esc_attr($license_num); ?>" class="widefat">
        </p>
        <p>
            <label>رقم القيد:</label><br>
            <input type="text" name="mf_reg_num" value="<?php echo esc_attr($reg_num); ?>" class="widefat">
        </p>
        <p>
            <label>جهة الإصدار:</label><br>
            <input type="text" name="mf_issue_authority" value="<?php echo esc_attr($issue_authority); ?>" class="widefat">
        </p>
        <p>
            <label>الدرجة المهنية:</label><br>
            <input type="text" name="mf_prof_rank" value="<?php echo esc_attr($prof_rank); ?>" class="widefat">
        </p>
        <p>
            <label>التخصص المهني:</label><br>
            <input type="text" name="mf_prof_spec" value="<?php echo esc_attr($prof_spec); ?>" class="widefat">
        </p>
        <p>
            <label>تاريخ انتهاء الرخصة:</label><br>
            <input type="date" name="mf_expiry_date" value="<?php echo esc_attr($expiry_date); ?>" class="widefat">
        </p>
    </div>
    <?php
}

function member_files_institution_callback($post) {
    wp_nonce_field('member_files_save_meta', 'member_files_nonce');
    $national_id = get_post_meta($post->ID, '_mf_national_id', true);
    $inst_num = get_post_meta($post->ID, '_mf_inst_num', true);
    $expiry_date = get_post_meta($post->ID, '_mf_expiry_date', true);
    
    $comm_reg = get_post_meta($post->ID, '_mf_comm_reg', true);
    $tax_id = get_post_meta($post->ID, '_mf_tax_id', true);
    ?>
    <div class="mf-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <p>
            <label>الرقم القومي للمالك/المسؤول:</label><br>
            <input type="text" name="mf_national_id" value="<?php echo esc_attr($national_id); ?>" class="widefat" required>
        </p>
        <p>
            <label>رقم المؤسسة/المركز:</label><br>
            <input type="text" name="mf_inst_num" value="<?php echo esc_attr($inst_num); ?>" class="widefat">
        </p>
        <p>
            <label>السجل التجاري:</label><br>
            <input type="text" name="mf_comm_reg" value="<?php echo esc_attr($comm_reg); ?>" class="widefat">
        </p>
        <p>
            <label>الرقم الضريبي:</label><br>
            <input type="text" name="mf_tax_id" value="<?php echo esc_attr($tax_id); ?>" class="widefat">
        </p>
        <p>
            <label>تاريخ انتهاء ترخيص المؤسسة:</label><br>
            <input type="date" name="mf_expiry_date" value="<?php echo esc_attr($expiry_date); ?>" class="widefat">
        </p>
    </div>
    <?php
}

function member_files_payment_callback($post) {
    wp_nonce_field('member_files_save_meta', 'member_files_nonce');
    $national_id = get_post_meta($post->ID, '_mf_national_id', true);
    $amount = get_post_meta($post->ID, '_mf_amount', true);
    $status = get_post_meta($post->ID, '_mf_payment_status', true);
    $type = get_post_meta($post->ID, '_mf_payment_type', true);
    $trans_id = get_post_meta($post->ID, '_mf_trans_id', true);
    ?>
    <div class="mf-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <p>
            <label>الرقم القومي للعضو:</label><br>
            <input type="text" name="mf_national_id" value="<?php echo esc_attr($national_id); ?>" class="widefat" required>
        </p>
        <p>
            <label>نوع المدفوعات:</label><br>
            <select name="mf_payment_type" class="widefat">
                <option value="membership" <?php selected($type, 'membership'); ?>>تجديد عضوية</option>
                <option value="license" <?php selected($type, 'license'); ?>>ترخيص مهني</option>
                <option value="institution" <?php selected($type, 'institution'); ?>>ترخيص مؤسسة</option>
                <option value="other" <?php selected($type, 'other'); ?>>رسوم أخرى</option>
            </select>
        </p>
        <p>
            <label>المبلغ المستحق:</label><br>
            <input type="number" step="0.01" name="mf_amount" value="<?php echo esc_attr($amount); ?>" class="widefat">
        </p>
        <p>
            <label>حالة الدفع:</label><br>
            <select name="mf_payment_status" class="widefat">
                <option value="pending" <?php selected($status, 'pending'); ?>>قيد الانتظار</option>
                <option value="paid" <?php selected($status, 'paid'); ?>>تم الدفع</option>
            </select>
        </p>
        <p>
            <label>رقم العملية (Transaction ID):</label><br>
            <input type="text" name="mf_trans_id" value="<?php echo esc_attr($trans_id); ?>" class="widefat">
        </p>
    </div>
    <?php
}

function member_files_notes_callback($post) {
    $notes = get_post_meta($post->ID, '_mf_notes', true);
    ?>
    <textarea name="mf_notes" class="widefat" rows="5"><?php echo esc_textarea($notes); ?></textarea>
    <?php
}

function member_files_save_meta_boxes($post_id) {
    if (!isset($_POST['member_files_nonce']) || !wp_verify_nonce($_POST['member_files_nonce'], 'member_files_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $fields = [
        'mf_national_id', 'mf_member_num', 'mf_sub_syndicate', 'mf_expiry_date',
        'mf_license_num', 'mf_reg_num', 'mf_prof_rank', 'mf_prof_spec',
        'mf_inst_num', 'mf_amount', 'mf_payment_status', 'mf_notes',
        'mf_phone', 'mf_email', 'mf_address', 'mf_qualification',
        'mf_issue_authority', 'mf_comm_reg', 'mf_tax_id', 'mf_payment_type', 'mf_trans_id'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'member_files_save_meta_boxes');
