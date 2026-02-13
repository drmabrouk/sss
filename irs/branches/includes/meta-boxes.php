<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('add_meta_boxes', function() {
    add_meta_box('branch_details', 'تفاصيل الفرع وبيانات التواصل', 'branches_details_callback', 'branches');
});

function branches_details_callback($post) {
    $chairman = get_post_meta($post->ID, '_branch_chairman', true);
    $secretary = get_post_meta($post->ID, '_branch_secretary', true);
    $address = get_post_meta($post->ID, '_branch_address', true);
    $phone = get_post_meta($post->ID, '_branch_phone', true);
    $email = get_post_meta($post->ID, '_branch_email', true);
    $facebook = get_post_meta($post->ID, '_branch_facebook', true);
    ?>
    <div style="padding: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; direction: rtl;">
        <p><label>👤 رئيس الفرع:</label><br><input type="text" name="branch_chairman" value="<?php echo esc_attr($chairman); ?>" style="width:100%" /></p>
        <p><label>✍️ أمين الفرع:</label><br><input type="text" name="branch_secretary" value="<?php echo esc_attr($secretary); ?>" style="width:100%" /></p>
        <p><label>📧 البريد الإلكتروني:</label><br><input type="email" name="branch_email" value="<?php echo esc_attr($email); ?>" style="width:100%" /></p>
        <p><label>📞 رقم الموبايل:</label><br><input type="text" name="branch_phone" value="<?php echo esc_attr($phone); ?>" style="width:100%" /></p>
        <p><label>📍 العنوان:</label><br><input type="text" name="branch_address" value="<?php echo esc_attr($address); ?>" style="width:100%" /></p>
        <p><label>🔵 رابط الفيسبوك:</label><br><input type="url" name="branch_facebook" value="<?php echo esc_attr($facebook); ?>" style="width:100%" placeholder="https://facebook.com/..." /></p>
    </div>
    <?php
}

add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = [
        'branch_chairman' => '_branch_chairman',
        'branch_secretary' => '_branch_secretary',
        'branch_address' => '_branch_address',
        'branch_phone' => '_branch_phone',
        'branch_email' => '_branch_email',
        'branch_facebook' => '_branch_facebook'
    ];
    foreach ($fields as $key => $meta_key) {
        if (isset($_POST[$key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$key]));
        }
    }
});
