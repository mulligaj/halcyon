@extends('layouts.master')

@push('scripts')
<script src="{{ Module::asset('pages:js/pages.js') . '?v=' . filemtime(public_path() . '/modules/pages/js/pages.js') }}"></script>
@endpush

@php
app('pathway')
	->append(
		trans('pages::pages.module name'),
		route('admin.pages.index')
	);
/*		@if (auth()->user()->can('manage pages'))
			{!!
				Toolbar::checkin('admin.pages.checkin');
				Toolbar::spacer();
			!!}
		@endif*/
@endphp

@section('toolbar')
	@if ($filters['state'] == 'trashed')
		@if (auth()->user()->can('edit.state pages'))
			{!!
				Toolbar::publishList(route('admin.pages.restore'), 'Restore');
				Toolbar::custom(route('admin.pages.restore'), 'refresh', 'refresh', 'Restore', false);
				Toolbar::spacer();
			!!}
		@endif
		@if (auth()->user()->can('delete pages'))
			{!! Toolbar::deleteList(trans('global.confirm delete'), route('admin.pages.delete')) !!}
		@endif
	@else
		@if (auth()->user()->can('edit.state pages'))
			{!!
				Toolbar::publishList(route('admin.pages.publish'));
				Toolbar::unpublishList(route('admin.pages.unpublish'));
				Toolbar::spacer();
			!!}
		@endif
		@if (auth()->user()->can('delete pages'))
			{!! Toolbar::deleteList(trans('global.confirm delete'), route('admin.pages.delete')) !!}
		@endif
		@if (auth()->user()->can('create pages'))
			{!! Toolbar::addNew(route('admin.pages.create')) !!}
		@endif
		@if (auth()->user()->can('admin pages'))
			{!!
				Toolbar::spacer();
				Toolbar::preferences('pages')
			!!}
		@endif
	@endif

	{!! Toolbar::render() !!}
@stop

@section('title')
{!! config('pages.name') !!}
@stop

@section('content')
<form action="{{ route('admin.pages.index') }}" method="post" name="adminForm" id="adminForm" class="form-inline">

	<fieldset id="filter-bar" class="container-fluid">
		<div class="row">
			<div class="col col-md-4 filter-search">
				<div class="form-group">
					<label class="sr-only" for="filter_search">{{ trans('search.label') }}</label>
					<span class="input-group">
						<input type="text" name="search" id="filter_search" class="form-control filter" placeholder="{{ trans('search.placeholder') }}" value="{{ $filters['search'] }}" />
						<span class="input-group-append"><span class="input-group-text"><span class="icon-search" aria-hidden="true"></span></span></span>
					</span>
				</div>
			</div>
			<div class="col col-md-8 filter-select text-right">
				<label class="sr-only" for="filter_state">{{ trans('pages::pages.state') }}</label>
				<select name="state" class="form-control filter filter-submit">
					<option value="*"<?php if ($filters['state'] == '*'): echo ' selected="selected"'; endif;?>>{{ trans('pages::pages.state_all') }}</option>
					<option value="published"<?php if ($filters['state'] == 'published'): echo ' selected="selected"'; endif;?>>{{ trans('global.published') }}</option>
					<option value="unpublished"<?php if ($filters['state'] == 'unpublished'): echo ' selected="selected"'; endif;?>>{{ trans('global.unpublished') }}</option>
					<option value="trashed"<?php if ($filters['state'] == 'trashed'): echo ' selected="selected"'; endif;?>>{{ trans('global.trashed') }}</option>
				</select>

				<label class="sr-only" for="filter-access">{{ trans('pages::pages.access level') }}</label>
				<select name="access" id="filter-access" class="form-control filter filter-submit">
					<option value="">{{ trans('pages::pages.access select') }}</option>
					<?php foreach (App\Halcyon\Access\Viewlevel::all() as $access): ?>
						<option value="<?php echo $access->id; ?>"<?php if ($filters['access'] == $access->id) { echo ' selected="selected"'; } ?>><?php echo e($access->title); ?></option>
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
		<thead>
			<tr>
				<th>
					{!! Html::grid('checkall') !!}
				</th>
				<th scope="col" class="priority-5">
					{!! Html::grid('sort', trans('pages::pages.id'), 'id', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col">
					{!! Html::grid('sort', trans('pages::pages.title'), 'title', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col">
					{!! Html::grid('sort', trans('pages::pages.path'), 'path', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col">
					{!! Html::grid('sort', trans('pages::pages.state'), 'state', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col">
					{!! Html::grid('sort', trans('pages::pages.access'), 'access', $filters['order_dir'], $filters['order']) !!}
				</th>
				<th scope="col" class="priority-4">
					{!! Html::grid('sort', trans('pages::pages.updated'), 'updated_at', $filters['order_dir'], $filters['order']) !!}
				</th>
			</tr>
		</thead>
		<tbody>
		@foreach ($rows as $i => $row)
			<tr>
				<td>
					@if ($row->parent_id != 0)
						@if (auth()->user()->can('manage pages'))
						<span class="form-check"><input type="checkbox" name="id[]" id="cb{{ $i }}" value="{{ $row->id }}" class="form-check-input checkbox-toggle" /><label for="cb{{ $i }}"></label></span>
						@endif
					@endif
				</td>
				<td class="priority-5">
					{{ $row->id }}
				</td>
				<td>
					<?php echo str_repeat('<span class="gi">|&mdash;</span>', $row->level); ?>
					@if (auth()->user()->can('edit pages'))
						<a href="{{ route('admin.pages.edit', ['id' => $row->id]) }}">
							{{ $row->title }}
						</a>
					@else
						{{ $row->title }}
					@endif
				</td>
				<td>
					<a href="{{ route('admin.pages.edit', ['id' => $row->id]) }}">
						/{{ ltrim($row->path, '/') }}
					</a>
				</td>
				<td>
					@if ($row->isRoot())
						<span class="state published">
							{{ trans('pages::pages.published') }}
						</span>
					@else
						@if ($row->trashed())
							@if (auth()->user()->can('edit pages'))
								<a class="btn btn-secondary state trashed" href="{{ route('admin.pages.restore', ['id' => $row->id]) }}" title="{{ trans('pages::pages.set state to', ['state' => trans('global.published')]) }}">
							@endif
								{{ trans('pages::pages.trashed') }}
							@if (auth()->user()->can('edit pages'))
								</a>
							@endif
						@elseif ($row->state == 1)
							@if (auth()->user()->can('edit pages'))
								<a class="btn btn-secondary state published" href="{{ route('admin.pages.unpublish', ['id' => $row->id]) }}" title="{{ trans('pages::pages.set state to', ['state' => trans('global.unpublished')]) }}">
							@endif
								{{ trans('pages::pages.published') }}
							@if (auth()->user()->can('edit pages'))
								</a>
							@endif
						@else
							@if (auth()->user()->can('edit pages'))
								<a class="btn btn-secondary state unpublished" href="{{ route('admin.pages.publish', ['id' => $row->id]) }}" title="{{ trans('pages::pages.set state to', ['state' => trans('global.published')]) }}">
							@endif
								{{ trans('pages::pages.unpublished') }}
							@if (auth()->user()->can('edit pages'))
								</a>
							@endif
						@endif
					@endif
				</td>
				<td>
					<span class="badge access {{ str_replace(' ', '', strtolower($row->viewlevel->title)) }}">{{ $row->viewlevel->title }}</span>
				</td>
				<td class="priority-4">
					<span class="datetime">
						@if ($row->getOriginal('updated_at') && $row->getOriginal('updated_at') != '0000-00-00 00:00:00')
							<time datetime="{{ Carbon\Carbon::parse($row->updated_at)->format('Y-m-d\TH:i:s\Z') }}">{{ $row->updated_at }}</time>
						@else
							@if ($row->getOriginal('created_at') && $row->getOriginal('created_at') != '0000-00-00 00:00:00')
								<time datetime="{{ Carbon\Carbon::parse($row->created_at)->format('Y-m-d\TH:i:s\Z') }}">
									@if ($row->getOriginal('created_at') > Carbon\Carbon::now()->toDateTimeString())
										{{ $row->created_at->diffForHumans() }}
									@else
										{{ $row->created_at }}
									@endif
								</time>
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