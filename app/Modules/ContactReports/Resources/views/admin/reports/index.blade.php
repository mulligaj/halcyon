@extends('layouts.master')

@section('styles')
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/tagsinput/jquery.tagsinput.css') }}" />
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/select2/css/select2.css') }}" />
@stop

@section('scripts')
<script src="{{ asset('modules/core/vendor/tagsinput/jquery.tagsinput.js?v=' . filemtime(public_path() . '/modules/core/vendor/tagsinput/jquery.tagsinput.js')) }}"></script>
<script src="{{ asset('modules/core/vendor/select2/js/select2.min.js?v=' . filemtime(public_path() . '/modules/core/vendor/select2/js/select2.min.js')) }}"></script>
<script src="{{ asset('modules/contactreports/js/admin.js?v=' . filemtime(public_path() . '/modules/contactreports/js/admin.js')) }}"></script>
@stop

@php
app('pathway')
	->append(
		trans('contactreports::contactreports.module name'),
		route('admin.contactreports.index')
	);
@endphp

@section('toolbar')
	@if (auth()->user()->can('delete contactreports'))
		{!! Toolbar::deleteList('', route('admin.contactreports.delete')) !!}
	@endif

	@if (auth()->user()->can('create contactreports'))
		{!! Toolbar::addNew(route('admin.contactreports.create')) !!}
	@endif

	@if (auth()->user()->can('admin contactreports'))
		{!!
			Toolbar::spacer();
			Toolbar::preferences('contactreports')
		!!}
	@endif

	{!! Toolbar::render() !!}
@stop

@section('title')
{!! config('contactreports.name') !!}
@stop

@section('content')
@component('contactreports::admin.submenu')
	reports
@endcomponent
<form action="{{ route('admin.contactreports.index') }}" method="post" name="adminForm" id="adminForm" class="form-inline">

	<fieldset id="filter-bar" class="container-fluid">
		<div class="row">
			<div class="col col-md-5">
				<div class="form-group">
					<label class="sr-only" for="filter_search">{{ trans('search.label') }}</label>
					<span class="input-group">
						<input type="text" name="search" id="filter_search" class="form-control filter" placeholder="{{ trans('search.placeholder') }}" value="{{ $filters['search'] }}" />
						<span class="input-group-append"><span class="input-group-text"><span class="icon-search" aria-hidden="true"></span></span></span>
					</span>
				</div>
			</div>
			<div class="col col-md-3 text-right">
				<label class="sr-only" for="filter_contactreporttypeid">{{ trans('contactreports::contactreports.type') }}:</label>
				<select name="type" id="filter_contactreporttypeid" class="form-control filter filter-submit">
					<option value="*"<?php if ($filters['type'] == '*') { echo ' selected="selected"'; } ?>>{{ trans('contactreports::contactreports.all types') }}</option>
					<option value="0"<?php if (!$filters['type']) { echo ' selected="selected"'; } ?>>{{ trans('global.none') }}</option>
					@foreach ($types as $type)
						<option value="{{ $type->id }}"<?php if ($filters['type'] == $type->id) { echo ' selected="selected"'; } ?>>{{ $type->name }}</option>
					@endforeach
				</select>
			</div>
			<div class="col col-md-2">
				<label class="sr-only" for="filter_start">{{ trans('contactreports::contactreports.start') }}</label>
				<span class="input-group">
					<input type="text" name="start" id="filter_start" class="form-control filter filter-submit date" value="{{ $filters['start'] }}" placeholder="Start date" />
					<span class="input-group-append"><span class="input-group-text"><span class="icon-calendar" aria-hidden="true"></span></span>
				</span>
			</div>
			<div class="col col-md-2">
				<label class="sr-only" for="filter_stop">{{ trans('contactreports::contactreports.stop') }}</label>
				<span class="input-group">
					<input type="text" name="stop" id="filter_stop" class="form-control filter filter-submit date" value="{{ $filters['stop'] }}" placeholder="End date" />
					<span class="input-group-append"><span class="input-group-text"><span class="icon-calendar" aria-hidden="true"></span></span></span>
				</span>
			</div>
		</div>

		<input type="hidden" name="order" value="{{ $filters['order'] }}" />
		<input type="hidden" name="order_dir" value="{{ $filters['order_dir'] }}" />

		<button class="btn btn-secondary sr-only" type="submit">{{ trans('search.submit') }}</button>
	</fieldset>

	<div class="card mb-4">
	<table class="table table-hover adminlist">
		<caption class="sr-only">{{ trans('contactreports::contactreports.contact reports') }}</caption>
		<thead>
			<tr>
				@if (auth()->user()->can('delete contactreports'))
					<th>
						{!! Html::grid('checkall') !!}
					</th>
				@endif
				<th scope="col" class="priority-5">
					{!! Html::grid('sort', trans('contactreports::contactreports.id'), 'id', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col">
					{!! Html::grid('sort', trans('contactreports::contactreports.report'), 'report', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col" class="priority-4">
					{!! Html::grid('sort', trans('contactreports::contactreports.group'), 'groupid', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col" class="priority-4">
					{{ trans('contactreports::contactreports.users') }}
				</th>
				<th scope="col" class="priority-4">
					{!! Html::grid('sort', trans('contactreports::contactreports.contacted'), 'datetimecontact', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col" class="priority-2 text-right">
					{{ trans('contactreports::contactreports.comments') }}
				</th>
			</tr>
		</thead>
		<tbody>
		@foreach ($rows as $i => $row)
			<tr>
				@if (auth()->user()->can('delete contactreports'))
					<td>
						{!! Html::grid('id', $i, $row->id) !!}
					</td>
				@endif
				<td class="priority-5">
					{{ $row->id }}
				</td>
				<td>
					@if (auth()->user()->can('edit contactreports'))
						<a href="{{ route('admin.contactreports.edit', ['id' => $row->id]) }}">
							{{ Illuminate\Support\Str::limit($row->report, 70) }}
						</a>
					@else
						<span>
							{{ Illuminate\Support\Str::limit($row->report, 70) }}
						</span>
					@endif
					@if (count($row->tags))
						<br />
						@foreach ($row->tags as $tag)
							<a class="badge badge-sm badge-secondary" href="{{ route('admin.contactreports.index', ['tag' => $tag->slug]) }}">{{ $tag->name }}</a>
						@endforeach
					@endif
				</td>
				<td class="priority-4">
					@if ($row->group && $row->group->id)
						{{ $row->group->name }}
					@else
						<span class="none">{{ trans('global.none') }}</span>
					@endif
				</td>
				<td class="priority-4">
					<?php
					$users = array();
					foreach ($row->users as $user)
					{
						$u = ($user->user ? $user->user->name : $user->userid . ' <span class="unknown">' . trans('global.unknown') . '</span>');
						
						if ($user->notified()):
							$u .= ' <time datetime="' . $user->datetimelastnotify->toDateTimeString() . '">' . $user->datetimelastnotify->format('Y-m-d') . '</time>';
						endif;

						$users[] = $u;
					}
					?>
					@if (count($users))
						{!! implode('<br />', $users) !!}
					@else
						<span class="none">{{ trans('global.none') }}</span>
					@endif
				</td>
				<td class="priority-4">
					<span class="datetime">
						@if ($row->datetimecontact && $row->datetimecontact != '0000-00-00 00:00:00' && $row->datetimecontact != '-0001-11-30 00:00:00')
							<time datetime="{{ $row->datetimecontact }}">
								@if ($row->datetimecontact->getTimestamp() > Carbon\Carbon::now()->getTimestamp())
									{{ $row->datetimecontact->diffForHumans() }}
								@else
									{{ $row->datetimecontact->format('Y-m-d') }}
								@endif
							</time>
						@else
							<span class="unknown">{{ trans('global.unknown') }}</span>
						@endif
					</span>
				</td>
				<td class="priority-4 text-right">
					<?php /*<a href="{{ route('admin.contactreports.comments', ['report' => $row->id]) }}">*/?>
						{{ $row->comments_count }}
					<?php /*</a>*/ ?>
				</td>
			</tr>
		@endforeach
		</tbody>
	</table>
	</div>

	{{ $rows->render() }}

	<input type="hidden" name="boxchecked" value="0" />

	@csrf
</form>

@stop