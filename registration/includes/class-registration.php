<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Files_Registration {

    public function render_login_page_shortcode() {
        $profile_url = site_url('/profile/');
        ob_start();
        ?>
        <div class="mf-login-link-wrapper">
            <?php if ( is_user_logged_in() ) : ?>
                <?php 
                $user_id = get_current_user_id();
                $photo_id = get_user_meta( $user_id, 'mf_profile_photo', true );
                ?>
                <a href="<?php echo esc_url( $profile_url ); ?>" class="mf-header-account-link">
                    <?php if ( $photo_id ) : ?>
                        <?php echo wp_get_attachment_image( $photo_id, array(48, 48), false, array('class' => 'mf-header-avatar') ); ?>
                    <?php else : ?>
                        <span class="dashicons dashicons-admin-users" style="font-size: 24px; width: 24px; height: 24px;"></span>
                    <?php endif; ?>
                    <span>حسابي الشخصي</span>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( $profile_url ); ?>" class="mf-header-login-link">
                    <span class="dashicons dashicons-lock"></span>
                    <span>تسجيل الدخول / التسجيل</span>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

	public function render_shortcode() {
		if ( is_user_logged_in() ) {
			$profile = new Member_Files_Profile();
			return $profile->render_profile();
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'login' ) {
            $login = new Member_Files_Login();
            return $login->render_login_form();
        }

		return $this->render_registration_form();
	}

	public function render_registration_form() {
		ob_start();
		?>
		<div class="member-files-container registration-box-v3 mf-reg-container" dir="rtl">
			<h2 class="form-title">تسجيل عضوية جديدة</h2>
            
            <div class="mf-reg-steps-indicator">
                <div class="step-dot active" data-step="1"><span>1</span><label>الشخصية</label></div>
                <div class="step-dot" data-step="2"><span>2</span><label>الأكاديمية</label></div>
                <div class="step-dot" data-step="3"><span>3</span><label>المهنية</label></div>
                <div class="step-dot" data-step="4"><span>4</span><label>المرفقات</label></div>
                <div class="step-dot" data-step="5"><span>5</span><label>التواصل</label></div>
                <div class="step-dot" data-step="6"><span>6</span><label>التأكيد</label></div>
            </div>

            <div class="form-notice-box">
                بعد التسجيل، سيتم مراجعة طلبك من قبل النقابة. للأعضاء الجدد، يرجى متابعة خطوات إرسال المستندات الورقية بالبريد.
            </div>

			<?php if ( isset( $_GET['reg_success'] ) ) : ?>
				<div class="mf-registration-success-alert">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p>تم تقديم طلبك بنجاح. سيتم مراجعته من قبل الإدارة. يرجى متابعة بريدك الإلكتروني لأي تحديثات.</p>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="?action=login" class="button button-primary">الذهاب لصفحة تسجيل الدخول</a>
                </div>
			<?php else : ?>
            <?php if ( isset( $_GET['reg_error'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( urldecode( $_GET['reg_error'] ) ); ?></p></div>
			<?php endif; ?>
			<form action="" method="post" class="member-files-form registration-form-pro" enctype="multipart/form-data" id="mf-registration-multi-step">
				<?php wp_nonce_field( 'member_files_registration', 'mf_reg_nonce' ); ?>
				
                <!-- Step 1: Personal Info -->
                <div class="mf-reg-step active" id="step-1">
                    <div class="mf-reg-type-selector">
                        <label class="reg-type-btn">
                            <input type="radio" name="member_type" value="existing" checked onclick="toggleRegType('existing')">
                            <span class="btn-content">عضو مقيد</span>
                        </label>
                        <label class="reg-type-btn">
                            <input type="radio" name="member_type" value="new" onclick="toggleRegType('new')">
                            <span class="btn-content">عضو جديد</span>
                        </label>
                    </div>

                    <h3 class="reg-section-title">المعلومات الشخصية</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="الاسم بالكامل" required>
                        </div>
                        <div class="form-group">
                            <select name="gender">
                                <option value="">الجنس</option>
                                <option value="ذكر">ذكر</option>
                                <option value="أنثى">أنثى</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="nid" placeholder="رقم الهوية الوطنية" required pattern="[0-9]{14}">
                        </div>
                        <div class="form-group existing-member-only">
                            <input type="text" name="member_no" placeholder="رقم عضوية النقابة">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="nationality" placeholder="الجنسية" required>
                        </div>
                        <div class="form-group mf-inline-label">
                            <input type="date" name="dob" id="dob_input" required>
                            <label for="dob_input" class="inner-label">تاريخ الميلاد</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="mf-next-step" onclick="goToStep(2)">التالي: المعلومات الأكاديمية</button>
                    </div>
                </div>

                <!-- Step 2: Academic Info -->
                <div class="mf-reg-step" id="step-2">
                    <h3 class="reg-section-title">المعلومات الأكاديمية</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <select name="degree">
                                <option value="">الدرجة العلمية</option>
                                <option value="Undergraduate">طالب (Undergraduate)</option>
                                <option value="Bachelor">بكالوريوس (Bachelor)</option>
                                <option value="Master">ماجستير (Master)</option>
                                <option value="Doctorate">دكتوراه (Doctorate)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" name="university" placeholder="الجامعة">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="college" placeholder="الكلية">
                        </div>
                        <div class="form-group">
                            <input type="text" name="department" placeholder="القسم">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="acad_special" placeholder="التخصص الأكاديمي">
                        </div>
                        <div class="form-group mf-inline-label">
                            <input type="date" name="grad_year" id="grad_year_input">
                            <label for="grad_year_input" class="inner-label">تاريخ التخرج</label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="file-label">رفع شهادة المؤهل الأكاديمي (وجه وظهر في ملف PDF واحد)</label>
                            <input type="file" name="academic_cert" accept="application/pdf">
                        </div>
                    </div>

                    <div class="new-member-only mf-declaration-box" style="display:none;">
                        <label class="mf-checkbox-label">
                            <input type="checkbox" name="academic_confirm">
                            أؤكد أن شهادة المؤهل الأكاديمي المقدمة صحيحة ودقيقة، وأي بيانات غير صحيحة قد تعرضني للمساءلة القانونية.
                        </label>
                    </div>

                    <div class="form-actions multi-btns">
                        <button type="button" class="mf-prev-step" onclick="goToStep(1)">السابق</button>
                        <button type="button" class="mf-next-step" onclick="goToStep(3)">التالي: المعلومات المهنية</button>
                    </div>
                </div>

                <!-- Step 3: Professional Info -->
                <div class="mf-reg-step" id="step-3">
                    <h3 class="reg-section-title">المعلومات المهنية</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>هل ترغب في التقدم لطلب ترخيص مزاولة المهنة؟</label>
                            <select name="apply_license">
                                <option value="no">لا</option>
                                <option value="yes">نعم</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>هل مارست المهنة من قبل؟</label>
                            <select name="practiced_before" onchange="toggleYearsField(this.value)">
                                <option value="no">لا</option>
                                <option value="yes">نعم</option>
                            </select>
                        </div>
                        <div class="form-group" id="practice_years_wrapper" style="display:none;">
                            <label>منذ كم سنة؟</label>
                            <input type="number" name="practice_years" placeholder="عدد السنوات">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="work_authority" placeholder="جهة العمل">
                        </div>
                        <div class="form-group">
                            <input type="text" name="work_name" placeholder="اسم مكان العمل">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>مكان العمل</label>
                            <select name="work_location">
                                <option value="inside">داخل مصر</option>
                                <option value="outside">خارج مصر</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>أبحاث منشورة في مجلات علمية</label>
                            <select name="published_research">
                                <option value="no">لا</option>
                                <option value="yes">نعم</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions multi-btns">
                        <button type="button" class="mf-prev-step" onclick="goToStep(2)">السابق</button>
                        <button type="button" class="mf-next-step" onclick="goToStep(4)">التالي: رفع المرفقات</button>
                    </div>
                </div>

                <!-- Step 4: Attachments -->
                <div class="mf-reg-step" id="step-4">
                    <h3 class="reg-section-title">المرفقات والمستندات</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="file-label">الصورة الشخصية</label>
                            <input type="file" name="photo" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="file-label">صورة الهوية الوطنية</label>
                            <input type="file" name="id_photo" accept="image/*">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="file-label">السيرة الذاتية (CV)</label>
                            <input type="file" name="cv" accept=".pdf,.doc,.docx">
                        </div>
                        <div class="form-group">
                            <label class="file-label">صورة جواز السفر</label>
                            <input type="file" name="passport" accept="image/*,application/pdf">
                        </div>
                    </div>
                    <div class="form-row existing-member-only">
                        <div class="form-group">
                            <label class="file-label">صورة كارنيه النقابة</label>
                            <input type="file" name="membership_photo" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="file-label">صورة رخصة المزاولة (إن وجدت)</label>
                            <input type="file" name="license_photo" accept="image/*">
                        </div>
                    </div>

                    <div class="form-actions multi-btns">
                        <button type="button" class="mf-prev-step" onclick="goToStep(3)">السابق</button>
                        <button type="button" class="mf-next-step" onclick="goToStep(5)">التالي: معلومات التواصل</button>
                    </div>
                </div>

                <!-- Step 5: Contact Info -->
                <div class="mf-reg-step" id="step-5">
                    <h3 class="reg-section-title">معلومات التواصل والشحن</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="البريد الإلكتروني" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="رقم الهاتف" required pattern="[0-9]{11}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="res_country" placeholder="دولة الإقامة" value="مصر">
                        </div>
                        <div class="form-group">
                            <?php 
                            $govs_raw = get_option('mf_governorates_list');
                            if ($govs_raw) : 
                                $govs = explode("\n", str_replace("\r", "", $govs_raw));
                                ?>
                                <select name="res_province">
                                    <option value="">اختر المحافظة</option>
                                    <?php foreach ($govs as $g) : if(trim($g)) : ?>
                                        <option value="<?php echo esc_attr(trim($g)); ?>"><?php echo esc_html(trim($g)); ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            <?php else : ?>
                                <input type="text" name="res_province" placeholder="محافظة الإقامة">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="res_city" placeholder="مدينة الإقامة">
                        </div>
                        <div class="form-group">
                            <input type="text" name="postal_code" placeholder="الرمز البريدي">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <textarea name="address" placeholder="يرجى إدخال العنوان بالكامل وبالتفصيل" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group mf-whatsapp-field">
                            <input type="tel" name="whatsapp" placeholder="رقم الواتساب (اختياري)">
                        </div>
                        <div class="form-group mf-facebook-field">
                            <input type="url" name="facebook" placeholder="رابط الملف الشخصي على فيسبوك (اختياري)">
                        </div>
                    </div>

                    <div class="form-actions multi-btns">
                        <button type="button" class="mf-prev-step" onclick="goToStep(4)">السابق</button>
                        <button type="button" class="mf-next-step" onclick="goToStep(6)">التالي: التأكيد</button>
                    </div>
                </div>

                <!-- Step 6: Final Confirmation -->
                <div class="mf-reg-step" id="step-6">
                    <h3 class="reg-section-title">الشروط والأحكام والإقرارات القانونية</h3>
                    <div class="mf-terms-container">
                        <p>1. أقر بأن جميع البيانات والمستندات المقدمة صحيحة وتحت مسؤوليتي الشخصية الكاملة.</p>
                        <p>2. أقر بأنني أتحمل كافة التبعات القانونية في حال ثبوت عدم صحة أي معلومة أو مستند مقدم من قبلي للنقابة عبر هذا النظام.</p>
                        <p>3. أوافق على كافة القوانين واللوائح المنظمة للعمل النقابي وسياسات العضوية الخاصة بالنقابة.</p>
                        <p>4. أتعهد بتحديث بياناتي في حال طرأ عليها أي تغيير، وبإخطار النقابة بذلك فوراً.</p>
                        <p>5. أوافق على معالجة واستخدام بياناتي للأغراض الإدارية والخدمية داخل النقابة.</p>
                        <p style="color: #e74c3c; font-weight: 800; border: 2px solid #e74c3c; padding: 15px; border-radius: 10px; margin-top: 20px;">
                            تنبيه هام جداً: تقديم أي بيانات خاطئة أو مستندات مزورة سيؤدي فوراً إلى رفض الطلب، وإيقاف القيد بالنقابة نهائياً، واتخاذ كافة الإجراءات القانونية اللازمة ضدي. يجب التأكد من دقة وصحة كافة البيانات قبل الإرسال.
                        </p>
                    </div>
                    <div class="mf-confirmation-checkbox">
                        <label class="mf-checkbox-label">
                            <input type="checkbox" name="final_confirm" required>
                            أوافق على كافة الشروط والأحكام المذكورة أعلاه وأقر بمسؤوليتي عن صحة البيانات.
                        </label>
                    </div>

                    <div class="form-actions multi-btns">
                        <button type="button" class="mf-prev-step" onclick="goToStep(5)">السابق</button>
                        <button type="submit" name="mf_register_submit" class="mf-submit-btn">إرسال طلب الانضمام</button>
                    </div>
                </div>

                <script>
                let currentStep = 1;

                function toggleRegType(type) {
                    const existingFields = document.querySelectorAll('.existing-member-only');
                    const newFields = document.querySelectorAll('.new-member-only');

                    if (type === 'new') {
                        existingFields.forEach(f => f.style.display = 'none');
                        newFields.forEach(f => f.style.display = 'block');
                    } else {
                        existingFields.forEach(f => f.style.display = 'block');
                        newFields.forEach(f => f.style.display = 'none');
                    }
                }

                function toggleYearsField(val) {
                    const wrapper = document.getElementById('practice_years_wrapper');
                    wrapper.style.display = (val === 'yes') ? 'block' : 'none';
                }

                function goToStep(step) {
                    // Validation logic when moving forward
                    if (step > currentStep) {
                        const currentStepEl = document.getElementById('step-' + currentStep);
                        const inputs = currentStepEl.querySelectorAll('input, select, textarea');
                        let allValid = true;
                        
                        for (let input of inputs) {
                            // Check validity for required fields that are currently visible
                            if (input.hasAttribute('required') && input.offsetParent !== null) {
                                if (!input.checkValidity()) {
                                    input.reportValidity();
                                    allValid = false;
                                    return; // Stop and show browser bubble
                                }
                            }
                        }
                    }

                    // Proceed to next step if valid or moving backward
                    document.querySelectorAll('.mf-reg-step').forEach(s => s.classList.remove('active'));
                    const targetStep = document.getElementById('step-' + step);
                    if (targetStep) {
                        targetStep.classList.add('active');
                        
                        document.querySelectorAll('.step-dot').forEach(dot => {
                            const dotStep = parseInt(dot.dataset.step);
                            if (dotStep <= step) {
                                dot.classList.add('active');
                            } else {
                                dot.classList.remove('active');
                            }
                        });

                        currentStep = step;
                        document.querySelector('.mf-reg-container').scrollIntoView({ behavior: 'smooth' });
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    toggleRegType('existing');

                    const form = document.getElementById('mf-registration-multi-step');
                    if (form) {
                        form.onsubmit = function(e) {
                            const steps = document.querySelectorAll('.mf-reg-step');
                            for (let i = 0; i < steps.length; i++) {
                                const inputs = steps[i].querySelectorAll('input, select, textarea');
                                for (let input of inputs) {
                                    if (input.hasAttribute('required') && input.offsetParent !== null) {
                                        if (!input.checkValidity()) {
                                            // Jump to the step with error
                                            goToStep(i + 1);
                                            setTimeout(() => input.reportValidity(), 100);
                                            return false;
                                        }
                                    }
                                }
                            }
                            // Final check for final_confirm which is always in step 6
                            const finalConfirm = form.querySelector('input[name="final_confirm"]');
                            if (finalConfirm && !finalConfirm.checked) {
                                goToStep(6);
                                finalConfirm.reportValidity();
                                return false;
                            }
                            return true;
                        };
                    }
                });
                </script>
                <div class="form-links">
                    <a href="?action=login">لديك حساب بالفعل؟ تسجيل الدخول</a>
                </div>
			</form>
            <?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_registration() {
		if ( ! isset( $_POST['mf_register_submit'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['mf_reg_nonce'], 'member_files_registration' ) ) {
			return;
		}

        $data = array();
        foreach ( Member_Files::$profile_fields as $key => $label ) {
            if ( isset($_POST[$key]) ) {
                $data[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        
        // Include member_type for identification
        if ( isset($_POST['member_type']) ) {
            $data['member_type'] = sanitize_text_field($_POST['member_type']);
        }

		$name        = isset($data['name']) ? $data['name'] : '';
		$email       = isset($data['email']) ? $data['email'] : '';
		$nid         = isset($data['nid']) ? $data['nid'] : '';

		// Validation
		if ( empty( $name ) || empty( $email ) || empty( $nid ) ) {
			wp_redirect( add_query_arg( 'reg_error', urlencode( 'الاسم والبريد والرقم القومي حقول مطلوبة.' ), wp_get_referer() ) );
			exit;
		}

        if ( ! preg_match( '/^[0-9]{14}$/', $nid ) ) {
            wp_redirect( add_query_arg( 'reg_error', urlencode( 'الرقم القومي يجب أن يتكون من 14 رقم.' ), wp_get_referer() ) );
			exit;
        }

		if ( username_exists( $nid ) || email_exists( $email ) ) {
			wp_redirect( add_query_arg( 'reg_error', urlencode( 'المستخدم مسجل بالفعل.' ), wp_get_referer() ) );
			exit;
		}

		// Create user with pending_member role
		$user_id = wp_insert_user( array(
			'user_login' => $nid,
			'user_email' => $email,
			'display_name' => $name,
			'user_pass'  => wp_generate_password(),
			'role'       => 'pending_member',
		) );

		if ( is_wp_error( $user_id ) ) {
			wp_redirect( add_query_arg( 'reg_error', urlencode( $user_id->get_error_message() ), wp_get_referer() ) );
			exit;
		}

        // Handle File Uploads during registration
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $file_fields = array('photo', 'id_photo', 'cv', 'passport', 'academic_cert', 'membership_photo', 'license_photo');
        foreach ($file_fields as $ff) {
            if ( ! empty( $_FILES[$ff]['name'] ) ) {
                $attach_id = media_handle_upload( $ff, 0 );
                if ( ! is_wp_error( $attach_id ) ) {
                    if ($ff === 'photo') update_user_meta( $user_id, 'mf_profile_photo', $attach_id );
                    $data[$ff] = wp_get_attachment_url($attach_id);
                }
            }
        }

        // Store info using standardized helper
        Member_Files::update_user_data( $user_id, $data );

		// Notify admin
		$admin_email = get_option( 'admin_email' );
		$subject = 'طلب تسجيل عضوية جديد: ' . $name;
		$message = "تم استلام طلب تسجيل جديد.\n\nالاسم: $name\nالرقم القومي: $nid\nالبريد الإلكتروني: $email\n\nيرجى مراجعة لوحة التحكم للموافقة على الطلب.";
		wp_mail( $admin_email, $subject, $message );

		wp_redirect( add_query_arg( 'reg_success', '1', wp_get_referer() ) );
		exit;
	}
}
