@extends('layouts.master')

@push('scripts')
<script src="{{ asset('modules/users/js/users.js?v=' . filemtime(public_path() . '/modules/users/js/users.js')) }}"></script>
@endpush

@php
app('pathway')
	->append(
		trans('users::users.module name'),
		route('admin.users.index')
	)
	->append(
		'#' . $user->id
	);
@endphp

@section('toolbar')
	{!! Toolbar::link('back', trans('users::users.back'), route('admin.users.index'), false) !!}

	{!! Toolbar::render() !!}
@stop

@section('title')
{{ trans('users::users.module name') }}: {{ '#' . $user->id }}
@stop

@section('content')
<div id="item-form" class="editform">

	@if ($errors->any())
		<div class="alert alert-error">
			<ul>
				@foreach ($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<nav class="container-fluid">
		<ul id="useer-tabs" class="nav nav-tabs" role="tablist">
			<li class="nav-item" role="presentation"><a class="nav-link active" href="#user-account" data-toggle="tab" role="tab" id="user-account-tab" aria-controls="user-account" aria-selected="true">Account</a></li>
			@if ($user->id)
				<li class="nav-item" role="presentation">
					<a href="#user-attributes" class="nav-link" data-toggle="tab" role="tab" id="user-attributes-tab" aria-controls="user-attributes" aria-selected="false">{{ trans('users::users.attributes') }}</a>
				</li>
				@if (auth()->user()->can('view users.notes'))
					<li class="nav-item" role="presentation">
						<a href="#user-notes" class="nav-link" data-toggle="tab" role="tab" id="user-notes-tab" aria-controls="user-notes" aria-selected="false">{{ trans('users::users.notes') }}</a>
					</li>
				@endif
				@foreach ($sections as $k => $section)
					<li class="nav-item" role="presentation">
						<a href="#user-{{ $k }}" class="nav-link" data-toggle="tab" role="tab" id="user-{{ $k }}-tab" aria-controls="user-{{ $k }}" aria-selected="false">{!! $section['name'] !!}</a>
					</li>
				@endforeach
			@endif
		</ul>
	</nav>

	<div class="tab-content" id="user-tabs-content">
		<div class="tab-pane show active" id="user-account" role="tabpanel" aria-labelledby="user-account-tab">
			<div class="row">
				<div class="col col-md-6">

					<div class="card">
						<div class="card-header">
							<a class="btn btn-sm btn-link float-right" data-toggle="modal" href="#manage_details_dialog" data-tip="Edit User Info">
								<span class="fa fa-pencil" aria-hidden="true"></span>
								<span class="sr-only">Edit</span>
							</a>
							<div class="card-title">{{ trans('global.details') }}</div>
						</div>
						<div class="card-body">
							<table class="table mb-3">
								<caption class="sr-only">{{ trans('global.metadata') }}</caption>
								<tbody>
									<tr>
										<th scope="row">{{ trans('users::users.id') }}</th>
										<td>{{ $user->id }}</td>
									</tr>
									<tr>
										<th scope="row">{{ trans('users::users.name') }}</th>
										<td>{{ $user->name }}</td>
									</tr>
									<tr>
										<th scope="row">Title</th>
										<td>{!! $user->title ? e($user->title) : '<span class="none">' . trans('global.unknown') . '</span>' !!}</td>
									</tr>
									<tr>
										<th scope="row">Campus</th>
										<td>{!! $user->campus ? e($user->campus) : '<span class="none">' . trans('global.unknown') . '</span>' !!}</td>
									</tr>
									<tr>
										<th scope="row">Phone</th>
										<td>{!! $user->phone ? e($user->phone) : '<span class="none">' . trans('global.unknown') . '</span>' !!}</td>
									</tr>
									<tr>
										<th scope="row">Building</th>
										<td>{!! $user->building ? e($user->building) : '<span class="none">' . trans('global.unknown') . '</span>' !!}</td>
									</tr>
									<tr>
										<th scope="row">Email</th>
										<td>{{ $user->email }}</td>
									</tr>
									<tr>
										<th scope="row">Room</th>
										<td>{!! $user->roomnumber ? e($user->roomnumber) : '<span class="none">' . trans('global.unknown') . '</span>' !!}</td>
									</tr>
									<tr>
										<th scope="row">{{ trans('users::users.organization id') }}</th>
										<td>{{ $user->puid }}</td>
									</tr>
								</tbody>
							</table>

							<table class="table table-bordered mb-3">
								<caption class="sr-only">Usernames</caption>
								<thead>
									<tr>
										<th scope="col">ID</th>
										<th scope="col">Username</th>
										<th scope="col">Created</th>
										<th scope="col">Removed</th>
										<th scope="col">Last Visited</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($user->usernames()->withTrashed()->orderBy('id', 'asc')->get() as $username)
									<tr<?php if ($username->trashed()) { echo ' class="trashed"'; } ?>>
										<td>
											{{ $username->id }}
										</td>
										<td>
											{{ $username->username }}
										</td>
										<td>
											@if ($username->isCreated())
												<time datetime="{{ $username->datecreated->format('Y-m-d\TH:i:s\Z') }}">
													@if ($username->datecreated->toDateTimeString() > Carbon\Carbon::now()->toDateTimeString())
														{{ $username->datecreated->diffForHumans() }}
													@else
														{{ $username->datecreated->format('Y-m-d') }}
													@endif
												</time>
											@else
												{{ trans('global.unknown') }}
											@endif
										</td>
										<td>
											@if ($username->trashed())
												<time datetime="{{ $username->dateremoved->format('Y-m-d\TH:i:s\Z') }}">
													@if ($username->dateremoved->toDateTimeString() > Carbon\Carbon::now()->toDateTimeString())
														{{ $username->dateremoved->diffForHumans() }}
													@else
														{{ $username->dateremoved->format('Y-m-d') }}
													@endif
												</time>
											@endif
										</td>
										<td>
											@if ($username->hasVisited())
												<time datetime="{{ $username->datelastseen->format('Y-m-d\TH:i:s\Z') }}">
													@if ($username->datelastseen->toDateTimeString() > Carbon\Carbon::now()->toDateTimeString())
														{{ $username->datelastseen->diffForHumans() }}
													@else
														{{ $username->datelastseen->format('Y-m-d') }}
													@endif
												</time>
											@else
												{{ trans('global.never') }}
											@endif
										</td>
									</td>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>

					<div class="card">
						<div class="card-header">
							<a class="btn btn-sm btn-link float-right" data-toggle="modal" href="#manage_access_dialog" data-tip="Edit Assigned Roles">
								<span class="fa fa-pencil" aria-hidden="true"></span>
								<span class="sr-only">Edit</span>
							</a>
							<div class="card-title">{{ trans('users::users.assigned roles') }}</div>
						</div>
						<div class="card-body">

						<div class="form-group">
							<?php
							$roles = $user->roles
								->pluck('role_id')
								->all();

							//echo App\Halcyon\Html\Builder\Access::roles('fields[newroles]', $roles, true);

							$ug = new App\Halcyon\Access\Role;

							$options = App\Halcyon\Access\Role::query()
								->select(['a.id', 'a.title', 'a.parent_id', Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT b.id) AS level')])
								->from($ug->getTable() . ' AS a')
								->leftJoin($ug->getTable() . ' AS b', function($join)
									{
										$join->on('a.lft', '>', 'b.lft')
											->on('a.rgt', '<', 'b.rgt');
									})
								->groupBy(['a.id', 'a.title', 'a.lft', 'a.rgt', 'a.parent_id'])
								->orderBy('a.lft', 'asc')
								->get();

							$html = array();
							$html[] = '<ul class="checklist usergroups">';

							foreach ($options as $i => $item)
							{
								// Setup  the variable attributes.
								$eid = 'role_' . $item->id;
								// Don't call in_array unless something is selected
								$checked = '';
								if ($roles)
								{
									$checked = in_array($item->id, $roles) ? ' checked="checked"' : '';
								}
								$rel = ($item->parent_id > 0) ? ' rel="role_' . $item->parent_id . '"' : '';

								// Build the HTML for the item.
								$html[] = '	<li>';
								$html[] = '		<div class="form-check' . ($checked ? ' text-success' : '') . '">';
								if ($checked)
								{
									$html[] = '			<span class="fa fa-check-square" aria-hidden="true"></span><span class="sr-only">' . trans('global.yes') . '</span>';
								}
								else
								{
									$html[] = '			<span class="fa fa-square" aria-hidden="true"></span><span class="sr-only">' . trans('global.no') . '</span>';
								}
								//$html[] = '		<input type="checkbox" class="form-check-input" disabled name="role[]" value="' . $item->id . '" id="' . $eid . '"' . $checked . $rel . ' />';
								//$html[] = '		<label for="' . $eid . '" class="form-check-label">';
								$html[] = '		' . str_repeat('<span class="gi">|&mdash;</span>', $item->level) . $item->title;
								//$html[] = '		</label>';
								$html[] = '		</div>';
								$html[] = '	</li>';
							}
							$html[] = '</ul>';

							echo implode("\n", $html);
							?>
						</div>
						</div>
					</div>
					<!-- </fieldset> -->
				</div>
				<div class="col col-md-6">
					@foreach ($parts as $part)
						{!! $part !!}
					@endforeach
				</div><!-- / .col -->
			</div><!-- / .grid -->
		</div><!-- / #user-account -->

		@if ($user->id)
			<div class="tab-pane" id="user-attributes" role="tabpanel" aria-labelledby="user-attributes-tab">
				<div class="card">
					<table class="table table-hover">
						<caption class="sr-only">{{ trans('users::users.attributes') }}</caption>
						<thead>
							<tr>
								<th scope="col" width="25">{{ trans('users::users.locked') }}</th>
								<th scope="col">{{ trans('users::users.key') }}</th>
								<th scope="col">{{ trans('users::users.value') }}</th>
								<th scope="col">{{ trans('users::users.access') }}</th>
							</tr>
						</thead>
						<tbody>
						<?php
						$i = 0;
						?>
						@foreach ($user->facets as $facet)
							<tr id="facet-{{ $facet->id }}">
								<td>
									@if ($facet->locked)
										<span class="icon-lock glyph">{{ trans('users::users.locked') }}</span>
									@endif
								</td>
								<td><input type="text" name="facet[{{ $i }}][key]" id="facet-{{ $facet->id }}-key" class="form-control" value="{{ $facet->key }}" {{ $facet->locked ? ' readonly="readonly"' : '' }} /></td>
								<td><input type="text" name="facet[{{ $i }}][value]" id="facet-{{ $facet->id }}-value" class="form-control" value="{{ $facet->value }}" {{ $facet->locked ? ' readonly="readonly"' : '' }} /></td>
								<td>
									<select name="facet[{{ $i }}][access]" id="facet-{{ $facet->id }}-access" class="form-control">
										<option value="0">{{ trans('users::users.private') }}</option>
										@foreach (App\Halcyon\Access\Viewlevel::all() as $access)
											<option value="{{ $access->id }}"{{ $facet->access == $access->id ? ' selected="selected"' : '' }}>{{ $access->title }}</option>
										@endforeach
									</select>
								</td>
								<td class="text-right">
									<input type="hidden" name="facet[{{ $i }}][id]" class="form-control" value="{{ $facet->id }}" />
									<button class="btn update-facet"
										data-target="#facet-{{ $facet->id }}"
										data-success="Item updated"
										data-api="{{ route('api.users.facets.update', ['id' => $facet->id]) }}">
										<span class="spinner-border spinner-border-sm d-none" role="status"><span class="sr-only">{{ trans('global.loading') }}</span></span>
										<span class="fa fa-save" aria-hidden="true"></span>
										<span class="sr-only">{{ trans('global.save') }}</span>
									</button>
									<button class="btn text-danger remove-facet"
										data-target="#facet-{{ $facet->id }}"
										data-success="Item removed"
										data-api="{{ route('api.users.facets.delete', ['id' => $facet->id]) }}"
										data-confirm="{{ trans('global.confirm delete') }}">
										<span class="fa fa-trash" aria-hidden="true"></span>
										<span class="sr-only">{{ trans('global.trash') }}</span>
									</button>
								</td>
							</tr>
							<?php
							$i++;
							?>
						@endforeach
						</tbody>
						<tfoot>
							<tr id="newfacet">
								<td></td>
								<td><input type="text" name="facet[{{ $i }}][key]" id="newfacet-key" class="form-control" value="" /></td>
								<td><input type="text" name="facet[{{ $i }}][value]" id="newfacet-value" class="form-control" value="" /></td>
								<td>
									<select name="facet[{{ $i }}][access]" id="newfacet-access" class="form-control">
										<option value="0">{{ trans('users::users.private') }}</option>
										@foreach (App\Halcyon\Access\Viewlevel::all() as $access)
											<option value="{{ $access->id }}">{{ $access->title }}</option>
										@endforeach
									</select>
								</td>
								<td class="text-right">
									<a href="#newfacet" class="btn btn-success add-facet"
										data-userid="{{ $user->id }}"
										data-api="{{ route('api.users.facets.create') }}">
										<span class="icon-plus glyph">{{ trans('global.add') }}</span>
									</a>
								</td>
							</tr>
						</tfoot>
					</table>
					<script id="facet-template" type="text/x-handlebars-template">
						<tr id="facet-{id}" data-id="{id}">
							<td></td>
							<td><input type="text" name="facet[{i}][key]" id="facet-{id}-key" class="form-control" value="{key}" /></td>
							<td><input type="text" name="facet[{i}][value]" id="facet-{id}-value" class="form-control" value="{value}" /></td>
							<td>
								<select name="facet[{i}][access]" id="facet-{id}-access" class="form-control">
									<option value="0">{{ trans('users::users.private') }}</option>
									@foreach (App\Halcyon\Access\Viewlevel::all() as $access)
										<option value="{{ $access->id }}">{{ $access->title }}</option>
									@endforeach
								</select>
							</td>
							<td class="text-right">
								<input type="hidden" name="facet[{i}][id]" class="form-control" value="{id}" />
								<button class="btn update-facet"
									data-target="#facet-{id}"
									data-success="Item updated"
									data-api="{{ route('api.users.facets') }}/{id}">
									<span class="spinner-border spinner-border-sm d-none" role="status"><span class="sr-only">{{ trans('global.loading') }}</span></span>
									<span class="fa fa-save" aria-hidden="true"></span>
									<span class="sr-only">{{ trans('global.save') }}</span>
								</button>
								<button class="btn text-danger remove-facet"
									data-target="#facet-{id}"
									data-success="Item removed"
									data-api="{{ route('api.users.facets.create') }}/{id}"
									data-confirm="{{ trans('global.confirm delete') }}">
									<span class="fa fa-trash" aria-hidden="true"></span>
									<span class="sr-only">{{ trans('global.trash') }}</span>
								</button>
							</td>
						</tr>
					</script>
				</div>
			</div>

			@if (auth()->user()->can('view users.notes'))
				<div class="tab-pane" id="user-notes" role="tabpanel" aria-labelledby="user-notes-tab">
					<div class="row">
						<div class="col-md-6">
							<?php
							$notes = $user->notes()->orderBy('created_at', 'desc')->get();
							if (count($notes)):
								foreach ($notes as $note):
									?>
									<div class="card">
										<div class="card-body">
											<h4 class="card-title">{{ $note->subject }}</h4>
											{!! $note->body !!}
										</div>
										<div class="card-footer">
											<div class="row">
												<div class="col-md-6">
													<span class="datetime">
														<time datetime="{{ $note->created_at->toDateTimeString() }}">
															@if ($note->created_at->format('Y-m-dTh:i:s') > Carbon\Carbon::now()->toDateTimeString())
																{{ $note->created_at->diffForHumans() }}
															@else
																{{ $note->created_at->format('Y-m-d') }}
															@endif
														</time>
													</span>
													<span class="creator">
														{{ $note->creator ? $note->creator->name : trans('global.unknown') }}
													</span>
												</div>
												<div class="col-md-6 text-right">
													@if (auth()->user()->can('manage users.notes'))
														<button data-api="{{ route('api.users.notes.update', ['id' => $note->id]) }}" class="btn btn-sm btn-secondary">
															<span class="icon-edit glyph">{{ trans('global.edit') }}</span>
														</button>
														<button data-api="{{ route('api.users.notes.delete', ['id' => $note->id]) }}" class="btn btn-sm btn-danger">
															<span class="icon-trash glyph">{{ trans('global.trash') }}</span>
														</button>
													@endif
												</div>
											</div>
										</div>
									</div>
									<?php
								endforeach;
							else:
								?>
								<p>No notes found.</p>
								<?php
							endif;
							?>
						</div>
						<div class="col-md-6">
							<?php /*<fieldset class="adminform">
								<legend>{{ trans('global.details') }}</legend>

								<div class="form-group">
									<label for="field-subject">{{ trans('users::notes.subject') }}: <span class="required">{{ trans('global.required') }}</span></label><br />
									<input type="text" class="form-control required" name="fields[subject]" id="field-subject" value="" />
								</div>

								<div class="form-group">
									<label for="field-body">{{ trans('users::notes.body') }}:</label>
									{!! editor('fields[body]', '', ['rows' => 15, 'class' => 'minimal no-footer']) !!}
								</div>

								<div class="form-group">
									<label for="field-state">{{ trans('global.state') }}:</label>
									<select name="fields[state]" class="form-control" id="field-state">
										<option value="0">{{ trans('global.unpublished') }}</option>
										<option value="1">{{ trans('global.published') }}</option>
										<option value="2">{{ trans('global.trashed') }}</option>
									</select>
								</div>
							</fieldset>*/ ?>
						</div>
					</div>
				</div><!-- / #user-notes -->
			@endif

			@foreach ($sections as $k => $section)
				<div class="tab-pane" id="user-{{ $k }}" role="tabpanel" aria-labelledby="user-{{ $k }}-tab">
					{!! $section['content'] !!}
				</div>
			@endforeach
		@endif
	</div><!-- / .tab-content -->

	<input type="hidden" name="userid" id="userid" value="{{ $user->id }}" />
	<input type="hidden" name="id" value="{{ $user->id }}" />
</div>

<div id="manage_details_dialog" data-id="{{ $user->id }}" title="Edit Details" class="modal dialog details-dialog" aria-hidden="true">
	<div class="modal-dialog">
		<form method="post" class="modal-content" action="{{ route('admin.users.store') }}">
			<div class="modal-header">
				<h3 class="modal-title">Edit Details</h3>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				@if ($user->sourced)
					<p class="alert alert-info">{{ trans('users::users.sourced description') }}</p>
				@endif

				<div class="form-group">
					<label for="field_username" id="field_username-lbl">{{ trans('users::users.username') }}: <span class="required star">{{ trans('global.required') }}</span></label>
					<input type="text" name="ufields[username]" id="field_username" value="{{ $user->username }}" maxlength="16" class="form-control<?php if ($user->id) { echo ' readonly" readonly="readonly'; } ?>" required />
					<span class="invalid-feedback">{{ trans('users::users.invalid.username') }}</span>
				</div>

				<div class="form-group">
					<label for="field-name">{{ trans('users::users.name') }}: <span class="required star">{{ trans('global.required') }}</span></label>
					<input type="text" class="form-control<?php if ($user->sourced) { echo ' readonly" readonly="readonly'; } ?>" required maxlength="128" name="fields[name]" id="field-name" value="{{ $user->name }}" />
					<span class="invalid-feedback">{{ trans('users::users.invalid.name') }}</span>
				</div>

				<div class="form-group">
					<label for="field_email" id="field_email-lbl">{{ trans('users::users.email') }}:</label>
					<input type="text" name="ufields[email]" id="field_email" value="{{ $user->email }}" maxlength="250" class="form-control" />
					<span class="invalid-feedback">{{ trans('users::users.invalid.email') }}</span>
				</div>

				<div class="form-group">
					<label for="field-organization_id">{{ trans('users::users.organization id') }}:</label>
					<input type="text" class="form-control" name="fields[puid]" id="field-organization_id" maxlength="10" value="{{ $user->puid }}" />
				</div>

				@if ($user->id)
				<div class="form-group">
					<label for="field-api_token">{{ trans('users::users.api token') }}:</label>
					<span class="input-group">
						<input type="text" class="form-control readonly" readonly="readonly" name="fields[api_token]" id="field-api_token" maxlength="100" value="{{ $user->api_token }}" />
						<span class="input-group-append">
							<button class="input-group-text btn btn-secondary btn-apitoken">{{ trans('users::users.regenerate') }}</button>
						</span>
					</span>
					<span class="form-text text-muted">{{ trans('users::users.api token hint') }}</span>
				</div>
				@endif

				<span id="details_errors" class="alert alert-warning hide"></span>

				<input type="hidden" name="userid" value="{{ $user->id }}" />
				@csrf
				<input type="hidden" name="id" value="{{ $user->id }}" />
			</div>
			<div class="modal-footer">
				<button id="user_details_save" class="btn btn-success" data-id="{{ $user->id }}">Save</button>
			</div>
		</form>
	</div>
</div>

<div id="manage_access_dialog" data-id="{{ $user->id }}" title="Edit Assigned Roles" class="modal dialog access-dialog" aria-hidden="true">
	<div class="modal-dialog">
		<form method="post" class="modal-content" action="{{ route('admin.users.store') }}">
			<div class="modal-header">
				<h3 class="modal-title">Edit Assigned Roles</h3>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<?php
				echo App\Halcyon\Html\Builder\Access::roles('fields[newroles]', $roles, true);
				?>

				<span id="access_errors" class="alert alert-warning hide"></span>

				<input type="hidden" name="fields[name]" value="{{ $user->name }}" />
				<input type="hidden" name="userid" value="{{ $user->id }}" />
				@csrf
				<input type="hidden" name="id" value="{{ $user->id }}" />
			</div>
			<div class="modal-footer">
				<button id="user_access_save" data-api="{{ route('api.users.update', ['id' => $user->id]) }}" class="btn btn-success" data-id="{{ $user->id }}">Save</button>
			</div>
		</form>
	</div>
</div>
@stop
