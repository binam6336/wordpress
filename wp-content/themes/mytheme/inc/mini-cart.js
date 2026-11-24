(() => {
	const dialog = document.querySelector('#mt-cart');
	if (!dialog) return;
	const template = document.querySelector('#mt-trigger-template');
	// Upgrade the cart button in previously imported Elementor headers, including cached layouts.
	document.querySelectorAll('.velour-mast a.elementor-button').forEach(link => {
		if (link.textContent.trim() === 'سبد خرید') link.replaceWith(template.content.cloneNode(true));
	});
	const lines = dialog.querySelector('.mt-lines');
	const status = dialog.querySelector('[role=status]');
	const fa = new Intl.NumberFormat('fa-IR');
	const channelName = 'mytheme:' + mythemeCart.routes.cart;
	const channel = window.BroadcastChannel ? new BroadcastChannel(channelName) : null;
	if (channel) channel.onmessage = () => sync();
	let nonce, queue = Promise.resolve(), pending = 0;
	let returnFocus = null;

	function node(tag, value, className) {
		const element = document.createElement(tag);
		if (value !== undefined) element.textContent = value;
		if (className) element.className = className;
		return element;
	}
	function decode(value) {
		const field = document.createElement('textarea');
		field.innerHTML = value;
		return field.value;
	}
	function price(data, key) {
		const amount = Number(data[key]) / 10 ** data.currency_minor_unit;
		const number = amount.toLocaleString('fa-IR', {minimumFractionDigits: data.currency_minor_unit, maximumFractionDigits: data.currency_minor_unit});
		if (['IRR', 'IRT'].includes(data.currency_code)) return number + '\u00a0' + decode(data.currency_symbol);
		return decode(data.currency_prefix + number + data.currency_suffix);
	}
	function render(cart) {
		document.querySelectorAll('.mt-cart-trigger').forEach(trigger => {
			const badge = trigger.querySelector('.mt-count');
			badge.textContent = fa.format(cart.items_count);
			badge.hidden = false;
			trigger.setAttribute('aria-label', 'سبد خرید، ' + fa.format(cart.items_count) + ' کالا');
		});
		const focusedKey = document.activeElement.closest('[data-key]')?.dataset.key;
		const focusedAction = document.activeElement.dataset.action;
		lines.replaceChildren();
		for (const item of cart.items) {
			const row = node('article');
			row.dataset.key = item.key;
			if (item.images[0]) {
				const image = node('img');
				image.src = item.images[0].thumbnail || item.images[0].src;
				image.alt = '';
				row.append(image);
			}
			const info = node('div');
			const name = node('a', decode(item.name));
			name.href = item.permalink;
			const attributes = item.variation.map(v => decode(v.attribute + '، ' + v.value)).join(' · ');
			info.append(name, node('small', attributes), node('small', 'قیمت واحد · ' + price(item.prices, 'price')));
			const controls = node('div', undefined, 'mt-actions');
			for (const [action, title, value] of [['plus','افزایش تعداد','+'],['minus','کاهش تعداد','−'],['remove','حذف محصول','حذف']]) {
				if (action === 'minus') controls.append(node('bdi', fa.format(item.quantity)));
				const button = node('button', value);
				const limits = item.quantity_limits;
				const qty = Number(item.quantity) + (action === 'plus' ? 1 : -1) * limits.multiple_of;
				button.dataset.action = action;
				button.dataset.delta = qty - item.quantity;
				button.setAttribute('aria-label', title + ' ' + decode(item.name));
				button.disabled = action !== 'remove' && (!limits.editable || qty < limits.minimum || qty > limits.maximum);
				controls.append(button);
			}
			info.append(controls, node('small', 'جمع ردیف · ' + price(item.totals, 'line_total')));
			row.append(info);
			lines.append(row);
			if (item.key === focusedKey && focusedAction) {
				const candidate = controls.querySelector('[data-action="' + focusedAction + '"]:not(:disabled)');
				(candidate || controls.querySelector('[data-action=remove]')).focus();
			}
		}
		if (!cart.items.length) lines.append(node('p','سبد خرید شما خالی است.', 'mt-empty'));
		dialog.querySelector('footer').hidden = !cart.items.length;
		dialog.querySelector('[data-total]').textContent = price(cart.totals,'total_price');
	}
	async function request(route, body) {
		const headers = {};
		if (mythemeCart.restNonce) headers['X-WP-Nonce'] = mythemeCart.restNonce;
		if (body) { headers.Nonce = nonce; headers['Content-Type'] = 'application/json'; }
		const response = await fetch(mythemeCart.routes[route], {method: body ? 'POST' : 'GET', credentials:'same-origin', cache:'no-store', headers, body:body && JSON.stringify(body)});
		const data = await response.json();
		nonce = response.headers.get('Nonce') || nonce;
		if (!response.ok) throw new Error(data.message || 'دریافت اطلاعات سبد ناموفق بود.');
		return data;
	}
	function sync(route = 'cart', body) {
		pending++;
		dialog.setAttribute('aria-busy','true');
		status.textContent = 'در حال به‌روزرسانی…';
		queue = queue.then(async () => {
			if (body && !nonce) await request('cart');
			const execute = async () => {
				if (route === 'update-item') {
					const current = await request('cart');
					const item = current.items.find(entry => entry.key === body.key);
					if (!item) return current;
					return request(route, {key:body.key, quantity:item.quantity + body.delta});
				}
				return request(route, body);
			};
			const cart = body && navigator.locks ? await navigator.locks.request(channelName, execute) : await execute();
			render(cart);
			if (body) {
				channel?.postMessage('changed');
				jQuery(document.body).trigger('wc_update_cart').trigger('update_checkout').trigger('wc_fragment_refresh');
				window.wp?.data?.dispatch('wc/store/cart')?.invalidateResolutionForStoreSelector?.('getCartData');
			}
			status.textContent = '';
		}).catch(async error => {
			if (body) {
				try { render(await request('cart')); } catch {}
				if (!dialog.open) dialog.showModal();
			}
			status.textContent = 'عملیات سبد خرید انجام نشد. ' + error.message;
		}).finally(() => {
			if (!--pending) dialog.removeAttribute('aria-busy');
		});
	}
	document.addEventListener('click', event => {
		const trigger = event.target.closest('.mt-cart-trigger');
		if (trigger) { event.preventDefault(); returnFocus = trigger; dialog.showModal(); sync(); return; }
		const add = event.target.closest('[data-mytheme-add]');
		if (add && !event.ctrlKey && !event.metaKey) {
			event.preventDefault();
			sync('add-item', {id:Number(add.dataset.mythemeAdd),quantity:1});
		}
	});
	// Let product extensions with extra fields handle their own submission.
	document.addEventListener('submit', event => {
		const form = event.target;
		if (event.defaultPrevented || !form.matches('form.cart') || !form.querySelector('.single_add_to_cart_button')) return;
		const fields = new FormData(form);
		const variation = [];
		for (const [name, value] of fields) {
			if (name.startsWith('attribute_')) variation.push({attribute:name,value});
			else if (!['add-to-cart','product_id','variation_id','quantity'].includes(name)) return;
		}
		const id = Number(fields.get('variation_id') || fields.get('product_id') || event.submitter?.value);
		if (!id || form.matches('.variations_form') && !Number(fields.get('variation_id'))) return;
		event.preventDefault();
		returnFocus = event.submitter || form.querySelector('.single_add_to_cart_button');
		dialog.showModal();
		sync('add-item', {id, quantity:Number(fields.get('quantity') || 1), variation});
	});
	dialog.addEventListener('close', () => {
		if (returnFocus?.isConnected) returnFocus.focus();
		returnFocus = null;
	});
	dialog.addEventListener('click', event => {
		const bounds = dialog.getBoundingClientRect();
		const backdrop = event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom);
		if (event.target.closest('[data-close]') || backdrop) { dialog.close(); return; }
		if (pending && event.target.closest('footer a')) { event.preventDefault(); return; }
		const button = event.target.closest('[data-action]');
		if (!button || pending) return;
		const key = button.closest('[data-key]').dataset.key;
		if (button.dataset.action === 'remove') sync('remove-item',{key});
		else sync('update-item',{key,delta:Number(button.dataset.delta)});
	});
	jQuery(document.body).on('added_to_cart removed_from_cart updated_wc_div', () => sync());
	document.body.addEventListener('wc-blocks_added_to_cart', () => sync());
	document.body.addEventListener('wc-blocks_removed_from_cart', () => sync());
	window.addEventListener('pageshow', () => sync());
	document.addEventListener('visibilitychange', () => { if (!document.hidden) sync(); });
})();
