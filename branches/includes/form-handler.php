<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_post_branch_contact_form', 'branches_handle_contact_form');
add_action('admin_post_nopriv_branch_contact_form', 'branches_handle_contact_form');

function branches_handle_contact_form() {
    if (!isset($_POST['branch_nonce']) || !wp_verify_nonce($_POST['branch_nonce'], 'branch_contact_nonce')) {
        wp_die('Security check failed');
    }
    $branch_id = intval($_POST['branch_id']);
    $branch_email = get_post_meta($branch_id, '_branch_email', true);
    if (!$branch_email || !is_email($branch_email)) {
        wp_redirect(add_query_arg('sent', '0', get_permalink($branch_id)) . '#branch-contact');
        exit;
    }
    $name = sanitize_text_field($_POST['sender_name']);
    $email = sanitize_email($_POST['sender_email']);
    $subject = sanitize_text_field($_POST['subject']);
    $message = sanitize_textarea_field($_POST['message']);
    $to = $branch_email;
    $email_subject = "رسالة جديدة من: $name - $subject";
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    $body = "<h2>رسالة تواصل جديدة عبر موقع الفروع</h2><p><strong>من:</strong> $name ($email)</p><p><strong>الموضوع:</strong> $subject</p><p><strong>الرسالة:</strong></p><p>" . nl2br($message) . "</p>";
    $sent = wp_mail($to, $email_subject, $body, $headers);
    wp_redirect(add_query_arg('sent', $sent ? '1' : '0', get_permalink($branch_id)) . '#branch-contact');
    exit;
}
