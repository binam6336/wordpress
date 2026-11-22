const setup = document.querySelector('.mytheme-setup');
const install = setup.querySelector('[data-install]');
if (install) {
	const rows = [...setup.querySelectorAll('[data-file]')];
	const next = setup.querySelector('[data-next]');
	const retryAll = setup.querySelector('[data-retry-all]');
	const message = setup.querySelector('[data-message]');
	const nonce = mythemeSetup.installerNonce;
	const errors = {
		network: 'ارتباط با سرور برقرار نشد. اتصال اینترنت را بررسی کنید.',
		download: 'وردپرس نتوانست اطلاعات یا بستهٔ افزونه را از مخزن دریافت کند.',
		filesystem: 'نوشتن فایل‌های افزونه ممکن نشد. دسترسی پوشه‌ها و اطلاعات اتصال هاست را بررسی کنید.',
		activate: 'وردپرس نتوانست افزونه را فعال کند. جزئیات در گزارش خطای سرور ثبت شده است.',
		install: 'وردپرس نتوانست نصب بستهٔ افزونه را تکمیل کند.',
		dependency: 'ابتدا افزونهٔ پیش‌نیاز این مورد را فعال کنید.',
		incompatible: 'نسخهٔ PHP یا وردپرس با این افزونه سازگار نیست.',
		permission: 'مجوز انجام این عملیات را ندارید.',
		session: 'اعتبار درخواست تمام شده است. صفحه را تازه‌سازی کنید.',
		busy: 'یک عملیات نصب دیگر در حال اجراست. کمی بعد تلاش کنید.',
		cancel: 'ورود اطلاعات اتصال به فایل‌ها لغو شد.',
		server: 'پاسخ معتبر از وردپرس دریافت نشد. وضعیت سرور را بررسی کنید.'
	};
	let running = false;
	next.onclick = () => location.assign(next.dataset.next);

	function reason(error) {
		const code = error?.reason || error?.responseJSON?.data?.reason;
		if (Object.hasOwn(errors, code)) return code;
		if (error?.status === 0 || !navigator.onLine) return 'network';
		if (error?.status === 403 || error === -1) return 'session';
		return 'server';
	}
	function request(file, operation) {
		return wp.ajax.post('mytheme_dependency', {installerNonce:nonce, plugin:file, operation});
	}
	function download(file, event) {
		return new Promise((resolve, reject) => {
			jQuery(document).one('credential-modal-cancel.mytheme', () => reject({reason:'cancel'}));
			wp.updates.maybeRequestFilesystemCredentials(event);
			wp.updates.installPlugin({slug:file.split('/')[0], plugin:file, installerNonce:nonce, success:resolve, error:reject});
		}).finally(() => jQuery(document).off('credential-modal-cancel.mytheme'));
	}
	function storageKey(row) {
		return ['mytheme-install', ajaxurl, mythemeSetup.user, row.dataset.file].join('|');
	}
	function feedback(row, phase, code) {
		row.dataset.failure = code ? phase : '';
		const detail = row.querySelector('.mytheme-failure');
		detail.hidden = !code;
		detail.querySelector('[role=alert]').textContent = code ? row.querySelector('strong').textContent + ' — ' + errors[code] : '';
		try {
			if (code) sessionStorage.setItem(storageKey(row), JSON.stringify({phase,code}));
			else sessionStorage.removeItem(storageKey(row));
		} catch {}
		if (code) row.querySelector('[role=status]').textContent = phase === 'install' ? 'نصب ناموفق' : 'فعال‌سازی ناموفق';
	}
	function controls() {
		const failed = rows.filter(row => row.dataset.failure);
		const done = rows.every(row => row.dataset.state === 'active' && !row.dataset.failure);
		retryAll.hidden = failed.length < 2;
		retryAll.disabled = running;
		next.disabled = running || !done;
		install.disabled = running || rows.every(row => row.dataset.failure || row.dataset.state === 'active');
		for (const row of rows) row.querySelector('[data-retry]').disabled = running;
		message.textContent = done ? 'افزونه‌های ضروری نصب و فعال هستند.' : '';
	}
	async function processRow(row, event) {
		const file = row.dataset.file;
		const status = row.querySelector('[role=status]');
		let phase = row.dataset.state === 'missing' ? 'install' : 'activate';
		feedback(row);
		row.setAttribute('aria-busy','true');
		try {
			status.textContent = 'در حال بررسی…';
			row.dataset.state = await request(file,'status');
			if (row.dataset.state === 'missing') {
				phase = 'install';
				status.textContent = 'در حال نصب…';
				await download(file,event);
				row.dataset.state = await request(file,'status');
				if (row.dataset.state === 'missing') throw {reason:'install'};
			}
			if (row.dataset.state !== 'active') {
				phase = 'activate';
				status.textContent = 'در حال فعال‌سازی…';
				await request(file,'activate');
				row.dataset.state = await request(file,'status');
				if (row.dataset.state !== 'active') throw {reason:'activate'};
			}
			status.textContent = 'فعال';
		} catch (error) {
			// A dropped response or an activation hook exiting early can follow a committed change.
			let actual;
			try { actual = await request(file,'status'); } catch {}
			if (actual) row.dataset.state = actual;
			if (actual === 'active') status.textContent = 'فعال';
			else {
				const code = reason(error);
				console.warn('[mytheme]', file, phase, code);
				feedback(row,phase,code);
				if (code === 'filesystem') wp.updates.filesystemCredentials.available = false;
			}
		} finally {
			row.removeAttribute('aria-busy');
		}
	}
	async function run(selected,event) {
		if (running) return;
		running = true;
		controls();
		try {
			for (const row of selected) await processRow(row,event);
		} finally { running = false; controls(); }
	}
	for (const row of rows) {
		try {
			const saved = JSON.parse(sessionStorage.getItem(storageKey(row)));
			if (row.dataset.state !== 'active' && saved && Object.hasOwn(errors,saved.code)) feedback(row,saved.phase,saved.code);
			else feedback(row);
		} catch {}
		row.querySelector('[data-retry]').onclick = event => run([row],event);
	}
	retryAll.onclick = event => run(rows.filter(row => row.dataset.failure),event);
	install.onclick = event => run(rows.filter(row => row.dataset.state !== 'active' && !row.dataset.failure),event);
	controls();
}

document.querySelectorAll('[data-demo]').forEach(button => {
	const panel = button.closest('section');
	const bar = panel.querySelector('progress');
	const status = panel.querySelector('output');
	setup.addEventListener('change', () => {
		button.disabled = !panel.querySelector('input').checked || panel.dataset.active === 'true';
	});
	button.onclick = async () => {
		button.disabled = true;
		panel.setAttribute('aria-busy', 'true');
		try {
			while (bar.value < 100) {
				const body = new URLSearchParams({action: 'mytheme_import', demo: button.dataset.demo, _ajax_nonce: mythemeSetup.nonce});
				const response = await fetch(ajaxurl, {method: 'POST', body});
				const result = await response.json();
				if (!result.success) throw new Error(result.data || 'خطا در نصب دمو');
				bar.value = result.data;
				status.textContent = bar.value + '%';
			}
			status.textContent = 'در حال فعال‌سازی…';
			const body = new URLSearchParams({action: 'mytheme_activate_demo', demo: button.dataset.demo, _ajax_nonce: mythemeSetup.nonce});
			const response = await fetch(ajaxurl, {method: 'POST', body});
			const result = await response.json();
			if (!result.success) throw new Error(result.data || 'فعال‌سازی دمو ناموفق بود');
			setup.querySelectorAll('.mytheme-demo').forEach(other => {
				other.dataset.active = String(other === panel);
				other.querySelector('[data-visit]').hidden = other !== panel;
				other.querySelector('strong em')?.remove();
				other.querySelector('[data-demo]').textContent = other.querySelector('progress').value === 100 ? 'فعال‌سازی دمو' : 'نصب و تکمیل';
			});
			const badge = document.createElement('em');
			badge.textContent = 'دموی فعال';
			panel.querySelector('strong').append(badge);
			panel.querySelector('[data-visit]').hidden = false;
			status.textContent = '۱۰۰٪ — دمو آماده است';
		} catch (error) {
			status.textContent = error.message;
		} finally {
			panel.removeAttribute('aria-busy');
			setup.dispatchEvent(new Event('change'));
		}
	};
});
