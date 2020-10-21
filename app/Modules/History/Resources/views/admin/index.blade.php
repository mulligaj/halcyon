@extends('layouts.master')

@section('scripts')
<script src="{{ asset('modules/history/js/admin.js?v=' . filemtime(public_path() . '/modules/history/js/admin.js')) }}"></script>
@stop

@php
app('pathway')
	->append(
		trans('history::history.module name'),
		route('admin.history.index')
	);
@endphp

@section('toolbar')
	@if (auth()->user()->can('admin history'))
		{!!
			Toolbar::spacer();
			Toolbar::preferences('history');
		!!}
	@endif

	{!! Toolbar::render() !!}
@stop

@section('title')
{{ trans('history::history.history manager') }}
@stop

@section('content')
<form action="{{ route('admin.history.index') }}" method="post" name="adminForm" id="adminForm" class="form-inline">

	<fieldset id="filter-bar" class="container-fluid">
		<div class="row">
			<div class="col filter-search col-md-4">
				<div class="form-group">
					<label class="sr-only" for="filter_search">{{ trans('search.label') }}</label>
					<span class="input-group">
						<input type="text" name="search" id="filter_search" class="form-control filter" placeholder="{{ trans('search.placeholder') }}" value="{{ $filters['search'] }}" />
						<span class="input-group-append"><span class="input-group-text"><span class="icon-search" aria-hidden="true"></span></span></span>
					</span>
				</div>
			</div>
			<div class="col filter-select col-md-8 text-right">
				<label class="sr-only" for="filter_action">{{ trans('history::history.action') }}</label>
				<select name="action" class="form-control filter filter-submit">
					<option value=""<?php if ($filters['action'] == ''): echo ' selected="selected"'; endif;?>>{{ trans('history::history.all actions') }}</option>
					<option value="created"<?php if ($filters['action'] == 'created'): echo ' selected="selected"'; endif;?>>created</option>
					<option value="updated"<?php if ($filters['action'] == 'updated'): echo ' selected="selected"'; endif;?>>updated</option>
					<option value="deleted"<?php if ($filters['action'] == 'deleted'): echo ' selected="selected"'; endif;?>>deleted</option>
				</select>

				<label class="sr-only" for="filter_type">{{ trans('history::history.type') }}</label>
				<select name="type" class="form-control filter filter-submit">
					<option value=""<?php if ($filters['type'] == ''): echo ' selected="selected"'; endif;?>>{{ trans('history::history.all types') }}</option>
					<?php foreach ($types as $type): ?>
						<option value="created"<?php if ($filters['type'] == $type->historable_type): echo ' selected="selected"'; endif;?>>{{ $type->historable_type }}</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<input type="hidden" name="order" value="{{ $filters['order'] }}" />
		<input type="hidden" name="order_dir" value="{{ $filters['order_dir'] }}" />

		<button class="btn btn-secondary sr-only" type="submit">{{ trans('search.submit') }}</button>
	</fieldset>

	<div class="card mb-4">
	<table class="table table-hover adminlist">
		<caption class="sr-only">{{ trans('history::history.history manager') }}</caption>
		<thead>
			<tr>
				<th>
					<span class="form-check"><input type="checkbox" name="toggle" value="" id="toggle-all" class="form-check-input checkbox-toggle toggle-all" /><label for="toggle-all"></label></span>
				</th>
				<th scope="col">{{ trans('history::history.id') }}</th>
				<th scope="col">{{ trans('history::history.item id') }}</th>
				<th scope="col">{{ trans('history::history.item type') }}</th>
				<th scope="col">{{ trans('history::history.item table') }}</th>
				<th scope="col">{{ trans('history::history.action') }}</th>
				<th scope="col">{{ trans('history::history.actor') }}</th>
				<th scope="col" class="priority-4">{{ trans('history::history.timestamp') }}</th>
			</tr>
		</thead>
		<tbody>
		@foreach ($rows as $i => $row)
			<tr>
				<td>
					<span class="form-check"><input type="checkbox" name="id[]" id="cb{{ $i }}" value="{{ $row->id }}" class="form-check-input checkbox-toggle" /><label for="cb{{ $i }}"></label></span>
				</td>
				<td class="priority-5">
					{{ $row->id }}
				</td>
				<td>
					<a href="{{ route('admin.history.show', ['id' => $row->id]) }}">
						{{ $row->historable_id }}
					</a>
				</td>
				<td>
					<a href="{{ route('admin.history.show', ['id' => $row->id]) }}">
						{{ $row->historable_type }}
					</a>
				</td>
				<td>
					{{ $row->historable_table }}
				</td>
				<td>
					{{ $row->action }}
				</td>
				<td>
					@if ($row->user)
						{{ $row->user->name }}
					@else
						<span class="unknown">{{ trans('global.unknown') }}</span>
					@endif
				</td>
				<td class="priority-4">
					<span class="datetime">
						@if ($row->getOriginal('updated_at') && $row->getOriginal('updated_at') != '0000-00-00 00:00:00')
							<time datetime="{{ Carbon\Carbon::parse($row->updated_at)->format('Y-m-d\TH:i:s\Z') }}">{{ $row->updated_at }}</time>
						@else
							@if ($row->getOriginal('created_at') && $row->getOriginal('created_at') != '0000-00-00 00:00:00')
								<time datetime="{{ Carbon\Carbon::parse($row->created_at)->format('Y-m-d\TH:i:s\Z') }}">{{ $row->created_at }}</time>
							@else
								<span class="never">{{ trans('global.unknown') }}</span>
							@endif
						@endif
					</span>
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