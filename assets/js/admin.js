/**
 * Backorder Flow settings screen.
 *
 * @package Backorder_Flow
 */

(function($) {
	'use strict';

	$(function() {
		var root = document.getElementById('wmbf-settings');
		var dataEl = document.getElementById('wmbf-settings-data');
		if (!root || !dataEl) {
			return;
		}

		var data = JSON.parse(dataEl.textContent);
		var t = data.i18n;
		var statuses = data.statuses || [];
		var savedMessage = data.defaultMessage || '';

		function $1(sel, ctx) { return (ctx || root).querySelector(sel); }
		function $all(sel, ctx) { return Array.prototype.slice.call((ctx || root).querySelectorAll(sel)); }

		function esc(value) {
			return String(value).replace(/[&<>"']/g, function(c) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
			});
		}

		// Minimal sprintf for %s, %d and numbered %1$s / %2$d placeholders.
		function sprintf(str) {
			var args = Array.prototype.slice.call(arguments, 1);
			var i = 0;
			return String(str).replace(/%(\d+\$)?[sd]/g, function(match, num) {
				var value = num ? args[parseInt(num, 10) - 1] : args[i++];
				return value === undefined ? match : value;
			});
		}

		function byId(id) {
			for (var i = 0; i < statuses.length; i++) {
				if (statuses[i].id === id) {
					return statuses[i];
				}
			}
			return null;
		}

		function ajax(action, payload) {
			return $.post(data.ajaxUrl, $.extend({ action: action, nonce: data.nonce }, payload || {})).then(function(response) {
				if (!response || !response.success) {
					return $.Deferred().reject(response && response.data && response.data.message ? response.data.message : t.error);
				}
				return response.data;
			}, function() {
				return $.Deferred().reject(t.error);
			});
		}

		function statusPayload(status, priority) {
			return {
				status_id: status.id || '',
				name: status.name,
				description: status.description || '',
				color: status.color,
				priority: priority,
				enabled: status.enabled ? 'true' : 'false'
			};
		}

		// WooCommerce wraps settings tabs in a form; Enter in our fields must not submit it.
		$('#mainform').on('submit', function(e) {
			e.preventDefault();
		});

		/* ---------- Toasts ---------- */

		function toast(message, options) {
			options = options || {};
			var el = document.createElement('div');
			el.className = 'wmbf-toast' + (options.error ? ' is-error' : '');
			el.innerHTML = '<span>' + esc(message) + '</span>';
			if (options.action) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.textContent = options.action.label;
				btn.addEventListener('click', function() {
					el.remove();
					options.action.fn();
				});
				el.appendChild(btn);
			}
			$1('#wmbf-toasts').appendChild(el);
			setTimeout(function() { el.remove(); }, options.action ? 7000 : 3500);
		}

		function copyText(text, message) {
			var fail = function() { toast(t.copyFailed, { error: true }); };
			try {
				navigator.clipboard.writeText(text).then(function() { toast(message || t.copied); }, fail);
			} catch (e) {
				fail();
			}
		}

		root.addEventListener('click', function(e) {
			var copy = e.target.closest('[data-copy]');
			if (copy) {
				copyText(copy.getAttribute('data-copy'), t.shortcodeCopied);
				return;
			}
			var from = e.target.closest('[data-copy-from]');
			if (from) {
				var el = document.getElementById(from.getAttribute('data-copy-from'));
				copyText('value' in el ? el.value : el.textContent, 'wmbf-f-id' === el.id ? t.idCopied : t.copied);
			}
		});

		/* ---------- Tabs ---------- */

		var TABS = ['statuses', 'message', 'shortcodes', 'import'];

		function showTab(name) {
			if (TABS.indexOf(name) === -1) {
				name = 'statuses';
			}
			$all('.wmbf-nav [data-tab]').forEach(function(btn) {
				btn.setAttribute('aria-selected', String(btn.getAttribute('data-tab') === name));
			});
			$all('[data-panel]').forEach(function(panel) {
				panel.hidden = panel.getAttribute('data-panel') !== name;
			});
			if (window.history && window.history.replaceState) {
				window.history.replaceState(null, '', '#' + name);
			}
			if ('shortcodes' === name) {
				buildShortcode();
			}
			if ('import' === name) {
				renderJson();
			}
		}

		root.addEventListener('click', function(e) {
			var tab = e.target.closest('[data-tab], [data-goto]');
			if (tab) {
				e.preventDefault();
				showTab(tab.getAttribute('data-tab') || tab.getAttribute('data-goto'));
			}
		});

		/* ---------- Statuses list ---------- */

		function plural(n, one, many) {
			return n + ' ' + (1 === n ? one : many);
		}

		function renderStatuses() {
			var body = $1('#wmbf-status-body');
			$1('#wmbf-nav-count').textContent = statuses.length;

			if (!statuses.length) {
				body.innerHTML = '<div class="wmbf-empty">' +
					'<img src="' + esc(data.iconUrl) + '" alt="" width="64" height="64">' +
					'<h3>' + esc(t.emptyTitle) + '</h3>' +
					'<p>' + esc(t.emptyText) + '</p>' +
					'<div class="wmbf-row"><button type="button" class="wmbf-btn wmbf-btn-primary" data-act="add">' + esc(t.addStatus) + '</button>' +
					'<button type="button" class="wmbf-btn" data-act="starter">' + esc(t.starter) + '</button></div>' +
					'<div class="wmbf-starter">' + t.starterNames.map(function(name, i) {
						return pill(name, ['#16CF9E', '#702BB2', '#2F7DE1'][i]);
					}).join('') + '</div></div>';
				return;
			}

			var live = statuses.filter(function(s) { return s.enabled; }).length;
			var used = statuses.reduce(function(sum, s) { return sum + (s.products || 0); }, 0);

			var html = '<div class="wmbf-summary"><span><b>' + statuses.length + '</b>' + esc(t.statuses) + '</span>' +
				'<span class="is-live"><b id="wmbf-live-count">' + live + '</b>' + esc(t.live) + '</span>' +
				'<span><b>' + used + '</b>' + esc(t.assigned) + '</span></div>' +
				'<div class="wmbf-sl-head" aria-hidden="true"><span></span><span>' + esc(t.colStatus) + '</span><span>' + esc(t.colMessage) + '</span>' +
				'<span class="is-r">' + esc(t.colProducts) + '</span><span>' + esc(t.colLive) + '</span><span class="is-r">' + esc(t.colActions) + '</span></div>' +
				'<div class="wmbf-sl" id="wmbf-sl">';

			statuses.forEach(function(s, i) {
				var products = s.products || 0;
				html += '<div class="wmbf-sl-row' + (s.enabled ? '' : ' is-off') + '" data-id="' + esc(s.id) + '">' +
					'<button type="button" class="wmbf-handle" aria-label="' + esc(sprintf(t.moveLabel, s.name, i + 1, statuses.length)) + '">' +
						'<svg class="wmbf-i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="6" r="1.3"/><circle cx="15" cy="6" r="1.3"/><circle cx="9" cy="12" r="1.3"/><circle cx="15" cy="12" r="1.3"/><circle cx="9" cy="18" r="1.3"/><circle cx="15" cy="18" r="1.3"/></svg>' +
						'<span class="wmbf-pos">' + (i + 1) + '</span></button>' +
					'<div class="wmbf-sl-name"><span class="wmbf-sw" style="background:' + esc(s.color) + '"></span><div>' +
						'<strong>' + esc(s.name) + '</strong>' +
						'<button type="button" class="wmbf-idchip" data-copy-id="' + esc(s.id) + '" title="' + esc(t.copyId) + '">' + esc(s.id) + '</button></div></div>' +
					'<div class="wmbf-sl-msg">' + (s.description ? esc(s.description) : '<em>' + esc(t.noMessage) + '</em>') + '</div>' +
					'<div class="wmbf-sl-num is-r">' + products + '<small>' + esc(1 === products ? t.product : t.products) + '</small></div>' +
					'<div><label class="wmbf-toggle"><input type="checkbox" data-toggle' + (s.enabled ? ' checked' : '') + ' aria-label="' + esc(sprintf(t.showLabel, s.name)) + '"><span></span></label></div>' +
					'<div class="wmbf-sl-actions">' +
						'<button type="button" class="wmbf-icon-btn" data-edit aria-label="' + esc(sprintf(t.editLabel, s.name)) + '"><svg class="wmbf-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.12 2.12 0 0 0-3-3L5 17v3z"/><path d="M14 8l3 3"/></svg></button>' +
						'<button type="button" class="wmbf-icon-btn is-danger" data-delete aria-label="' + esc(sprintf(t.deleteLabel, s.name)) + '"><svg class="wmbf-i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7V4.5h6V7M6 7l1 13h10l1-13"/></svg></button>' +
					'</div></div>';
			});

			html += '</div><div class="wmbf-card-foot"><span>' + esc(t.footNote) + ' <a href="#message" data-goto="message">' + esc(t.defaultMessage) + '</a>.</span></div>';
			body.innerHTML = html;

			if ($.fn.sortable) {
				$('#wmbf-sl').sortable({
					handle: '.wmbf-handle',
					axis: 'y',
					placeholder: 'wmbf-sl-placeholder',
					forcePlaceholderSize: true,
					update: function() {
						var order = $all('#wmbf-sl .wmbf-sl-row').map(function(row) { return row.getAttribute('data-id'); });
						statuses.sort(function(a, b) { return order.indexOf(a.id) - order.indexOf(b.id); });
						saveOrder().then(function() { toast(t.orderSaved); });
						renderStatuses();
					}
				});
			}
		}

		function pill(name, color) {
			return '<span class="wmbf-pill" style="--c:' + esc(color) + '"><i></i>' + esc(name) + '</span>';
		}

		function saveOrder() {
			statuses.forEach(function(s, i) { s.priority = i + 1; });
			return ajax('wmbf_update_status_order', { order: statuses.map(function(s) { return s.id; }) }).fail(function(message) {
				toast(message, { error: true });
			});
		}

		var card = $1('#wmbf-status-card');

		card.addEventListener('click', function(e) {
			var act = e.target.closest('[data-act]');
			if (act && 'add' === act.getAttribute('data-act')) {
				openDrawer(null, act);
				return;
			}
			if (act && 'starter' === act.getAttribute('data-act')) {
				act.disabled = true;
				ajax('wmbf_add_starter_statuses').then(function(res) {
					statuses = res.statuses;
					renderStatuses();
					toast(t.starterAdded);
				}, function(message) {
					act.disabled = false;
					toast(message, { error: true });
				});
				return;
			}

			var chip = e.target.closest('[data-copy-id]');
			if (chip) {
				copyText(chip.getAttribute('data-copy-id'), t.idCopied);
				return;
			}

			var row = e.target.closest('.wmbf-sl-row');
			if (!row) {
				return;
			}
			var status = byId(row.getAttribute('data-id'));
			if (e.target.closest('[data-edit]')) {
				openDrawer(status, e.target.closest('[data-edit]'));
			} else if (e.target.closest('[data-delete]')) {
				confirmDelete(status, e.target.closest('[data-delete]'));
			}
		});

		card.addEventListener('change', function(e) {
			if (!e.target.matches('[data-toggle]')) {
				return;
			}
			var input = e.target;
			var row = input.closest('.wmbf-sl-row');
			var status = byId(row.getAttribute('data-id'));
			status.enabled = input.checked;
			row.classList.toggle('is-off', !status.enabled);
			$1('#wmbf-live-count').textContent = statuses.filter(function(s) { return s.enabled; }).length;

			ajax('wmbf_add_status', statusPayload(status, statuses.indexOf(status) + 1)).then(function() {
				toast(sprintf(status.enabled ? t.turnedOn : t.turnedOff, status.name));
			}, function(message) {
				status.enabled = !status.enabled;
				renderStatuses();
				toast(message, { error: true });
			});
		});

		card.addEventListener('keydown', function(e) {
			var handle = e.target.closest('.wmbf-handle');
			if (!handle || ('ArrowUp' !== e.key && 'ArrowDown' !== e.key)) {
				return;
			}
			e.preventDefault();
			var id = handle.closest('.wmbf-sl-row').getAttribute('data-id');
			var from = statuses.indexOf(byId(id));
			var to = 'ArrowUp' === e.key ? from - 1 : from + 1;
			if (to < 0 || to >= statuses.length) {
				return;
			}
			var moved = statuses.splice(from, 1)[0];
			statuses.splice(to, 0, moved);
			renderStatuses();
			$1('.wmbf-sl-row[data-id="' + id + '"] .wmbf-handle').focus();
			saveOrder().then(function() { toast(sprintf(t.moved, moved.name, to + 1)); });
		});

		/* ---------- Drawer (add / edit) ---------- */

		var drawer = $1('#wmbf-drawer');
		var scrim = $1('#wmbf-scrim');
		var editing = null;
		var color = data.palette[0];
		var returnFocus = null;

		function openDrawer(status, opener) {
			editing = status;
			returnFocus = opener || null;
			$1('#wmbf-d-title').textContent = status ? t.editStatus : t.addStatus;
			$1('#wmbf-d-sub').textContent = status
				? (status.products ? sprintf(1 === status.products ? t.usedOnOne : t.usedOnMany, status.products) : t.notUsed)
				: t.addSub;
			$1('#wmbf-f-name').value = status ? status.name : '';
			$1('#wmbf-f-msg').value = status ? status.description : '';
			$1('#wmbf-f-on').checked = status ? status.enabled : true;
			color = status ? status.color : data.palette[statuses.length % data.palette.length];
			$1('#wmbf-f-id-wrap').hidden = !status;
			$1('#wmbf-f-id').value = status ? status.id : '';
			$1('#wmbf-d-save').textContent = status ? t.saveChanges : t.addStatus;
			$1('#wmbf-d-save').disabled = false;
			showNameError(false);
			renderSwatches();
			updatePreview();

			scrim.hidden = false;
			drawer.hidden = false;
			requestAnimationFrame(function() {
				scrim.classList.add('is-open');
				drawer.classList.add('is-open');
				setTimeout(function() { $1('#wmbf-f-name').focus(); }, 120);
			});
		}

		function closeDrawer() {
			scrim.classList.remove('is-open');
			drawer.classList.remove('is-open');
			setTimeout(function() {
				scrim.hidden = true;
				drawer.hidden = true;
			}, 220);
			if (returnFocus && document.body.contains(returnFocus)) {
				returnFocus.focus();
			}
		}

		function renderSwatches() {
			var html = data.palette.map(function(c) {
				return '<button type="button" style="background:' + c + '" data-color="' + c + '" aria-label="' + esc(sprintf(t.colorLabel, c)) + '" aria-pressed="' + String(c.toLowerCase() === color.toLowerCase()) + '"></button>';
			}).join('');
			html += '<span class="wmbf-hex"><input type="color" id="wmbf-f-color" value="' + esc(color) + '" aria-label="' + esc(t.customColor) + '">' +
				'<input type="text" id="wmbf-f-hex" value="' + esc(color) + '" maxlength="7" aria-label="' + esc(t.customColor) + '"></span>';
			$1('#wmbf-swatches').innerHTML = html;
		}

		function setColor(value) {
			color = value;
			$all('#wmbf-swatches [data-color]').forEach(function(btn) {
				btn.setAttribute('aria-pressed', String(btn.getAttribute('data-color').toLowerCase() === color.toLowerCase()));
			});
			updatePreview();
		}

		$1('#wmbf-swatches').addEventListener('click', function(e) {
			var btn = e.target.closest('[data-color]');
			if (btn) {
				setColor(btn.getAttribute('data-color'));
				$1('#wmbf-f-color').value = color;
				$1('#wmbf-f-hex').value = color;
			}
		});

		$1('#wmbf-swatches').addEventListener('input', function(e) {
			if ('wmbf-f-color' === e.target.id) {
				$1('#wmbf-f-hex').value = e.target.value;
				setColor(e.target.value);
			} else if ('wmbf-f-hex' === e.target.id && /^#[0-9a-f]{6}$/i.test(e.target.value)) {
				$1('#wmbf-f-color').value = e.target.value;
				setColor(e.target.value);
			}
		});

		function updatePreview() {
			var name = $1('#wmbf-f-name').value.trim() || t.statusName;
			var msg = $1('#wmbf-f-msg').value.trim();
			var preview = $1('#wmbf-f-preview');
			preview.style.setProperty('--c', color);
			$all('[data-pv="name"]', preview).forEach(function(el) { el.textContent = name; });
			$all('[data-pv="msg"]', preview).forEach(function(el) {
				el.textContent = msg;
				el.hidden = !msg;
			});
			$1('#wmbf-f-msg-count').textContent = $1('#wmbf-f-msg').value.length + '/200';
		}

		function showNameError(show) {
			$1('#wmbf-f-name').classList.toggle('is-error', show);
			$1('#wmbf-f-name-err').hidden = !show;
			$1('#wmbf-f-name-err span').textContent = t.nameRequired;
		}

		$1('#wmbf-f-name').addEventListener('input', function() {
			if (this.value.trim()) {
				showNameError(false);
			}
			updatePreview();
		});
		$1('#wmbf-f-msg').addEventListener('input', updatePreview);

		$1('#wmbf-d-save').addEventListener('click', function() {
			var name = $1('#wmbf-f-name').value.trim();
			if (!name) {
				showNameError(true);
				$1('#wmbf-f-name').focus();
				return;
			}

			var save = this;
			var draft = {
				id: editing ? editing.id : '',
				name: name,
				description: $1('#wmbf-f-msg').value.trim(),
				color: color,
				enabled: $1('#wmbf-f-on').checked
			};
			var priority = editing ? statuses.indexOf(editing) + 1 : statuses.length + 1;

			save.disabled = true;
			save.textContent = t.saving;

			ajax('wmbf_add_status', statusPayload(draft, priority)).then(function(res) {
				var saved = res.status;
				if (editing) {
					editing.name = saved.name;
					editing.description = saved.description;
					editing.color = saved.color;
					editing.enabled = !!saved.enabled;
					toast(sprintf(t.saved, saved.name));
				} else {
					statuses.push({
						id: saved.id,
						name: saved.name,
						description: saved.description,
						color: saved.color,
						priority: priority,
						enabled: !!saved.enabled,
						products: 0
					});
					toast(sprintf(t.added, saved.name));
				}
				closeDrawer();
				renderStatuses();
			}, function(message) {
				save.disabled = false;
				save.textContent = editing ? t.saveChanges : t.addStatus;
				toast(message, { error: true });
			});
		});

		root.addEventListener('click', function(e) {
			if (e.target.closest('[data-close="drawer"]') || e.target === scrim) {
				closeDrawer();
			}
		});

		$1('#wmbf-drawer').addEventListener('keydown', function(e) {
			if ('Enter' === e.key && 'INPUT' === e.target.tagName && 'checkbox' !== e.target.type) {
				e.preventDefault();
				$1('#wmbf-d-save').click();
			}
		});

		/* ---------- Confirm dialog ---------- */

		var dlg = $1('#wmbf-dlg');
		var dlgScrim = $1('#wmbf-dlg-scrim');
		var onConfirm = null;
		var dlgReturn = null;

		function dialog(options) {
			$1('#wmbf-dlg-title').textContent = options.title;
			$1('#wmbf-dlg-text').textContent = options.text;
			$1('#wmbf-dlg-yes').textContent = options.yes;
			$1('#wmbf-dlg-no').textContent = t.cancel;
			onConfirm = options.onConfirm;
			dlgReturn = options.opener || null;
			dlgScrim.hidden = false;
			dlg.hidden = false;
			requestAnimationFrame(function() {
				dlgScrim.classList.add('is-open');
				dlg.classList.add('is-open');
				$1('#wmbf-dlg-no').focus();
			});
		}

		function closeDialog() {
			dlgScrim.classList.remove('is-open');
			dlg.classList.remove('is-open');
			setTimeout(function() {
				dlgScrim.hidden = true;
				dlg.hidden = true;
			}, 180);
			if (dlgReturn && document.body.contains(dlgReturn)) {
				dlgReturn.focus();
			}
		}

		$1('#wmbf-dlg-no').addEventListener('click', closeDialog);
		dlgScrim.addEventListener('click', closeDialog);
		$1('#wmbf-dlg-yes').addEventListener('click', function() {
			closeDialog();
			if (onConfirm) {
				onConfirm();
			}
		});

		document.addEventListener('keydown', function(e) {
			if ('Escape' !== e.key) {
				return;
			}
			if (!dlg.hidden) {
				closeDialog();
			} else if (!drawer.hidden) {
				closeDrawer();
			}
		});

		function confirmDelete(status, opener) {
			var products = status.products || 0;
			dialog({
				title: sprintf(t.deleteTitle, status.name),
				text: products ? sprintf(1 === products ? t.deleteUsedOne : t.deleteUsedMany, products) : t.deleteUnused,
				yes: t.deleteBtn,
				opener: opener,
				onConfirm: function() {
					var index = statuses.indexOf(status);
					ajax('wmbf_delete_status', { status_id: status.id }).then(function() {
						statuses.splice(index, 1);
						renderStatuses();
						toast(sprintf(t.deleted, status.name), {
							action: {
								label: t.undo,
								fn: function() { restoreStatus(status, index); }
							}
						});
					}, function(message) {
						toast(message, { error: true });
					});
				}
			});
		}

		function restoreStatus(status, index) {
			ajax('wmbf_add_status', statusPayload(status, index + 1)).then(function() {
				statuses.splice(Math.min(index, statuses.length), 0, status);
				renderStatuses();
				return saveOrder();
			}).then(function() {
				toast(sprintf(t.restored, status.name));
			}, function(message) {
				toast(message, { error: true });
			});
		}

		/* ---------- Default message ---------- */

		var msgInput = $1('#wmbf-default-message');

		function syncMessage() {
			var value = msgInput.value;
			var dirty = value !== savedMessage;
			$1('#wmbf-default-preview').textContent = value.trim() || '…';
			$1('#wmbf-default-count').textContent = value.length + '/200';
			$1('#wmbf-savebar').classList.toggle('is-clean', !dirty);
			$1('#wmbf-save-text').textContent = dirty ? t.unsaved : t.allSaved;
			$1('#wmbf-discard').hidden = !dirty;
			$1('#wmbf-save-message').disabled = !dirty;
			if (value.trim()) {
				$1('#wmbf-default-err').hidden = true;
				msgInput.classList.remove('is-error');
			}
		}

		msgInput.addEventListener('input', syncMessage);

		$1('#wmbf-discard').addEventListener('click', function() {
			msgInput.value = savedMessage;
			syncMessage();
		});

		$1('#wmbf-save-message').addEventListener('click', function() {
			var value = msgInput.value.trim();
			if (!value) {
				$1('#wmbf-default-err').hidden = false;
				msgInput.classList.add('is-error');
				msgInput.focus();
				return;
			}
			var btn = this;
			btn.disabled = true;
			ajax('wmbf_save_default_message', { message: value }).then(function() {
				savedMessage = value;
				msgInput.value = value;
				syncMessage();
				toast(t.msgSaved);
			}, function(message) {
				btn.disabled = false;
				toast(message, { error: true });
			});
		});

		window.addEventListener('beforeunload', function(e) {
			if (msgInput.value !== savedMessage) {
				e.preventDefault();
				e.returnValue = '';
			}
		});

		/* ---------- Shortcode builder ---------- */

		var shortcodeText = '';

		function buildShortcode() {
			var type = $1('#wmbf-b-type').value;
			var select = $1('#wmbf-b-status');
			var current = select.value;

			select.innerHTML = statuses.map(function(s) {
				return '<option value="' + esc(s.id) + '">' + esc(s.name) + (s.enabled ? '' : ' ' + esc(t.hidden)) + '</option>';
			}).join('');
			if (current && byId(current)) {
				select.value = current;
			}

			var status = byId(select.value);
			$1('#wmbf-b-status-wrap').hidden = 'products' !== type;
			$1('#wmbf-b-product-wrap').hidden = 'badge' !== type;
			$1('#wmbf-b-dot').style.background = status ? status.color : '#c3c4c7';

			var html;
			if ('products' === type) {
				var id = status ? status.id : 'status-id';
				shortcodeText = '[wmbf_backorder_products status="' + id + '"]';
				html = '<span class="k">[wmbf_backorder_products</span> <span class="a">status</span>=<span class="v">"' + esc(id) + '"</span><span class="k">]</span>';
			} else if ('badge' === type) {
				var product = parseInt($1('#wmbf-b-product').value, 10) || 123;
				shortcodeText = '[wmbf_backorder_badge product_id="' + product + '"]';
				html = '<span class="k">[wmbf_backorder_badge</span> <span class="a">product_id</span>=<span class="v">"' + product + '"</span><span class="k">]</span>';
			} else {
				shortcodeText = 'status' === type ? '[wmbf_backorder_status]' : '[wmbf_backorder_groups]';
				html = '<span class="k">' + shortcodeText + '</span>';
			}
			$1('#wmbf-b-out').innerHTML = html;
		}

		['wmbf-b-type', 'wmbf-b-status', 'wmbf-b-product'].forEach(function(id) {
			$1('#' + id).addEventListener('input', buildShortcode);
		});
		$1('#wmbf-b-copy').addEventListener('click', function() {
			copyText(shortcodeText, t.shortcodeCopied);
		});

		/* ---------- Import / export ---------- */

		function exportData() {
			return statuses.map(function(s, i) {
				return { id: s.id, name: s.name, description: s.description, color: s.color, priority: i + 1, enabled: s.enabled };
			});
		}

		function renderJson() {
			$1('#wmbf-json-out').textContent = JSON.stringify(exportData(), null, 2);
		}

		$1('#wmbf-export').addEventListener('click', function() {
			var blob = new Blob([JSON.stringify(exportData(), null, 2)], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var link = document.createElement('a');
			link.href = url;
			link.download = 'backorder-flow-statuses-' + new Date().toISOString().slice(0, 10) + '.json';
			document.body.appendChild(link);
			link.click();
			link.remove();
			setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
			toast(sprintf(t.exported, statuses.length));
		});

		var pending = null;

		function reviewImport(list) {
			pending = list;
			var currentNames = statuses.map(function(s) { return s.name; });
			var incomingNames = list.map(function(s) { return s.name; });

			var rows = list.map(function(s) {
				var replaces = currentNames.indexOf(s.name) !== -1;
				return '<div class="wmbf-diff-row"><span class="wmbf-sw is-sm" style="background:' + esc(/^#[0-9a-f]{3,6}$/i.test(s.color || '') ? s.color : '#c3c4c7') + '"></span>' +
					esc(s.name) + '<span class="wmbf-tag' + (replaces ? '' : ' is-mint') + '">' + esc(replaces ? t.importReplaces : t.importNew) + '</span></div>';
			});
			statuses.forEach(function(s) {
				if (incomingNames.indexOf(s.name) === -1) {
					rows.push('<div class="wmbf-diff-row is-removed"><span class="wmbf-sw is-sm" style="background:' + esc(s.color) + '"></span><s>' + esc(s.name) + '</s>' +
						'<span class="wmbf-tag is-cream">' + esc(t.importRemoved) + (s.products ? ' · ' + esc(sprintf(t.importAffected, s.products)) : '') + '</span></div>');
				}
			});

			$1('#wmbf-diff').innerHTML = rows.join('');
			$1('#wmbf-import-go').textContent = sprintf(t.importBtn, list.length);
			$1('#wmbf-import-go').disabled = false;
			$1('#wmbf-import-review').hidden = false;
		}

		function readFile(file) {
			if (!file) {
				return;
			}
			var reader = new FileReader();
			reader.onload = function() {
				var list;
				try {
					var parsed = JSON.parse(reader.result);
					list = Array.isArray(parsed) ? parsed : (parsed && Array.isArray(parsed.data) ? parsed.data : null);
				} catch (err) {
					list = null;
				}
				list = (list || []).filter(function(s) { return s && 'object' === typeof s && s.name; });
				if (!list.length) {
					toast(sprintf(t.importBadFile, file.name), { error: true });
					return;
				}
				reviewImport(list);
			};
			reader.readAsText(file);
		}

		$1('#wmbf-import-file').addEventListener('change', function(e) {
			readFile(e.target.files[0]);
			e.target.value = '';
		});

		var drop = $1('#wmbf-drop');
		['dragenter', 'dragover'].forEach(function(type) {
			drop.addEventListener(type, function(e) {
				e.preventDefault();
				drop.classList.add('is-over');
			});
		});
		['dragleave', 'drop'].forEach(function(type) {
			drop.addEventListener(type, function(e) {
				e.preventDefault();
				drop.classList.remove('is-over');
			});
		});
		drop.addEventListener('drop', function(e) {
			readFile(e.dataTransfer.files[0]);
		});

		$1('#wmbf-import-cancel').addEventListener('click', function() {
			pending = null;
			$1('#wmbf-import-review').hidden = true;
		});

		$1('#wmbf-import-go').addEventListener('click', function() {
			var btn = this;
			btn.disabled = true;
			ajax('wmbf_import_statuses', { data: JSON.stringify(pending) }).then(function(res) {
				statuses = res.statuses;
				pending = null;
				$1('#wmbf-import-review').hidden = true;
				renderStatuses();
				renderJson();
				toast(sprintf(t.imported, statuses.length));
			}, function(message) {
				btn.disabled = false;
				toast(message, { error: true });
			});
		});

		/* ---------- Boot ---------- */

		renderStatuses();
		syncMessage();
		showTab(window.location.hash.replace('#', ''));
	});
})(jQuery);
