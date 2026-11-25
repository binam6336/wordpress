(() => {
	if (!window.mythemeDiagnostics) return;
	let sent = 0;
	function report(code, message, source) {
		if (++sent > 10) return;
		const body = new URLSearchParams({action:'mytheme_client_log',_ajax_nonce:mythemeDiagnostics.nonce,code,message:String(message).slice(0,500),source:source || ''});
		if (navigator.sendBeacon) navigator.sendBeacon(mythemeDiagnostics.url,body);
		else fetch(mythemeDiagnostics.url,{method:'POST',body,keepalive:true,credentials:'same-origin'}).catch(() => {});
	}
	window.addEventListener('error', event => {
		if (event.filename && new URL(event.filename,location.href).origin !== location.origin) return;
		report('js_error',event.message || 'خطای جاوااسکریپت',event.filename || '');
	});
	window.addEventListener('unhandledrejection', event => {
		report('js_rejection',event.reason?.message || 'درخواست یا وعدهٔ ناموفق','');
	});
})();
