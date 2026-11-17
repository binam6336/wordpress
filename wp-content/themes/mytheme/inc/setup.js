document.querySelectorAll('[data-demo]').forEach(button => {
	const panel = button.closest('section');
	const bar = panel.querySelector('progress');
	const status = panel.querySelector('output');
	button.disabled = bar.value === 100;
	button.onclick = async () => {
		button.disabled = true;
		try {
			while (bar.value < 100) {
				const body = new URLSearchParams({action: 'mytheme_import', demo: button.dataset.demo, _ajax_nonce: mythemeSetup.nonce});
				const response = await fetch(ajaxurl, {method: 'POST', body});
				const result = await response.json();
				if (!result.success) throw new Error(result.data || 'خطا در نصب دمو');
				bar.value = result.data;
				status.textContent = bar.value + '%';
			}
			status.textContent = '۱۰۰٪ — دمو آماده است';
		} catch (error) {
			status.textContent = error.message;
			button.disabled = false;
		}
	};
});
