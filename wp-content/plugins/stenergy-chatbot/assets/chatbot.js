/* ST Store · widget del asistente virtual. Todo el texto se inserta con textContent (sin HTML de terceros). */
(function () {
	'use strict';

	var cfg = window.STS_CHATBOT;
	var root = document.getElementById('sts-chatbot');
	if (!cfg || !root) { return; }

	var KEY = 'sts_chatbot_v1';
	var state = load() || { open: false, messages: [], context: {} };
	var busy = false;

	function load() {
		try { return JSON.parse(sessionStorage.getItem(KEY)); } catch (e) { return null; }
	}
	function save() {
		try {
			sessionStorage.setItem(KEY, JSON.stringify({ open: state.open, messages: state.messages.slice(-30), context: state.context }));
		} catch (e) { /* almacenamiento no disponible */ }
	}

	function h(tag, attrs, children) {
		var node = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			if (k === 'text') { node.textContent = attrs[k]; }
			else if (k === 'class') { node.className = attrs[k]; }
			else { node.setAttribute(k, attrs[k]); }
		});
		(children || []).forEach(function (c) { if (c) { node.appendChild(c); } });
		return node;
	}

	function safeUrl(u) {
		return /^https?:\/\//i.test(u || '') ? u : '#';
	}

	/* ---------- Estructura ---------- */
	var fabIcon = '<svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path fill="currentColor" d="M8.500 11.200h1.600v1.600H8.500zm2.700 0h1.600v1.600h-1.600zm2.700 0h1.600v1.600h-1.600z"/></svg>';

	var fab = h('button', { 'class': 'sts-bot__fab', type: 'button', 'aria-label': 'Abrir asistente virtual', 'aria-expanded': 'false' });
	fab.innerHTML = fabIcon; // SVG fijo, sin contenido del usuario
	var fabLabel = h('span', { 'class': 'sts-bot__fab-label', text: '¿Te ayudo?' });
	fab.appendChild(fabLabel);

	var title = h('div', {}, [h('strong', { text: cfg.name }), h('small', { text: 'Respuestas al instante · stock en vivo' })]);
	var closeBtn = h('button', { 'class': 'sts-bot__close', type: 'button', 'aria-label': 'Cerrar asistente', text: '×' });
	var head = h('header', { 'class': 'sts-bot__head' }, [title, closeBtn]);
	var list = h('div', { 'class': 'sts-bot__msgs', 'aria-live': 'polite' });
	var input = h('input', { type: 'text', maxlength: '300', placeholder: 'Escribe tu consulta…', 'aria-label': 'Mensaje', autocomplete: 'off' });
	var send = h('button', { type: 'submit', text: 'Enviar' });
	var form = h('form', { 'class': 'sts-bot__form' }, [input, send]);
	var panel = h('section', { 'class': 'sts-bot__panel', role: 'dialog', 'aria-label': cfg.name, hidden: 'hidden' }, [head, list, form]);

	root.appendChild(panel);
	root.appendChild(fab);

	/* ---------- Render ---------- */
	function renderMessage(m) {
		if (m.type === 'user') {
			return h('div', { 'class': 'sts-bot__msg is-user', text: m.text });
		}
		if (m.type === 'text') {
			return h('div', { 'class': 'sts-bot__msg is-bot', text: m.text });
		}
		if (m.type === 'chips') {
			return h('div', { 'class': 'sts-bot__chips' }, m.items.map(function (c) {
				var b = h('button', { type: 'button', text: c.label });
				b.addEventListener('click', function () { ask(c.message, c.label); });
				return b;
			}));
		}
		if (m.type === 'links') {
			return h('div', { 'class': 'sts-bot__links' }, m.items.map(function (l) {
				var url = safeUrl(l.url);
				var a = h('a', { href: url, text: l.label });
				if (url.indexOf(location.origin) !== 0) { a.setAttribute('target', '_blank'); a.setAttribute('rel', 'noopener'); }
				return a;
			}));
		}
		if (m.type === 'products') {
			return h('div', { 'class': 'sts-bot__cards' }, m.items.map(function (p) {
				var media = p.image
					? h('img', { src: safeUrl(p.image), alt: '', loading: 'lazy' })
					: h('span', { 'class': 'sts-bot__ph', text: (p.name || '?').charAt(0).toUpperCase() });
				var actions = [h('a', { 'class': 'sts-bot__btn', href: safeUrl(p.url), text: 'Ver' })];
				if (p.add_url) { actions.push(h('a', { 'class': 'sts-bot__btn is-primary', href: safeUrl(p.add_url), text: 'Agregar' })); }
				return h('article', { 'class': 'sts-bot__card' }, [
					h('div', { 'class': 'sts-bot__card-media' }, [media]),
					h('div', { 'class': 'sts-bot__card-body' }, [
						h('strong', { text: p.name }),
						h('span', { 'class': 'sts-bot__price', text: p.price }),
						h('span', { 'class': 'sts-bot__stock is-' + (/^(ok|low|out)$/.test(p.level) ? p.level : 'ok'), text: p.stock }),
						h('div', { 'class': 'sts-bot__actions' }, actions)
					])
				]);
			}));
		}
		return null;
	}

	function render() {
		list.textContent = '';
		state.messages.forEach(function (m) {
			var node = renderMessage(m);
			if (node) { list.appendChild(node); }
		});
		list.scrollTop = list.scrollHeight;
	}

	function push(m) {
		state.messages.push(m);
		var node = renderMessage(m);
		if (node) { list.appendChild(node); }
		list.scrollTop = list.scrollHeight;
	}

	function typing(on) {
		var t = list.querySelector('.sts-bot__typing');
		if (on && !t) {
			list.appendChild(h('div', { 'class': 'sts-bot__typing', 'aria-label': 'Escribiendo' }, [h('i'), h('i'), h('i')]));
			list.scrollTop = list.scrollHeight;
		} else if (!on && t) {
			t.remove();
		}
	}

	/* ---------- Conversación ---------- */
	function ask(message, label) {
		message = (message || '').trim();
		if (!message || busy) { return; }
		busy = true;
		push({ type: 'user', text: label || message });
		typing(true);

		fetch(cfg.endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ message: message, context: state.context || {} })
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				typing(false);
				state.context = (data && data.context && Object.keys(data.context).length) ? data.context : {};
				((data && data.messages) || []).forEach(push);
			})
			.catch(function () {
				typing(false);
				push({ type: 'text', text: 'No pude conectarme en este momento. Inténtalo de nuevo o escríbenos por WhatsApp.' });
			})
			.then(function () {
				busy = false;
				save();
			});
	}

	function setOpen(open) {
		state.open = open;
		panel.hidden = !open;
		fab.setAttribute('aria-expanded', open ? 'true' : 'false');
		root.classList.toggle('is-open', open);
		if (open) {
			fabLabel.style.display = 'none';
			setTimeout(function () { input.focus(); }, 60);
		}
		save();
	}

	fab.addEventListener('click', function () { setOpen(!state.open); });
	closeBtn.addEventListener('click', function () { setOpen(false); fab.focus(); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && state.open) { setOpen(false); } });
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var v = input.value;
		input.value = '';
		ask(v);
	});

	/* ---------- Arranque ---------- */
	if (!state.messages.length) {
		state.messages.push({ type: 'text', text: cfg.greeting });
		state.messages.push({ type: 'chips', items: cfg.chips });
	}
	render();
	setOpen(!!state.open);
	root.setAttribute('data-ready', '1');
})();
