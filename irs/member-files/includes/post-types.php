<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Post Types
 */
function member_files_register_post_types() {
    // Members
    register_post_type('member_record', array(
        'labels' => array(
            'name' => 'الأعضاء',
            'singular_name' => 'عضو',
            'menu_name' => 'ملفات الأعضاء',
            'add_new' => 'إضافة عضو جديد',
            'edit_item' => 'تعديل بيانات العضو',
        ),
        'public' => false,
        'show_ui' => true,
        'supports' => array('title', 'thumbnail'),
        'menu_icon' => 'dashicons-admin-users',
        'show_in_menu' => true,
    ));

    // Professional Licenses
    register_post_type('member_license', array(
        'labels' => array(
            'name' => 'التراخيص المهنية',
            'singular_name' => 'ترخيص مهني',
            'menu_name' => 'التراخيص المهنية',
            'add_new' => 'إضافة ترخيص جديد',
            'edit_item' => 'تعديل الترخيص',
        ),
        'public' => false,
        'show_ui' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-id',
        'show_in_menu' => 'edit.php?post_type=member_record',
    ));

    // Institution Licenses
    register_post_type('member_institution', array(
        'labels' => array(
            'name' => 'تراخيص المؤسسات',
            'singular_name' => 'مؤسسة/مركز',
            'menu_name' => 'تراخيص المؤسسات',
            'add_new' => 'إضافة مؤسسة/مركز',
            'edit_item' => 'تعديل بيانات المؤسسة',
        ),
        'public' => false,
        'show_ui' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-networking',
        'show_in_menu' => 'edit.php?post_type=member_record',
    ));

    // Payments
    register_post_type('member_payment', array(
        'labels' => array(
            'name' => 'المدفوعات',
            'singular_name' => 'دفعة',
            'menu_name' => 'المدفوعات والرسوم',
            'add_new' => 'إضافة دفعة/رسوم',
            'edit_item' => 'تعديل الدفعة',
        ),
        'public' => false,
        'show_ui' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-money-alt',
        'show_in_menu' => 'edit.php?post_type=member_record',
    ));
}
add_action('init', 'member_files_register_post_types');
