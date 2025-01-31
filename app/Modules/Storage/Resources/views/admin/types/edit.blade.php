@extends('layouts.master')

@php
app('request')->merge(['hidemainmenu' => 1]);

app('pathway')
	->append(
		trans('storage::storage.module name'),
		route('admin.storage.index')
	)
	->append(
		trans('storage::storage.notification types'),
		route('admin.storage.types')
	)
	->append(
		($row->id ? trans('global.edit') . ' #' . $row->id : trans('global.create'))
	);
@endphp

@section('toolbar')
	@if (auth()->user()->can('edit storage'))
		{!! Toolbar::save(route('admin.storage.types.store')) !!}
	@endif

	{!!
		Toolbar::spacer();
		Toolbar::cancel(route('admin.storage.types'));
	!!}

	{!! Toolbar::render() !!}
@stop

@section('title')
{{ trans('storage::storage.module name') }}: {{ trans('storage::storage.notification types') }}: {{ ($row->id ? trans('global.edit') . ' #' . $row->id : trans('global.create')) }}
@stop

@section('content')
<form action="{{ route('admin.storage.types.store') }}" method="post" name="adminForm" id="item-form" class="editform">
	<div class="row">
		<div class="col col-md-7">
			<fieldset class="adminform">
				<legend>{{ trans('global.details') }}</legend>

				<div class="form-group">
					<label for="field-name">{{ trans('storage::storage.name') }}: <span class="required">{{ trans('global.required') }}</span></label>
					<input type="text" name="fields[name]" id="field-name" class="form-control{{ $errors->has('fields.name') ? ' is-invalid' : '' }}" required maxlength="100" value="{{ $row->name }}" />
					<span class="invalid-feedback">{{ trans('storage::storage.error.invalid name') }}</span>
				</div>

				<div class="form-group">
					<label for="field-defaulttimeperiodid">{{ trans('storage::storage.time period') }}:</label>
					<select name="fields[defaulttimeperiodid]" id="field-defaulttimeperiodid" class="form-control">
						<option value="0">{{ trans('global.none') }}</option>
						<?php foreach ($timeperiods as $timeperiod): ?>
							<?php $selected = ($timeperiod->id == $row->defaulttimeperiodid ? ' selected="selected"' : ''); ?>
							<option value="{{ $timeperiod->id }}"<?php echo $selected; ?>>{{ $timeperiod->name }}</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="field-importhostname">{{ trans('storage::storage.value type') }}:</label>
					<select name="fields[valuetype]" id="field-valuetype" class="form-control">
						<option value="1"<?php echo ($row->valuetype == 1 ? ' selected="selected"' : ''); ?>>{{ trans('global.none') }}</option>
						<option value="2"<?php echo ($row->valuetype == 2 ? ' selected="selected"' : ''); ?>>{{ trans('storage::storage.bytes') }}</option>
						<option value="3"<?php echo ($row->valuetype == 3 ? ' selected="selected"' : ''); ?>>{{ trans('storage::storage.percent') }}</option>
						<option value="4"<?php echo ($row->valuetype == 4 ? ' selected="selected"' : ''); ?>>{{ trans('storage::storage.number') }}</option>
					</select>
				</div>
			</fieldset>
		</div>
		<div class="col col-md-5">
			<table class="meta">
				<caption class="sr-only">{{ trans('global.metadata') }}</caption>
				<tbody>
					<tr>
						<th scope="row">{{ trans('storage::storage.notifications') }}</th>
						<td>{{ number_format($row->notifications_count) }}</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<input type="hidden" name="id" id="field-id" value="{{ $row->id }}" />

	@csrf
</form>
@stop