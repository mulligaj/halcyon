@extends('layouts.master')

@section('styles')
<link rel="stylesheet" type="text/css" media="all" href="{{ asset('modules/core/vendor/fancytree/skin-xp/ui.fancytree.css') }}" />
@stop

@section('scripts')
<script src="{{ asset('modules/core/vendor/fancytree/jquery.fancytree-all.js') }}"></script>
<script src="{{ asset('modules/storage/js/admin.js?v=' . filemtime(public_path() . '/modules/storage/js/admin.js')) }}"></script>
@stop

@php
app('pathway')
	->append(
		trans('storage::storage.module name'),
		route('admin.storage.index')
	)
	->append(
		trans('storage::storage.directories'),
		route('admin.storage.directories')
	)
	->append(
		($row->id ? trans('global.edit') . ' #' . $row->id : trans('global.create'))
	);
@endphp

@section('toolbar')
	@if (auth()->user()->can('edit storage'))
		{!! Toolbar::save(route('admin.storage.directories.store')) !!}
	@endif

	{!!
		Toolbar::spacer();
		Toolbar::cancel(route('admin.storage.directories.cancel'));
	!!}

	{!! Toolbar::render() !!}
@stop

@section('title')
{!! config('storage.name') !!}: {{ $row->id ? 'Edit: #' . $row->id : 'Create' }}
@stop

@section('content')
<form action="{{ route('admin.storage.directories.store') }}" method="post" name="adminForm" id="item-form" class="editform form-validate">
	<div class="row">
		<div class="col col-md-7">
			<fieldset class="adminform">
				<legend>{{ trans('global.details') }}</legend>

				<div class="form-group">
					<label for="storageresourceid">{{ trans('storage::storage.FIELD_PARENT') }}: <span class="required">{{ trans('global.required') }}</span></label>
					<select name="fields[storageresourceid]" id="storageresourceid" class="form-control">
						<option value="0"><?php echo trans('global.none'); ?></option>
						<?php foreach ($storageresources as $s): ?>
							<?php $selected = ($s->id == $row->storageresourceid ? ' selected="selected"' : ''); ?>
							<option value="{{ $s->id }}"<?php echo $selected; ?>>{{ $s->name }}</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="field-name">{{ trans('storage::storage.name') }}: <span class="required">{{ trans('global.required') }}</span></label>
					<input type="text" name="fields[name]" id="field-name" class="form-control required" value="{{ $row->name }}" />
				</div>

				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="groupid">{{ trans('storage::storage.group') }}:</label>
							<span class="input-group">
								<input type="text" name="fields[groupid]" id="groupid" class="form-control form-groups" data-uri="{{ url('/') }}/api/groups/?api_token={{ auth()->user()->api_token }}&search=%s" value="{{ ($row->group ? $row->group->name . ':' . $row->groupid : '') }}" />
								<span class="input-group-append"><span class="input-group-text icon-users"></span></span>
							</span>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="field-owneruserid">{{ trans('storage::storage.owner') }}:</label>
							<span class="input-group">
								<input type="text" name="fields[owneruserid]" id="field-owneruserid" class="form-control form-users" data-uri="{{ url('/') }}/api/users/?api_token={{ auth()->user()->api_token }}&search=%s" value="{{ ($row->owner ? $row->owner->name . ':' . $row->owneruserid : '') }}" />
								<span class="input-group-append"><span class="input-group-text icon-user"></span></span>
							</span>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label for="field-bytes">{{ trans('storage::storage.quota') }}:</label>
					<input type="text" name="fields[bytes]" id="field-bytes" class="form-control" value="{{ App\Halcyon\Utility\Number::formatBytes($row->bytes) }}" />
				</div>
			</fieldset>
@if ($row->id)
			<fieldset class="adminform">
				<legend>{{ trans('storage::storage.directories') }}</legend>

				<button class="btn btn-sm btn-secondary"><span class="icon-plus"></span> {{ trans('global.button.create') }}</button>

				<div id="new_dir_dialog" title="Add new directory" class="dialog">
							<div class="form-group">
								<label for="new_dir_type">Name:</label>
								<span class="input-group">
									<span class="input-group-prepend"><span class="input-group-text">{{ $row->storageResource->path }}/<span id="new_dir_path"></span></span></span>
									<input type="text" id="new_dir_input" class="form-control" />
								</span>
							</div>
							<div class="form-group">
								<label for="new_dir_type">Type:</label>
									<select id="new_dir_type" class="form-control">
										<option value="normal">Group Shared</option>
										<option value="autouserread">Auto User - Group Readable</option>
										<option value="autouserreadwrite">Auto User - Group Readable & Writeable</option>
										<option value="autouserprivate">Auto User - Private</option>
										<option value="user">User Owned - Group Readable</option>
										<option value="userwrite">User Owned - Group Writeable</option>
										<option value="userprivate">User Owned - Private</option>
									</select>
							</div>
							<fieldset>
								<legend>Quota:</legend>
								<div class="form-group">
									<div class="form-check">
										<input type="radio" name="usequota" value="parent" class="form-check-input" checked="true" id="share_radio" />
										<label class="form-check-label" for="share_radio">Share with parent quota (<span id="new_dir_quota_available"></span>)</label>
									</div>
								</div>
								<div class="form-group">
									<div class="form-check">
										<input type="radio" name="usequota" id="deduct_radio" class="form-check-input" value="deduct" />
										<label class="form-check-label" for="deduct_radio">Deduct from parent quota (<span id="new_dir_quota_available2"></span>):</label>

										<input type="text" id="new_dir_quota_deduct" class="form-control" size="3" />
									<?php
									$bucket = null;
									foreach ($row->group->storageBuckets as $bucket)
									{
										if ($bucket['resourceid'] == $row->storageResource->parentresourceid)
										{
											break;
										}
									}

									$style = '';
									$disabled = '';
									if ($bucket['unallocatedbytes'] == 0)
									{
										$disabled = 'disabled="true"';
										$style = 'color:gray';
									}
									?>
									</div>
								</div>
								<div class="form-group">
									<div class="form-check">
										<input <?php echo $disabled; ?> type="radio" name="usequota" value="unalloc" id="unalloc_radio" class="form-check-input" />
										<label class="form-check-label" for="unalloc_radio">
											<span style="<?php echo $style; ?>" id="unalloc_span">
												Deduct from unallocated space (<span name="unallocated"><?php echo $bucket['unallocatedbytes']; ?></span>):
											</span>
										</label>
										<input <?php echo $disabled; ?> type="text" id="new_dir_quota_unalloc" class="form-control" size="3" />
									</div>
								</div>
							</fieldset>
							<div class="form-group">
								<label for="new_dir_unixgroup_select">Access Unix Group:</label>
								<select id="new_dir_unixgroup_select" class="form-control">
									<option value="">(Select Unix Group)</option>
									<?php foreach ($row->group->unixgroups as $unixgroup) { ?>
										<option value="<?php echo $unixgroup->id; ?>" data-api="{{ route('api.unixgroups.read', ['id' => $unixgroup->id]) }}"><?php echo $unixgroup->longname; ?></option>
									<?php } ?>
								</select>
								<select id="new_dir_unixgroup_select_decoy" class="form-control d-none">
								</select>
							</div>
							<div id="new_dir_autouserunixgroup_row" class="form-group d-none">
								<label for="new_dir_autouserunixgroup_select">Populating Unix Group</label>
								<select id="new_dir_autouserunixgroup_select" class="form-control">
									<option value="">(Select Unix Group)</option>
									<?php foreach ($row->group->unixgroups as $unixgroup) { ?>
										<option value="<?php echo $unixgroup->id; ?>"><?php echo $unixgroup->longname; ?></option>
									<?php } ?>
								</select>
							</div>
							<div id="new_dir_user_row" class="form-group d-none">
								<label for="new_dir_user_select">User:</label>
								<select id="new_dir_user_select" class="form-control">
									<option value="">(Select User)</option>
								</select>
							</div>
							<div class="form-group text-right">
								<button id="new_dir" class="btn btn-success" data-api="{{ route('api.storage.directories.create') }}">
									<span id="new_dir_img" class="icon-plus"></span> Create directory
								</button>
							</div>

					<p><span id="new_dir_error"></span></p>
				</div>

				<table id="tree" class="tree">
					<thead>
						<tr>
							<th scope="col">Directory</th>
							<th scope="col" class="quota">Current Quota</th>
							<th scope="col" class="quota">Future Quota</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>

				<input type="hidden" id="selected_dir" />
				<input type="hidden" id="selected_dir_unixgroup" />

				<script type="application/json" id="tree-data"><?php
				$data = array($row->tree());
				echo json_encode($data);
				?></script>

				<?php
				$dirhash = array();
				function get_dir($dirs, $dirhash, $id)
				{
					if (count($dirhash) == 0)
					{
						foreach ($dirs as $d)
						{
							$dirhash[$d->id] = $d;
						}
					}

					if (isset($dirhash[$id]))
					{
						return $dirhash[$id];
					}

					return null;
				}
				$configuring = array();
				$removing = array();
				$directories = []; //$row->group->directories;
				foreach ($row->nested() as $dir)
				{
					$did = $dir->id;
					?>
					<div id="<?php echo $did; ?>_dialog" title="<?php echo $dir->storageResource->path . '/' . $dir->path; ?>" class="dialog">
						<fieldset>

							<table class="table editStorageTable">
							<caption class="sr-only"><?php echo $dir->name; ?></caption>
							<tbody>
							<?php if ($dir['quotaproblem'] == 1 && $dir->bytes) { ?>
								<th scope="row">Desired quota</th>
								<td><?php echo App\Halcyon\Utility\Number::formatBytes($dir->bytes); ?></td>
							</tr>
							<tr>
								<th scope="row" class="quotaProblem">
									Actual quota <span class="icon-warning" data-tip="Storage space is over-allocated. Quotas reduced until allocation balanced."></span>
								</th>
								<td class="quotaProblem"><?php echo App\Halcyon\Utility\Number::formatBytes($dir->quota); ?></td>
							<?php } else { ?>
								<th scope="row">Quota</th>
								<td>
									<?php
									$value = App\Halcyon\Utility\Number::formatBytes($dir->bytes, true);
									if (!$dir->bytes)
									{
										$value = '-';
									}
									?>
									<span id="<?php echo $dir->id; ?>_quota_span"><?php echo $value; ?></span>
									<?php if ($dir->bytes) { ?>
										<input type="text" id="<?php echo $dir->id; ?>_quota_input" class="form-control" value="<?php echo $value; ?>" />
									<?php } ?>
								</td>
							<?php } ?>
							</tr>
							<tr>
								<th scope="row">Access Unix Group</th>
								<td>
									<select id="<?php echo $dir->id; ?>_unixgroup_select" class="form-control">
										<option value="0">{{ trans('global.none') }}</option>
										<?php
										foreach ($dir->group->unixgroups as $unixgroup)
										{
											$selected = '';
											if (isset($dir->unixgroup->id) && $unixgroup->id == $dir->unixgroup->id)
											{
												$selected = 'selected="selected"';
											}

											echo '<option ' . $selected . ' value="' . $unixgroup->id . '">' . $unixgroup->longname . '</option>';
										}
										?>
									</select>
								</td>
							</tr>
						<?php if ($dir->autouser) { ?>
							<tr>
								<th scope="row">Populating Unix Group</th>
								<td>
									<span id="<?php echo $dir->id; ?>_autouserunixgroup_span"><?php echo $dir->autouserunixgroup->longname; ?></span>
									<select id="<?php echo $dir->id; ?>_autouserunixgroup_select" class="stash">
										<?php foreach ($dir->group->unixgroups as $unixgroup) { ?>
											<?php
											$selected = '';
											if ($dir->autouserunixgroup && $unixgroup->id == $dir->autouserunixgroup->id)
											{
												$selected = 'selected="selected"';
											}
											?>
											<option <?php echo $selected; ?> value="<?php echo $unixgroup->id; ?>"><?php echo $unixgroup->longname; ?></option>
										<?php } ?>
									</select>
								</td>
							</tr>
						<?php } ?>
						<?php if ($dir->owner && $dir->owner->name != 'root') { ?>
							<tr>
								<th scope="row">Owner</th>
								<td>
									<?php echo $dir->owner->name; ?>
								</td>
							</tr>
							<tr>
								<th scope="row">Type</th>
								<td>
									<!-- <span id="<?php echo $dir->id; ?>_dir_type">
										<?php
										if ($dir->unixPermissions->group->write)
										{
											echo 'User Owned - Group Writable';
										}
										elseif ($dir->unixPermissions->group->read)
										{
											echo 'User Owned - Group Readable';
										}
										else
										{
											echo 'User Owned - Private';
										}
										?>
									</span> -->
									<select id="<?php echo $dir->id; ?>_dir_type_select" class="form-control">
										<?php if ($dir->unixPermissions->group->write) { ?>
											<option selected="selected" value="userwrite">User Owned - Group Writable</option>
											<option value="user">User Owned - Group Readable</option>
											<option value="userprivate">User Owned - Private</option>
										<?php } elseif ($dir->unixPermissions->group->read) { ?>
											<option selected="selected" value="user">User Owned - Group Readable</option>
											<option value="userwrite">User Owned - Group Writable</option>
											<option value="userprivate">User Owned - Private</option>
										<?php } else { ?>
											<option value="user">User Owned - Group Readable</option>
											<option value="userwrite">User Owned - Group Writable</option>
											<option selected="selected" value="userprivate">User Owned - Private</option>
										<?php } ?>
									</select>
								</td>
							</tr>
						<?php } ?>
						<?php if ($dir->autouser) { ?>
							<tr>
								<th scope="row">Auto Populate User Default</th>
								<td>
									<!-- <span id="<?php echo $dir->id; ?>_dir_type">
										<?php
										if ($dir->autouser == '1')
										{
											echo 'Group Readable';
										}
										else if ($dir->autouser == '2')
										{
											echo 'Private';
										}
										else if ($dir->autouser == '3')
										{
											echo 'Group Readable Writable';
										}
										?>
									</span>-->
									<select id="<?php echo $dir->id; ?>_dir_type_select" class="form-control">
										<option value="autouser"<?php if ($dir->autouser == '1') { ?> selected="selected"<?php } ?>>Auto User - Group Readable</
										<option value="autouserreadwrite"<?php if ($dir->autouser == '3') { ?> selected="selected"<?php } ?>>Auto User - Group Readable Writable</option>
										<option value="autouserprivate"<?php if ($dir->autouser == '2') { ?> selected="selected"<?php } ?>>Auto User - Private</option>
									</select>
								</td>
							</tr>
						<?php } ?>
						<?php
						$child_dirs = array();
						$check = array();

						array_push($check, $dir->id);

						while (count($check) > 0)
						{
							$child = null;
							$child = get_dir($directories, $dirhash, array_pop($check));

							if (!$child)
							{
								break;
							}

							if ($child->unixPermissions->other->read || $child->id == $dir->id)
							{
								array_push($child_dirs, $child);

								foreach ($child->children as $d)
								{
									array_push($check, $d->id);
								}
							}
						}

						// Find bottle necks
						$bottle_dirs = array();

						if ($dir->parentstoragedirid)
						{
							$bottle_dir = get_dir($directories, $dirhash, $dir->parentstoragedirid);

							while (1)
							{
								if (!$bottle_dir)
								{
									break;
								}

								if (!$bottle_dir->unixPermissions->other->read)
								{
									array_push($bottle_dirs, $bottle_dir->unixgroup->longname);
								}

								if (!$bottle_dir->parentstoragedirid)
								{
									break;
								}

								$bottle_dir = get_dir($directories, $dirhash, $bottle_dir->parentstoragedirid);
							}
						}

						$bottle_dirs_string = 'Public';
						if (count($bottle_dirs) > 0)
						{
							$bottle_dirs_string = implode(' + ', $bottle_dirs);
						}

						if (count($child_dirs) > 0 && $dir->parentstoragedirid) { ?>
							<tr>
								<th scope="row">Read access for <?php echo $bottle_dirs_string; ?></th>
								<td>
									<?php if ($dir->unixPermissions->other->read) { ?>
										<input type="checkbox" id="<?php echo $dir->id; ?>_other_read_box" checked="checked" class="hide" />
										<span id="<?php echo $dir->id; ?>_other_read_span">{{ trans('global.yes') }}</span>
									<?php } else { ?>
										<input type="checkbox" id="<?php echo $dir->id; ?>_other_read_box" class="hide" />
										<span id="<?php echo $dir->id; ?>_other_read_span">{{ trans('global.no') }}</span>
									<?php } ?>
									to directories:
								</td>
							</tr>
							<?php foreach ($child_dirs as $child) { ?>
								<tr>
									<td></td>
									<td>{{ $child->path }}</td>
								</tr>
							<?php } ?>
						<?php } else if (!$dir->parentstoragedirid) { ?>
							<tr>
								<th scope="row">Public read access?</th>
								<td>
									<span class="form-check">
										<input type="radio" name="<?php echo $dir->id; ?>_other_read_box" id="<?php echo $dir->id; ?>_other_read_box1" <?php if ($dir->unixPermissions->other->read) { ?>checked="checked"<?php } ?> class="form-check-input hide" />
										<label class="form-check-label" for="<?php echo $dir->id; ?>_other_read_box" id="<?php echo $dir->id; ?>_other_read_span">{{ trans('global.yes') }}</label>
									</span>

									<span class="form-check">
										<input type="radio" name="<?php echo $dir->id; ?>_other_read_box" id="<?php echo $dir->id; ?>_other_read_box0" <?php if (!$dir->unixPermissions->other->read) { ?>checked="checked"<?php } ?> class="form-check-input hide" />
										<label class="form-check-label" for="<?php echo $dir->id; ?>_other_read_box" id="<?php echo $dir->id; ?>_other_read_span">{{ trans('global.no') }}</label>
									</span>
								</td>
							</tr>
						<?php } ?>
							<!-- <tr>
								<td></td>
								<td>
									<?php
									$disabled = '';
									if (in_array($dir->id, $removing))
									{
										$disabled = 'disabled="true"';
									}
									?>
									<input <?php echo $disabled; ?> id="<?php echo $dir->id; ?>_edit_button" class="btn btn-secondary unixgroup-edit" data-dir="<?php echo $dir->id; ?>" type="button" value="Edit Directory" />
								</td>
							</tr> -->
						</tbody>
					</table>

					<!-- <fieldset>
						<legend>Permissions</legend>-->
					<div class="card">
						<div class="card-header">
							<div class="row">
							<div class="col-md-6">
								<p class="card-title">Permissions</p>
							</div>
							<div class="col-md-6 text-right">
								@if ($dir->children()->count() == 0)
									<input <?php echo $disabled; ?> id="{{ $dir->id }}_edit_button" class="btn btn-sm btn-secondary permissions-reset" data-dir="{{ $dir->id }}" data-path="{{ $dir->path }}" type="button" value="Fix File Permissions" />
								@endif
							</div>
							</div>
						</div>

						<table class="table table-hover">
							<caption class="sr-only">Permissions</caption>
							<thead>
								<tr>
									<th scope="col">Group</th>
									<th scope="col" class="text-center">Read</th>
									<th scope="col" class="text-center">Write</th>
								</tr>
							</thead>
							<tbody>
							<?php
							$childs = array();

							$highest_read = $dir->id;
							$can_read = true;

							if ($parent = get_dir($directories, $dirhash, $dir->id))
							{
								$childs[] = $parent;
							}

							if ($dir->parentstoragedirid)
							{
								do
								{
									if (!$parent)
									{
										break;
									}
									$parent = get_dir($directories, $dirhash, $parent->parentstoragedirid);
									//array_push($childs, $parent);

									if ($parent->unixPermissions->other->read && $can_read)
									{
										$highest_read = $parent['id'];
									}
									else
									{
										$can_read = false;
									}
								}
								while ($parent->parentstoragedirid);
							}

							$highest = array();
							$highest['unixgroup'] = array('longname' => $bottle_dirs_string);
							if ($dir->unixPermissions->other->read)
							{
								$highest['permissions'] = array('group' => array('write' => 0, 'read' => 1));
							}
							else
							{
								$highest['permissions'] = array('group' => array('write' => 0, 'read' => 0));
							}

							$childs[] = $highest;

							if ($bottle_dirs_string != 'Public')
							{
								$public = array();
								$public['unixgroup'] = array('longname' => 'Public');

								if ($parent['id'] == $highest_read && $can_read)
								{
									$public['permissions'] = array('group' => array('write' => 0, 'read' => 1));
								}
								else
								{
									$public['permissions'] = array('group' => array('write' => 0, 'read' => 0));
								}

								$childs[] = $public;
							}

							foreach ($childs as $child)
							{
								?>
								<tr>
									<td>
										{{ $child['unixgroup']['longname'] }}
									</td>
									<td class="text-center">
										@if ($child['permissions']['group']['read'])
											<span class="glyph icon-check success dirperm">{{ trans('global.yes') }}</span>
										@else
											<span class="glyph icon-x failed dirperm">{{ trans('global.no') }}</span>
										@endif
									</td>
									<td class="text-center">
										@if ($child['permissions']['group']['write'])
											<span class="glyph icon-check success dirperm">{{ trans('global.yes') }}</span>
										@else
											<span class="glyph icon-x failed dirperm">{{ trans('global.no') }}</span>
										@endif
									</td>
								</tr>
								<?php
							}
							/*?>
							@if ($dir->children()->count() == 0)
						</tbody>
						<tfoot>
							<tr>
									<td colspan="3">
									<input <?php echo $disabled; ?> id="{{ $dir->id }}_edit_button" class="btn btn-sm btn-secondary permissions-reset" data-dir="{{ $dir->id }}" data-path="{{ $dir->path }}" type="button" value="Fix File Permissions" />
								</td>
							</tr>
						</tfoot>
								@endif*/?>
						</tbody>
					</table>
					</div> <!--/ .card -->

						<?php if (count($dir->futurequotas) > 0) { ?>
							<table class="table table-hover">
								<caption>Future Quota Changes</caption>
								<thead>
								<!-- <tr>
									<td></td>
									<td colspan="2">
										<input id="<?php echo $dir->id; ?>_edit_button" class="unixgroup-edit" data-dir="<?php echo $dir->id; ?>" type="button" value="Edit Directory" />
									</td>
								</tr> -->
									<tr>
										<th scope="col">Date</th>
										<th scope="col">Quota</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($dir->futurequotas as $change) { ?>
										<tr>
											<td><?php echo date('M d, Y', strtotime($change['time'])); ?></td>
											<td><?php echo App\Halcyon\Utility\Number::formatBytes($change['quota']); ?></td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						<?php } ?>

						<span id="<?php echo $dir->id; ?>_error"></span>
						<?php /*<p>
							Unallocated space: <span name="unallocated"><?php echo formatBytes($bucket['unallocatedbytes']); ?></span> / <span name="totalbytes"><?php echo formatBytes($bucket['totalbytes']); ?></span>
							<?php
							if ($dir->bytes)
							{
								$cls = '';
								if ($bucket['unallocatedbytes'] == 0)
								{
									$cls = ' stash';
								}

								if ($dir->quotaproblem == 1 && $dir->bytes && $dir->quota < $dir->bytes)
								{
									if (-$bucket['unallocatedbytes'] < $dir->bytes)
									{
										?>
										<a href="/admin/storage/edit/?g=<?php echo escape($_GET['g']); ?>&amp;r=<?php echo escape($_GET['r']); ?>&amp;dir=<?php echo $dir['id']; ?>&amp;quota=up" id="<?php echo $dir['id']; ?>_quota_upa" class="quota_upa<?php echo $cls; ?>" data-dir="<?php echo $dir['id']; ?>">
											<img id="<?php echo $dir['id']; ?>_quota_up" class="img editicon" src="/include/images/arrow_down.png" alt="Remove over-allocated space from this directory." />
										</a>
										<?php
									}
								}
								else
								{
									?>
									<a href="/admin/storage/edit/?g=<?php echo escape($_GET['g']); ?>&amp;r=<?php echo escape($_GET['r']); ?>&amp;dir=<?php echo $dir['id']; ?>&amp;quota=up" id="<?php echo $dir['id']; ?>_quota_upa" class="quota_upa<?php echo $cls; ?>" data-dir="<?php echo $dir['id']; ?>">
										<img id="<?php echo $dir['id']; ?>_quota_up" class="img editicon" src="/include/images/arrow_up.png" alt="Distribute remaining space" />
									</a>
									<?php
								}
							}
							?>
						</p>

						<?php
						*/
						if ($dir->children()->count() == 0)
						{
							if (in_array($dir->id, $removing) || in_array($dir->id, $configuring))
							{
								echo '<p>Delete Disabled - Operations Pending</p>';
							}
							else
							{
								?>
								<div class="dialog-footer text-right">
								<p>
									<a href="<?php route('api.storage.delete', ['id' => $dir->id]); ?>"
										class="btn btn-danger dir-delete"
										data-dir="<?php echo $dir->id; ?>"
										data-path="<?php echo $dir->path; ?>">
										{{ trans('global.button.delete') }}
									</a>
								</p></div>
								<?php
							}
						}
						?>
					</div><!-- / #<?php echo $did; ?>_dialog -->
				<?php } ?>
			</fieldset>
@endif
		</div>
		<div class="col col-md-5">
			<div class="card">
			<table class="table table-hover">
				<caption>{{ trans('storage::storage.messages') }}</caption>
				<thead>
					<tr>
						<th scope="col">{{ trans('storage::storage.status') }}</th>
						<th scope="col">{{ trans('storage::storage.path') }}</th>
						<th scope="col">{{ trans('storage::storage.action') }}</th>
						<th scope="col">{{ trans('storage::storage.submitted') }}</th>
						<th scope="col">{{ trans('storage::storage.completed') }}</th>
						<th scope="col">{{ trans('storage::storage.runtime') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($row->group->messages as $message)
						<tr>
							<td>{{ $message->status }}</td>
							<td>{{ $message->target->path }}</td>
							<td>{{ $message->type->name }}</td>
							<td>{{ $message->datetimesubmitted->format('Y-m-d') }}</td>
							<td>
								@if ($message->completed())
									{{ $message->datetimecompleted->format('Y-m-d') }}
								@else
									-
								@endif
							</td>
							<td>
								@if (strtotime($message->datetimesubmitted) <= date("U"))
									{{ $message->runtime }}
								@else
									-
								@endif
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
			</div>

			@include('history::admin.history')
		</div>
	</div>

	<input type="hidden" name="id" id="field-id" value="{{ $row->id }}" />
	<input type="hidden" name="resourceid" id="resourceid" value="{{ $row->storageResource ? $row->storageResource->parentresourceid : '' }}" />

	@csrf
</form>
@stop