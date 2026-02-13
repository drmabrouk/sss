<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Admin Menu
 */
function member_files_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=member_record',
        'مركز إحصائيات النظام',
        'إحصائيات النظام',
        'manage_options',
        'mf-statistics',
        'member_files_statistics_page'
    );
}
add_action('admin_menu', 'member_files_admin_menu');

/**
 * Statistics Page Callback
 */
function member_files_statistics_page() {
    $count_members = wp_count_posts('member_record')->publish;
    $count_licenses = wp_count_posts('member_license')->publish;
    $count_institutions = wp_count_posts('member_institution')->publish;
    
    // Detailed Payment Stats
    $payments = get_posts([
        'post_type' => 'member_payment',
        'posts_per_page' => -1,
        'meta_query' => [['key' => '_mf_payment_status', 'value' => 'pending', 'compare' => '=']]
    ]);

    $total_pending = 0;
    $pending_membership = 0;
    $pending_license = 0;
    $pending_inst = 0;

    foreach ($payments as $p) {
        $amount = (float) get_post_meta($p->ID, '_mf_amount', true);
        $type = get_post_meta($p->ID, '_mf_payment_type', true);
        $total_pending += $amount;
        
        if ($type === 'membership') $pending_membership += $amount;
        elseif ($type === 'license') $pending_license += $amount;
        elseif ($type === 'institution') $pending_inst += $amount;
    }
    
    ?>
    <div class="wrap" style="direction: rtl;">
        <h1 style="margin-bottom: 20px;">لوحة إحصائيات نظام ملفات الأعضاء</h1>
        
        <div class="mf-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="mf-stat-card" style="border-right: 5px solid #0073aa;">
                <h3>إجمالي الأعضاء</h3>
                <div class="mf-stat-val"><?php echo esc_html($count_members); ?></div>
            </div>
            <div class="mf-stat-card" style="border-right: 5px solid #27ae60;">
                <h3>التراخيص المهنية</h3>
                <div class="mf-stat-val"><?php echo esc_html($count_licenses); ?></div>
            </div>
            <div class="mf-stat-card" style="border-right: 5px solid #e67e22;">
                <h3>تراخيص المؤسسات</h3>
                <div class="mf-stat-val"><?php echo esc_html($count_institutions); ?></div>
            </div>
            <div class="mf-stat-card" style="border-right: 5px solid #d32f2f;">
                <h3>إجمالي المستحقات المعلقة</h3>
                <div class="mf-stat-val"><?php echo number_format($total_pending, 2); ?> ج.م</div>
            </div>
        </div>

        <h2 style="margin-top: 40px;">تفاصيل المبالغ المعلقة حسب النوع</h2>
        <div class="mf-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="mf-stat-card secondary">
                <h4>اشتراكات العضوية</h4>
                <p><?php echo number_format($pending_membership, 2); ?> ج.م</p>
            </div>
            <div class="mf-stat-card secondary">
                <h4>رسوم التراخيص</h4>
                <p><?php echo number_format($pending_license, 2); ?> ج.م</p>
            </div>
            <div class="mf-stat-card secondary">
                <h4>رسوم المؤسسات</h4>
                <p><?php echo number_format($pending_inst, 2); ?> ج.م</p>
            </div>
        </div>
    </div>

    <style>
        .mf-stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .mf-stat-card h3 { margin: 0 0 10px 0; color: #555; font-size: 16px; }
        .mf-stat-val { font-size: 28px; font-weight: bold; color: #222; }
        .mf-stat-card.secondary { background: #f9f9f9; padding: 15px; border-right: 3px solid #ccc; }
        .mf-stat-card.secondary h4 { margin: 0; font-size: 14px; color: #666; }
        .mf-stat-card.secondary p { margin: 5px 0 0 0; font-size: 18px; font-weight: bold; }
    </style>
    <?php
}

/**
 * Customize Columns for Members
 */
function mf_set_member_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['photo'] = 'الصورة';
    $new_columns['title'] = 'الاسم';
    $new_columns['national_id'] = 'الرقم القومي';
    $new_columns['member_num'] = 'رقم العضوية';
    $new_columns['status'] = 'الحالة';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_member_record_posts_columns', 'mf_set_member_columns');

function mf_fill_member_columns($column, $post_id) {
    switch ($column) {
        case 'photo':
            echo get_the_post_thumbnail($post_id, [40, 40]);
            break;
        case 'national_id':
            echo esc_html(get_post_meta($post_id, '_mf_national_id', true));
            break;
        case 'member_num':
            echo esc_html(get_post_meta($post_id, '_mf_member_num', true));
            break;
        case 'status':
            $expiry = get_post_meta($post_id, '_mf_expiry_date', true);
            if (mf_is_expired($expiry)) {
                echo '<span style="color:red; font-weight:bold;">منتهي</span>';
            } else {
                echo '<span style="color:green; font-weight:bold;">ساري</span>';
            }
            break;
    }
}
add_action('manage_member_record_posts_custom_column', 'mf_fill_member_columns', 10, 2);
