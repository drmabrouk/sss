<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Files {

    public static $profile_fields = array(
        'name'             => 'الاسم بالكامل',
        'nid'              => 'رقم الهوية الوطنية',
        'dob'              => 'تاريخ الميلاد',
        'nationality'      => 'الجنسية',
        'member_no'        => 'رقم العضوية',
        'prof_level'       => 'المستوى المهني',
        'union_branch'     => 'فرع النقابة التابع له',
        'prof_special'     => 'التخصص المهني',
        'degree'           => 'الدرجة العلمية',
        'university'       => 'الجامعة',
        'college'          => 'الكلية',
        'department'       => 'القسم',
        'acad_special'     => 'التخصص الأكاديمي',
        'grad_year'        => 'تاريخ التخرج',
        'res_country'      => 'دولة الإقامة',
        'res_province'     => 'محافظة الإقامة',
        'res_city'         => 'مدينة الإقامة',
        'address'          => 'العنوان',
        'phone'            => 'رقم هاتف',
        'phone_alt'        => 'رقم هاتف آخر',
        'photo'            => 'صورة شخصية',
        'id_photo'         => 'صورة الهوية',
        'cv'               => 'السيرة الذاتية',
        'passport'         => 'جواز السفر',
        'member_exp'       => 'تاريخ انتهاء العضوية',
        'member_issue'     => 'تاريخ إصدار العضوية',
        'license_issue'    => 'تاريخ إصدار رخصة المزاولة',
        'license_exp'      => 'تاريخ انتهاء رخصة المزاولة',
        'facility_issue'   => 'تاريخ إصدار رخصة المنشأة',
        'facility_exp'     => 'تاريخ انتهاء رخصة المنشأة',
        'gender'           => 'الجنس',
        'bio'              => 'نبذة شخصية',
        'email'            => 'البريد الإلكتروني',
        'whatsapp'         => 'رقم الواتساب',
        'facebook'         => 'رابط الفيسبوك',
        'apply_license'    => 'طلب ترخيص مزاولة المهنة',
        'practiced_before' => 'هل مارست المهنة من قبل؟',
        'practice_years'   => 'عدد سنوات الممارسة',
        'work_location'    => 'مكان العمل',
        'published_research' => 'أبحاث منشورة',
        'academic_cert'    => 'شهادة المؤهل الأكاديمي',
        'work_authority'   => 'جهة العمل',
        'work_name'        => 'اسم مكان العمل',
        'postal_code'      => 'الرمز البريدي',
    );

    /**
     * Helper to save user data across all compatible keys
     */
    public static function update_user_data( $user_id, $data ) {
        // Core mappings from field keys to meta keys (including fallbacks for integration)
        $mapping = array(
            'nid'              => array( 'nid', '_services_national_id', 'national_id', '_mf_national_id', 'mf_national_id' ),
            'member_no'        => array( 'member_no', 'membership_number', 'membership_no', '_mf_member_num', 'mf_member_num' ),
            'name'             => array( 'name', 'full_name', 'mf_full_name' ),
            'email'            => array( 'email' ),
            'phone'            => array( 'phone', 'billing_phone' ),
            'address'          => array( 'address', 'billing_address_1', 'shipping_address' ),
            'degree'           => array( 'degree', 'qualification' ),
            'acad_special'     => array( 'acad_special', 'specialization', 'major' ),
            'grad_year'        => array( 'grad_year', 'graduation_year' ),
            'union_branch'     => array( 'union_branch', 'branch' ),
            'prof_level'       => array( 'prof_level', 'professional_level' ),
            'prof_special'     => array( 'prof_special', 'professional_specialization' ),
            'res_country'      => array( 'res_country', 'residence_country' ),
            'res_province'     => array( 'res_province', 'shipping_province' ),
            'res_city'         => array( 'res_city', 'shipping_city' ),
            'dob'              => array( 'dob', 'birth_date' ),
            'whatsapp'         => array( 'whatsapp' ),
            'facebook'         => array( 'facebook' ),
        );

        $services_last_data = array();

        foreach ( $data as $key => $value ) {
            // Save to primary and fallback keys
            if ( isset( $mapping[$key] ) ) {
                foreach ( $mapping[$key] as $meta_key ) {
                    update_user_meta( $user_id, $meta_key, $value );
                }
            } else {
                // Default save for keys not in mapping (like file URLs or other custom fields)
                update_user_meta( $user_id, $key, $value );
                update_user_meta( $user_id, 'mf_' . $key, $value );
            }

            // Populate services_last_data array with Arabic labels
            $label = isset( self::$profile_fields[$key] ) ? self::$profile_fields[$key] : $key;
            $services_last_data[$label] = $value;
        }

        // Special handling for National ID as the primary key
        if ( isset( $data['nid'] ) ) {
            update_user_meta( $user_id, '_services_national_id', $data['nid'] );
        }

        // Save the whole array for the Services plugin
        update_user_meta( $user_id, '_services_last_data', $services_last_data );
    }

	public function __construct() {
		$this->load_dependencies();
		$this->define_public_hooks();
		$this->define_admin_hooks();
	}

	private function load_dependencies() {
		require_once REGISTRATION_PATH . 'includes/class-logger.php';
		require_once REGISTRATION_PATH . 'includes/class-registration.php';
		require_once REGISTRATION_PATH . 'includes/class-login.php';
		require_once REGISTRATION_PATH . 'includes/class-profile.php';
		require_once REGISTRATION_PATH . 'includes/class-admin.php';
	}

	private function define_public_hooks() {
        add_action( 'send_headers', array( $this, 'add_no_cache_headers' ) );

        add_action( 'mf_cleanup_messages_event', array( $this, 'cleanup_old_messages' ) );
        if ( ! wp_next_scheduled( 'mf_cleanup_messages_event' ) ) {
            wp_schedule_event( time(), 'daily', 'mf_cleanup_messages_event' );
        }

		$registration = new Member_Files_Registration();
		add_shortcode( 'Registration', array( $registration, 'render_shortcode' ) );
        add_shortcode( 'login-page', array( $registration, 'render_login_page_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'init', array( $registration, 'handle_registration' ) );
        
        $login = new Member_Files_Login();
        add_action( 'init', array( $login, 'handle_login_request' ) );
        add_action( 'init', array( $login, 'handle_otp_verification' ) );

        $profile = new Member_Files_Profile();
        add_action( 'template_redirect', array( $profile, 'handle_profile_updates' ) );
        add_action( 'wp_ajax_mf_send_email_otp', array( $profile, 'ajax_send_email_otp' ) );

        add_filter( 'show_admin_bar', array( $this, 'handle_admin_bar_visibility' ) );
	}

    public function handle_admin_bar_visibility( $show ) {
        if ( current_user_can( 'union_member' ) && ! current_user_can( 'edit_posts' ) ) {
            return false;
        }
        return $show;
    }


	private function define_admin_hooks() {
		$admin = new Member_Files_Admin();
		add_action( 'admin_menu', array( $admin, 'add_plugin_admin_menu' ) );
        add_action( 'admin_init', array( $admin, 'handle_approval' ) );
        add_action( 'admin_init', array( $admin, 'handle_member_actions' ) );
	}

	public function enqueue_styles() {
        wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'registration-style', REGISTRATION_URL . 'assets/css/member-files.css', array(), REGISTRATION_VERSION );
	}

	public function run() {
		// Initialization logic if needed
	}

    public function add_no_cache_headers() {
        if ( ! is_admin() ) {
            $page_slug = get_post_field( 'post_name', get_queried_object_id() );
            if ( $page_slug === 'profile' || isset($_GET['action']) ) {
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
            }
        }
    }

    public function cleanup_old_messages() {
        $users = get_users( array(
            'meta_key' => 'mf_user_messages',
            'compare'  => 'EXISTS'
        ) );

        $thirty_days_ago = time() - ( 30 * DAY_IN_SECONDS );

        foreach ( $users as $user ) {
            $messages = get_user_meta( $user->ID, 'mf_user_messages', true );
            if ( ! is_array( $messages ) ) continue;

            $new_messages = array();
            $changed = false;

            foreach ( $messages as $msg ) {
                if ( isset( $msg['time'] ) && $msg['time'] > $thirty_days_ago ) {
                    $new_messages[] = $msg;
                } else {
                    $changed = true;
                }
            }

            if ( $changed ) {
                update_user_meta( $user->ID, 'mf_user_messages', $new_messages );
            }
        }
    }

	public static function activate() {
		// Activation logic: create necessary roles
        if ( ! get_role( 'pending_member' ) ) {
            add_role( 'pending_member', 'عضو معلق', array( 'read' => true ) );
        }
        
        // Use "Union Member" role
        if ( ! get_role( 'union_member' ) ) {
            add_role( 'union_member', 'عضو نقابة', array( 
                'read' => true,
                'upload_files' => true,
            ) );
        }

        // Add Syndicate Administrator Role (Direct request)
        if ( ! get_role( 'syndicate_admin' ) ) {
            add_role( 'syndicate_admin', 'مسؤول النقابة', array(
                'read' => true,
                'upload_files' => true,
                'edit_posts' => true,
                'manage_options' => true, // Enabled to allow access to management pages
            ) );
        }

        // Add Syndicate Member Role (Direct request)
        if ( ! get_role( 'syndicate_member' ) ) {
            add_role( 'syndicate_member', 'عضو نقابة (عامل)', array(
                'read' => true,
                'upload_files' => true,
            ) );
        }

        // Clean up old role if it exists (optional but cleaner)
        if ( get_role( 'approved_member' ) ) {
            remove_role( 'approved_member' );
        }

        // Auto-create Profile Page
        $page_title = 'الملف الشخصي';
        $page_slug  = 'profile';
        $page_content = '[Registration]';
        
        $page_check = get_page_by_path( $page_slug );
        if ( ! $page_check ) {
            wp_insert_post( array(
                'post_title'   => $page_title,
                'post_name'    => $page_slug,
                'post_content' => $page_content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ) );
        }
	}

	public static function deactivate() {
		// Deactivation logic
	}
}
