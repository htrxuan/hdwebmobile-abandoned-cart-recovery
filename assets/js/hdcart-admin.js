(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		var toggle = document.querySelector('input[name="hdcart_options[discount_enabled]"]');
		if (!toggle) {
			return;
		}

		var fields = [
			'select[name="hdcart_options[discount_type]"]',
			'input[name="hdcart_options[discount_amount]"]',
			'input[name="hdcart_options[coupon_expiry_days]"]',
		];

		function rows() {
			var els = [];
			fields.forEach(function (selector) {
				var el = document.querySelector(selector);
				if (el) {
					var row = el.closest('tr');
					if (row) {
						els.push(row);
					}
				}
			});
			return els;
		}

		function refresh() {
			rows().forEach(function (row) {
				row.style.display = toggle.checked ? '' : 'none';
			});
		}

		toggle.addEventListener('change', refresh);
		refresh();
	});
})();
