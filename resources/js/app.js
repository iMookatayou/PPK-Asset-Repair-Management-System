import '../css/app.css'

// SearchSelect component behavior: enhances div[data-ss]
(() => {
	if (window.__searchSelectInit) return;
	window.__searchSelectInit = true;

	const normalize = (s) => (s||'').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'');

	const init = (root) => {
			const textInput = root.querySelector('[data-ss-text]');
			const toggleBtn = root.querySelector('[data-ss-toggle]');
		const panel = root.querySelector('[data-ss-panel]');
		const input = root.querySelector('[data-ss-input]');
		const list = root.querySelector('[data-ss-list]');
		const empty = root.querySelector('[data-ss-empty]');
			if (!textInput || !toggleBtn || !panel || !input || !list) return;

		const allOptions = Array.from(list.querySelectorAll('[data-ss-option]'));

			const open = () => {
				panel.classList.remove('hidden');
				textInput.setAttribute('aria-expanded','true');
				// Reset filter each open but keep current chosen display
				pendingFilterTerm = '';
				filter('');
				setTimeout(() => textInput.focus(), 0);
				document.addEventListener('mousedown', onDoc); // use mousedown for earlier close
			};
			const close = () => {
				if (panel.classList.contains('hidden')) return;
				panel.classList.add('hidden');
				textInput.setAttribute('aria-expanded','false');
				document.removeEventListener('mousedown', onDoc);
			};
			const onDoc = (e) => { if (!root.contains(e.target)) close(); };

			const selectValue = (val, text) => {
				input.value = val;
				if (typeof text === 'string') {
					textInput.value = text;
				} else if (!val) {
					textInput.value = '';
				}
				close();
			};

			const filter = (raw) => {
				const term = raw.trim();
				const t = normalize(term);
			let shown = 0;
			allOptions.forEach(opt => {
				const v = opt.getAttribute('data-value') || '';
				const txt = (opt.getAttribute('data-label') || opt.textContent || '').trim();
				const isPlaceholder = v === '';
				const ok = isPlaceholder || !t || normalize(txt).includes(t);
				opt.style.display = ok ? '' : 'none';
				if (ok && !isPlaceholder) shown++;
			});
			if (empty) empty.classList.toggle('hidden', shown !== 0);
		};

				toggleBtn.addEventListener('click', (e) => { e.preventDefault(); panel.classList.contains('hidden') ? open() : close(); });
				textInput.addEventListener('focus', () => { if (panel.classList.contains('hidden')) open(); });
				textInput.addEventListener('click', () => { if (panel.classList.contains('hidden')) open(); });
				textInput.addEventListener('input', () => filter(textInput.value));
				textInput.addEventListener('keydown', (e) => {
					if (e.key === 'ArrowDown') {
						e.preventDefault();
						const first = list.querySelector('[data-ss-option]:not([style*="display: none"])');
						first && first.focus();
					} else if (e.key === 'Escape') {
						close();
					}
				});
		list.addEventListener('click', (e) => {
			const li = e.target.closest('[data-ss-option]');
			if (!li) return;
			const val = li.getAttribute('data-value') || '';
			const txt = (li.getAttribute('data-label') || li.textContent || '').trim();
			selectValue(val, txt);
		});
				// Make list items focusable for keyboard navigation
				allOptions.forEach(o => o.setAttribute('tabindex','-1'));
				list.addEventListener('keydown', (e) => {
					const current = document.activeElement.closest('[data-ss-option]');
					if (!current) return;
					if (e.key === 'ArrowDown') {
						e.preventDefault();
						let next = current.nextElementSibling;
						while (next && (next.style.display==='none' || !next.hasAttribute('data-ss-option'))) next = next.nextElementSibling;
						next && next.focus();
					} else if (e.key === 'ArrowUp') {
						e.preventDefault();
						let prev = current.previousElementSibling;
						while (prev && (prev.style.display==='none' || !prev.hasAttribute('data-ss-option'))) prev = prev.previousElementSibling;
						prev ? prev.focus() : textInput.focus();
					} else if (e.key === 'Enter') {
						e.preventDefault();
						current.click();
					} else if (e.key === 'Escape') {
						e.preventDefault();
						close();
						textInput.focus();
					}
				});
	};

	const setup = () => document.querySelectorAll('[data-ss]').forEach(init);
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup, { once: true }); else setup();
})();