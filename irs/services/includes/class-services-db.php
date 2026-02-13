<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Services_DB {

	public function __construct() {
		if ( did_action( 'init' ) ) {
			$this->register_post_type();
		} else {
			add_action( 'init', array( $this, 'register_post_type' ) );
		}
	}

	public function create_tables() {
		// If we were using custom tables, we would do it here.
		// For now, we use CPT, so nothing to do on activation specifically for schema
		// unless we want to flush rewrite rules.
		$this->register_post_type();
		flush_rewrite_rules();
	}

	public function register_post_type() {
		// Service Categories Taxonomy
		register_taxonomy( 'service_category', 'service_item', array(
			'labels' => array(
				'name'              => 'تصنيفات الخدمات',
				'singular_name'     => 'التصنيف',
				'search_items'      => 'البحث في التصنيفات',
				'all_items'         => 'كل التصنيفات',
				'edit_item'         => 'تعديل التصنيف',
				'update_item'       => 'تحديث التصنيف',
				'add_new_item'      => 'إضافة تصنيف جديد',
				'new_item_name'     => 'اسم التصنيف الجديد',
				'menu_name'         => 'تصنيفات الخدمات',
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'service-category' ),
			'show_in_menu'      => 'services-management',
		) );

		// Requests CPT
		register_post_type( 'service_request', array(
			'labels'              => array(
				'name'               => 'الطلبات',
				'singular_name'      => 'الطلب',
				'menu_name'          => 'طلبات الخدمات',
				'add_new'            => 'أضف جديد',
				'add_new_item'       => 'أضف طلب جديد',
				'edit_item'          => 'تعديل الطلب',
				'new_item'           => 'طلب جديد',
				'view_item'          => 'عرض الطلب',
				'search_items'       => 'البحث عن طلبات',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'services-management',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'menu_icon'           => 'dashicons-clipboard',
		) );

		// Services CPT
		register_post_type( 'service_item', array(
			'labels'              => array(
				'name'               => 'الخدمات المتاحة',
				'singular_name'      => 'الخدمة',
				'menu_name'          => 'إدارة الخدمات',
				'add_new'            => 'أضف خدمة',
				'add_new_item'       => 'أضف خدمة جديدة',
				'edit_item'          => 'تعديل الخدمة',
				'new_item'           => 'خدمة جديدة',
				'view_item'          => 'عرض الخدمة',
				'search_items'       => 'البحث عن خدمات',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'services-management',
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-admin-tools',
		) );

		// Branches CPT
		register_post_type( 'service_branch', array(
			'labels'              => array(
				'name'               => 'الفروع',
				'singular_name'      => 'الفرع',
				'menu_name'          => 'إدارة الفروع',
				'add_new'            => 'أضف فرع',
				'add_new_item'       => 'أضف فرع جديد',
				'edit_item'          => 'تعديل الفرع',
				'new_item'           => 'فرع جديد',
				'view_item'          => 'عرض الفرع',
				'search_items'       => 'البحث عن فروع',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'services-management',
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-location',
		) );
	}

	public static function get_statuses() {
		return array(
			'pending'    => 'قيد الانتظار',
			'processing' => 'جاري المعالجة',
			'completed'  => 'تم الانتهاء',
			'cancelled'  => 'ملغي',
		);
	}

	public static function get_branches() {
		$branches = get_posts( array(
			'post_type'      => 'service_branch',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		return wp_list_pluck( $branches, 'post_title' );
	}

	public static function get_services() {
		$services_posts = get_posts( array(
			'post_type'      => 'service_item',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_service_hidden',
					'value'   => '1',
					'compare' => '!=',
				),
				array(
					'key'     => '_service_hidden',
					'compare' => 'NOT EXISTS',
				),
			),
		) );

		$services = array();
		foreach ( $services_posts as $post ) {
			$categories = wp_get_post_terms( $post->ID, 'service_category', array( 'fields' => 'slugs' ) );
			$services[ $post->ID ] = array(
				'title'      => $post->post_title,
				'icon'       => get_post_meta( $post->ID, '_service_icon', true ),
				'payment'    => get_post_meta( $post->ID, '_payment_info', true ),
				'terms'      => get_post_meta( $post->ID, '_service_terms', true ),
				'fields'     => get_post_meta( $post->ID, '_service_fields', true ) ?: array(),
				'enable_complaint_title'   => get_post_meta( $post->ID, '_enable_complaint_title', true ),
				'enable_complaint_details' => get_post_meta( $post->ID, '_enable_complaint_details', true ),
				'enable_notes'             => get_post_meta( $post->ID, '_enable_notes', true ),
				'categories' => $categories,
			);
		}

		// Fallback for first run if no services exist
		if ( empty( $services ) && !get_option('services_setup_done') ) {
			self::install_default_data();
			return self::get_services();
		}

		return $services;
	}

	public static function get_available_fields() {
		return array(
			'name'           => 'الاسم الكامل',
			'nid'            => 'رقم الهوية الوطنية',
			'dob'            => 'تاريخ الميلاد',
			'nationality'    => 'الجنسية',
			'member_no'      => 'رقم العضوية',
			'prof_level'     => 'المستوى المهني',
			'union_branch'   => 'فرع النقابة التابع له',
			'prof_special'   => 'التخصص المهني',
			'degree'         => 'الدرجة العلمية',
			'university'     => 'الجامعة',
			'college'        => 'الكلية',
			'department'     => 'القسم',
			'acad_special'   => 'التخصص الأكاديمي',
			'grad_year'      => 'تاريخ التخرج',
			'res_country'    => 'دولة الإقامة',
			'res_province'   => 'محافظة الإقامة',
			'res_city'       => 'مدينة الإقامة',
			'address'        => 'العنوان',
			'phone'          => 'رقم الهاتف',
			'phone_alt'      => 'رقم هاتف آخر',
			'photo'          => 'صورة شخصية',
			'id_photo'       => 'صورة الهوية',
			'cv'             => 'السيرة الذاتية',
			'passport'       => 'جواز السفر',
			'member_exp'     => 'تاريخ انتهاء العضوية',
			'member_issue'   => 'تاريخ إصدار العضوية',
			'license_issue'  => 'تاريخ إصدار رخصة المزاولة',
			'license_exp'    => 'تاريخ انتهاء رخصة المزاولة',
			'facility_issue' => 'تاريخ إصدار رخصة المنشأة',
			'facility_exp'   => 'تاريخ انتهاء رخصة المنشأة',
			'gender'         => 'الجنس',
			'bio'            => 'نبذة شخصية',
			'email'          => 'البريد الإلكتروني',
		);
	}

	public static function get_stats( $period = 'all' ) {
		$args = array(
			'post_type'      => 'service_request',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		if ( $period === 'today' ) {
			$args['date_query'] = array(
				array(
					'after'     => 'today',
					'inclusive' => true,
				),
			);
		} elseif ( $period === 'week' ) {
			$args['date_query'] = array(
				array(
					'after'     => '1 week ago',
					'inclusive' => true,
				),
			);
		} elseif ( $period === 'month' ) {
			$args['date_query'] = array(
				array(
					'after'     => '1 month ago',
					'inclusive' => true,
				),
			);
		}

		$query = new WP_Query( $args );
		$requests = $query->posts;

		$stats = array(
			'total'      => count( $requests ),
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'cancelled'  => 0,
			'by_service' => array(),
		);

		foreach ( $requests as $req ) {
			$status = get_post_meta( $req->ID, '_request_status', true );
			if ( ! $status ) $status = 'pending';
			if ( isset( $stats[ $status ] ) ) {
				$stats[ $status ]++;
			}

			$service_id = get_post_meta( $req->ID, '_service_type', true );
			if ( $service_id ) {
				$service_title = is_numeric( $service_id ) ? get_the_title( $service_id ) : $service_id;
				if ( ! isset( $stats['by_service'][ $service_title ] ) ) {
					$stats['by_service'][ $service_title ] = 0;
				}
				$stats['by_service'][ $service_title ]++;
			}
		}

		return $stats;
	}

	private static function install_default_data() {
		// Create default categories
		$categories = array(
			'عضوية' => 'Membership',
			'تراخيص' => 'Licenses',
			'تسجيل' => 'Registration',
		);
		$cat_ids = array();
		foreach ( $categories as $name => $slug ) {
			$term = wp_insert_term( $name, 'service_category', array( 'slug' => strtolower($slug) ) );
			if ( ! is_wp_error( $term ) ) {
				$cat_ids[strtolower($slug)] = $term['term_id'];
			}
		}

		$defaults = array(
			array('title' => 'طلب إصدار رخصة جديدة', 'icon' => 'dashicons-media-spreadsheet'),
			array('title' => 'طلب إصدار عضوية جديدة', 'icon' => 'dashicons-id-alt'),
			array('title' => 'طلب إعادة إصدار رخصة فقط', 'icon' => 'dashicons-update'),
			array('title' => 'طلب بدل فاقد بطاقة عضوية', 'icon' => 'dashicons-warning'),
			array('title' => 'إصدار شهادة خبرة', 'icon' => 'dashicons-welcome-learn-more'),
		);

		foreach ($defaults as $d) {
			$pid = wp_insert_post(array(
				'post_title' => $d['title'],
				'post_type' => 'service_item',
				'post_status' => 'publish'
			));
			update_post_meta($pid, '_service_icon', $d['icon']);
			update_post_meta($pid, '_service_fields', array('name', 'nid', 'email', 'phone', 'member_no', 'union_branch'));
			
			// Assign category
			if (strpos($d['title'], 'عضوية') !== false) {
				wp_set_post_terms($pid, array($cat_ids['membership']), 'service_category');
			} elseif (strpos($d['title'], 'رخصة') !== false) {
				wp_set_post_terms($pid, array($cat_ids['licenses']), 'service_category');
			} else {
				wp_set_post_terms($pid, array($cat_ids['registration']), 'service_category');
			}
		}

		$branches = array('فرع القاهرة', 'فرع الإسكندرية', 'فرع الجيزة');
		foreach ($branches as $b) {
			wp_insert_post(array(
				'post_title' => $b,
				'post_type' => 'service_branch',
				'post_status' => 'publish'
			));
		}

		update_option('services_setup_done', 1);
	}
}
