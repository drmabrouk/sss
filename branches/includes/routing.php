<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * نظام التوجيه الذكي - تم تحسينه لضمان العزل التام للموقع
 */

// 1. تعديل الرابط ليظهر بعد الدومين مباشرة (فقط للعرض)
add_filter('post_type_link', function($post_link, $post) {
    if ($post->post_type === 'branches' && $post->post_status === 'publish') {
        return home_url('/' . $post->post_name . '/');
    }
    return $post_link;
}, 10, 2);

// 2. التعامل مع الطلبات (Request Handling)
// بدلاً من استخدام قواعد إعادة كتابة عدوانية، نستخدم فلتر request للتحقق من وجود الفرع
add_filter('request', function($query_vars) {
    // إذا كان الطلب يحتوي على 'name' ولا يحتوي على 'post_type' (طلب محتمل في الجذر)
    if (isset($query_vars['name']) && !isset($query_vars['post_type'])) {
        $slug = $query_vars['name'];

        // التأكد من عدم وجود صفحة ثابتة بنفس الاسم أولاً (الأولوية للصفحات)
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page) {
            return $query_vars;
        }

        // التحقق إذا كان هناك فرع بهذا الاسم
        $branch_query = new WP_Query(array(
            'post_type' => 'branches',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids',
            'no_found_rows' => true,
        ));

        if ($branch_query->have_posts()) {
            $query_vars['post_type'] = 'branches';
        }
    }
    return $query_vars;
});

// 3. توجيه القالب لصفحة الفرع
add_filter('template_include', function($template) {
    // نتحقق بدقة أننا في صفحة فرع منفردة
    if (is_singular('branches') && is_main_query()) {
        $custom_template = BRANCHES_PATH . 'templates/single-branch.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
    }
    return $template;
});
