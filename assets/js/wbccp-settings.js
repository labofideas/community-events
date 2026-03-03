(function() {
	function initSettingsUi() {
		if (!document.body || !document.body.classList.contains('wbccp-settings-admin')) {
			return;
		}

		var form = document.querySelector('.wbccp-settings-form');
		var nav = document.querySelector('.wbccp-settings-nav');
		if (!form || !nav) {
			return;
		}

		var panel = form.querySelector('.wbccp-settings-panels');
		if (!panel) {
			return;
		}

		var firstHeading = panel.querySelector('h2');
		if (!firstHeading) {
			return;
		}

		var sections = [];
		var index = 0;
		var heading = panel.querySelector('h2');
		while (heading) {
			var section = document.createElement('section');
			section.className = 'wbccp-settings-section';
			section.id = 'wbccp-section-' + (index + 1);
			panel.insertBefore(section, heading);

			var cursor = heading;
			while (cursor) {
				var next = cursor.nextElementSibling;
				if (cursor !== heading && cursor.tagName === 'H2') {
					break;
				}
				section.appendChild(cursor);
				cursor = next;
			}
			sections.push(section);
			index++;
			heading = cursor;
		}

		sections.forEach(function(section) {
			var titleNode = section.querySelector('h2');
			var title = titleNode ? titleNode.textContent.trim() : 'Section';
			var link = document.createElement('a');
			link.href = '#' + section.id;
			link.textContent = title;
			link.addEventListener('click', function(event) {
				event.preventDefault();
				section.scrollIntoView({ behavior: 'smooth', block: 'start' });
				history.replaceState({}, '', '#' + section.id);
			});
			nav.appendChild(link);
		});

		var navLinks = Array.prototype.slice.call(nav.querySelectorAll('a'));
		function setActive(sectionId) {
			navLinks.forEach(function(link) {
				link.classList.toggle('is-active', link.getAttribute('href') === '#' + sectionId);
			});
		}

		if ('IntersectionObserver' in window) {
			var observer = new IntersectionObserver(
				function(entries) {
					entries.forEach(function(entry) {
						if (entry.isIntersecting) {
							setActive(entry.target.id);
							entry.target.classList.add('is-visible');
						}
					});
				},
				{ rootMargin: '-30% 0px -60% 0px', threshold: 0.01 }
			);

			sections.forEach(function(section, index) {
				observer.observe(section);
				setTimeout(function() {
					section.classList.add('is-visible');
				}, 80 * index);
			});
		} else {
			sections.forEach(function(section) {
				section.classList.add('is-visible');
			});
		}

		if (sections[0]) {
			setActive(sections[0].id);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSettingsUi);
	} else {
		initSettingsUi();
	}
})();
