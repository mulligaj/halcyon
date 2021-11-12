@extends('layouts.master')

@push('styles')
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/datatables/dataTables.bootstrap4.min.css?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/dataTables.bootstrap4.min.css')) }}" />
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/select2/css/select2.css?v=' . filemtime(public_path() . '/modules/core/vendor/select2/css/select2.css')) }}" />
@endpush

@push('scripts')
<script src="{{ asset('modules/core/vendor/handlebars/handlebars.min-v4.7.6.js?v=' . filemtime(public_path() . '/modules/core/vendor/handlebars/handlebars.min-v4.7.6.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/datatables/datatables.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/datatables.min.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/datatables/dataTables.bootstrap4.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/select2/js/select2.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/select2/js/select2.min.js')) }}"></script>
<script src="{{ asset('modules/groups/js/admin.js?v=' . filemtime(public_path() . '/modules/groups/js/admin.js')) }}"></script>
@endpush

@php
app('request')->merge(['hidemainmenu' => 1]);

app('pathway')
	->append(
		trans('groups::groups.module name'),
		route('admin.groups.index')
	)
	->append(
		'#' . $row->id
	);
@endphp

@section('toolbar')
	{!! Toolbar::link('back', trans('groups::groups.back'), route('admin.groups.index'), false) !!}

	{!! Toolbar::render() !!}
@stop

@section('title')
{!! trans('groups::groups.module name') !!}: {{ 'Edit: #' . $row->id }}
@stop

@section('panel')
<div class="card mb-4">
	<div class="card-header">
		<a class="float-right" href="{{ route('admin.groups.edit', ['id' => $row->id]) }}" data-tip="{{ trans('global.edit') }}">
			<span class="fa fa-pencil" aria-hidden="true"></span>
			<span class="sr-only">{{ trans('global.edit') }}</span>
		</a>
		<h3 class="card-title pt-0">{{ trans('global.details') }}</h3>
	</div>
	<div class="card-body">
		<div class="form-group">
			<strong>{{ trans('groups::groups.name') }}</strong>:
			<div>{{ $row->name }}</div>
		</div>

		<div class="form-group">
			<strong>{{ trans('groups::groups.unix group base name') }}</strong>:
			<div>{{ $row->unixgroup ? $row->unixgroup : trans('global.none') }}</div>
		</div>

		<div class="form-group">
			<div><strong>{{ trans('groups::groups.department') }}</strong>:</div>
			@if (count($row->departments))
				<ul>
					@foreach ($row->departments as $dept)
						<li id="department-{{ $dept->id }}" class="mb-2" data-id="{{ $dept->id }}">
							<?php
							$prf = '';
							foreach ($dept->department->ancestors() as $ancestor):
								if (!$ancestor->parentid):
									continue;
								endif;

								$prf .= $ancestor->name . ' > ';
							endforeach;
							?>{{ $prf . $dept->department->name }}
						</li>
					@endforeach
				</ul>
			@else
				<div class="text-muted">{{ trans('global.none') }}</div>
			@endif
		</div>

		<div class="form-group">
			<div><strong>{{ trans('groups::groups.field of science') }}</strong>:</div>
			@if (count($row->fieldsOfScience))
				<ul>
					@foreach ($row->fieldsOfScience as $field)
						<li id="fieldofscience-{{ $field->id }}" class="mb-2" data-id="{{ $field->id }}">
							<?php
							$prf = '';
							foreach ($field->field->ancestors() as $ancestor):
								if (!$ancestor->parentid):
									continue;
								endif;

								$prf .= $ancestor->name . ' > ';
							endforeach;
							?>{{ $prf . $field->field->name }}
						</li>
					@endforeach
				</ul>
			@else
				<div class="text-muted">{{ trans('global.none') }}</div>
			@endif
		</div>
	</div>
</div>

<div class="card mb-4">
	<div class="card-header">
		<a class="float-right" href="{{ route('admin.groups.edit', ['id' => $row->id]) }}" data-tip="{{ trans('global.edit') }}">
			<span class="fa fa-pencil" aria-hidden="true"></span>
			<span class="sr-only">{{ trans('global.edit') }}</span>
		</a>

		<h3 class="card-title pt-0">{{ trans('groups::groups.unix groups') }}</h3>
	</div>
	<div class="card-body">
		@if (count($row->unixGroups))
			<table class="table table-hover">
				<caption class="sr-only">{{ trans('groups::groups.unix groups') }}</caption>
				<thead>
					<tr>
						<th scope="col">{{ trans('groups::groups.unix group') }}</th>
						<!-- <th scope="col">{{ trans('groups::groups.short name') }}</th> -->
						<th scope="col" class="text-right">{{ trans('groups::groups.members') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($row->unixGroups as $i => $u)
						<tr id="unixgroup-{{ $u->id }}" data-id="{{ $u->id }}">
							<td>{{ $u->longname }}</td>
							<!-- <td>{{ $u->shortname }}</td> -->
							<td class="text-right">{{ $u->members()->count() }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@else
			<p class="text-center"><span class="none">{{ trans('global.none') }}</span></p>
		@endif
	</div>
</div>
@stop

@section('content')
	<nav class="container-fluid">
		<ul id="group-tabs" class="nav nav-tabs" role="tablist">
			<li class="nav-item" role="presentation">
				<a href="#group-members" id="group-members-tab" class="nav-link active" data-toggle="tab" role="tab" aria-controls="group-members" aria-selected="true">
					{{ trans('queues::queues.queue') }}
				</a>
			</li>
			@foreach ($sections as $section)
				<li class="nav-item" role="presentation">
					<a href="#group-{{ $section['route'] }}" id="group-{{ $section['route'] }}-tab" class="nav-link" data-toggle="tab" role="tab" aria-controls="group-{{ $section['route'] }}" aria-selected="false">
						{{ $section['name'] }}
					</a>
				</li>
			@endforeach
			<li class="nav-item" role="presentation">
				<a href="#group-motd" id="group-motd-tab" class="nav-link" data-toggle="tab" role="tab" aria-controls="group-motd" aria-selected="false">
					{{ trans('queues::queues.purchases and loans') }}
				</a>
			</li>
		</ul>
	</nav>
	<div class="tab-content" id="queue-tabs-contant">
		<div class="tab-pane show active" id="group-members" role="tabpanel" aria-labelledby="group-members-tab">
			@include('groups::admin.groups.members', ['group' => $row])
		</div>

		@foreach ($sections as $section)
			<div class="tab-pane" id="group-{{ $section['route'] }}" role="tabpanel" aria-labelledby="group-{{ $section['route'] }}-tab">
				{!! $section['content'] !!}
			</div>
		@endforeach

		<div class="tab-pane" id="group-motd" role="tabpanel" aria-labelledby="group-motd-tab">
			@include('groups::admin.groups.motd', ['group' => $row])
		</div>
	</div>

	<input type="hidden" name="id" value="{{ $row->id }}" />
@stop