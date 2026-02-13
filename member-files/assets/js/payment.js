document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.querySelector('.mf-elec-payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.mf-btn-pay');
            const originalText = btn.innerText;
            
            btn.innerText = 'جاري معالجة الدفع...';
            btn.disabled = true;
            
            // Simulate processing
            setTimeout(() => {
                alert('تمت معالجة الدفع بنجاح! شكراً لك.');
                btn.innerText = 'تم الدفع بنجاح';
                btn.style.background = '#27ae60';
                this.reset();
            }, 2000);
        });
    }
});
