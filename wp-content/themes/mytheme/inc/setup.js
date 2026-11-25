const setup = document.querySelector('.mytheme-setup');
const currencySearch = setup.querySelector('#mytheme-currency-search');
if (currencySearch) {
	const options = [...setup.querySelector('#mytheme-currency').options];
	currencySearch.addEventListener('input', () => {
		const query = currencySearch.value.trim().toLocaleLowerCase('fa-IR');
		for (const option of options) option.hidden = !!query && !option.textContent.toLocaleLowerCase('fa-IR').includes(query);
	});
}
const appearanceForm = setup.querySelector('[data-appearance-form]');
if (appearanceForm) {
	const preview = setup.querySelector('[data-appearance-preview]');
	const status = appearanceForm.querySelector('[data-appearance-status]');
	const save = appearanceForm.querySelector('[data-save-appearance]');
	const initial = new FormData(appearanceForm);
	function changed() {
		const current = new FormData(appearanceForm);
		return ['accent','palette','font','header','footer'].some(key => current.get(key) !== initial.get(key));
	}
	function updatePreview() {
		const data = new FormData(appearanceForm);
		const palette = appearanceForm.querySelector('[name=palette]:checked');
		preview.style.setProperty('--sample-accent', data.get('accent'));
		preview.style.setProperty('--sample-canvas', palette?.dataset.canvas || '#f7f9f6');
		preview.style.setProperty('--sample-surface', palette?.dataset.surface || '#e8efe9');
		preview.style.setProperty('--sample-ink', palette?.dataset.ink || '#27392f');
		preview.style.fontFamily = mythemeSetup.fonts[data.get('font')] || 'Tahoma,sans-serif';
		preview.querySelector('.sample-header').hidden = data.get('header') === 'compact';
		preview.querySelector('.sample-footer').hidden = data.get('footer') === 'simple';
		save.disabled = !changed();
		status.textContent = changed() ? 'تغییرات ذخیره نشده‌اند.' : 'تغییری ثبت نشده است.';
	}
	appearanceForm.addEventListener('input', updatePreview);
	appearanceForm.addEventListener('change', updatePreview);
	appearanceForm.querySelectorAll('[name=palette]').forEach(radio => radio.addEventListener('change', () => {
		if (radio.checked) appearanceForm.elements.accent.value = radio.dataset.accent;
		updatePreview();
	}));
	save.addEventListener('click', () => { appearanceForm.elements.mode.value = 'save'; });
	appearanceForm.querySelector('[data-reset-appearance]').addEventListener('click', () => { appearanceForm.elements.mode.value = 'reset'; });
	appearanceForm.addEventListener('submit', () => { status.textContent = 'در حال ذخیره…'; });
	updatePreview();
}
const healthScan = setup.querySelector('[data-health-scan]');
if (healthScan) {
	const progress = setup.querySelector('[data-health-progress]');
	const status = setup.querySelector('[data-health-status]');
	healthScan.addEventListener('click', async () => {
		healthScan.disabled = true;
		progress.hidden = false;
		progress.value = 0;
		let failures = 0;
		for (const part of mythemeSetup.healthParts) {
			status.textContent = 'در حال بررسی ' + part + '…';
			try {
				const response = await fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:new URLSearchParams({action:'mytheme_health_scan',part,_ajax_nonce:mythemeSetup.healthNonce})});
				const result = await response.json();
				if (!result.success) throw new Error(result.data?.message || 'بررسی ناموفق بود.');
			} catch (error) { failures++; status.textContent = error.message + ' بررسی بخش‌های دیگر ادامه دارد.'; }
			progress.value++;
		}
		status.textContent = failures ? 'بررسی پایان یافت؛ ' + failures + ' بخش کامل نشد. برای جزئیات صفحه را تازه‌سازی کنید.' : 'بررسی پایان یافت. برای دیدن نتیجه صفحه را تازه‌سازی کنید.';
		healthScan.disabled = false;
	});
}
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

const demoPanels = [...setup.querySelectorAll('.mytheme-demo')];
if (demoPanels.length) {
	const dialog = setup.querySelector('[data-confirm-demo]');
	const message = setup.querySelector('[data-demo-message]');
	let selected = null;
	let action = 'mytheme_activate_demo';
	let running = false;

	async function request(action, demo) {
		const body = new URLSearchParams({action, demo, _ajax_nonce: mythemeSetup.nonce});
		const response = await fetch(ajaxurl, {method:'POST', body});
		let result;
		try { result = await response.json(); }
		catch { throw new Error('پاسخ معتبری از سرور دریافت نشد. دوباره تلاش کنید.'); }
		if (!result.success) throw new Error(result.data?.message || 'عملیات انجام نشد. دوباره تلاش کنید.');
		return result.data;
	}
	function controls() {
		for (const panel of demoPanels) {
			const button = panel.querySelector('[data-demo]');
			button.disabled = running || !panel.querySelector('input').checked || panel.dataset.active === 'true';
		}
	}
	function setActive(panel) {
		for (const other of demoPanels) {
			other.dataset.active = String(other === panel);
			other.querySelector('strong em')?.remove();
			other.querySelector('[data-visit]').hidden = other !== panel;
		}
		const badge = document.createElement('em');
		badge.textContent = 'دموی فعال';
		panel.querySelector('strong').append(badge);
		controls();
	}
	function review(panel, operation) {
		selected = panel;
		action = operation;
		const previous = demoPanels.find(item => item.dataset.active === 'true');
		const name = panel.dataset.title;
		dialog.querySelector('[data-confirm-description]').textContent = previous ? 'ظاهر سایت از «' + previous.dataset.title + '» به «' + name + '» تغییر می‌کند.' : 'دموی «' + name + '» روی سایت فعال می‌شود.';
		dialog.querySelector('[data-accept-demo]').textContent = operation === 'mytheme_rollback_demo' ? 'تأیید بازگشت' : 'تأیید فعال‌سازی';
		dialog.showModal();
	}
	setup.addEventListener('change', controls);
	dialog.querySelector('[data-cancel-demo]').onclick = () => dialog.close();
	dialog.querySelector('[data-accept-demo]').onclick = async () => {
		if (running || !selected) return;
		const panel = selected;
		const button = dialog.querySelector('[data-accept-demo]');
		running = true;
		button.disabled = true;
		message.textContent = 'در حال تغییر دمو…';
		controls();
		try {
			const previous = demoPanels.find(item => item.dataset.active === 'true');
			await request(action, panel.querySelector('[data-demo]').dataset.demo);
			setActive(panel);
			panel.querySelector('output').textContent = 'دمو فعال است';
			message.textContent = 'دمو فعال شد.';
			if (previous && previous !== panel) {
				let rollback = setup.querySelector('[data-rollback]');
				if (!rollback) {
					const wrapper = document.createElement('p');
					rollback = document.createElement('button');
					rollback.type = 'button';
					rollback.className = 'button';
					rollback.textContent = 'بازگشت به دموی قبلی';
					wrapper.append(rollback);
					message.before(wrapper);
				}
				rollback.dataset.rollback = previous.querySelector('[data-demo]').dataset.demo;
			}
			dialog.close();
		} catch (error) { message.textContent = error.message; }
		finally { running = false; button.disabled = false; controls(); }
	};
	for (const panel of demoPanels) {
		const button = panel.querySelector('[data-demo]');
		const bar = panel.querySelector('progress');
		const status = panel.querySelector('output');
		button.onclick = async () => {
			if (running) return;
			if (bar.value === 100) { review(panel, 'mytheme_activate_demo'); return; }
			running = true;
			panel.setAttribute('aria-busy', 'true');
			message.textContent = '';
			controls();
			try {
				while (bar.value < 100) {
					const result = await request('mytheme_import', button.dataset.demo);
					bar.value = result.progress;
					status.textContent = bar.value + '%';
					for (const [page, url] of Object.entries(result.previews || {})) {
						const link = panel.querySelector('[data-preview-live="' + page + '"]');
						if (link && url) { link.href = url; link.hidden = false; }
					}
				}
				button.textContent = 'بررسی فعال‌سازی';
				status.textContent = 'نصب کامل شد. پیش‌نمایش زنده آماده است.';
			} catch (error) { status.textContent = error.message; }
			finally { running = false; panel.removeAttribute('aria-busy'); controls(); }
		};
	}
	setup.addEventListener('click', event => {
		const rollback = event.target.closest('[data-rollback]');
		if (!rollback) return;
		const panel = demoPanels.find(item => item.querySelector('[data-demo]').dataset.demo === rollback.dataset.rollback);
		if (panel) review(panel, 'mytheme_rollback_demo');
	});
	controls();
}
