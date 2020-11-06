@extends('layouts.master')

@push('styles')
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/tagsinput/jquery.tagsinput.css') }}" />
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/news/css/news.css') }}" />
@endpush

@push('scripts')
<script src="{{ asset('modules/core/vendor/tagsinput/jquery.tagsinput.js') }}"></script>
<script src="{{ asset('modules/news/js/site.js') }}"></script>
@endpush

@section('content')
<div class="sidenav col-lg-3 col-md-3 col-sm-12 col-xs-12">
	@include('news::site.menu', ['types' => $types, 'active' => 'search'])
</div>

<div class="contentInner col-lg-9 col-md-9 col-sm-12 col-xs-12">
	<div id="everything">
		<form method="get" action="{{ route('site.news.search') }}">
			<fieldset>
				<legend>Search News</legend>

				<div class="form-group row tab-search tab-add tab-edit" id="TR_date">
							<label for="datestartshort" class="col-sm-2 col-form-label">Date from</label>
							<div class="col-sm-4">
								<?php
								$startdate = '';
								$starttime = '';
								if ($value = $filters['start'])
								{
									$value = explode('!', $value);
									$startdate = $value[0];
									if (isset($value[1]))
									{
										$starttime = $value[1];
										// Convert to human readable form
										$values = explode(':', $starttime);
										if ($values[0] > 12)
										{
											$values[0] -= 12;
											$starttime = $values[0] . ':' . $values[1] . ' PM';
										}
										else if ($values[0] == 12)
										{
											$starttime = $values[0] . ':' . $values[1] . ' PM';
										}
										else if ($values[0] == 0)
										{
											$values[0] += 12;
											$starttime = $values[0] . ':' . $values[1] . ' AM';
										}
										else
										{
											$starttime = $values[0] . ':' . $values[1] . ' AM';
										}
										$starttime = preg_replace('/^0/', '', $starttime);
									}
								}

								$stopdate = '';
								$stoptime = '';

								$value = $filters['stop'];
								if ($value && $value != '0000-00-00 00:00:00')
								{
									$value = explode('!', $value);
									$stopdate = $value[0];
									if (isset($value[1]) && $value[1] != '00:00:00')
									{
										$stoptime = $value[1];
										// Convert to human readable form
										$values = explode(':', $stoptime);
										if ($values[0] > 12)
										{
											$values[0] -= 12;
											$stoptime = $values[0] . ':' . $values[1] . ' PM';
										}
										else if ($values[0] == 12)
										{
											$stoptime = $values[0] . ':' . $values[1] . ' PM';
										}
										else if ($values[0] == 0)
										{
											$values[0] += 12;
											$stoptime = $values[0] . ':' . $values[1] . ' AM';
										}
										else
										{
											$stoptime = $values[0] . ':' . $values[1] . ' AM';
										}
										$stoptime = preg_replace('/^0/', '', $stoptime);
									}
								}

								if ($starttime == '12:00 AM' && $stoptime == '12:00 AM')
								{
									$starttime = $stoptime;
								}
								?>
								<div class="input-group">
									<span class="input-group-addon"><span class="input-group-text fa fa-calendar" aria-hidden="true"></span></span>
									<input id="datestartshort" type="text" class="date-pick form-control" name="start" placeholder="YYYY-MM-DD" data-start="{{ $startdate }}" value="{{ $startdate }}" />
								</div>
								<div class="input-group input-time tab-add tab-edit hide">
									<span class="input-group-addon"><span class="input-group-text fa fa-clock-o" aria-hidden="true"></span></span>
									<input id="timestartshort" type="text" class="time-pick form-control" name="starttime" placeholder="h:mm AM/PM" value="{{ $starttime }}" />
								</div>
							</div>

							<label for="datestopshort" class="col-sm-2 col-form-label align-right">Date to</label>
							<div class="col-sm-4">
								<div class="input-group" id="enddate">
									<span class="input-group-addon"><span class="input-group-text fa fa-calendar" aria-hidden="true"></span></span>
									<input id="datestopshort" type="text" class="date-pick form-control" name="stop" placeholder="YYYY-MM-DD" data-stop="{{ $stopdate }}" value="{{ $stopdate }}">
								</div>
								<div class="input-group input-time tab-add tab-edit hide">
									<span class="input-group-addon"><span class="input-group-text fa fa-clock-o" aria-hidden="true"></span></span>
									<input id="timestopshort" type="text" class="time-pick form-control" name="stoptime" placeholder="h:mm AM/PM" value="{{ $stoptime }}" />
								</div>
							</div>
						</div>
				<div class="form-group row" id="TR_newstype">
					<label for="newstype" class="col-sm-2 col-form-label">News Type</label>
					<div class="col-sm-10">
						<select id="newstype" name="newstype" class="form-control">
							<option id="OPTION_all" name="all" value="-1">All</option>
							@foreach ($types as $type)
								<option value="{{ $type->id }}"<?php if ($filters['newstype'] == $type->id) { echo ' selected="selected"'; } ?> data-tagresources="{{ $type->tagresources }}" data-taglocation="{{ $type->location }}">{{ $type->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				<div class="form-group row" id="TR_keywords">
					<label for="keywords" class="col-sm-2 col-form-label">Keywords</label>
					<div class="col-sm-10">
						<input type="text" v-model="keywords" v-on:keyup="read" name="keyword" id="keywords" size="45" class="form-control" value="{{ $filters['keyword'] }}" />
					</div>
				</div>
				<div class="form-group row tab-search tab-add tab-edit" id="TR_resource">
							<label for="newsresource" class="col-sm-2 col-form-label">Resource</label>
							<div class="col-sm-10">
								<?php
								$resources = array();
								if ($res = $filters['resource'])
								{
									foreach (explode(',', $res) as $r)
									{
										if (trim($r))
										{
											$resource = App\Modules\Resources\Entities\Asset::findOrFail($r);
											$resources[] = $resource->name . ':' . $r . '';
										}
									}
								}
								?>
								<input name="resource" id="newsresource" size="45" class="form-control" value="{{ implode(',', $resources) }}" data-uri="{{ route('api.resources.index') }}?search=%s" />
							</div>
						</div>
				<div class="form-group row" id="TR_location">
					<label for="location" class="col-sm-2 col-form-label">Location</label>
					<div class="col-sm-10">
						<input name="location" id="location" type="text" size="45" maxlength="32" class="form-control" value="{{ $filters['location'] }}" />
					</div>
				</div>
				<div class="form-group row" id="TR_id">
					<label for="id" class="col-sm-2 col-form-label">NEWS#</label>
					<div class="col-sm-10">
						<input name="id" type="text" id="id" size="45" class="form-control" value="{{ $filters['id'] }}" />
					</div>
				</div>
				<div class="form-group row" id="TR_search">
					<div class="col-sm-2">
					</div>
					<div class="col-sm-10 offset-sm-10">
						<input type="submit" class="btn btn-primary" value="Search" id="INPUT_search" />
						<input type="reset" class="btn btn-secondary" value="Clear" id="INPUT_clear" />
					</div>
				</div>

				<span id="TAB_search_action"></span>
				<span id="TAB_add_action"></span>

				<input type="hidden" name="page" id="page" value="{{ $filters['page'] }}" />
			</fieldset>
		</form>

		<?php
		$string = array();
		foreach ($filters as $key => $val)
		{
			if (!$val)
			{
				continue;
			}
			$string[] = $key . '=' . $val;
		}
		$string = implode('&', $string);
		?>

		<p><strong id="matchingnews">Search results:</strong></p>
		<div id="news" data-query="{{ $string }}">
			News stories are loading...
		</div>

		<div id="preview"></div>
		<div id="mailpreview"></div>
	</div>

	<?php /*<div id="app">
		<example-component></example-component>
	</div>
	<script type="text/javascript" src="{{ asset('js/app.js') }}"></script>*/ ?>
</div>
@stop