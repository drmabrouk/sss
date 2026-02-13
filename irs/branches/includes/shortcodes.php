<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('Branches', 'branches_shortcode_handler');
add_shortcode('branches', 'branches_shortcode_handler');
add_shortcode('branchs', 'branches_shortcode_handler');

function branches_shortcode_handler() {
    ob_start();
    $terms = get_terms('branch_category');
    ?>
    <style>
        .union-wrapper { direction: rtl; margin: 2em 0; color: var(--ast-global-color-2); text-align: right; font-family: inherit; }
        .union-filter-nav { display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 12px; margin-bottom: 40px; padding: 10px; background: #fcfcfc; border-radius: 50px; border: 1px solid #eee; }
        .union-filter-nav button { background: transparent; border: none; color: #666; padding: 10px 25px; border-radius: 50px; cursor: pointer; transition: 0.3s; font-weight: 600; font-family: inherit; font-size: 0.9rem; }
        .union-filter-nav button:hover { background: #f0f0f0; color: var(--ast-global-color-0); }
        .union-filter-nav button.active { background: var(--ast-global-color-0); color: #fff; box-shadow: 0 4px 15px rgba(2, 116, 190, 0.3); }
        .branches-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
        @media (max-width: 920px) { .branches-grid { grid-template-columns: 1fr; } }
        .branch-card { background: #fff; border-radius: 20px; border: 1px solid #f0f0f0; display: flex; transition: 0.4s; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 30px; overflow: hidden; position: relative; }
        .branch-card::before { content: ""; position: absolute; top: 0; right: 0; width: 5px; height: 100%; background: var(--ast-global-color-0); opacity: 0; transition: 0.3s; }
        .branch-card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
        .branch-card:hover::before { opacity: 1; }
        .branch-logo-side { flex: 0 0 100px; margin-left: 25px; }
        .branch-logo-side img { width: 100px; height: 100px; border-radius: 18px; object-fit: cover; border: 1px solid #f5f5f5; }
        .branch-content { flex: 1; }
        .branch-content h3 { margin: 0 0 10px 0; color: var(--ast-global-color-0); font-size: 1.4rem; font-weight: 800; }
        .branch-content h3 a { color: inherit; text-decoration: none; }
        .branch-excerpt { font-size: 1rem; color: #777; margin-bottom: 20px; line-height: 1.6; }
        .management-team { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f9f9f9; padding: 15px; border-radius: 15px; margin-bottom: 20px; }
        .member-info { font-size: 0.9rem; font-weight: 700; color: #444; }
        .member-info span { display: block; font-size: 0.8rem; color: #999; margin-bottom: 4px; }
        .branch-footer { padding-top: 15px; font-size: 0.9rem; }
        .contact-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 12px; }
        .footer-item { display: flex; align-items: center; gap: 8px; color: #555; }
        @media (max-width: 500px) { .branch-card { flex-direction: column; align-items: center; text-align: center; } .branch-logo-side { margin-left: 0; margin-bottom: 15px; } }
    </style>
    <div class="union-wrapper">
        <div class="union-filter-nav">
            <button class="filter-btn active" data-filter="all">كافة الفروع</button>
            <?php foreach ($terms as $term) : ?>
                <button class="filter-btn" data-filter="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></button>
            <?php endforeach; ?>
        </div>
        <div class="branches-grid">
            <?php
            $query = new WP_Query(array('post_type' => 'branches', 'posts_per_page' => -1));
            while ($query->have_posts()) : $query->the_post();
                $termsArray = get_the_terms(get_the_ID(), 'branch_category');
                $slugs = $termsArray ? implode(' ', wp_list_pluck($termsArray, 'slug')) : '';
                $chairman = get_post_meta(get_the_ID(), '_branch_chairman', true);
                $secretary = get_post_meta(get_the_ID(), '_branch_secretary', true);
                $phone = get_post_meta(get_the_ID(), '_branch_phone', true);
                $email = get_post_meta(get_the_ID(), '_branch_email', true);
                $logo = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://via.placeholder.com/100';
            ?>
                <div class="branch-card" data-category="<?php echo esc_attr($slugs); ?>">
                    <div class="branch-logo-side"><a href="<?php the_permalink(); ?>"><img src="<?php echo esc_url($logo); ?>" alt="Logo"></a></div>
                    <div class="branch-content">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <div class="branch-excerpt"><?php echo wp_trim_words(get_the_content(), 15); ?></div>
                        <div class="management-team">
                            <div class="member-info"><span>رئيس الفرع</span> <?php echo esc_html($chairman ?: '---'); ?></div>
                            <div class="member-info"><span>أمين الفرع</span> <?php echo esc_html($secretary ?: '---'); ?></div>
                        </div>
                        <div class="branch-footer">
                            <div class="contact-row">
                                <?php if($email): ?><div class="footer-item">📧 <?php echo esc_html($email); ?></div><?php endif; ?>
                                <?php if($phone): ?><div class="footer-item">📞 <?php echo esc_html($phone); ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
    <script>
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const filter = button.getAttribute('data-filter');
            document.querySelectorAll('.branch-card').forEach(card => {
                card.style.display = (filter === 'all' || card.getAttribute('data-category').includes(filter)) ? 'flex' : 'none';
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
