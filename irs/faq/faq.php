<?php
/**
 * Sub-Plugin Name: الأسئلة الشائعة
 * Description: إضافة FAQ احترافية متوافقة مع Astra، تدعم الأقسام، التقييم، والفلترة. تطوير الدكتور أحمد مبروك.
 * Version: 4.5
 * Author: الدكتور أحمد مبروك
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. إنشاء جدول التقييمات عند تفعيل الإضافة (Moved to main irs.php)
// register_activation_hook( __FILE__, 'faq_pro_install' );
function faq_pro_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'faq_votes';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        likes int(11) DEFAULT 0,
        dislikes int(11) DEFAULT 0,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// 2. تسجيل نوع المقال (FAQ) والأقسام (Categories)
add_action( 'init', function() {
    register_post_type( 'faq', [
        'labels' => [
            'name' => 'الأسئلة الشائعة',
            'singular_name' => 'سؤال',
            'menu_name' => 'الأسئلة الشائعة',
            'add_new' => 'إضافة سؤال جديد'
        ],
        'public' => true,
        'menu_icon' => 'dashicons-format-chat',
        'supports' => ['title', 'editor'],
        'show_in_rest' => true,
        'show_in_menu' => 'irs-admin-panel'
    ]);

    register_taxonomy( 'faq_category', 'faq', [
        'labels' => ['name' => 'أقسام الأسئلة'],
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
});

// 3. لوحة الإدارة: التقارير ومعلومات المبرمج
add_action('admin_menu', function() {
    add_submenu_page('irs-admin-panel', 'تقارير الإعجابات', 'تقارير الإعجابات', 'manage_options', 'faq-stats', 'faq_pro_stats_page');
    add_submenu_page('irs-admin-panel', 'عن الإضافة', 'دليل الاستخدام ℹ️', 'manage_options', 'faq-about', 'faq_pro_about_page');
});

function faq_pro_stats_page() {
    global $wpdb;
    $results = $wpdb->get_results("SELECT post_id, likes, dislikes FROM {$wpdb->prefix}faq_votes ORDER BY likes DESC");
    echo '<div class="wrap"><h1>إحصائيات الأسئلة الشائعة</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>السؤال</th><th>👍 مفيد</th><th>👎 غير مفيد</th></tr></thead><tbody>';
    foreach ($results as $row) {
        $title = get_the_title($row->post_id);
        if($title) echo "<tr><td><strong>$title</strong></td><td>$row->likes</td><td>$row->dislikes</td></tr>";
    }
    echo '</tbody></table></div>';
}

function faq_pro_about_page() {
    ?>
    <div class="wrap">
        <div style="background: #fff; padding: 30px; border-right: 6px solid #0274be; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px;">
            <h1 style="color: #0274be;">إضافة الأسئلة الشائعة (FAQ)</h1>
            <p style="font-size: 1.3em;">تمت البرمجة بواسطة: <strong>الدكتور أحمد مبروك</strong></p>
            <hr>
            <h3>📘 كيفية الاستخدام:</h3>
            <p>لعرض الأسئلة في أي صفحة، قم بنسخ الشورت كود التالي:</p>
            <code style="font-size: 1.5em; background: #eee; padding: 5px 15px; border-radius: 4px;">[FAQ]</code>
            <h3 style="margin-top: 30px;">🌟 ميزات النظام:</h3>
            <ul style="line-height: 1.8; font-size: 1.1em;">
                <li>✅ فلترة احترافية حسب الأقسام (محاذاة لليمين).</li>
                <li>✅ تصميم شبكي (Grid) يظهر سؤاليْن في كل صف.</li>
                <li>✅ نظام تقييم AJAX (Like/Dislike) مرتبط بقاعدة البيانات.</li>
                <li>✅ متوافق تماماً مع خطوط وألوان قالب Astra.</li>
                <li>✅ دعم محركات البحث SEO عبر Schema Markup.</li>
            </ul>
        </div>
    </div>
    <?php
}

// 4. معالجة التقييم عبر AJAX
add_action('wp_ajax_faq_vote', 'faq_ajax_vote_handler');
add_action('wp_ajax_nopriv_faq_vote', 'faq_ajax_vote_handler');
function faq_ajax_vote_handler() {
    global $wpdb;
    $post_id = intval($_POST['post_id']);
    $type = ($_POST['type'] == 'like') ? 'likes' : 'dislikes';
    $table = $wpdb->prefix . 'faq_votes';
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE post_id = %d", $post_id));
    if ($exists) {
        $wpdb->query($wpdb->prepare("UPDATE $table SET $type = $type + 1 WHERE post_id = %d", $post_id));
    } else {
        $wpdb->insert($table, ['post_id' => $post_id, $type => 1]);
    }
    wp_send_json_success();
}

// 5. الشورت كود الرئيسي [FAQ] والعرض الواجهي
add_shortcode('FAQ', function() {
    $categories = get_terms(['taxonomy' => 'faq_category', 'hide_empty' => true]);
    $faqs = new WP_Query(['post_type' => 'faq', 'posts_per_page' => -1]);
    
    ob_start(); ?>
    
    <style>
        :root { --f-primary: var(--ast-global-color-0, #0274be); --f-text: var(--ast-global-color-3, #444); }
        .faq-v4-wrapper { direction: rtl; text-align: right; margin: 30px 0; }
        
        /* الفلترة يمين */
        .faq-filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; justify-content: flex-start; }
        .f-filter-btn { cursor: pointer; padding: 8px 22px; border: 1px solid #ddd; border-radius: 5px; background: #fff; transition: 0.3s; font-weight: 500; }
        .f-filter-btn.active, .f-filter-btn:hover { background: var(--f-primary); color: #fff; border-color: var(--f-primary); }

        /* الشبكة صفين */
        .faq-grid-system { display: grid; grid-template-columns: repeat(auto-fill, minmax(48%, 1fr)); gap: 20px; }
        @media (max-width: 768px) { .faq-grid-system { grid-template-columns: 1fr; } }
        
        .faq-item-card { border: 1px solid #eee; border-radius: 10px; background: #fff; transition: 0.3s; height: fit-content; }
        .faq-item-header { padding: 18px 22px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold; color: var(--f-primary); font-size: 1.1em; }
        .faq-item-body { padding: 0 22px; max-height: 0; overflow: hidden; transition: 0.4s ease; color: var(--f-text); line-height: 1.8; }
        .faq-item-card.is-open { border-color: var(--f-primary); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .faq-item-card.is-open .faq-item-body { padding: 15px 22px 25px; max-height: 1200px; border-top: 1px solid #f9f9f9; }
        
        /* التقييم */
        .faq-vote-bar { margin-top: 15px; font-size: 13px; display: flex; align-items: center; gap: 10px; border-top: 1px solid #eee; padding-top: 12px; }
        .v-button { border: 1px solid #eee; background: #fdfdfd; cursor: pointer; padding: 4px 12px; border-radius: 4px; transition: 0.2s; }
        .v-button:hover { background: #f0f0f0; }

        /* صندوق التواصل */
        .faq-contact-footer { margin-top: 50px; padding: 40px; background: #f9f9f9; border: 2px dashed var(--f-primary); border-radius: 15px; text-align: center; }
        .faq-contact-footer h3 { color: var(--f-primary); margin-bottom: 15px; font-size: 1.6em; }
        .faq-cta-btn { display: inline-block; padding: 14px 40px; background: var(--f-primary); color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 15px; transition: 0.3s; }
        .faq-cta-btn:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>

    <div class="faq-v4-wrapper">
        <div class="faq-filters">
            <div class="f-filter-btn active" onclick="faqFilter('all', this)">الكل</div>
            <?php foreach($categories as $cat): ?>
                <div class="f-filter-btn" onclick="faqFilter('cat-<?php echo $cat->term_id; ?>', this)"><?php echo $cat->name; ?></div>
            <?php endforeach; ?>
        </div>

        <div class="faq-grid-system">
            <?php if ($faqs->have_posts()) : while ($faqs->have_posts()) : $faqs->the_post(); 
                $terms = get_the_terms(get_the_ID(), 'faq_category');
                $cat_slugs = $terms ? implode(' ', array_map(function($t){return 'cat-'.$t->term_id;}, $terms)) : '';
            ?>
                <div class="faq-item-card <?php echo $cat_slugs; ?>" itemscope itemtype="https://schema.org/Question">
                    <div class="faq-item-header" onclick="this.parentElement.classList.toggle('is-open')">
                        <span itemprop="name"><?php the_title(); ?></span>
                        <span class="f-icon">▾</span>
                    </div>
                    <div class="faq-item-body" itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
                        <div itemprop="text"><?php the_content(); ?></div>
                        <div class="faq-vote-bar">
                            <span>هل أفادتك الإجابة؟</span>
                            <button class="v-button" onclick="faqSendVote(<?php echo get_the_ID(); ?>, 'like', this)">👍 نعم</button>
                            <button class="v-button" onclick="faqSendVote(<?php echo get_the_ID(); ?>, 'dislike', this)">👎 لا</button>
                        </div>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); endif; ?>
        </div>

        <div class="faq-contact-footer">
            <h3>لديك سؤال آخر؟</h3>
            <p>إذا لم تجد الإجابة التي تبحث عنها، يسعدنا تواصلك معنا مباشرة للإجابة على استفسارك.</p>
            <a href="<?php echo home_url('/connect/'); ?>" class="faq-cta-btn">تواصل معنا الآن</a>
        </div>
    </div>

    <script>
    function faqFilter(slug, btn) {
        document.querySelectorAll('.f-filter-btn').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.faq-item-card').forEach(card => {
            card.style.display = (slug === 'all' || card.classList.contains(slug)) ? 'block' : 'none';
        });
    }

    function faqSendVote(postId, type, btn) {
        const fd = new FormData();
        fd.append('action', 'faq_vote');
        fd.append('post_id', postId);
        fd.append('type', type);
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(() => { btn.parentElement.innerHTML = "✅ شكراً لتقييمك!"; });
    }
    </script>
    <?php
    return ob_get_clean();
});