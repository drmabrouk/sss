<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Syndicate_Admin_Dashboard {

    public function render_dashboard() {
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        // Aggregate Stats
        $total_members = count( get_users( array( 'role__in' => array('union_member', 'syndicate_member') ) ) );
        $pending_members = count( get_users( array( 'role' => 'pending_member' ) ) );
        $service_requests = wp_count_posts( 'service_request' )->publish;

        ob_start();
        ?>
        <div class="sa-dashboard" dir="rtl">
            <aside class="sa-sidebar">
                <div class="sa-brand">
                    <span class="dashicons dashicons-performance"></span>
                    <h3>لوحة تحكم المسؤول</h3>
                </div>
                <nav class="sa-nav">
                    <a href="#sa-overview" class="active" data-tab="sa-overview"><span class="dashicons dashicons-dashboard"></span> نظرة عامة</a>
                    <a href="#sa-members" data-tab="sa-members"><span class="dashicons dashicons-admin-users"></span> إدارة الأعضاء <span class="sa-count"><?php echo $pending_members; ?></span></a>
                    <a href="#sa-services" data-tab="sa-services"><span class="dashicons dashicons-clipboard"></span> طلبات الخدمات</a>
                    <a href="#sa-branches" data-tab="sa-branches"><span class="dashicons dashicons-location"></span> الفروع</a>
                    <a href="#sa-faq" data-tab="sa-faq"><span class="dashicons dashicons-format-chat"></span> الأسئلة الشائعة</a>
                    <a href="<?php echo admin_url('admin.php?page=irs-admin-panel'); ?>" target="_blank"><span class="dashicons dashicons-admin-generic"></span> الإعدادات المتقدمة</a>
                    <a href="<?php echo wp_logout_url( get_permalink() ); ?>" class="sa-logout"><span class="dashicons dashicons-exit"></span> تسجيل الخروج</a>
                </nav>
            </aside>

            <main class="sa-main-content">
                <header class="sa-top-bar">
                    <div class="sa-welcome">مرحباً بك، <strong><?php echo esc_html($user->display_name); ?></strong></div>
                    <div class="sa-date"><?php echo date_i18n('l، j F Y'); ?></div>
                </header>

                <section id="sa-overview" class="sa-tab-panel active">
                    <div class="sa-stats-grid">
                        <div class="sa-stat-card primary">
                            <div class="card-icon"><span class="dashicons dashicons-groups"></span></div>
                            <div class="card-info">
                                <span>إجمالي الأعضاء</span>
                                <strong><?php echo $total_members; ?></strong>
                            </div>
                        </div>
                        <div class="sa-stat-card warning">
                            <div class="card-icon"><span class="dashicons dashicons-clock"></span></div>
                            <div class="card-info">
                                <span>طلبات معلقة</span>
                                <strong><?php echo $pending_members; ?></strong>
                            </div>
                        </div>
                        <div class="sa-stat-card info">
                            <div class="card-icon"><span class="dashicons dashicons-media-text"></span></div>
                            <div class="card-info">
                                <span>طلبات الخدمات</span>
                                <strong><?php echo $service_requests; ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="sa-recent-activity sa-card">
                        <h3><span class="dashicons dashicons-list-view"></span> آخر طلبات العضوية</h3>
                        <?php $this->render_pending_members_list(5); ?>
                    </div>
                </section>

                <section id="sa-members" class="sa-tab-panel">
                    <div class="sa-card">
                        <h3>إدارة كافة الأعضاء والطلبات</h3>
                        <?php $this->render_pending_members_list(); ?>
                    </div>
                </section>

                <section id="sa-services" class="sa-tab-panel">
                    <div class="sa-card">
                        <h3>مراقبة طلبات الخدمات</h3>
                        <?php $this->render_service_requests_list(); ?>
                    </div>
                </section>

                <section id="sa-branches" class="sa-tab-panel">
                    <div class="sa-card">
                        <h3>إدارة الفروع</h3>
                        <p>يمكنك إدارة الفروع بشكل كامل من خلال الرابط التالي:</p>
                        <a href="<?php echo admin_url('edit.php?post_type=branches'); ?>" class="sa-btn-primary" target="_blank">فتح إدارة الفروع</a>
                    </div>
                </section>

                <section id="sa-faq" class="sa-tab-panel">
                    <div class="sa-card">
                        <h3>إدارة الأسئلة الشائعة</h3>
                        <p>يمكنك إدارة الأسئلة والأقسام من خلال الرابط التالي:</p>
                        <a href="<?php echo admin_url('edit.php?post_type=faq'); ?>" class="sa-btn-primary" target="_blank">فتح إدارة FAQ</a>
                    </div>
                </section>
            </main>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.sa-nav a[data-tab]').on('click', function(e) {
                e.preventDefault();
                var tabId = $(this).data('tab');
                $('.sa-nav a').removeClass('active');
                $(this).addClass('active');
                $('.sa-tab-panel').removeClass('active');
                $('#' + tabId).addClass('active');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function render_pending_members_list($limit = -1) {
        $users = get_users( array( 'role' => 'pending_member', 'number' => $limit ) );
        ?>
        <table class="sa-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الرقم القومي</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)) : foreach($users as $u) :
                    $name = get_user_meta($u->ID, 'name', true);
                    $nid = get_user_meta($u->ID, 'nid', true);
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($name); ?></strong></td>
                        <td><?php echo esc_html($nid); ?></td>
                        <td><?php echo date('Y/m/d', strtotime($u->user_registered)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=member-requests&user_id=' . $u->ID); ?>" class="sa-btn-sm" target="_blank">مراجعة</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="4">لا توجد طلبات معلقة حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_service_requests_list() {
        $requests = get_posts( array( 'post_type' => 'service_request', 'posts_per_page' => 10 ) );
        ?>
        <table class="sa-table">
            <thead>
                <tr>
                    <th>كود الطلب</th>
                    <th>الخدمة</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)) : foreach($requests as $r) :
                    $status = get_post_meta($r->ID, '_request_status', true);
                    $type = get_post_meta($r->ID, '_service_type', true);
                ?>
                    <tr>
                        <td>#<?php echo esc_html($r->post_title); ?></td>
                        <td><?php echo esc_html($type); ?></td>
                        <td><span class="sa-badge <?php echo $status; ?>"><?php echo $status; ?></span></td>
                        <td><?php echo get_the_date('Y/m/d', $r->ID); ?></td>
                        <td>
                            <a href="<?php echo admin_url('post.php?post=' . $r->ID . '&action=edit'); ?>" class="sa-btn-sm" target="_blank">تعديل</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5">لا توجد طلبات خدمات حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}
