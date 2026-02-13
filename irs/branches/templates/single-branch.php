<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
while (have_posts()) : the_post();
    $post_id = get_the_ID();
    $chairman = get_post_meta($post_id, '_branch_chairman', true);
    $secretary = get_post_meta($post_id, '_branch_secretary', true);
    $address = get_post_meta($post_id, '_branch_address', true);
    $phone = get_post_meta($post_id, '_branch_phone', true);
    $email = get_post_meta($post_id, '_branch_email', true);
    $facebook = get_post_meta($post_id, '_branch_facebook', true);
    ?>
    <style>
        /* التنسيق الخاص بصفحة الفرع فقط لضمان عدم التأثير على باقي الموقع */
        .branch-layout-grid { display: grid; grid-template-columns: 1fr 350px; gap: 50px; padding: 60px 0; direction: rtl; }
        .main-info-column { text-align: right; }
        .branch-header-compact { margin-bottom: 40px; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 30px; }
        .branding-parallel-flex { display: flex; align-items: center; gap: 25px; }
        .logo-circle-premium { width: 110px; height: 110px; border-radius: 50%; overflow: hidden; border: 1px solid #eee; background: #fff; flex-shrink: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .logo-circle-premium img { width: 100%; height: 100%; object-fit: cover; }
        .placeholder-char { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f0f2f5; font-size: 2.5rem; font-weight: 800; color: var(--ast-global-color-0); }
        .category-capsule-gray { display: inline-block; background: #f0f2f5; color: #555; padding: 4px 14px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; margin-bottom: 10px; }
        .branch-title-refined { font-size: 2.2rem; font-weight: 800; margin: 0; color: var(--ast-global-color-2); line-height: 1.2; }
        .prose-block { margin-bottom: 45px; }
        .section-sub-heading { font-size: 1.2rem; font-weight: 700; margin-bottom: 25px; color: var(--ast-global-color-0); border-right: 4px solid var(--ast-global-color-0); padding-right: 15px; }
        .admin-grid-clean { display: flex; gap: 20px; flex-wrap: wrap; }
        .admin-box { background: #f9f9f9; padding: 20px 25px; border-radius: 12px; border: 1px solid #f0f0f0; flex: 1; min-width: 200px; }
        .admin-box .role { display: block; font-size: 0.85rem; color: #888; margin-bottom: 5px; font-weight: 600; }
        .admin-box .name { font-weight: 700; font-size: 1.1rem; color: #333; }
        .sidebar-info-column { position: sticky; top: 40px; align-self: start; }
        .contact-card-v4 { background: #fff; border: 1px solid #f0f0f0; padding: 35px; border-radius: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); }
        .sidebar-title { font-size: 1.2rem; margin-bottom: 25px; font-weight: 800; color: var(--ast-global-color-2); border-bottom: 2px solid #f9f9f9; padding-bottom: 15px; }
        .contact-rows { margin-bottom: 30px; }
        .c-row { display: flex; flex-direction: column; gap: 4px; margin-bottom: 18px; background: #fcfcfc; padding: 12px 18px; border-radius: 12px; }
        .c-label { font-size: 0.85rem; color: var(--ast-global-color-0); font-weight: 600; opacity: 0.8; }
        .c-value { font-weight: 700; font-size: 1rem; color: #444; }
        .facebook-button-minimal { display: flex; align-items: center; justify-content: center; gap: 12px; background: #1877f2; color: #fff !important; text-decoration: none; padding: 15px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; transition: 0.3s; box-shadow: 0 4px 12px rgba(24, 119, 242, 0.2); }
        .facebook-button-minimal:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(24, 119, 242, 0.3); }
        .fb-icon-box { background: #fff; color: #1877f2; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-weight: 900; }
        /* إخفاء نظام التنقل التلقائي في صفحة الفرع فقط */
        .single-branches .post-navigation, 
        .single-branches .nav-links, 
        .single-branches .ast-single-post-navigation { display: none !important; }
        
        @media (max-width: 921px) { .branch-layout-grid { grid-template-columns: 1fr; gap: 40px; } .branding-parallel-flex { flex-direction: column; text-align: center; } .logo-circle-premium { margin: 0 auto; } .sidebar-info-column { position: static; } }
    </style>

    <div class="ast-container">
        <div class="branch-layout-grid">
            <div class="main-info-column">
                <header class="branch-header-compact">
                    <div class="branding-parallel-flex">
                        <div class="logo-circle-premium">
                            <?php if(has_post_thumbnail()): the_post_thumbnail('medium'); else: ?>
                                <div class="placeholder-char"><?php echo mb_substr(get_the_title(), 0, 1); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="title-meta-group">
                            <?php $terms = get_the_terms($post_id, 'branch_category'); if ($terms) : ?>
                                <span class="category-capsule-gray"><?php echo esc_html($terms[0]->name); ?></span>
                            <?php endif; ?>
                            <h1 class="branch-title-refined"><?php the_title(); ?></h1>
                        </div>
                    </div>
                </header>

                <section class="prose-block">
                    <h3 class="section-sub-heading">حول الفرع</h3>
                    <div class="entry-content" style="background: #fff; padding: 35px; border-radius: 25px; border: 1px solid #f0f0f0; line-height: 1.8;">
                        <?php the_content(); ?>
                    </div>
                </section>

                <?php if ($chairman || $secretary) : ?>
                <section class="prose-block">
                    <h3 class="section-sub-heading">الهيكل الإداري</h3>
                    <div class="admin-grid-clean">
                        <?php if ($chairman) : ?>
                        <div class="admin-box"><span class="role">رئيس الفرع</span><span class="name"><?php echo esc_html($chairman); ?></span></div>
                        <?php endif; ?>
                        <?php if ($secretary) : ?>
                        <div class="admin-box"><span class="role">أمين الفرع</span><span class="name"><?php echo esc_html($secretary); ?></span></div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <aside class="sidebar-info-column">
                <div class="contact-card-v4">
                    <h4 class="sidebar-title">تواصل معنا</h4>
                    <div class="contact-rows">
                        <?php if($phone): ?><div class="c-row"><span class="c-label">الهاتف</span><span class="c-value" dir="ltr"><a href="tel:<?php echo esc_attr($phone); ?>" style="text-decoration:none; color:inherit;"><?php echo esc_html($phone); ?></a></span></div><?php endif; ?>
                        <?php if($email): ?><div class="c-row"><span class="c-label">البريد</span><span class="c-value"><a href="mailto:<?php echo esc_attr($email); ?>" style="text-decoration:none; color:inherit;"><?php echo esc_html($email); ?></a></span></div><?php endif; ?>
                        <?php if($address): ?><div class="c-row"><span class="c-label">الموقع</span><span class="c-value"><?php echo esc_html($address); ?></span></div><?php endif; ?>
                    </div>

                    <?php if($facebook): ?>
                        <a href="<?php echo esc_url($facebook); ?>" class="facebook-button-minimal" target="_blank">
                            <span class="fb-icon-box">f</span> تابعنا على فيسبوك
                        </a>
                    <?php endif; ?>

                    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #f9f9f9;">
                        <h4 style="font-size: 1.1rem; margin-bottom: 20px; font-weight: 800;">راسلنا مباشرة</h4>
                        <?php if (isset($_GET['sent']) && $_GET['sent'] == '1'): ?>
                            <div style="background: #e6fffa; color: #234e52; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; font-weight: 600;">تم الإرسال بنجاح!</div>
                        <?php elseif (isset($_GET['sent']) && $_GET['sent'] == '0'): ?>
                            <div style="background: #fff5f5; color: #822727; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; font-weight: 600;">حدث خطأ ما.</div>
                        <?php endif; ?>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="branch-contact">
                            <input type="hidden" name="action" value="branch_contact_form">
                            <input type="hidden" name="branch_id" value="<?php echo $post_id; ?>">
                            <?php wp_nonce_field('branch_contact_nonce', 'branch_nonce'); ?>
                            <input type="text" name="sender_name" placeholder="الاسم" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #eee; border-radius:8px;">
                            <input type="email" name="sender_email" placeholder="البريد" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #eee; border-radius:8px;">
                            <input type="text" name="subject" placeholder="الموضوع" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #eee; border-radius:8px;">
                            <textarea name="message" rows="3" placeholder="الرسالة" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #eee; border-radius:8px;"></textarea>
                            <button type="submit" style="width:100%; background:var(--ast-global-color-0); color:#fff; border:none; padding:12px; border-radius:8px; font-weight:700; cursor:pointer;">إرسال</button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    <?php
endwhile;
get_footer();
