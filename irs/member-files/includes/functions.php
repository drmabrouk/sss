<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all data associated with a National ID
 */
function mf_get_all_by_national_id($national_id) {
    if (empty($national_id)) return [];

    $results = [];

    // Get Member Record
    $member = get_posts([
        'post_type' => 'member_record',
        'meta_query' => [
            [
                'key' => '_mf_national_id',
                'value' => $national_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ]);
    if (!empty($member)) $results['member'] = $member[0];

    // Get Licenses
    $results['licenses'] = get_posts([
        'post_type' => 'member_license',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_mf_national_id',
                'value' => $national_id,
                'compare' => '='
            ]
        ]
    ]);

    // Get Institutions
    $results['institutions'] = get_posts([
        'post_type' => 'member_institution',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_mf_national_id',
                'value' => $national_id,
                'compare' => '='
            ]
        ]
    ]);

    // Get Payments
    $results['payments'] = get_posts([
        'post_type' => 'member_payment',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_mf_national_id',
                'value' => $national_id,
                'compare' => '='
            ]
        ]
    ]);

    return $results;
}

/**
 * Check if a date is expired
 */
function mf_is_expired($date) {
    if (empty($date)) return false;
    return strtotime($date) < time();
}

/**
 * Get pending payments for a National ID
 */
function mf_get_pending_payments($national_id, $type = '') {
    $meta_query = [
        'relation' => 'AND',
        [
            'key' => '_mf_national_id',
            'value' => $national_id,
            'compare' => '='
        ],
        [
            'key' => '_mf_payment_status',
            'value' => 'pending',
            'compare' => '='
        ]
    ];

    if ($type) {
        $meta_query[] = [
            'key' => '_mf_payment_type',
            'value' => $type,
            'compare' => '='
        ];
    }

    $payments = get_posts([
        'post_type' => 'member_payment',
        'posts_per_page' => -1,
        'meta_query' => $meta_query
    ]);

    $total = 0;
    foreach ($payments as $p) {
        $total += (float) get_post_meta($p->ID, '_mf_amount', true);
    }
    return $total;
}
