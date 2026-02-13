<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * [Member] Shortcode
 */
function member_files_shortcode() {
    ob_start();
    ?>
    <div class="mf-wrapper">
        <div class="mf-search-box">
            <h2 class="mf-search-title">مركز الاستعلام الموحد</h2>
            <form method="get" class="mf-form">
                <input type="text" name="mf_q" placeholder="أدخل الرقم القومي، رقم العضوية، أو رقم الرخصة..." value="<?php echo isset($_GET['mf_q']) ? esc_attr($_GET['mf_q']) : ''; ?>" required>
                <button type="submit">بحث متقدم</button>
            </form>
        </div>

        <?php
        if (isset($_GET['mf_q'])) {
            $q = sanitize_text_field($_GET['mf_q']);
            member_files_display_results($q);
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('Member', 'member_files_shortcode');

/**
 * Display Search Results
 */
function member_files_display_results($q) {
    // 1. Try National ID (shows everything)
    $members_by_nid = get_posts([
        'post_type' => 'member_record',
        'meta_query' => [['key' => '_mf_national_id', 'value' => $q, 'compare' => '=']],
        'posts_per_page' => 1
    ]);

    if (!empty($members_by_nid)) {
        $nid = get_post_meta($members_by_nid[0]->ID, '_mf_national_id', true);
        member_files_render_all(mf_get_all_by_national_id($nid));
        return;
    }

    // 2. Try Membership Number (now also shows everything)
    $members_by_num = get_posts([
        'post_type' => 'member_record',
        'meta_query' => [['key' => '_mf_member_num', 'value' => $q, 'compare' => '=']],
        'posts_per_page' => 1
    ]);
    if (!empty($members_by_num)) {
        $nid = get_post_meta($members_by_num[0]->ID, '_mf_national_id', true);
        member_files_render_all(mf_get_all_by_national_id($nid));
        return;
    }

    // 3. Try License Number (Specific Search)
    $licenses = get_posts([
        'post_type' => 'member_license',
        'meta_query' => [['key' => '_mf_license_num', 'value' => $q, 'compare' => '=']],
        'posts_per_page' => 1
    ]);
    if (!empty($licenses)) {
        $nid = get_post_meta($licenses[0]->ID, '_mf_national_id', true);
        // If it's a license, maybe they want the full file too? 
        // User said: "Searching by license number shows only that license."
        // But then said "Entering the general National ID shows all cards... Associated with that person"
        // And "When entering a membership number, all member data should populate automatically."
        // I will stick to "only that license" for license number unless it's a member record.
        member_files_render_license_card($licenses[0]);
        return;
    }

    // 4. Try Institution Number
    $institutions = get_posts([
        'post_type' => 'member_institution',
        'meta_query' => [['key' => '_mf_inst_num', 'value' => $q, 'compare' => '=']],
        'posts_per_page' => 1
    ]);
    if (!empty($institutions)) {
        member_files_render_institution_card($institutions[0]);
        return;
    }

    echo '<div class="mf-no-results">عذراً، لم يتم العثور على بيانات مطابقة.</div>';
}

/**
 * Render all data (Full Member File View)
 */
function member_files_render_all($data) {
    if (empty($data)) return;

    $nid = isset($data['member']) ? get_post_meta($data['member']->ID, '_mf_national_id', true) : '';

    echo '<div class="mf-profile-container">';
    echo '<h2 class="mf-section-title">الملف الكامل للعضو</h2>';

    if (isset($data['member'])) {
        echo '<div class="mf-profile-header">';
        member_files_render_member_card($data['member']);
        echo '</div>';
    }
    
    if (!empty($data['licenses'])) {
        echo '<h3 class="mf-sub-title">التراخيص المهنية المسجلة</h3>';
        echo '<div class="mf-results-grid">';
        foreach ($data['licenses'] as $license) {
            member_files_render_license_card($license);
        }
        echo '</div>';
    }

    if (!empty($data['institutions'])) {
        echo '<h3 class="mf-sub-title">تراخيص المؤسسات والمراكز</h3>';
        echo '<div class="mf-results-grid">';
        foreach ($data['institutions'] as $inst) {
            member_files_render_institution_card($inst);
        }
        echo '</div>';
    }

    if (!empty($data['payments'])) {
        echo '<h3 class="mf-sub-title">السجل المالي والمستحقات</h3>';
        member_files_render_payments($data['payments'], $nid);
    }

    if (isset($data['member'])) {
        $notes = get_post_meta($data['member']->ID, '_mf_notes', true);
        if (!empty($notes)) {
            echo '<div class="mf-notes-box"><h3>ملاحظات وتنبيهات إضافية</h3><p>' . nl2br(esc_html($notes)) . '</p></div>';
        }
    }
    echo '</div>';
}

/**
 * Render Member Card
 */
function member_files_render_member_card($post) {
    $meta = get_post_custom($post->ID);
    $nid = $meta['_mf_national_id'][0] ?? '';
    $num = $meta['_mf_member_num'][0] ?? '';
    $sub = $meta['_mf_sub_syndicate'][0] ?? '';
    $exp = $meta['_mf_expiry_date'][0] ?? '';
    $phone = $meta['_mf_phone'][0] ?? '';
    $email = $meta['_mf_email'][0] ?? '';
    $qual = $meta['_mf_qualification'][0] ?? '';
    
    $photo = get_the_post_thumbnail_url($post->ID, 'medium') ?: 'https://via.placeholder.com/150?text=No+Photo';
    $is_expired = mf_is_expired($exp);
    $pending_amount = mf_get_pending_payments($nid, 'membership');

    ?>
    <div class="mf-card interactive-card member-card <?php echo $is_expired ? 'is-expired' : ''; ?>">
        <div class="card-badge">ملف عضو</div>
        <div class="card-inner">
            <div class="card-photo-wrap">
                <img src="<?php echo $photo; ?>" alt="Member Photo">
            </div>
            <div class="card-content">
                <h3 class="member-name"><?php echo get_the_title($post); ?></h3>
                <div class="info-grid">
                    <div class="info-item"><label>رقم العضوية:</label> <span><?php echo esc_html($num); ?></span></div>
                    <div class="info-item"><label>الرقم القومي:</label> <span><?php echo esc_html($nid); ?></span></div>
                    <div class="info-item"><label>النقابة الفرعية:</label> <span><?php echo esc_html($sub); ?></span></div>
                    <div class="info-item"><label>المؤهل:</label> <span><?php echo esc_html($qual); ?></span></div>
                </div>
                <div class="card-footer-info">
                    <span class="expiry-status">انتهاء العضوية: <strong><?php echo esc_html($exp); ?></strong></span>
                    <?php if($pending_amount > 0): ?>
                        <span class="pending-fee">مستحقات: <strong><?php echo $pending_amount; ?> ج.م</strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($is_expired || $pending_amount > 0): ?>
            <div class="card-action">
                <a href="#payment-form" class="mf-btn-action">تجديد العضوية والدفع</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render License Card
 */
function member_files_render_license_card($post) {
    $meta = get_post_custom($post->ID);
    $nid = $meta['_mf_national_id'][0] ?? '';
    $num = $meta['_mf_license_num'][0] ?? '';
    $reg = $meta['_mf_reg_num'][0] ?? '';
    $rank = $meta['_mf_prof_rank'][0] ?? '';
    $spec = $meta['_mf_prof_spec'][0] ?? '';
    $auth = $meta['_mf_issue_authority'][0] ?? '';
    $exp = $meta['_mf_expiry_date'][0] ?? '';
    
    $is_expired = mf_is_expired($exp);
    $pending_amount = mf_get_pending_payments($nid, 'license');

    ?>
    <div class="mf-card interactive-card license-card <?php echo $is_expired ? 'is-expired' : ''; ?>">
        <div class="card-badge green">ترخيص مهني</div>
        <div class="card-inner">
            <div class="card-content full-width">
                <h3 class="member-name"><?php echo get_the_title($post); ?></h3>
                <div class="info-grid">
                    <div class="info-item"><label>رقم الرخصة:</label> <span><?php echo esc_html($num); ?></span></div>
                    <div class="info-item"><label>رقم القيد:</label> <span><?php echo esc_html($reg); ?></span></div>
                    <div class="info-item"><label>الدرجة:</label> <span><?php echo esc_html($rank); ?></span></div>
                    <div class="info-item"><label>التخصص:</label> <span><?php echo esc_html($spec); ?></span></div>
                    <div class="info-item"><label>جهة الإصدار:</label> <span><?php echo esc_html($auth); ?></span></div>
                </div>
                <div class="card-footer-info">
                    <span class="expiry-status">تاريخ الانتهاء: <strong><?php echo esc_html($exp); ?></strong></span>
                    <?php if($pending_amount > 0): ?>
                        <span class="pending-fee">مستحقات: <strong><?php echo $pending_amount; ?> ج.م</strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($is_expired || $pending_amount > 0): ?>
            <div class="card-action">
                <a href="#payment-form" class="mf-btn-action green">تجديد الترخيص</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Institution Card
 */
function member_files_render_institution_card($post) {
    $meta = get_post_custom($post->ID);
    $nid = $meta['_mf_national_id'][0] ?? '';
    $num = $meta['_mf_inst_num'][0] ?? '';
    $comm = $meta['_mf_comm_reg'][0] ?? '';
    $tax = $meta['_mf_tax_id'][0] ?? '';
    $exp = $meta['_mf_expiry_date'][0] ?? '';
    
    $is_expired = mf_is_expired($exp);
    $pending_amount = mf_get_pending_payments($nid, 'institution');

    ?>
    <div class="mf-card interactive-card institution-card <?php echo $is_expired ? 'is-expired' : ''; ?>">
        <div class="card-badge orange">ترخيص مؤسسة</div>
        <div class="card-inner">
            <div class="card-content full-width">
                <h3 class="member-name"><?php echo get_the_title($post); ?></h3>
                <div class="info-grid">
                    <div class="info-item"><label>رقم المؤسسة:</label> <span><?php echo esc_html($num); ?></span></div>
                    <div class="info-item"><label>السجل التجاري:</label> <span><?php echo esc_html($comm); ?></span></div>
                    <div class="info-item"><label>الرقم الضريبي:</label> <span><?php echo esc_html($tax); ?></span></div>
                </div>
                <div class="card-footer-info">
                    <span class="expiry-status">تاريخ الانتهاء: <strong><?php echo esc_html($exp); ?></strong></span>
                    <?php if($pending_amount > 0): ?>
                        <span class="pending-fee">مستحقات: <strong><?php echo $pending_amount; ?> ج.م</strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($is_expired || $pending_amount > 0): ?>
            <div class="card-action">
                <a href="#payment-form" class="mf-btn-action orange">تجديد بيانات المؤسسة</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Payments and Electronic Form
 */
function member_files_render_payments($payments, $nid) {
    $total_pending = 0;
    foreach ($payments as $p) {
        if (get_post_meta($p->ID, '_mf_payment_status', true) === 'pending') {
            $total_pending += (float) get_post_meta($p->ID, '_mf_amount', true);
        }
    }

    if ($total_pending > 0): ?>
        <div id="payment-form" class="mf-payment-section">
            <div class="mf-payment-header">
                <h3>إجمالي المستحقات المالية: <?php echo $total_pending; ?> ج.م</h3>
                <p>يرجى استكمال البيانات التالية لإتمام عملية الدفع الإلكتروني</p>
            </div>
            <form class="mf-elec-payment-form">
                <div class="payment-grid">
                    <div class="form-group">
                        <label>الاسم بالكامل (كما في البطاقة)</label>
                        <input type="text" placeholder="الاسم الكامل" required>
                    </div>
                    <div class="form-group">
                        <label>رقم الهاتف</label>
                        <input type="tel" placeholder="01xxxxxxxxx" required>
                    </div>
                    <div class="form-group">
                        <label>رقم البطاقة البنكية</label>
                        <input type="text" placeholder="xxxx xxxx xxxx xxxx" required>
                    </div>
                    <div class="form-group-triple">
                        <div class="form-group">
                            <label>تاريخ الانتهاء</label>
                            <input type="text" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label>رمز CVV</label>
                            <input type="text" placeholder="xxx" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="mf-btn-pay">تأكيد الدفع الإلكتروني بقيمة <?php echo $total_pending; ?> ج.م</button>
            </form>
        </div>
    <?php endif;
}
