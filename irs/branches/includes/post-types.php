<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function branches_register_post_types() {
    register_post_type('branches', array(
        'labels' => array(
            'name' => 'الفروع',
            'singular_name' => 'فرع',
            'all_items' => 'كافة الفروع',
            'add_new' => 'إضافة فرع جديد',
            'add_new_item' => 'إضافة فرع جديد',
            'edit_item' => 'تعديل بيانات الفرع',
            'new_item' => 'فرع جديد',
            'view_item' => 'عرض الفرع',
            'search_items' => 'البحث عن فروع',
            'not_found' => 'لم يتم العثور على فروع',
            'not_found_in_trash' => 'لا توجد فروع في السلة',
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => 'irs-admin-panel',
        'query_var' => true,
        'menu_icon' => 'dashicons-location',
        'supports' => array('title', 'editor', 'thumbnail', 'revisions'),
        'taxonomies' => array('branch_category'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'branch', 'with_front' => false), // استخدام بادئة مؤقتة للسماح بتحرير الرابط
    ));

    register_taxonomy('branch_category', 'branches', array(
        'label' => 'تصنيف الفروع',
        'hierarchical' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'branch-category'),
    ));
}
add_action('init', 'branches_register_post_types');
