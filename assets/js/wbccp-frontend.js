(function() {
	function initRecurrenceToggle() {
		var containers = document.querySelectorAll('.wbccp-recurrence-fields');
		if (!containers.length) {
			return;
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

			if (monthday) {
				var useNth = bysetpos && byweekday && bysetpos.value && byweekday.value;
				monthday.style.display = isEnabled && (f === 'MONTHLY' || f === 'YEARLY') && !useNth ? '' : 'none';
			}
		}

		containers.forEach(function(container) {
			update(container);
			container.addEventListener('change', function() {
				update(container);
			});
		});
	}

	function initRsvpAjax() {
		if (!window.wbccpData || !window.wbccpData.ajaxUrl) {
			return;
		}

		function setMessage(container, message, isError) {
			if (!container) {
				return;
			}
			container.textContent = message || '';
			container.classList.toggle('is-error', !!isError);
			if (message) {
				clearTimeout(container._wbccpTimer);
				container._wbccpTimer = setTimeout(function() {
					container.textContent = '';
					container.classList.remove('is-error');
				}, 4000);
			}
		}

		function updateCounts(eventId, counts) {
			if (!eventId || !counts) {
				return;
			}
			var countBlocks = document.querySelectorAll('.wbccp-event-rsvp-counts[data-event-id="' + eventId + '"]');
			countBlocks.forEach(function(block) {
				var attending = block.querySelector('[data-count="attending"]');
				var maybe = block.querySelector('[data-count="maybe"]');
				var cant = block.querySelector('[data-count="cant"]');
				if (attending) {
					if (attending.classList.contains('wbccp-rsvp-stat__value')) {
						attending.textContent = String(counts.attending || 0);
					} else {
						attending.textContent = 'Attending: ' + (counts.attending || 0);
					}
				}
				if (maybe) {
					if (maybe.classList.contains('wbccp-rsvp-stat__value')) {
						maybe.textContent = String(counts.maybe || 0);
					} else {
						maybe.textContent = 'Maybe: ' + (counts.maybe || 0);
					}
				}
				if (cant) {
					if (cant.classList.contains('wbccp-rsvp-stat__value')) {
						cant.textContent = String(counts.cant || 0);
					} else {
						cant.textContent = "Can't: " + (counts.cant || 0);
					}
				}
				var card = block.closest('.wbccp-event-rsvp');
				var total = card ? card.querySelector('[data-count="total"]') : null;
				if (total) {
					total.textContent = String((counts.attending || 0) + (counts.maybe || 0) + (counts.cant || 0));
				}
			});
		}

		function updateCapacity(eventId, counts, capacityValue) {
			if (!eventId || !counts) {
				return;
			}
			var spotBlocks = document.querySelectorAll('.wbccp-event-spots[data-event-id="' + eventId + '"]');
			spotBlocks.forEach(function(block) {
				var capacity = capacityValue || parseInt(block.getAttribute('data-capacity') || '0', 10);
				if (!capacity) {
					return;
				}
				var total = (counts.attending || 0) + (counts.maybe || 0);
				var remaining = capacity - total;
				block.textContent = remaining > 0 ? ('Spots left: ' + remaining) : 'Event is full.';
			});
		}

		function updateStatusBadge(eventId, status) {
			if (!eventId) {
				return;
			}
			var statusLabels = {
				attending: 'Attending',
				maybe: 'Maybe',
				cant: 'Cannot attend'
			};
			var statusText = status ? (statusLabels[status] || status) : '';
			var forms = document.querySelectorAll('.wbccp-event-rsvp-actions[data-event-id="' + eventId + '"]');
			forms.forEach(function(form) {
				var card = form.closest('.wbccp-event-rsvp');
				if (!card) {
					return;
				}
				var badge = card.querySelector('[data-current-status]');
				if (!badge) {
					return;
				}
				if (statusText) {
					badge.textContent = 'Your status: ' + statusText;
					badge.classList.remove('is-empty');
				} else {
					badge.textContent = 'No RSVP yet';
					badge.classList.add('is-empty');
				}
			});
			var badge = document.querySelector('.wbccp-event-rsvp[data-event-id="' + eventId + '"]');
			if (badge) {
				badge.textContent = status ? status.toUpperCase() : '';
			}
		}

		function updateButtons(eventId, status) {
			var buttons = document.querySelectorAll('.wbccp-event-rsvp-actions[data-event-id="' + eventId + '"], .wbccp-rsvp-form[data-event-id="' + eventId + '"]');
			buttons.forEach(function(container) {
				var btns = container.querySelectorAll('.wbccp-rsvp-button');
				btns.forEach(function(btn) {
					var isActive = btn.getAttribute('data-status') === status;
					btn.classList.toggle('is-active', isActive);
					btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
				});
			});
		}

		function setButtonsDisabled(eventId, disabled) {
			var buttons = document.querySelectorAll('.wbccp-event-rsvp-actions[data-event-id="' + eventId + '"], .wbccp-rsvp-form[data-event-id="' + eventId + '"]');
			buttons.forEach(function(container) {
				var btns = container.querySelectorAll('button');
				btns.forEach(function(btn) {
					btn.disabled = disabled;
				});
			});
		}

		document.addEventListener('submit', function(event) {
			var form = event.target;
			if (!form || !(form.classList.contains('wbccp-event-rsvp-actions') || form.classList.contains('wbccp-rsvp-form'))) {
				return;
			}

			event.preventDefault();

			var submitter = event.submitter || null;
			var formData = new FormData(form);
			if (submitter && submitter.name) {
				formData.set(submitter.name, submitter.value);
			}

			var eventId = formData.get('wbccp_event_id') || form.getAttribute('data-event-id');
			var status = formData.get('wbccp_status') || (submitter ? submitter.value : '');
			if (!eventId || !status) {
				return;
			}

			var messageBox = null;
			var rsvpCard = form.closest('.wbccp-event-rsvp');
			if (rsvpCard) {
				messageBox = rsvpCard.querySelector('.wbccp-event-rsvp-message');
			} else {
				var eventItem = form.closest('.wbccp-event-item');
				if (eventItem) {
					messageBox = eventItem.querySelector('.wbccp-event-rsvp-message');
				}
			}

			setButtonsDisabled(eventId, true);

			var payload = new URLSearchParams();
			payload.set('action', 'wbccp_rsvp');
			payload.set('nonce', wbccpData.nonce || '');
			payload.set('event_id', eventId);
			payload.set('status', status);

			fetch(wbccpData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: payload.toString()
			})
				.then(function(response) {
					return response.json();
				})
				.then(function(data) {
					if (data && data.success) {
						setMessage(messageBox, (data.data && data.data.message) || wbccpData.messages.success, false);
						updateCounts(eventId, data.data.counts || {});
						updateCapacity(eventId, data.data.counts || {}, data.data.capacity || 0);
						updateStatusBadge(eventId, data.data.status || status);
						updateButtons(eventId, data.data.status || status);
					} else {
						setMessage(messageBox, (data && data.data && data.data.message) || wbccpData.messages.error, true);
						if (data && data.data && data.data.counts) {
							updateCounts(eventId, data.data.counts);
							updateCapacity(eventId, data.data.counts, data.data.capacity || 0);
						}
					}
				})
				.catch(function() {
					setMessage(messageBox, wbccpData.messages.error, true);
				})
				.finally(function() {
					setButtonsDisabled(eventId, false);
				});
		});

		document.querySelectorAll('.wbccp-rsvp-button').forEach(function(btn) {
			btn.setAttribute('aria-pressed', btn.classList.contains('is-active') ? 'true' : 'false');
		});
	}

	function initLocalTime() {
		if (!window.wbccpData || !wbccpData.showViewerTime) {
			return;
		}
		var elements = document.querySelectorAll('.wbccp-event-local-time[data-start-ts]');
		if (!elements.length) {
			return;
		}
		var dateFormatter = null;
		var timeFormatter = null;
		var dateTimeFormatter = null;
		if (window.Intl && Intl.DateTimeFormat) {
			try {
				dateFormatter = new Intl.DateTimeFormat(undefined, {
					year: 'numeric',
					month: 'short',
					day: 'numeric'
				});
				timeFormatter = new Intl.DateTimeFormat(undefined, {
					hour: 'numeric',
					minute: '2-digit'
				});
				dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
					year: 'numeric',
					month: 'short',
					day: 'numeric',
					hour: 'numeric',
					minute: '2-digit'
				});
			} catch (e) {
				dateFormatter = null;
				timeFormatter = null;
				dateTimeFormatter = null;
			}
		}

		elements.forEach(function(el) {
			var start = parseInt(el.getAttribute('data-start-ts') || '0', 10);
			if (!start) {
				return;
			}
			var end = parseInt(el.getAttribute('data-end-ts') || '0', 10);
			var startDate = new Date(start * 1000);
			var startDateText = dateFormatter ? dateFormatter.format(startDate) : startDate.toLocaleDateString();
			var startTimeText = timeFormatter ? timeFormatter.format(startDate) : startDate.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
			var text = startDateText + ' • ' + startTimeText;
			if (end) {
				var endDate = new Date(end * 1000);
				var endTimeText = timeFormatter ? timeFormatter.format(endDate) : endDate.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
				var sameDay = startDate.getFullYear() === endDate.getFullYear() && startDate.getMonth() === endDate.getMonth() && startDate.getDate() === endDate.getDate();
				if (sameDay) {
					text = startDateText + ' • ' + startTimeText + ' - ' + endTimeText;
				} else {
					var endText = dateTimeFormatter ? dateTimeFormatter.format(endDate) : endDate.toLocaleString();
					text = (dateTimeFormatter ? dateTimeFormatter.format(startDate) : startDate.toLocaleString()) + ' - ' + endText;
				}
			}
			el.textContent = text;
		});
	}

	function initViewToggle() {
		var containers = document.querySelectorAll('.wbccp-view-container');
		if (!containers.length) {
			return;
		}

		containers.forEach(function(container) {
			var defaultView = container.getAttribute('data-default-view') || 'list';
			var tabs = container.querySelectorAll('.wbccp-view-toggle__tab[data-view]');
			var panels = container.querySelectorAll('.wbccp-view-panel[data-view]');

			function setActive(view) {
				panels.forEach(function(panel) {
					var isActive = panel.getAttribute('data-view') === view;
					panel.classList.toggle('is-active', isActive);
					panel.hidden = !isActive;
				});

				tabs.forEach(function(tab) {
					var isActive = tab.getAttribute('data-view') === view;
					tab.classList.toggle('is-active', isActive);
					tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
					tab.setAttribute('aria-current', isActive ? 'page' : 'false');
				});
			}

			function updateUrl(view) {
				try {
					var url = new URL(window.location.href);
					url.searchParams.set('wbccp_view', view);
					window.history.replaceState({}, '', url.toString());
				} catch (e) {
					// Ignore URL update errors.
				}
			}

			setActive(defaultView);

			tabs.forEach(function(tab) {
				tab.addEventListener('click', function(event) {
					event.preventDefault();
					var view = tab.getAttribute('data-view');
					setActive(view);
					updateUrl(view);
				});
			});
		});
	}

	function initAccordionHash() {
		function openFromHash() {
			if (!window.location.hash) {
				return;
			}
			var target = document.querySelector(window.location.hash + '.wbccp-accordion');
			if (target && !target.open) {
				target.open = true;
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}

		openFromHash();
		window.addEventListener('hashchange', openFromHash);
	}

	function initNotices() {
		var notices = document.querySelectorAll('[data-wbccp-notice]');
		if (!notices.length) {
			return;
		}

		notices.forEach(function(notice) {
			if (notice.scrollIntoView) {
				notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}

			var dismiss = notice.querySelector('.wbccp-notice-dismiss');
			if (dismiss) {
				dismiss.addEventListener('click', function() {
					notice.remove();
				});
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			initRecurrenceToggle();
			initRsvpAjax();
			initLocalTime();
			initViewToggle();
			initAccordionHash();
			initNotices();
		});
	} else {
		initRecurrenceToggle();
		initRsvpAjax();
		initLocalTime();
		initViewToggle();
		initAccordionHash();
		initNotices();
	}
})();
