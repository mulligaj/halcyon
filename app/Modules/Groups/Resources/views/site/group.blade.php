@push('styles')
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/tom-select/css/tom-select.bootstrap4.min.css?v=' . filemtime(public_path('/modules/core/vendor/tom-select/css/tom-select.bootstrap4.min.css'))) }}" />
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/media/vendor/dropzone-5.7.0/dist/min/dropzone.min.css') . '?v=' . filemtime(public_path() . '/modules/media/vendor/dropzone-5.7.0/dist/min/dropzone.min.css') }}" />
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/datatables/dataTables.bootstrap4.min.css?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/dataTables.bootstrap4.min.css')) }}" />
@endpush

@push('scripts')
<script src="{{ asset('modules/core/vendor/tom-select/js/tom-select.complete.min.js?v=' . filemtime(public_path('/modules/core/vendor/tom-select/js/tom-select.complete.min.js'))) }}"></script>
<script src="{{ asset('modules/core/vendor/handlebars/handlebars.min-v4.7.7.js?v=' . filemtime(public_path() . '/modules/core/vendor/handlebars/handlebars.min-v4.7.7.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/datatables/datatables.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/datatables.min.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/datatables/dataTables.bootstrap4.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>
<script src="{{ asset('modules/groups/js/motd.js?v=' . filemtime(public_path() . '/modules/groups/js/motd.js')) }}"></script>
<script src="{{ asset('modules/groups/js/userrequests.js?v=' . filemtime(public_path() . '/modules/groups/js/userrequests.js')) }}"></script>
<script src="{{ asset('modules/groups/js/site.js?v=' . filemtime(public_path() . '/modules/groups/js/site.js')) }}"></script>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.reveal').forEach(function (item) {
			item.addEventListener('click', function (e) {
				document.querySelectorAll(this.getAttribute('data-toggle')).forEach(function(el) {
					el.classList.toggle('hide');
				});

				var text = this.getAttribute('data-text');
				this.setAttribute('data-text', this.innerHTML);
				this.innerHTML = text;
			});
		});

		$('.list-group').on('click', '.delete-row', function(e){
				e.preventDefault();

				var result = confirm('Are you sure you want to remove this?');

				if (result) {
					var container = this.closest('li');
					container.remove();
				}
		});

		document.querySelectorAll('.edit-property').forEach(function(el) {
			el.addEventListener('click', function(e){
				e.preventDefault();

				var items = ['SPAN', 'INPUT', 'CANCEL', 'SAVE', 'EDIT'], item;
				for (var i = 0; i < items.length; i++)
				{
					item = document.getElementById(items[i] + '_' + this.getAttribute('data-prop') + '_' + this.getAttribute('data-value'));
					if (item) {
						item.classList.toggle('hide');
					}
				}
			});
		});

		document.querySelectorAll('.edit-property-input').forEach(function(el) {
			el.addEventListener('keyup', function(event){
				if (event.keyCode == 13) {
					EditProperty(
						this.getAttribute('data-prop'),
						this.getAttribute('data-value')
					);
				}
			});
		});

		document.querySelectorAll('.cancel-edit-property').forEach(function(el) {
			el.addEventListener('click', function(e){
				e.preventDefault();

				var items = ['SPAN', 'INPUT', 'CANCEL', 'SAVE', 'EDIT'], item;
				for (var i = 0; i < items.length; i++)
				{
					item = document.getElementById(items[i] + '_' + this.getAttribute('data-prop') + '_' + this.getAttribute('data-value'));
					if (item) {
						item.classList.toggle('hide');
					}
				}
			});
		});

		document.querySelectorAll('.save-property').forEach(function(el) {
			el.addEventListener('click', function(e){
				e.preventDefault();

				var btn = this,
					input = document.getElementById('INPUT_' + btn.getAttribute('data-prop') + '_' + btn.getAttribute('data-value'));

				btn.setAttribute('data-loading', true);
				//btn.find('.spinner-border').toggleClass('hide');
				//btn.find('.fa').toggleClass('hide');

				var post = {};
				post[btn.getAttribute('data-prop')] = input.value;

				fetch(btn.getAttribute('data-api'), {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
						'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]').getAttribute('content')
					},
					body: JSON.stringify(post)
				})
				.then(function (response) {
					if (response.ok) {
						if (btn.getAttribute('data-reload')) {
							window.location.reload(true);
							return;
						}
						return response.json();
					}
					return response.json().then(function (data) {
						var msg = data.message;
						if (typeof msg === 'object') {
							msg = Object.values(msg).join('<br />');
						}
						throw msg;
					});
				})
				.then(data => {
					var span = document.getElementById('SPAN_' + btn.getAttribute('data-prop') + '_' + btn.getAttribute('data-value'));
					if (span) {
						span.classList.toggle('hide');
						span.innerHTML = data[btn.getAttribute('data-prop')];
					}
					input.classList.toggle('hide');

					//btn.find('.spinner-border').toggleClass('hide');
					//btn.find('.fa').toggleClass('hide');
					btn.setAttribute('data-loading', false);
					btn.classList.toggle('hide');

					var cancel = document.getElementById('CANCEL_' + btn.getAttribute('data-prop') + '_' + btn.getAttribute('data-value'));
					if (cancel) {
						cancel.classList.toggle('hide');
					}
					var edit = document.getElementById('EDIT_' + btn.getAttribute('data-prop') + '_' + btn.getAttribute('data-value'));
					if (edit) {
						edit.classList.toggle('hide');
					}
				}).catch(function(err) {
					btn.setAttribute('data-loading', false);
					alert(err);
				});

				/*$.ajax({
					url: btn.getAttribute('data-api'),
					type: 'put',
					data: post,
					dataType: 'json',
					async: false,
					success: function (data) {
						if (btn.getAttribute('data-reload')) {
							window.location.reload(true);
							return;
						}

						var span = document.getElementById('SPAN_' + btn.getAttribute('data-prop') + '_' + btn.getAttribute('data-value'));
						if (span) {
							span.classList.toggle('hide');
							span.innerHTML = data[btn.getAttribute('data-prop')];
						}
						input.classList.toggle('hide');

						//btn.find('.spinner-border').toggleClass('hide');
						//btn.find('.fa').toggleClass('hide');
						btn.setAttribute('data-loading', false);
						btn.classList.toggle('hide');

						var cancel = document.getElementById('CANCEL_' + btn.getAttribute('data-prop') + '_' + btn.getAttribute('data-value'));
						if (cancel) {
							cancel.classList.toggle('hide');
						}
						var edit = document.getElementById('EDIT_' + btn.getAttribute('data-prop') + '_' + btn.getAttribute('data-value'));
						if (edit) {
							edit.classList.toggle('hide');
						}
					},
					error: function (xhr, ajaxOptions, thrownError) {
						//Halcyon.message('danger', xhr.response);
						//btn.find('spinner-border').toggleClass('hide');
						//btn.find('fa').toggleClass('hide');
						btn.setAttribute('data-loading', false);
						alert(xhr.responseJSON.message);
						//console.log(xhr);
					}
				});*/
			});
		});

		/*$('.create-default-unix-groups').on('click', function(e){
			e.preventDefault();
			CreateDefaultUnixGroups($(this).data('value'), $(this).data('group'));
		});
		$('.delete-unix-group').on('click', function(e){
			e.preventDefault();
			DeleteUnixGroup($(this), $(this).data('value'));
		});*/

		if ($('.datatable').length) {
			$('.datatable').each(function(i, el){
			$(el).DataTable({
				pageLength: 200,
				pagingType: 'numbers',
				paging: false,//($(el).attr('data-length') && parseInt($(el).attr('data-length')) > 200 ? true : false),
				scrollY: '50vh',
				scrollCollapse: true,
				headers: true,
				info: true,
				ordering: false,
				lengthChange: false,
				dom: "<'row'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6'" + ($(el).attr('data-length') && parseInt($(el).attr('data-length')) > 200 ? '' : 'i') + ">><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5'p><'col-sm-12 col-md-7'l>>",
				scrollX: true,
				//autoWidth: false,
				language: {
					searchPlaceholder: "Filter users...",
					search: "_INPUT_",
				},
				fixedColumns: {
					leftColumns: 1
				},
				initComplete: function () {
					$($.fn.dataTable.tables(true)).css('width', '100%');

					var table = this;
					this.api().columns().every(function (i) {
						if (i < 3) {
							return;
						}
						var column = this;
						var select = $('<select class="data-col-filter" data-index="' + i + '"><option value="all">- All -</option><option value="selected">Selected</option><option value="not-selected">Not selected</option></select><br />')
							.prependTo($(column.header()));
					});

					$('.data-col-filter').on('change', function(){
						$.fn.dataTable.ext.search = [];//.pop();

						$('.data-col-filter').each(function(k, el){
							var val = $(this).val(),
							index = $(this).data('index');

							// If all records should be displayed
							if (val === 'all'){
								return;
							}

							// If selected records should be displayed
							if (val === 'selected'){
								$.fn.dataTable.ext.search.push(
									function (settings, data, dataIndex){
										var has = $(table
											.api()
											.cell(dataIndex, index)
											.node())
											.find(':checked').length;

										return has ? true : false;
									}
								);
							}

							// If selected records should not be displayed
							if (val === 'not-selected'){
								$.fn.dataTable.ext.search.push(
									function (settings, data, dataIndex){
										var has = $(table
											.api()
											.cell(dataIndex, index)
											.node())
											.find(':checked').length;

										return has ? false : true;
									}
								);
							}
						});

						table.api().draw();
					});
				}
			});
			});
		}

		// [!] Fix dropdowns in datatables getting cut off if there is only one row
		$(document).on('shown.bs.dropdown', '.datatable', function (e) {
			// The .dropdown container
			var $container = $(e.target);

			// Find the actual .dropdown-menu
			var $dropdown = $container.find('.dropdown-menu');
			if ($dropdown.length) {
				// Save a reference to it, so we can find it after we've attached it to the body
				$container.data('dropdown-menu', $dropdown);
			} else {
				$dropdown = $container.data('dropdown-menu');
			}

			$dropdown.css('top', ($container.offset().top + $container.outerHeight()) + 'px');
			$dropdown.css('left', $container.offset().left + 'px');
			$dropdown.css('position', 'absolute');
			$dropdown.css('display', 'block');
			$dropdown.appendTo('body');
		});

		$(document).on('hide.bs.dropdown', '.datatable', function (e) {
			// Hide the dropdown menu bound to this button
			$(e.target).data('dropdown-menu').css('display', 'none');
		});
		/*var dts = false;
		$('a.tab').on('shown.bs.tab', function(e){
			//$($.fn.dataTable.tables(true)).DataTable().columns.adjust();//.draw();
			if (dts) {
				return;
			}
			$('.datatable').DataTable({
			pageLength: 20,
			pagingType: 'numbers',
			info: false,
			ordering: false,
			lengthChange: false,
			scrollX: true,
			//autoWidth: false,
			language: {
				searchPlaceholder: "Filter users...",
				search: "_INPUT_",
			},
			fixedColumns: {
				leftColumns: 1//,
				//rightColumns: 1
			},
			initComplete: function () {
				//this.page(0).draw(true);
				dts = true;
				$($.fn.dataTable.tables(true)).css('width', '100%');
			}
			});
		});*/

		document.querySelectorAll('.membership-edit').forEach(function(el) {
			el.addEventListener('click', function(e){
				e.preventDefault();

				document.getElementById(this.getAttribute('href').replace('#', '')).classList.toggle('hidden');
			});
		});

		/*
		 $('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
			$($.fn.dataTable.tables( true ) ).css('width', '100%');
			$($.fn.dataTable.tables( true ) ).DataTable().columns.adjust().draw();
		});
		*/

		//$('.dataTables_filter input').addClass('form-control');

		var dialog = $(".membership-dialog").dialog({
			autoOpen: false,
			height: 'auto',
			width: 500,
			modal: true
		});

		// Add members
		var addmembers = document.getElementById("addmembers");
		if (addmembers) {
			var addmembersts = new TomSelect(addmembers, {
				plugins: {
					remove_button: {
						title: 'Remove this email',
					}
				},
				valueField: 'id',
				labelField: 'name',
				searchField: ['name', 'username', 'email'],
				hidePlaceholder: true,
				persist: false,
				create: true,
				load: function (query, callback) {
					var url = addmembers.getAttribute('data-api') + '?search=' + encodeURIComponent(query);

					fetch(url, {
						method: 'GET',
						headers: {
							'Content-Type': 'application/json',
							'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]').getAttribute('content')
						}
					})
					.then(response => response.json())
					.then(json => {
						for (var i = 0; i < json.data.length; i++) {
							if (!json.data[i].id) {
								json.data[i].id = json.data[i].username;
							}
						}
						callback(json.data);
					}).catch(function(err) {
						callback();
					});
				},
				render: {
					option: function (item, escape) {
						var name = item.name;
						var label = name || item.username;
						var caption = name ? item.username : null;
						return '<div>' +
							'<span class="label">' + escape(label) + '</span>' +
							(caption ? ' <span class="caption text-muted">(' + escape(caption) + ')</span>' : '') +
							'</div>';
					}
				}
			});
			addmembersts.on('item_add', function (e) {
				document.getElementById('add_member_save').disabled = false;
			});
		}

		/*document.querySelectorAll('.add_member').forEach(function(el) {
			el.addEventListener('click', function(e){
				e.preventDefault();

				$(this.getAttribute('href')).dialog("open");
				document.getElementById('new_membertype').value = this.getAttribute('data-membertype');
			});
		});*/

		if ($("#import_member_dialog").length) {
			/*var dialogi = $("#import_member_dialog").dialog({
				autoOpen: false,
				height: 'auto',
				width: 500,
				modal: true
			});

			$('.import_member').off('click').on('click', function (e) {
				e.preventDefault();

				dialogi.dialog("open");
			});*/

			// feature detection for drag&drop upload
			var isAdvancedUpload = function () {
				var div = document.createElement('div');
				return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
			}();

			// applying the effect for every form
			var forms = document.querySelectorAll('.dropzone');
			Array.prototype.forEach.call(forms, function (form) {
				var input = form.querySelector('input[type="file"]'),
					//label = form.querySelector('label'),
					filelist = form.querySelector('.file-list'),
					droppedFiles = false,
					// output information
					output = function (msg) {
						filelist.innerHTML = msg + (input.getAttribute('multiple') ? filelist.innerHTML : '');
					},
					showFiles = function (files) {
						// process all File objects
						var i, f;
						for (i = 0; i < files.length; i++) {
							f = files[i];
							//parseFile(f);
							output(
								"<p>File information: <strong>" + f.name + "</strong> (" + f.size + " bytes)</p>"
							);
						}
					};

				// automatically submit the form on file select
				input.addEventListener('change', function (e) {
					showFiles(e.target.files);
				});

				// drag&drop files if the feature is available
				if (isAdvancedUpload) {
					form.classList.add('has-advanced-upload'); // letting the CSS part to know drag&drop is supported by the browser

					['drag', 'dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop'].forEach(function (event) {
						form.addEventListener(event, function (e) {
							// preventing the unwanted behaviours
							e.preventDefault();
							e.stopPropagation();
						});
					});

					['dragover', 'dragenter'].forEach(function (event) {
						form.addEventListener(event, function () {
							form.classList.add('is-dragover');
						});
					});

					['dragleave', 'dragend', 'drop'].forEach(function (event) {
						form.addEventListener(event, function () {
							form.classList.remove('is-dragover');
						});
					});

					form.addEventListener('drop', function (e) {
						droppedFiles = e.target.files || e.dataTransfer.files; // the files that were dropped
						input.files = droppedFiles;
						showFiles(droppedFiles);
					});
				}

				// Firefox focus bug fix for file input
				input.addEventListener('focus', function () {
					input.classList.add('has-focus');
				});
				input.addEventListener('blur', function () {
					input.classList.remove('has-focus');
				});
			});
		}

		var newmembertype = document.getElementById('new_membertype');
		if (newmembertype) {
			newmembertype.addEventListener('change', function(e) {
				var sel = this;
				if (sel.value == 2 && sel.getAttribute('data-cascade')) {
					document.querySelectorAll('.add-queue-member').forEach(function(el) {
						el.checked = true;
						const event = new Event('change');
						el.dispatchEvent(event);

						if (sel.getAttribute('data-disable')) {
							el.disabled = true;
						}
					});
					document.querySelectorAll('.add-unixgroup-member').forEach(function(el) {
						el.checked = true;
						const event = new Event('change');
						el.dispatchEvent(event);

						if (sel.getAttribute('data-disable')
						&& el.getAttribute('data-base')
						&& el.getAttribute('data-base') == el.getAttribute('id')) {
							el.disabled = true;
						}
					});
				} else {
					document.querySelectorAll('.add-queue-member').forEach(function(el) {
						if (sel.getAttribute('data-disable')) {
							el.disabled = false;
						}
					});
				}
			});
		}

		$('.add-queue-member,.add-unixgroup-member').on('change', function(e){
			e.preventDefault();

			var bx = $(this);

			if (bx.is(':checked')) {
				if (bx.attr('data-base') && bx.attr('data-base') != bx.attr('id')) {
					$('#' + bx.attr('data-base'))
						.prop('checked', true)
						.attr('checked', 'checked')
						.trigger('change');
				}
			}
		});

		$('#add_member_save').on('click', function(e){
			e.preventDefault();

			var btn = $(this);
			var users = $('#addmembers').val().split(',');

			$('#addmembers').removeClass('is-invalid');
			$('#add_member_error').addClass('hide').html('');

			if (!users || !users.length) {
				$('#addmembers').addClass('is-invalid');
				$('#add_member_error').removeClass('hide').html('Please specify the person(s) to add.');
				return;
			}

			btn.addClass('processing');

			var post = {
				'groupid': btn.data('group'),
				'userid': 0,
				'membertype': (newmembertype ? newmembertype.value : 1)
			};
			var queues = $('.add-queue-member:checked');
			var unixgroups = $('.add-unixgroup-member:checked');

			var processed = {
				users: 0,
				queues: 0,
				unixgroups: 0
			};

			var pending = {
				users: users.length,
				queues: queues.length * users.length,
				unixgroups: unixgroups.length * users.length
			};
			var errors = new Array;

			$.each(users, function(i, userid) {
				post['userid'] = userid;

				$.ajax({
					url: btn.data('api'),
					type: 'post',
					data: post,
					dataType: 'json',
					async: false,
					success: function (data) {
						processed['users']++;

						userid = data.userid;

						if (!queues.length && !unixgroups.length) {
							checkprocessed(processed, pending);
							return;
						}

						queues.each(function(k, checkbox){
							$.ajax({
								url: btn.data('api-queueusers'),
								type: 'post',
								data: {
									'userid': userid,
									'groupid': btn.data('group'),
									'queueid': checkbox.value,
								},
								dataType: 'json',
								async: false,
								success: function (data) {
									processed['queues']++;
									checkprocessed(processed, pending);
								},
								error: function (xhr, ajaxOptions, thrownError) {
									//Halcyon.message('danger', xhr.response);
									//alert(xhr.responseJSON.message);
									if (typeof xhr.responseJSON.message === 'object') {
										var lines = Object.values(xhr.responseJSON.message);
										for (var i = 0; i < lines.length; i++)
										{
											errors.push(lines[i]);
										}
									} else {
										errors.push(xhr.responseJSON.message);
									}

									processed['queues']++;
									checkprocessed(processed, pending);
								}
							});
							//console.log(btn.data('api-queueusers'));
						});

						unixgroups.each(function(k, checkbox){
							$.ajax({
								url: btn.data('api-unixgroupusers'),
								type: 'post',
								data: {
									'userid': userid,
									'groupid': btn.data('group'),
									'unixgroupid': checkbox.value
								},
								dataType: 'json',
								async: false,
								success: function (data) {
									processed['unixgroups']++;
									checkprocessed(processed, pending);
								},
								error: function (xhr, ajaxOptions, thrownError) {
									//Halcyon.message('danger', xhr.response);
									//alert(xhr.responseJSON.message);
									if (typeof xhr.responseJSON.message === 'object') {
										var lines = Object.values(xhr.responseJSON.message);
										for (var i = 0; i < lines.length; i++)
										{
											errors.push(lines[i]);
										}
									} else {
										errors.push(xhr.responseJSON.message);
									}

									processed['unixgroups']++;
									checkprocessed(processed, pending);
								}
							});
							//console.log(btn.data('api-unixgroupusers'));
						});
					},
					error: function (xhr, ajaxOptions, thrownError) {
						//Halcyon.message('danger', xhr.response);
						//alert(xhr.responseJSON.message);
						if (typeof xhr.responseJSON.message === 'object') {
							var lines = Object.values(xhr.responseJSON.message);
							for (var i = 0; i < lines.length; i++)
							{
								errors.push(lines[i]);
							}
						} else {
							errors.push(xhr.responseJSON.message);
						}
					}
				});
			});

			// Done?
			if (errors.length) {
				btn.removeClass('processing');
				$('#add_member_error').removeClass('hide').html(errors.join('<br />'));
			}
		});

		// Remove user
		$('body').on('click', '.membership-remove', function(e){
			e.preventDefault();

			var row = $($(this).attr('href'));
			var boxes = row.find('input[type=checkbox]:checked');
			var errors = new Array;

			var al = $($(this).closest('.card')).find('.alert');
			if (al.length) {
				al.addClass('hide').html(errors.join('<br />'));
			}

			boxes.each(function(i, el) {
				$.ajax({
					url: $(el).data('api'),
					type: 'delete',
					dataType: 'json',
					async: false,
					success: function (data) {
					},
					error: function (xhr, ajaxOptions, thrownError) {
						if (xhr.status == 416) {
							errors.push("Queue disabled for system/guest account.");
						}
						if (typeof xhr.responseJSON.message === 'object') {
							var lines = Object.values(xhr.responseJSON.message);
							for (var i = 0; i < lines.length; i++)
							{
								errors.push(lines[i]);
							}
						} else {
							errors.push(xhr.responseJSON.message);
						}
					}
				});
			});

			if ($(this).data('api')) {
				$.ajax({
					url: $(this).data('api'),
					type: 'delete',
					dataType: 'json',
					async: false,
					success: function (data) {
						location.reload(true);
					},
					error: function (xhr, ajaxOptions, thrownError) {
						if (typeof xhr.responseJSON.message === 'object') {
							var lines = Object.values(xhr.responseJSON.message);
							for (var i = 0; i < lines.length; i++)
							{
								errors.push(lines[i]);
							}
						} else {
							errors.push(xhr.responseJSON.message);
						}
					}
				});
			}

			if (errors.length && al.length) {
				al.removeClass('hide').html(errors.join('<br />'));
			}
		});

		$('body').on('click', '.membership-move', function(e){
			e.preventDefault();

			/*var parent = $($(this).attr('href'));

			parent.find('.membership-toggle').each(function(i, el){
				if ($(el).is(':checked')) {
					$(el).prop('checked', false).change();
				}
			});*/

			if ($(this).attr('data-api')) {
				$.ajax({
					url: $(this).data('api'),
					type: $(this).attr('data-method'),
					data: {
						userid: $(this).data('userid'),
						membertype: $(this).data('target'),
						groupid: $('#groupid').val()
					},
					dataType: 'json',
					async: false,
					success: function (data) {
						location.reload(true);
					},
					error: function (xhr, ajaxOptions, thrownError) {
						alert(xhr.responseJSON.message);
					}
				});
			}
		});

		$('body').on('change', '.membership-toggle', function(e){
			e.preventDefault();

			var al = $($(this).closest('.card')).find('.alert');
			if (al.length) {
				al.addClass('hide').html('');
			}

			var bx = $(this);
			bx.parent().find('.fa').remove();

			if (bx.is(':checked')) {
				if (bx.attr('data-base') && bx.attr('data-base') != bx.attr('id')) {
					$('#' + bx.attr('data-base'))
						.prop('checked', true)
						.attr('checked', 'checked')
						.prop('disabled', true)
						.trigger('change');
				}

				var post = {
					userid: $(this).data('userid')
				};
				post['groupid'] = $('#groupid').val();
				if ($(this).hasClass('queue-toggle')) {
					post['queueid'] = bx.data('objectid');
				} else {
					post['unixgroupid'] = bx.data('objectid');
				}

				$.ajax({
					url: bx.data('api-create'),
					type: 'post',
					data: post,
					dataType: 'json',
					async: false,
					success: function (data) {
						bx.data('api', data.api);
						if (typeof data.error != 'undefined') {
							if (al.length) {
								al.removeClass('hide').html(data.error);
							}
							bx.after($('<span class="fa fa-exclamation-triangle text-warning" aria-hidden="true" title="' + data.error + '"><span class="sr-only">' + data.error + '</span></span>'));
							alert('An error occurred. Try toggling the checkbox. If issues persist, please contact help.');
						}
					},
					error: function (xhr) {
						var msg = '';

						if (xhr.status == 416) {
							msg = "Queue enabled for system/guest account.";
						} else {
							msg = xhr.responseJSON.message;
						}

						if (al.length) {
							al.removeClass('hide').html(msg);
						} else {
							alert(msg);
						}
					}
				});
			} else {
				$.ajax({
					url: bx.data('api') + '?groupid=' + $('#groupid').val(),
					type: 'delete',
					dataType: 'json',
					async: false,
					success: function (data) {
						// Nothing to do here
						//bx.data('api', bx.data('api-create'));
					},
					error: function (xhr) { //xhr, ajaxOptions, thrownError
						var msg = '';

						if (xhr.status == 416) {
							msg = "Queue disabled for system/guest account.";
						} else {
							msg = xhr.responseJSON.message;
						}

						bx.after($('<span class="fa fa-exclamation-triangle text-warning" aria-hidden="true" title="' + msg + '"><span class="sr-only">' + msg+ '</span></span>'));

						if (al.length) {
							al.removeClass('hide').html(msg);
						} else {
							alert(msg);
						}
					}
				});
			}
		});

		$('body').on('click', '.membership-allqueues', function(e){
			e.preventDefault();

			var parent = $($(this).attr('href'));

			parent.find('.membership-toggle').each(function(i, el){
				if (!$(el).is(':checked')) {
					$(el).prop('checked', true).change();
				}
			});
		});

		$('#export_to_csv').on('click', function (e) {
			e.preventDefault();
			// Get the form unique to the current tab group using its id
			var form_id = "#csv_form_" + $(this).data('id');
			var form = $(form_id);
			/*var data = form.find('input:hidden[name=data]').val();
			// Data is json_parsed and uri decoded so convert it back
			data = JSON.parse(decodeURIComponent(data));
			// csvEscapeJSON is found in common.js and used to make the html render correctly
			data = csvEscapeJSON(JSON.stringify(data));
			// Insert data back into the form and make it submit to the php csv page. 
			form.find('input:hidden[name=data]').val(data)*/
			form.submit();
		});
	});

	function checkprocessed(processed, pending) {
		if (processed['users'] == pending['users']
		 && processed['queues'] == pending['queues']
		 && processed['unixgroups'] == pending['unixgroups']) {
			window.location.reload(true);
		}
	}
</script>
@endpush

@php
//$canManage = auth()->user()->can('edit groups') || (auth()->user()->can('edit.own groups') && $group->isManager(auth()->user()));
$canManage = auth()->user()->can('manage groups') || ((auth()->user()->can('edit groups') || auth()->user()->can('edit.own groups')) && $group->isManager(auth()->user()));
$subsection = request()->segment(4);
$subsection = $subsection ?: 'overview';

$pending = $group->pendingMembersCount;
@endphp

	<div class="contentInner">
		<div class="row mb-3">
			<div class="col-md-9">
				<h2>{{ $group->name }}</h2>
			</div>
			<div class="col-md-3 text-right">
				@if ($membership)
					@if ($membership->trashed())
						<span class="badge badge-danger">{{ trans('users::users.removed') }}</span>
					@elseif ($membership->membertype == 4)
						<span class="badge badge-warning">{{ $membership->type->name }}</span>
					@else
						<span class="badge {{ $membership->isManager() ? 'badge-success' : 'badge-secondary' }}">{{ $membership->type->name }}</span>
					@endif
				@endif
			</div>
		</div>

		<div id="everything">
			<ul class="nav nav-tabs mb-3">
				<li class="nav-item">
					<a href="{{ route('site.users.account.section.show', ['section' => 'groups', 'id' => $group->id, 'u' => $user->id != auth()->user()->id ? $user->id : null]) }}" id="group-overview" class="nav-link tab<?php if ($subsection == 'overview') { echo ' active activeTab'; } ?>">
						Overview
					</a>
				</li>
			@if ($canManage)
				<li class="nav-item">
					<a href="{{ route('site.users.account.section.show.subsection', ['section' => 'groups', 'id' => $group->id, 'subsection' => 'members', 'u' => $user->id != auth()->user()->id ? $user->id : null]) }}" id="group-members" class="nav-link tab<?php if ($subsection == 'members') { echo ' active activeTab'; } ?>">
						Members
						@if ($pending)
							<span class="badge badge-warning tip" title="Pending membership requests">{{ $pending }}</span>
						@endif
					</a>
				</li>
			@endif
			@foreach ($sections as $section)
				<li class="nav-item">
					<a href="{{ route('site.users.account.section.show.subsection', ['section' => 'groups', 'id' => $group->id, 'subsection' => $section['route'], 'u' => $user->id != auth()->user()->id ? $user->id : null]) }}" id="group-{{ $section['route'] }}" class="nav-link tab<?php if ($subsection == $section['route']) { echo ' active activeTab'; } ?>">{{ $section['name'] }}</a>
				</li>
			@endforeach
			@if ($canManage)
				<li class="nav-item">
					<a href="{{ route('site.users.account.section.show.subsection', ['section' => 'groups', 'id' => $group->id, 'subsection' => 'motd', 'u' => $user->id != auth()->user()->id ? $user->id : null]) }}" id="group-motd" class="nav-link tab<?php if ($subsection == 'motd') { echo ' active activeTab'; } ?>">
						Notices
					</a>
				</li>
				<?php /*<li class="nav-item">
					<a href="{{ route('site.users.account.section.show.subsection', ['section' => 'groups', 'id' => $group->id, 'subsection' => 'history', 'u' => $user->id != auth()->user()->id ? $user->id : null]) }}" id="group-history" class="nav-link tab<?php if ($subsection == 'history') { echo ' active activeTab'; } ?>">
						History
					</a>
				</li>*/ ?>
			@endif
			</ul>

			<input type="hidden" id="groupid" value="{{ $group->id }}" />
			<input type="hidden" id="HIDDEN_property_{{ $group->id }}" value="{{ $group->id }}" />

			@if ($subsection == 'overview')
			<div id="DIV_group-overview">
				@include('groups::site.group.overview', ['group' => $group])
			</div><!-- / #group-overview -->
			@endif

			@if ($subsection == 'members' && $canManage)
			<div id="DIV_group-members">
				@include('groups::site.group.members', ['group' => $group])
			</div><!-- / #group-members -->
			@endif

			@foreach ($sections as $section)
				@if ($subsection == $section['route'])
				<div id="DIV_group-{{ $section['route'] }}">
					{{ $section['content'] }}
				</div>
				@endif
			@endforeach

			@if ($canManage)
				@if ($subsection == 'motd')
				<div id="DIV_group-motd">
					@include('groups::site.group.motd', ['group' => $group])
				</div><!-- / #group-motd -->
				@endif

				<?php /*
				@if ($subsection == 'history')
				<div id="DIV_group-history">
					@include('groups::site.group.history', ['group' => $group])
				</div><!-- / #group-history -->
				@endif
				*/ ?>
			@endif
		</div><!-- / #everything -->
	</div><!-- / .contentInner -->
