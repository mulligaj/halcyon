/* global TomSelect */ // vendor/tom-select/js/tom-select.complete.min.js

document.addEventListener('DOMContentLoaded', function () {
	var alias = document.getElementById('field-alias');
	if (alias && !alias.value) {
		document.getElementById('field-title').addEventListener('keyup', function () {
			alias.value = this.value.toLowerCase()
				.replace(/\s+/g, '-')
				.replace(/[^a-zA-Z0-9\-_.]+/g, '');
		});
	}

	document.querySelector('body').addEventListener('click', function (e) {
		if (e.target.matches('.delete-row') || e.target.matches('.icon-trash')) {
			e.preventDefault();
			var el = e.target;
			if (e.target.matches('.icon-trash')) {
				el = e.target.parentNode;
			}
			document.querySelector(el.getAttribute('href')).remove();
		}
	});

	document.querySelectorAll('.add-row').forEach(function (el) {
		el.addEventListener('click', function (e) {
			e.preventDefault();

			var tr = document.getElementById(this.getAttribute('data-container')).querySelector('.d-none');

			var clone = tr.cloneNode(true);
			clone.classList.remove('d-none');
			clone.querySelectorAll('.btn').forEach(function (b) {
				b.classList.remove('disabled');
			});

			var cindex = document.getElementById(this.getAttribute('data-container')).querySelectorAll('.input-group').length;
			var inputs = clone.querySelectorAll('input,select');

			clone.setAttribute('id', clone.getAttribute('id').replace(/-\d+/, '-' + cindex));

			inputs.forEach(function (el) {
				el.value = '';
				el.setAttribute('name', el.getAttribute('name').replace(/\[\d+\]/, '[' + cindex + ']'));
				el.setAttribute('id', el.getAttribute('id').replace(/-\d+/, '-' + cindex));
			});

			clone.querySelectorAll('a').forEach(function (el) {
				el.setAttribute('href', el.getAttribute('href').replace(/-\d+/, '-' + cindex));
			});

			tr.parentNode.insertBefore(clone, tr);
		});
	});

	var select = document.getElementById('field-parent_id');
	if (select) {
		if (typeof TomSelect !== 'undefined') {
			var sel = new TomSelect(select, {
				plugins: ['dropdown_input'],
				render: {
					option: function (data, escape) {
						return '<div>' +
							'<span class="indent d-inline-block">' + escape(data.indent) + '</span>' + 
							'<span class="d-inline-block">' +
								'<span class="text">' + escape(data.text.replace(data.indent, '')) + '</span><br />' +
								'<span class="path text-muted">' + escape(data.path) + '</span>' +
							'</span>' +
							'</div>';
					},
					item: function (data, escape) {
						return '<div title="' + escape(data.path) + '">' + escape(data.text) + '</div>';
					}
				}
			});
			sel.on('change', function () {
				document.getElementById('parent-path').innerHTML = this.input.selectedOptions[0].getAttribute('data-path');
				document.getElementById('field-access').value = this.input.selectedOptions[0].getAttribute('data-access');
			});
		} else {
			select.addEventListener('change', function () {
				document.getElementById('parent-path').innerHTML = this.selectedOptions[0].getAttribute('data-path');
				document.getElementById('field-access').value = this.input.selectedOptions[0].getAttribute('data-access');
			});
		}
	}

	var taggables = document.querySelectorAll('.taggable');
	if (taggables.length) {
		taggables.forEach(function (el) {
			var sel = new TomSelect(el, {
				plugins: {
					remove_button: {
						title: 'Remove this tag',
					}
				},
				persist: false,
				//createOnBlur: true,
				create: true,
				valueField: 'slug',
				labelField: 'name',
				searchField: 'name',
				load: function (query, callback) {
					var url = el.getAttribute('data-api') + '?search=' + encodeURIComponent(query);
					fetch(url)
						.then(response => response.json())
						.then(json => {
							callback(json.data);
						}).catch(() => {
							callback();
						});
				}
			});
		});
	}
});
