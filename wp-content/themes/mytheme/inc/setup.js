const setup = document.querySelector('.mytheme-setup');
const install = setup.querySelector('[data-install]');
if (install) {
	const rows = [...setup.querySelectorAll('[data-file]')];
	const next = setup.querySelector('[data-next]');
	const message = setup.querySelector('[data-message]');
	next.onclick = () => location.assign(next.dataset.next);

	function download(slug, event) {
		return new Promise((resolve, reject) => {
			const cancel = () => reject(new Error('نصب لغو شد.'));
			jQuery(document).one('credential-modal-cancel.mytheme', cancel);
			function request() {
				return {slug, success: resolve, error(error) {
					if (error?.errorCode === 'unable_to_connect_to_filesystem') {
						wp.updates.queue.unshift({action: 'install-plugin', data: request()});
						wp.updates.filesystemCredentials.available = false;
						wp.updates.showErrorInCredentialsForm(error.errorMessage);
						wp.updates.requestFilesystemCredentials();
					} else reject(error);
				}};
			}
			wp.updates.maybeRequestFilesystemCredentials(event);
			wp.updates.installPlugin(request());
		}).finally(() => jQuery(document).off('credential-modal-cancel.mytheme'));
	}

	async function run(event, skip) {
		install.disabled = true;
		try {
			for (const row of rows) {
				if (row.dataset.state === 'active') continue;
				const plugin = row.dataset.file;
				const slug = plugin.split('/')[0];
				const status = row.querySelector('[role=status]');
				if (row.dataset.state === 'missing') {
					status.textContent = 'در حال نصب…';
					await download(slug, event);
					row.dataset.state = 'installed';
				}
				status.textContent = 'در حال فعال‌سازی…';
				await wp.ajax.post('activate-plugin', {_ajax_nonce: wp.updates.ajaxNonce, slug, plugin, name: row.querySelector('strong').textContent});
				row.dataset.state = 'active';
				status.textContent = 'فعال';
			}
			message.textContent = 'افزونه‌های ضروری نصب و فعال هستند.';
			next.disabled = false;
			if (skip) next.click();
		} catch (error) {
			message.textContent = error?.errorMessage || error?.message || 'عملیات کامل نشد. دوباره تلاش کنید.';
			install.disabled = false;
		}
	}
	install.onclick = event => run(event, false);
	if (!next.disabled) message.textContent = 'افزونه‌های ضروری نصب و فعال هستند.';
	else if (rows.every(row => row.dataset.state !== 'missing')) run(null, true);
}

document.querySelectorAll('[data-demo]').forEach(button => {
	const panel = button.closest('section');
	const bar = panel.querySelector('progress');
	const status = panel.querySelector('output');
	setup.addEventListener('change', () => {
		button.disabled = !panel.querySelector('input').checked || bar.value === 100;
	});
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
			panel.querySelector('[data-visit]').hidden = false;
			status.textContent = '۱۰۰٪ — دمو آماده است';
		} catch (error) {
			status.textContent = error.message;
			button.disabled = false;
		}
	};
});
