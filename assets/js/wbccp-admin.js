(function() {
	function initGroupFilter() {
		var input = document.getElementById('wbccp-group-filter');
		var select = document.getElementById('wbccp_group_id');
		if (!input || !select) {
			return;
		}

		input.addEventListener('input', function() {
			var query = input.value.toLowerCase();
			var options = select.options;
			for (var i = 0; i < options.length; i++) {
				var opt = options[i];
				if (!opt.value) {
					opt.hidden = false;
					continue;
				}
				opt.hidden = query && opt.text.toLowerCase().indexOf(query) === -1;
			}
		});
	}

	function injectIframeStyles() {
		var css = '.handle-order-higher, .handle-order-lower { display: none !important; }' +
			'.handlediv { display:none !important; }' +
			'.handlediv .toggle-indicator:before { font-size: 18px; }' +
			'.screen-reader-text{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;}' +
			'.hidden{display:none !important;}';

		function applyToDoc(doc) {
			if (!doc || !doc.head) {
				return;
			}
			if (!doc.getElementById('wbccp-iframe-style')) {
				var style = doc.createElement('style');
				style.id = 'wbccp-iframe-style';
				style.textContent = css;
				doc.head.appendChild(style);
			}
			var nested = doc.querySelectorAll('iframe');
			nested.forEach(function(nestedFrame) {
				try {
					applyToDoc(nestedFrame.contentDocument);
				} catch (e) {
					// Ignore cross-origin iframes.
				}
			});
		}

		applyToDoc(document);
	}

	function initRecurrenceToggle() {
		var containers = document.querySelectorAll('.wbccp-recurrence-fields');
		if (!containers.length) {
			return;
		}

		function toggleField(field, shouldEnable) {
			if (!field) {
				return;
			}
			if (field.length && field.tagName !== 'INPUT' && field.tagName !== 'SELECT' && field.tagName !== 'TEXTAREA') {
				for (var i = 0; i < field.length; i++) {
					field[i].disabled = !shouldEnable;
				}
				return;
			}
			if (field.querySelectorAll) {
				var inputs = field.querySelectorAll('input, select, textarea');
				inputs.forEach(function(input) {
					input.disabled = !shouldEnable;
				});
				return;
			}
			field.disabled = !shouldEnable;
		}

		function update(container) {
			var enabled = container.querySelector('.wbccp-recur-enabled');
			var freq = container.querySelector('.wbccp-recur-freq');
			var details = container.querySelector('.wbccp-recur-details');
			var weekly = container.querySelector('.wbccp-recur-weekly');
			var monthly = container.querySelectorAll('.wbccp-recur-monthly');
			var monthday = container.querySelector('.wbccp-recur-bymonthday-field');
			var bysetpos = container.querySelector('.wbccp-recur-bysetpos');
			var byweekday = container.querySelector('.wbccp-recur-byweekday');
			var interval = container.querySelector('.wbccp-recur-interval');
			var count = container.querySelector('.wbccp-recur-count');
			var until = container.querySelector('.wbccp-recur-until');
			var byday = container.querySelectorAll('.wbccp-recur-byday');

			var isEnabled = enabled ? enabled.checked : false;
			if (details) {
				details.style.display = isEnabled ? '' : 'none';
			}

			var f = freq ? freq.value : '';
			if (weekly) {
				weekly.style.display = isEnabled && f === 'WEEKLY' ? '' : 'none';
			}
			if (monthly.length) {
				for (var i = 0; i < monthly.length; i++) {
					monthly[i].style.display = isEnabled && (f === 'MONTHLY' || f === 'YEARLY') ? '' : 'none';
				}
			}

			var useNth = bysetpos && byweekday && bysetpos.value && byweekday.value;
			if (monthday) {
				monthday.style.display = isEnabled && (f === 'MONTHLY' || f === 'YEARLY') && !useNth ? '' : 'none';
			}

			toggleField(details, isEnabled);
			toggleField(weekly, isEnabled && f === 'WEEKLY');
			if (monthly.length) {
				for (var j = 0; j < monthly.length; j++) {
					toggleField(monthly[j], isEnabled && (f === 'MONTHLY' || f === 'YEARLY'));
				}
			}
			toggleField(monthday, isEnabled && (f === 'MONTHLY' || f === 'YEARLY') && !useNth);
			toggleField(bysetpos, isEnabled && (f === 'MONTHLY' || f === 'YEARLY'));
			toggleField(byweekday, isEnabled && (f === 'MONTHLY' || f === 'YEARLY'));
			toggleField(interval, isEnabled);
			toggleField(count, isEnabled);
			toggleField(until, isEnabled);
			if (byday && byday.length) {
				toggleField(byday, isEnabled && f === 'WEEKLY');
			}
		}

		containers.forEach(function(container) {
			update(container);
			container.addEventListener('change', function() {
				update(container);
			});
		});
	}

	function init() {
		if (!document.body || !document.body.classList.contains('post-type-wb_community_event')) {
			return;
		}
		initGroupFilter();
		initRecurrenceToggle();
		injectIframeStyles();
		var observer = new MutationObserver(function() {
			injectIframeStyles();
			initRecurrenceToggle();
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
