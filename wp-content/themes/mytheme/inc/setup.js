const setup = document.querySelector('.mytheme-setup');
const pluginButtons = [...setup.querySelectorAll('[data-plugin]')];

function setBusy(busy) {
	pluginButtons.forEach(button => { button.disabled = busy; });
}

pluginButtons.forEach(button => {
	const row = button.closest('[data-state]');
	const status = row.querySelector('[role=status]');
	const slug = button.dataset.plugin;

	function failed(error) {
		status.textContent = error?.errorMessage || 'عملیات کامل نشد. دوباره تلاش کنید.';
		setBusy(false);
	}

	async function activate() {
		row.dataset.state = 'installed';
		button.textContent = 'فعال‌سازی';
		status.textContent = 'در حال فعال‌سازی…';
		try {
			await wp.ajax.post('activate-plugin', {
				_ajax_nonce: wp.updates.ajaxNonce, slug, plugin: slug + '/' + slug + '.php',
				name: row.querySelector('strong').textContent
			});
			row.dataset.state = 'active';
			status.textContent = 'فعال';
			button.hidden = true;
			if (!setup.querySelector('[data-state]:not([data-state="active"])')) {
				setup.querySelector('[data-locked]').hidden = true;
				setup.querySelector('[data-demos]').hidden = false;
			}
			setBusy(false);
		} catch (error) {
			failed(error);
		}
	}

	function installArgs() {
		return {slug, success: activate, error(error) {
			if (error?.errorCode === 'unable_to_connect_to_filesystem') {
				wp.updates.queue.unshift({action: 'install-plugin', data: installArgs()});
				wp.updates.filesystemCredentials.available = false;
				wp.updates.showErrorInCredentialsForm(error.errorMessage);
				wp.updates.requestFilesystemCredentials();
			} else {
				failed(error);
			}
		}};
	}

	button.onclick = event => {
		setBusy(true);
		if (row.dataset.state === 'installed') {
			activate();
		} else {
			status.textContent = 'در حال نصب…';
			wp.updates.maybeRequestFilesystemCredentials(event);
			wp.updates.installPlugin(installArgs());
		}
	};

	jQuery(document).on('credential-modal-cancel', (event, job) => {
		if (job?.data?.slug === slug) failed({errorMessage: 'نصب لغو شد.'});
	});
});

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
