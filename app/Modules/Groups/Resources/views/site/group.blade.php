@push('styles')
<!-- <link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/datatables/datatables.min.css') }}" /> -->
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/datatables/dataTables.bootstrap4.min.css?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/dataTables.bootstrap4.min.css')) }}" />
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/select2/css/select2.css?v=' . filemtime(public_path() . '/modules/core/vendor/select2/css/select2.css')) }}" />
@endpush

@push('scripts')
<script src="{{ asset('modules/core/vendor/handlebars/handlebars.min-v4.7.6.js?v=' . filemtime(public_path() . '/modules/core/vendor/handlebars/handlebars.min-v4.7.6.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/datatables/datatables.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/datatables.min.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/datatables/dataTables.bootstrap4.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/select2/js/select2.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/select2/js/select2.min.js')) }}"></script>
<script src="{{ asset('modules/groups/js/motd.js?v=' . filemtime(public_path() . '/modules/groups/js/motd.js')) }}"></script>
<script src="{{ asset('modules/groups/js/userrequests.js?v=' . filemtime(public_path() . '/modules/groups/js/userrequests.js')) }}"></script>
<script src="{{ asset('modules/groups/js/site.js?v=' . filemtime(public_path() . '/modules/groups/js/site.js')) }}"></script>
<script>
	$(document).ready(function() {
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

		//$('.tabbed').tabs();

		/*$('.add-row').on('click', function(e){
			e.preventDefault();

			var btn = $(this);

			var val = $(btn.attr('href')).val();
			if (!val) {
				return;
			}

			var container = btn.closest('ul'),
				data = {
					collegedeptid: val
				};

			$.post(btn.data('api'), data, function(result){
				var source   = $(btn.data('row')).html(),
					template = Handlebars.compile(source),
					context  = {
						"index" : container.find('li').length,
						"ancestors": result.data.ancestors, //[{name: 'foo'}, {name: 'bar'}],
						"name": val
					},
					html = template(context);

				$(html).insertBefore(container.find('li:last-child'));
			});
		});*/
		/*$('.add-fieldofscience-row').on('click', function(e){
			e.preventDefault();

			var val = $($(this).attr('href')).val();
			if (!val) {
				return;
			}

			var container = $(this).closest('ul');

			//$.post($(this).data('api'), data, function(e){
				var source   = $('#new-fieldofscience-row').html(),
					template = Handlebars.compile(source),
					context  = {
						"index" : container.find('li').length,
						"ancestors": [{name: 'foo'}, {name: 'bar'}],
						"name": val
					},
					html = template(context);

				$(html).insertBefore(container.find('li:last-child'));
			//});
		});*/
		$('.list-group').on('click', '.delete-row', function(e){
			e.preventDefault();

			var result = confirm('Are you sure you want to remove this?');

			if (result) {
				var container = $(this).closest('li');

				//$.post($(this).data('api'), data, function(e){
					container.remove();
				//});
			}
		});


		/*$('#new_group_btn').on('click', function (event) {
			event.preventDefault();

			CreateNewGroup();
		});
		$('#new_group_input').on('keyup', function (event) {
			if (event.keyCode == 13) {
				CreateNewGroup();
			}
		});

		$('#create_gitorg_btn').on('click', function (event) {
			event.preventDefault();
			CreateGitOrg($(this).data('value'));
		});

		$('.add-property').on('click', function(e){
			e.preventDefault();

			AddProperty($(this).data('prop'), $(this).data('value'));
		});
		$('.add-property-input').on('keyup', function(e){
			e.preventDefault();

			if (event.keyCode==13){
				AddProperty($(this).data('prop'), $(this).data('value'));
			}
		});*/

		$('.edit-property').on('click', function(e){
			e.preventDefault();

			var items = ['SPAN', 'INPUT', 'CANCEL', 'SAVE', 'EDIT'], item;
			for (var i = 0; i < items.length; i++)
			{
				item = $('#' + items[i] + '_' + $(this).data('prop') + '_' + $(this).data('value'));
				if (item.length) {
					item.toggleClass('hide');
				}
			}
		});

		$('.edit-property-input').on('keyup', function(event){
			if (event.keyCode == 13) {
				EditProperty($(this).data('prop'), $(this).data('value'));
			}
		});

		$('.cancel-edit-property').on('click', function(e){
			e.preventDefault();

			var items = ['SPAN', 'INPUT', 'CANCEL', 'SAVE', 'EDIT'], item;
			for (var i = 0; i < items.length; i++)
			{
				item = $('#' + items[i] + '_' + $(this).data('prop') + '_' + $(this).data('value'));
				if (item.length) {
					item.toggleClass('hide');
				}
			}
		});

		$('.save-property').on('click', function(e){
			e.preventDefault();

			var btn = $(this),
				input = $('#INPUT_' + btn.data('prop') + '_' + btn.data('value'));

			btn.attr('data-loading', true);
			//btn.find('.spinner-border').toggleClass('hide');
			//btn.find('.fa').toggleClass('hide');

			var post = {};
			post[btn.data('prop')] = input.val();

			$.ajax({
				url: btn.data('api'),
				type: 'put',
				data: post,
				dataType: 'json',
				async: false,
				success: function (data) {
					if (btn.data('reload')) {
						window.location.reload(true);
						return;
					}

					var span = $('#SPAN_' + btn.data('prop') + '_' + btn.data('value'));
					if (span.length) {
						span.toggleClass('hide');
						span.html(data[btn.data('prop')]);
					}
					input.toggleClass('hide');

					//btn.find('.spinner-border').toggleClass('hide');
					//btn.find('.fa').toggleClass('hide');
					btn.attr('data-loading', false);
					btn.toggleClass('hide');

					var cancel = $('#CANCEL_' + btn.data('prop') + '_' + btn.data('value'));
					if (cancel.length) {
						cancel.toggleClass('hide');
					}
					var edit = $('#EDIT_' + btn.data('prop') + '_' + btn.data('value'));
					if (edit.length) {
						edit.toggleClass('hide');
					}
				},
				error: function (xhr, ajaxOptions, thrownError) {
					//Halcyon.message('danger', xhr.response);
					//btn.find('spinner-border').toggleClass('hide');
					//btn.find('fa').toggleClass('hide');
					btn.attr('data-loading', false);
					alert(xhr.responseJSON.message);
					//console.log(xhr);
				}
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

		$('.searchable-select').select2();

		if ($('.datatable').length) {
			$('.datatable').each(function(i, el){
			$(el).DataTable({
				pageLength: 200,
				pagingType: 'numbers',
				paging: ($(el).attr('data-length') && parseInt($(el).attr('data-length')) > 200 ? true : false),
				scrollY: '50vh',
				scrollCollapse: true,
				headers: true,
				info: true,
				ordering: false,
				lengthChange: false,
				dom: "<'row'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6'i>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5'p><'col-sm-12 col-md-7'l>>",
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

		$('.membership-edit').on('click', function(e){
			e.preventDefault();

			$($(this).attr('href')).toggleClass('hidden');
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

		$('.add_member').on('click', function(e){
			e.preventDefault();

			$($(this).attr('href')).dialog("open");
			$('#new_membertype').val($(this).data('membertype'));

			$('#addmembers').select2({
				ajax: {
					url: $('#addmembers').data('api'),
					dataType: 'json',
					tags: true,
					tokenSeparators: [','],
					//maximumSelectionLength: 1,
					//theme: "classic",
					data: function (params) {
						var query = {
							search: params.term,
							order: 'surname',
							order_dir: 'asc'
						}

						return query;
					},
					processResults: function (data) {
						for (var i = 0; i < data.data.length; i++) {
							data.data[i].id = data.data[i].id > 0 ? data.data[i].id : data.data[i].username;
							data.data[i].text = data.data[i].name + ' (' + data.data[i].username + ')';
						}

						return {
							results: data.data
						};
					}
				}
			});
			$('#addmembers').on('select2:select', function (e) {
				$('#add_member_save').prop('disabled', false);
			});
		});

		$('.add-unixgroup-member').on('change', function(e){
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
			var users = $('#addmembers').val();

			$('#addmembers').removeClass('is-invalid');
			$('#add_member_error').addClass('hide').html('');

			if (!users || !users.length) {
				$('#addmembers').addClass('is-invalid');
				$('#add_member_error').removeClass('hide').html('Please specify the person(s) to add.');
				return;
			}

			var post = {
				'groupid': btn.data('group'),
				'userid': 0,
				'membertype': $('#new_membertype').val()
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
							errors.push("Queue disabled for system/guest account. ACMaint Role removal must be requested manually from accounts@purdue.edu");
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

			var parent = $($(this).attr('href'));

			parent.find('.membership-toggle').each(function(i, el){
				if ($(el).is(':checked')) {
					$(el).prop('checked', false).change();
				}
			});

			if ($(this).data('api')) {
				$.ajax({
					url: $(this).data('api'),
					type: 'put',
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
							msg = "Queue enabled for system/guest account. ACMaint Role addition must be requested manually from accounts@purdue.edu";
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
							msg = "Queue disabled for system/guest account. ACMaint Role removal must be requested manually from accounts@purdue.edu";
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
		<div class="row">
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
			<ul class="nav nav-tabs">
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

			@if ($subsection == 'members')
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
