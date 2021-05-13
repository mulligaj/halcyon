<?php
$m = (new \App\Modules\Groups\Models\Member)->getTable();
$ug = (new \App\Modules\Groups\Models\UnixGroupMember)->getTable();
$u = (new \App\Modules\Users\Models\UserUsername)->getTable();
$q = (new \App\Modules\Queues\Models\Queue)->getTable();
$s = (new \App\Modules\Resources\Models\Child)->getTable();
$r = (new \App\Modules\Resources\Models\Asset)->getTable();

/*$managers = $group->members()
	->withTrashed()
	->select($m . '.*')
	->join($u, $u . '.userid', $m . '.userid')
	//->whereNull($u . '.deleted_at')
	->where(function($where) use ($u)
	{
		$where->whereNull($u . '.dateremoved')
			->orWhere($u . '.dateremoved', '=', '0000-00-00 00:00:00');
	})
	->where(function($where) use ($m)
	{
		$where->whereNull($m . '.dateremoved')
			->orWhere($m . '.dateremoved', '=', '0000-00-00 00:00:00');
	})
	->whereIsManager()
	->orderBy($m . '.datecreated', 'desc')
	->get();

$managerids = $managers->pluck('userid')->toArray();

$members = $group->members()
	->withTrashed()
	->select($m . '.*')//, $u . '.name')
	->join($u, $u . '.userid', $m . '.userid')
	//->whereNull($u . '.deleted_at')
	->where(function($where) use ($u)
	{
		$where->whereNull($u . '.dateremoved')
			->orWhere($u . '.dateremoved', '=', '0000-00-00 00:00:00');
	})
	->where(function($where) use ($m)
	{
		$where->whereNull($m . '.dateremoved')
			->orWhere($m . '.dateremoved', '=', '0000-00-00 00:00:00');
	})
	->whereIsMember()
	->whereNotIn($m . '.userid', $managerids)
	->orderBy($m . '.datecreated', 'desc')
	->get();

$resources = array();

$queues = $group->queues()
	->withTrashed()
	->select($q . '.*')
	->join($s, $s . '.subresourceid', $q . '.subresourceid')
	->join($r, $r . '.id', $s . '.resourceid')
	->where(function($wher) use ($q)
	{
		$wher->whereNull($q . '.datetimeremoved')
			->orWhere($q . '.datetimeremoved', '=', '0000-00-00 00:00:00');
	})
	->where(function($wher) use ($r)
	{
		$wher->whereNull($r . '.datetimeremoved')
			->orWhere($r . '.datetimeremoved', '=', '0000-00-00 00:00:00');
	})
	->get();

$q = (new \App\Modules\Queues\Models\User)->getTable();

foreach ($queues as $queue)
{
	if (!isset($resources[$queue->resource->name]))
	{
		$resources[$queue->resource->name] = array();
	}
	$resources[$queue->resource->name][] = $queue;

	$users = $queue->users()
		->withTrashed()
		->select($q . '.*')//, $u . '.name')
		->join($u, $u . '.userid', $q . '.userid')
		//->whereNull($u . '.deleted_at')
		->where(function($where) use ($u)
		{
			$where->whereNull($u . '.dateremoved')
				->orWhere($u . '.dateremoved', '=', '0000-00-00 00:00:00');
		})
		->where(function($where) use ($q)
		{
			$where->whereNull($q . '.datetimeremoved')
				->orWhere($q . '.datetimeremoved', '=', '0000-00-00 00:00:00');
		})
		->whereIsMember()
		->whereNotIn($q . '.userid', $managerids)
		->orderBy($q . '.datetimecreated', 'desc')
		->get();

	foreach ($users as $me)
	{
		if (!($found = $members->firstWhere('userid', $me->userid)))
		{
			$members->push($me);
		}
	}
}

$disabled = $group->members()
	->withTrashed()
	->select($m . '.*')//, $u . '.name')
	->join($u, $u . '.userid', $m . '.userid')
	->where(function($where) use ($m, $u)
	{
		$where->where(function($wher) use ($u)
			{
				$wher->whereNotNull($u . '.dateremoved')
					->where($u . '.dateremoved', '!=', '0000-00-00 00:00:00');
			})
			->orWhere(function($wher) use ($m)
			{
				$wher->whereNotNull($m . '.dateremoved')
					->where($m . '.dateremoved', '!=', '0000-00-00 00:00:00');
			});
	})
	//->whereIsMember()
	//->whereNotIn($m . '.userid', $members->pluck('userid')->toArray())
	->orderBy($m . '.datecreated', 'desc')
	->get();*/

$managers = collect([]);
$members = collect([]);
$viewers = collect([]);
$pending = collect([]);
$user_requests = array();
$disabled = collect([]);
$processed = array();

$users = $group->members()
	->withTrashed()
	->where(function($where)
	{
		$where->whereNull('dateremoved')
			->orWhere('dateremoved', '=', '0000-00-00 00:00:00');
	})
	->orderBy('datecreated', 'desc')
	->get();

foreach ($users as $me)
{
	if (in_array($me->userid, $processed))
	{
		continue;
	}

	if (!$me->user || $me->user->isTrashed())
	{
		/*if (!in_array($me->id, $disabledids))
		{
			$disabledids[] = $me->id;
			$disabled->push($me);
		}*/
		//echo $me->userid . '<br />';
		if (!($found = $disabled->firstWhere('userid', $me->userid)))
		{
			$disabled->push($me);
		}
	}
	else
	{
		$me->username = $me->user->username;
		if ($me->isManager())
		{
			if (!($found = $managers->firstWhere('userid', $me->userid)))
			{
				$managers->push($me);
			}
		}
		elseif ($me->isMember())
		{
			if (!($found = $members->firstWhere('userid', $me->userid)))
			{
				$members->push($me);
			}
		}
		elseif ($me->isViewer())
		{
			if (!($found = $viewers->firstWhere('userid', $me->userid)))
			{
				$viewers->push($me);
			}
		}
	}

	$processed[] = $me->userid;
}

$resources = array();

$queues = $group->queues()
	->withTrashed()
	->select($q . '.*')
	->join($s, $s . '.subresourceid', $q . '.subresourceid')
	->join($r, $r . '.id', $s . '.resourceid')
	->whereIsActive()
	->where(function($wher) use ($r)
	{
		$wher->whereNull($r . '.datetimeremoved')
			->orWhere($r . '.datetimeremoved', '=', '0000-00-00 00:00:00');
	})
	->get();

$q = (new \App\Modules\Queues\Models\User)->getTable();

foreach ($queues as $queue)
{
	if (!isset($resources[$queue->resource->name]))
	{
		$resources[$queue->resource->name] = array();
	}
	$resources[$queue->resource->name][] = $queue;

	$users = $queue->users()
		->withTrashed()
		->select($q . '.*')//, $u . '.name')
		->join($u, $u . '.userid', $q . '.userid')
		->whereIsActive()
		//->whereNotIn($q . '.userid', $processed)
		->orderBy($q . '.datetimecreated', 'desc')
		->get();

	foreach ($users as $me)
	{
		if (in_array($me->userid, $processed))
		{
			continue;
		}

		if (!$me->user || $me->user->isTrashed())
		{
			/*if (!in_array($me->id, $disabledids))
			{
				$disabledids[] = $me->id;
				$disabled->push($me);
			}*/
			if (!($found = $disabled->firstWhere('userid', $me->userid)))
			{
				$disabled->push($me);
			}
		}
		else
		{
			$me->username = $me->user->username;

			if ($me->isPending())
			{
				if (!isset($user_requests[$me->userid]))
				{
					$user_requests[$me->userid] = array();
				}
				$user_requests[$me->userid][] = $me->userrequestid;

				if (!($found = $pending->firstWhere('userid', $me->userid)))
				{
					$pending->push($me);
				}
			}
			elseif ($me->isManager())
			{
				if (!($found = $managers->firstWhere('userid', $me->userid)))
				{
					$managers->push($me);
				}
			}
			elseif ($me->isMember())
			{
				if (!($found = $members->firstWhere('userid', $me->userid)))
				{
					$members->push($me);
				}
			}
			elseif ($me->isViewer())
			{
				if (!($found = $viewers->firstWhere('userid', $me->userid)))
				{
					$viewers->push($me);
				}
			}
		}

		$processed[] = $me->userid;
	}
}

$unixgroups = $group->unixgroups()
	->withTrashed()
	->whereIsActive()
	->orderBy('longname', 'asc')
	->get();

$group_boxes = 0;
foreach ($unixgroups as $unixgroup)
{
	// Shortname is only defined when queue is actually a unix group
	// And we only want to add boxes for non-base groups (ones not ending in 0).
	if (!preg_match("/rcs[0-9]{4}0/", $unixgroup->shortname))
	{
		$group_boxes++;
	}

	$users = $unixgroup->members()
		->withTrashed()
		->whereIsActive()
		//->whereNotIn('userid', $processed)
		->get();

	$unixgroup->activemembers = $users;

	foreach ($users as $me)
	{
		if (in_array($me->userid, $processed))
		{
			continue;
		}

		if (!$me->user || $me->user->isTrashed())
		{
			//echo $me->userid . '<br />';
			if (!($found = $disabled->firstWhere('userid', $me->userid)))
			{
				$disabled->push($me);
			}
		}
		else
		{
			$me->username = $me->user->username;
			/*if ($me->isManager())
			{
				if (!($found = $managers->firstWhere('userid', $me->userid)))
				{
					$managers->push($me);
				}
			}
			elseif ($me->isMember())
			{*/
				if (!($found = $members->firstWhere('userid', $me->userid)))
				{
					$members->push($me);
				}
			/*}
			elseif ($me->isViewer())
			{
				if (!($found = $viewers->firstWhere('userid', $me->userid)))
				{
					$viewers->push($me);
				}
			}*/
		}

		$processed[] = $me->userid;
	}
}

/*foreach ($queues as $queue)
{
	$users = $queue->users()
		->withTrashed()
		->select($q . '.*', $u . '.dateremoved')//, $u . '.name')
		->join($u, $u . '.userid', $q . '.userid')
		//->join($s, $s . '.subresourceid', $q . '.subresourceid')
		//->join($r, $r . '.id', $s . '.resourceid')
		->where(function($where) use ($q, $u)
		{
			$where->where(function($wher) use ($u)
			{
				$wher->whereNotNull($u . '.dateremoved')
					->where($u . '.dateremoved', '!=', '0000-00-00 00:00:00');
			})
			->orWhere(function($wher) use ($q)
			{
				$wher->whereNotNull($q . '.datetimeremoved')
					->where($q . '.datetimeremoved', '!=', '0000-00-00 00:00:00');
			});
		})
		//->whereIsMember()
		//->whereNotIn($q . '.userid', $members->pluck('userid')->toArray())
		->orderBy($q . '.datetimecreated', 'desc')
		->orderBy($u . '.datecreated', 'desc')
		->get();

	foreach ($users as $me)
	{
		if (!($found = $disabled->firstWhere('userid', $me->userid)))
		{
			$disabled->push($me);
		}
	}
}

$unixgroups = $group->unixgroups()
	->withTrashed()
	->where(function($where)
	{
		$where->whereNull('datetimeremoved')
			->orWhere('datetimeremoved', '=', '0000-00-00 00:00:00');
	})
	->orderBy('longname', 'asc')
	->get();

foreach ($unixgroups as $unixgroup)
{
	$users = $unixgroup->members()
		->withTrashed()
		->select($ug . '.*')
		->join($u, $u . '.userid', $ug . '.userid')
		->where(function($where) use ($ug)
		{
			$where->whereNull($ug . '.datetimeremoved')
				->orWhere($ug . '.datetimeremoved', '=', '0000-00-00 00:00:00');
		})
		->where(function($where) use ($u)
		{
			$where->whereNull($u . '.dateremoved')
				->orWhere($u . '.dateremoved', '=', '0000-00-00 00:00:00');
		})
		->whereNotIn($ug . '.userid', $managerids)
		->get();

	foreach ($users as $me)
	{
		if (!($found = $members->firstWhere('userid', $me->userid)))
		{
			$members->push($me);
		}
	}
}*/

$managers = $managers->sortBy('username');
$members = $members->sortBy('username');
?>
<div class="row mb-3">
	<div class="col-md-6">
		<button id="export_to_csv" data-id="{{ $group->id }}" class="btn btn-info btn-sm">
			<i class="fa fa-download" ara-hidden="true"></i> Export to CSV
		</button>
	</div>
	<div class="col-md-6 text-right">
		<a href="#add_member_dialog" class="add_member btn btn-secondary btn-sm" data-membertype="1">
			<i class="fa fa-plus-circle" ara-hidden="true"></i> Add Member
		</a>
	</div>
</div>

@if (count($pending))
<div class="card mb-3">
	<div class="card-header">
		New membership requests
	</div>
	<div class="card-body">
		<form id="FORM_{{ $group->id }}">
			<table class="table table-hover fitToPanel">
				<caption class="sr-only">Membership Requests</caption>
				<thead>
					<tr>
						<th scope="col">Name(s)</th>
						<th scope="col" class="text-center">Accept</th>
						<th scope="col" class="text-center">Deny</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($pending as $i => $req)
						<tr id="entry{{ $i }}" data-id="{{ $req->id }}">
							<td>
								{{ $req->user->name }}
							</td>
							<td class="text-center">
								<?php
								$approves = array();
								$denies = array();
								if (isset($user_requests[$req->userid]))
								{
									foreach ($user_requests[$req->userid] as $reqid)
									{
										$approves[] = route('api.queues.requests.update', ['id' => $reqid]);
										$denies[] = route('api.queues.requests.delete', ['id' => $reqid]);
									}
								}
								?>
								<input type="radio" name="approve{{ $i }}" class="approve-request approve-value0" data-api="{{ implode(',', $approves) }}" value="{{ $req->userid }},0" />
							</td>
							<td class="text-center">
								<input type="radio" name="approve{{ $i }}" class="approve-request approve-value1" data-api="{{ implode(',', $denies) }}" value="{{ $req->userid }},1" />
							</td>
						</tr>
					@endforeach
					<tr id="selectAll">
						<td><strong>Select All</strong></td>
						<td class="text-center"><input type="radio" id="acceptAll" class="radio-toggle" value="0" /></td>
						<td class="text-center"><input type="radio" id="denyAll" class="radio-toggle" value="1" /></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						<td colspan="2" class="text-center">
							<button id="submit-requests" class="btn btn-success" disabled>{{ trans('global.button.save') }}</button>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>
	</div>
</div>
@endif

<div class="card mb-3">
	<div class="card-header">
		Managers
		<a href="#help_managers_span_{{ $group->id }}" class="help help-dialog btn text-info" data-tip="Help">
			<span class="fa fa-question-circle" aria-hidden="true"></span><span class="sr-only">Help</span>
		</a>
		<div class="dialog dialog-help" id="help_managers_span_{{ $group->id }}" title="Managers">
			<p>Managers are the owners or <abbr title="Principle Investigators">PIs</abbr> of this group and any others they may choose to delegate to manage access to this group. Only Managers can access this interface and are able to grant queue access for other people in the group. Managers can also grant and remove Group Management privileges to and from others, although you cannot remove your own Group Management privileges.</p>
		</div>
	</div>
	<div class="card-body">
		<table class="table table-hover datatable">
			<caption class="sr-only">Managers</caption>
			<thead>
				<tr>
					<th scope="col">&nbsp;</th>
					<th scope="col">&nbsp;</th>
					@if (count($queues))
					<th scope="col" class="col-queue" colspan="{{ count($queues) }}">Queues</th>
					@endif
					@if (count($unixgroups))
					<th scope="col" class="col-unixgroup text-nowrap" colspan="{{ count($unixgroups) }}">Unix Groups</th>
					@endif
					<th scope="col">&nbsp;</th>
				</tr>
				<tr>
					<th scope="col">User</th>
					<th scope="col">Username</th>
					<?php
					$csv_headers = array(
						'Name',
						'Username',
						'Table'
					);
					foreach ($queues as $queue):
						$csv_headers[] = $queue->name . ' (' . $queue->resource->name . ')';
						?>
						<th scope="col" class="col-queue text-nowrap text-center">{{ $queue->name }} ({{ $queue->resource->name }})</th>
						<?php
					endforeach;

					foreach ($unixgroups as $unix):
						$csv_headers[] = $unix->longname;
						?>
						<th scope="col" class="col-unixgroup text-nowrap text-center">{{ $unix->longname }}</th>
						<?php
					endforeach;

					$csv_data = array();
					$csv_data[] = $csv_headers;
					?>
					<th scope="col" class="text-right">Options</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($managers as $member)
					<tr id="manager-{{ $member->userid }}">
						<td class="text-nowrap">
							@if (auth()->user()->can('manage users'))
								<a href="{{ route('site.users.account', ['u' => $member->userid]) }}">
									{{ $member->user ? $member->user->name : trans('global.unknown') }}
								</a>
							@else
								{{ $member->user ? $member->user->name : trans('global.unknown') }}
							@endif
						</td>
						<td class="text-nowrap">
							{{ $member->user ? $member->user->username : trans('global.unknown') }}
						</td>
						<?php
						$in = array();
						$qu = array();
						$csv = array(
								($member->user ? $member->user->name : trans('global.unknown')),
								($member->user ? $member->username : trans('global.unknown')),
								'Managers'
							);
						foreach ($queues as $queue):
							//$qu[$queue->id] = $queue->users->pluck('userid')->toArray();
							$checked = '';
							$m = null;
							$disable = false;
							// Managers get explicit access to owned queues, but not for free queues.
							if (!$queue->free):
								$disable = true;
								$checked = ' checked="checked" disabled="disabled"';
							else:
								foreach ($queue->users as $m):
								//if (in_array($member->userid, $qu[$queue->id])):
									if ($member->userid == $m->userid):
										//$in[] = $queue->name;
										$checked = ' checked="checked"';
										break;
									endif;
								endforeach;
							endif;
							$csv[] = $checked ? 'yes' : 'no';
							?>
							<td class="col-queue text-nowrap text-center">
								<span class="form-check"><input type="checkbox"
									class="membership-toggle queue-toggle form-check-input"
									name="queue[{{ $queue->id }}]"{{ $checked }}
									data-userid="{{ $member->userid }}"
									data-objectid="{{ $queue->id }}"
									data-api="{{ $checked && !$disable ? route('api.queues.users.delete', ['id' => $m->id]) : route('api.queues.users.create') }}"
									value="1" />
									<label class="form-check-label"><span class="sr-only">{{ $queue->name }}</span></label>
								</span>
							</td>
							<?php
						endforeach;

						$uu = array();
						foreach ($unixgroups as $unix):
							//$uu[$unix->id] = $unix->members->pluck('userid')->toArray();
							$checked = '';
							$m = null;
							foreach ($unix->activemembers as $m):
								//if (in_array($member->userid, $uu[$unix->id])):
								if ($member->userid == $m->userid):
									//$in[] = $unix->longname;
									$checked = ' checked="checked"';
									break;
								endif;
							endforeach;
							$csv[] = $checked ? 'yes' : 'no';

							if (preg_match("/rcs[0-9]{4}0/", $unix->shortname)):
								if ($group_boxes > 0 && $checked):
									$checked .= ' disabled="disabled"';
								endif;
							endif;
							?>
							<td class="col-unixgroup text-nowrap text-center">
								<span class="form-check"><input type="checkbox"
									class="membership-toggle unixgroup-toggle"
									name="unix[{{ $unix->id }}]"{{ $checked }}
									data-userid="{{ $member->userid }}"
									data-objectid="{{ $unix->id }}"
									data-api="{{ $checked ? route('api.unixgroups.members.delete', ['id' => $m->id]) : route('api.unixgroups.members.create') }}"
									value="1" />
									<label class="form-check-label"><span class="sr-only">{{ $unix->name }}</span></label>
								</span>
							</td>
							<?php
						endforeach;
						$csv_data[] = $csv;
						?>
						<!-- </td> -->
						<td class="text-right text-nowrap">
								<a href="#manager-{{ $member->userid }}" class="membership-move demote tip" data-api="{{ route('api.groups.members.update', ['id' => $member->id]) }}" data-target="1" title="Remove manager privleges"><i class="fa fa-arrow-down" aria-hidden="true"></i><span class="sr-only">Demote</span></a>
								<a href="#member{{ $member->id }}" class="membership-remove delete tip" data-api="{{ $member->groupid ? route('api.groups.members.delete', ['id' => $member->id]) : '' }}" title="Remove from group"><i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Remove from group</span></a>
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>

<div class="card mb-3">
	<div class="card-header">
		Members
		<a href="#help_members_span_{{ $group->id }}" class="help help-dialog btn text-info" data-tip="Help">
			<span class="fa fa-question-circle" aria-hidden="true"></span><span class="sr-only">Help</span>
		</a>
		<div class="dialog dialog-help" id="help_members_span_{{ $group->id }}" title="Members">
			<p>Members are people that have access to some or all of this group's queues but have no other special privileges such as Group Usage Reporting privileges or Group Managment privileges. Enabling a queue for someone will also create an account for them on the appropriate resource if they do not already have one. New accounts on a cluster will be processed overnight and be ready use the next morning. The person will receive an email notification once their account is ready.</p>
		</div>
	</div>
	<div class="card-body">
		@if (count($members) > 0)
			<table class="table table-hover hover datatable">
				<caption class="sr-only">Members</caption>
				<thead>
					<tr>
						<th scope="col">&nbsp;</th>
						<th scope="col">&nbsp;</th>
						@if (count($queues))
						<th scope="col" class="col-queue" colspan="{{ count($queues) }}">Queues</th>
						@endif
						@if (count($unixgroups))
						<th scope="col" class="col-unixgroup" colspan="{{ count($unixgroups) }}">Unix Groups</th>
						@endif
						<th scope="col">&nbsp;</th>
					</tr>
					<tr>
						<th class="text-nowrap" scope="col">User</th>
						<th scope="col">Username</th>
						@foreach ($queues as $queue)
							<th scope="col" class="col-queue text-nowrap text-center">{{ $queue->name }} ({{ $queue->resource->name }})</th>
						@endforeach
						@foreach ($unixgroups as $unix)
							<th scope="col" class="col-unixgroup text-nowrap text-center">{{ $unix->longname }}</th>
						@endforeach
						<th scope="col" class="text-right">Options</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($members as $member)
						<tr id="member{{ $member->id }}">
							<td class="text-nowrap">
								@if (auth()->user()->can('manage users'))
									<a href="{{ route('site.users.account', ['u' => $member->userid]) }}">
										{{ $member->user ? $member->user->name : trans('global.unknown') }}
									</a>
								@else
									{{ $member->user ? $member->user->name : trans('global.unknown') }}
								@endif
							</td>
							<td class="text-nowrap">
								{{ $member->user ? $member->user->username : trans('global.unknown') }}
							</td>
							<?php
							$csv = array(
								($member->user ? $member->user->name : trans('global.unknown')),
								($member->user ? $member->username : trans('global.unknown')),
								'Members'
							);
							foreach ($queues as $queue):
								$checked = '';
								$m = null;

								foreach ($queue->users as $m):
									//if ($m->userid == $member->userid):
									if ($member->userid == $m->userid):
									//if (in_array($member->userid, $qu[$queue->id])):
										//$in[] = $queue->name;
										$checked = ' checked="checked"';
										break;
									endif;
								endforeach;
								$csv[] = $checked ? 'yes' : 'no';
								?>
								<td class="text-center col-queue">
									<input type="checkbox"
										class="membership-toggle queue-toggle"
										name="queue[{{ $queue->id }}]"{{ $checked }}
										data-userid="{{ $member->userid }}"
										data-objectid="{{ $queue->id }}"
										data-api="{{ $checked ? route('api.queues.users.delete', ['id' => $m->id]) : route('api.queues.users.create') }}"
										value="1" />
								</td>
								<?php
							endforeach;

							foreach ($unixgroups as $unix):
								$checked = '';
								$m = null;
								foreach ($unix->activemembers as $m):
									//if (in_array($member->userid, $uu[$unix->id])):
									if ($member->userid == $m->userid):
										//$in[] = $unix->longname;
										$checked = ' checked="checked"';
										break;
									endif;
								endforeach;
								$csv[] = $checked ? 'yes' : 'no';

								if (preg_match("/rcs[0-9]{4}0/", $unix->shortname)):
									if ($group_boxes > 0 && $checked):
										$checked .= ' disabled="disabled"';
									endif;
								endif;
								?>
								<td class="text-center col-unixgroup">
									<input type="checkbox"
										class="membership-toggle unixgroup-toggle"
										name="unix[{{ $unix->id }}]"{{ $checked }}
										data-userid="{{ $member->userid }}"
										data-objectid="{{ $unix->id }}"
										data-api="{{ $checked ? route('api.unixgroups.members.delete', ['id' => $m->id]) : route('api.unixgroups.members.create') }}"
										value="1" />
								</td>
								<?php
							endforeach;
							$csv_data[] = $csv;
							?>
							<td class="text-right text-nowrap">
								<!-- <div class="dropdown">
									<a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink{{ $member->id }}" data-bs-toggle="dropdown" aria-expanded="false">
										<i class="fa fa-ellipsis" aria-hidden="true"></i><span class="sr-only">Options</span>
									</a>
									<ul class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $member->id }}">
										<li>
											<a href="#member{{ $member->id }}" class="membership-allqueues allqueues tip" data-container="#member{{ $member->id }}" title="Enable all queues for this user">
												<i class="fa fa-check-square" aria-hidden="true"></i> Enable all queues
											</a>
										</li>
										<li><a href="#member{{ $member->id }}" class="membership-move change tip" data-api="{{ route('api.groups.members.update', ['id' => $member->id]) }}" data-target="1" title="Remove usage viewer privleges"><i class="fa fa-user" aria-hidden="true"></i><span class="sr-only">Remove usage viewer privleges</span></a></li>
										<li><a href="#member{{ $member->id }}" class="membership-remove delete tip" data-api="{{ $member->groupid ? route('api.groups.members.delete', ['id' => $member->id]) : '' }}" title="Remove from group"><i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Remove from group</span></a></li>
									</ul>
								</div> -->
								<a href="#member{{ $member->id }}" class="membership-allqueues allqueues tip" title="Enable all queues for this user"><i class="fa fa-check-square" aria-hidden="true"></i><span class="sr-only">Enable all queues</span></a>
								<a href="#member{{ $member->id }}" class="membership-move change tip" data-api="{{ route('api.groups.members.update', ['id' => $member->id]) }}" data-target="3" title="Grant usage viewer privleges"><i class="fa fa-bar-chart" aria-hidden="true"></i><span class="sr-only">Grant usage viewer privleges</span></a>
								<a href="#member{{ $member->id }}" class="membership-move promote tip" data-api="{{ route('api.groups.members.update', ['id' => $member->id]) }}" data-target="2" title="Grant manager privleges"><i class="fa fa-arrow-up" aria-hidden="true"></i><span class="sr-only">Grant manager privleges</span></a>
								<a href="#member{{ $member->id }}" class="membership-remove delete tip" data-api="{{ $member->groupid ? route('api.groups.members.delete', ['id' => $member->id]) : '' }}" title="Remove from group"><i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Remove from group</span></a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@else
			<p>No members found.</p>
		@endif
	</div>
</div>

@if (count($viewers))
<div class="card mb-3">
	<div class="card-header">
		Usage Reporting Viewers
		<a href="#help_viewers_span_{{ $group->id }}" class="help help-dialog text-info" data-tip="Help">
			<i class="fa fa-question-circle" aria-hidden="true"></i><span class="sr-only">Help</span>
		</a>
		<div class="dialog dialog-help" id="help_viewers_span_{{ $group->id }}" title="Usage Reporting Viewers">
			<p>Members are people that have access to some or all of this group's queues but have no other special privileges such as Group Usage Reporting privileges or Group Managment privileges. Enabling a queue for someone will also create an account for them on the appropriate resource if they do not already have one. New accounts on a cluster will be processed overnight and be ready use the next morning. The person will receive an email notification once their account is ready.</p>
		</div>
	</div>
	<div class="card-body">
		<table class="table table-hover hover datatable">
			<caption class="sr-only">Members</caption>
			<thead>
				<tr>
					<th scope="col">&nbsp;</th>
					<th scope="col">&nbsp;</th>
					@if (count($queues))
					<th scope="col" class="text-left col-queue" colspan="{{ count($queues) }}">Queues</th>
					@endif
					@if (count($unixgroups))
					<th scope="col" class="text-left col-unixgroup" colspan="{{ count($unixgroups) }}">Unix Groups</th>
					@endif
					<th scope="col">&nbsp;</th>
				</tr>
				<tr>
					<th class="text-nowrap" scope="col">User</th>
					<th scope="col">Username</th>
					@foreach ($queues as $queue)
						<th scope="col" class="text-nowrap text-center">{{ $queue->name }} ({{ $queue->resource->name }})</th>
					@endforeach
					@foreach ($unixgroups as $unix)
						<th scope="col" class="text-nowrap text-center">{{ $unix->longname }}</th>
					@endforeach
					<th scope="col" class="text-right">Options</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($viewers as $member)
					<tr id="member{{ $member->id }}">
						<td class="text-nowrap">
							@if (auth()->user()->can('manage users'))
								<a href="{{ route('site.users.account', ['u' => $member->userid]) }}">
									{{ $member->user ? $member->user->name : trans('global.unknown') }}
								</a>
							@else
								{{ $member->user ? $member->user->name : trans('global.unknown') }}
							@endif
						</td>
						<td class="text-nowrap">
							{{ $member->user ? $member->user->username : trans('global.unknown') }}
						</td>
						<?php
						$csv = array(
							($member->user ? $member->user->name : trans('global.unknown')),
							($member->user ? $member->username : trans('global.unknown')),
							'Usage Reporting Viewers'
						);
						foreach ($queues as $queue):
							$checked = '';
							$m = null;

							foreach ($queue->users as $m):
								if ($member->userid == $m->userid):
									$checked = ' checked="checked"';
									break;
								endif;
							endforeach;
							$csv[] = $checked ? 'yes' : 'no';
							?>
							<td class="text-center col-queue">
								<input type="checkbox"
									class="membership-toggle queue-toggle"
									name="queue[{{ $queue->id }}]"{{ $checked }}
									data-userid="{{ $member->userid }}"
									data-objectid="{{ $queue->id }}"
									data-api="{{ $checked ? route('api.queues.users.delete', ['id' => $m->id]) : route('api.queues.users.create') }}"
									value="1" />
							</td>
							<?php
						endforeach;

						foreach ($unixgroups as $unix):
							$checked = '';
							$m = null;
							foreach ($unix->activemembers as $m):
								if ($member->userid == $m->userid):
									$checked = ' checked="checked"';
									break;
								endif;
							endforeach;
							$csv[] = $checked ? 'yes' : 'no';

							if (preg_match("/rcs[0-9]{4}0/", $unix->shortname)):
								if ($group_boxes > 0 && $checked):
									$checked .= ' disabled="disabled"';
								endif;
							endif;
							?>
							<td class="text-center col-unixgroup">
								<input type="checkbox"
									class="membership-toggle unixgroup-toggle"
									name="unix[{{ $unix->id }}]"{{ $checked }}
									data-userid="{{ $member->userid }}"
									data-objectid="{{ $unix->id }}"
									data-api="{{ $checked ? route('api.unixgroups.members.delete', ['id' => $m->id]) : route('api.unixgroups.members.create') }}"
									value="1" />
							</td>
							<?php
						endforeach;
						$csv_data[] = $csv;
						?>
						<td class="text-right text-nowrap">
							<div class="dropdown">
								<a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink{{ $member->id }}" data-bs-toggle="dropdown" aria-expanded="false">
									<i class="fa fa-ellipsis" aria-hidden="true"></i><span class="sr-only">Options</span>
								</a>
								<ul class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $member->id }}">
									<li>
										<a href="#member{{ $member->id }}" class="membership-allqueues allqueues tip" data-container="#member{{ $member->id }}" title="Enable all queues for this user">
											<i class="fa fa-check-square" aria-hidden="true"></i> Enable all queues
										</a>
									</li>
									<li><a href="#member{{ $member->id }}" class="membership-move change tip" data-api="{{ route('api.groups.members.update', ['id' => $member->id]) }}" data-target="1" title="Remove usage viewer privleges"><i class="fa fa-user" aria-hidden="true"></i><span class="sr-only">Remove usage viewer privleges</span></a></li>
									<li><a href="#member{{ $member->id }}" class="membership-remove delete tip" data-api="{{ $member->groupid ? route('api.groups.members.delete', ['id' => $member->id]) : '' }}" title="Remove from group"><i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Remove from group</span></a></li>
								</ul>
							</div>
							<!-- <a href="#member{{ $member->id }}" class="membership-allqueues allqueues tip" data-container="#member{{ $member->id }}" title="Enable all queues for this user"><i class="fa fa-check-square" aria-hidden="true"></i><span class="sr-only">Enable all queues</span></a>
							<a href="#member{{ $member->id }}" class="membership-move change tip" data-api="{{ route('api.groups.members.update', ['id' => $member->id]) }}" data-target="1" title="Remove usage viewer privleges"><i class="fa fa-user" aria-hidden="true"></i><span class="sr-only">Remove usage viewer privleges</span></a>
							<a href="#member{{ $member->id }}" class="membership-remove delete tip" data-api="{{ $member->groupid ? route('api.groups.members.delete', ['id' => $member->id]) : '' }}" title="Remove from group"><i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Remove from group</span></a> -->
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@endif

@if (count($disabled))
<div class="card mb-3">
	<div class="card-header">
		Disabled Members
		<a href="#help_disabledmembers_span_{{ $group->id }}" class="help help-dialog text-info" data-tip="Help">
			<i class="fa fa-question-circle" aria-hidden="true"></i><span class="sr-only">Help</span>
		</a>
		<div class="dialog dialog-help" id="help_disabledmembers_span_{{ $group->id }}" title="Disabled Members">
			<p>Disabled Members are people that you have granted access to your queues but who no longer have an active account with ITaP Research Computing or have an active Purdue Career Account. Although queues may be enabled for them, they cannot log into Research Computing resources and use your queues without an active account. If the people listed here have left the University and are no longer participating in research, please remove them from your queues. If people listed here have left Purdue but still require access to your queues then you will need to file a Request for Privileges (R4P). If you believe people are listed here in error, please contact rcac-help@purdue.edu.</p>
		</div>
	</div>
	<div class="card-body">
		@if (count($disabled) > 0)
			<table class="table table-hover hover datatable">
				<caption class="sr-only">Disabled Members</caption>
				<thead>
					<tr>
						<th scope="col">&nbsp;</th>
						<th scope="col">&nbsp;</th>
						@if (count($queues))
						<th scope="col" class="col-queue" colspan="{{ count($queues) }}">Queues</th>
						@endif
						@if (count($unixgroups))
						<th scope="col" class="col-unixgroup" colspan="{{ count($unixgroups) }}">Unix Groups</th>
						@endif
						<th scope="col">&nbsp;</th>
					</tr>
					<tr>
						<th class="text-nowrap" scope="col">User</th>
						<th class="text-nowrap" scope="col">Username</th>
						@foreach ($queues as $queue)
							<th scope="col" class="text-nowrap text-center">{{ $queue->name }} ({{ $queue->resource->name }})</th>
						@endforeach
						@foreach ($unixgroups as $unix)
							<th scope="col" class="text-nowrap text-center">{{ $unix->longname }}</th>
						@endforeach
						<th scope="col" class="text-right">Options</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($disabled as $member)
						<tr id="member{{ $member->id }}">
							<td class="text-nowrap">
								@if (auth()->user()->can('manage users'))
									<a href="{{ route('site.users.account', ['u' => $member->userid]) }}">
										{{ $member->user ? $member->user->name : trans('global.unknown') }}
									</a>
								@else
									{{ $member->user ? $member->user->name : trans('global.unknown') }}
								@endif
							</td>
							<td class="text-nowrap">
								{{ $member->user ? $member->user->username : trans('global.unknown') }}
							</td>
							<?php
							$csv = array(
								($member->user ? $member->user->name : trans('global.unknown')),
								($member->user ? $member->username : trans('global.unknown')),
								'Disabled'
							);
							foreach ($queues as $queue):
								$checked = '';
								$m = null;

								foreach ($queue->users()->withTrashed()->get() as $m):
									//if ($m->userid == $member->userid):
									if ($member->userid == $m->userid):
									//if (in_array($member->userid, $qu[$queue->id])):
										//$in[] = $queue->name;
										$checked = ' checked="checked"';
										break;
									endif;
								endforeach;
								$csv[] = $checked ? 'yes' : 'no';
								?>
								<td class="text-center col-queue">
									<input type="checkbox"
										class="membership-toggle queue-toggle"
										name="queue[{{ $queue->id }}]"{{ $checked }}
										data-userid="{{ $member->userid }}"
										data-objectid="{{ $queue->id }}"
										data-api="{{ $checked ? route('api.queues.users.delete', ['id' => $m->id]) : route('api.queues.users.create') }}"
										disabled="disabled"
										value="1" />
								</td>
								<?php
							endforeach;

							foreach ($unixgroups as $unix):
								$checked = '';
								$m = null;
								foreach ($unix->members()->withTrashed()->get() as $m):
									//if (in_array($member->userid, $uu[$unix->id])):
									if ($member->userid == $m->userid):
										//$in[] = $unix->longname;
										$checked = ' checked="checked"';
										break;
									endif;
								endforeach;
								$csv[] = $checked ? 'yes' : 'no';
								?>
								<td class="text-center col-unixgroup">
									<input type="checkbox"
										class="membership-toggle unixgroup-toggle"
										name="unix[{{ $unix->id }}]"{{ $checked }}
										data-userid="{{ $member->userid }}"
										data-objectid="{{ $unix->groupid }}"
										data-api="{{ $checked ? route('api.unixgroups.members.delete', ['id' => $m->id]) : route('api.unixgroups.members.create') }}"
										disabled="disabled"
										value="1" />
								</td>
								<?php
							endforeach;
							$csv_data[] = $csv;
							?>
							<td class="text-right text-nowrap">
								<a href="#member{{ $member->id }}" class="membership-remove delete tip" data-api="{{ $member->groupid ? route('api.groups.members.delete', ['id' => $member->id]) : '' }}" title="Remove from group"><i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Remove from group</span></a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@else
			<p>No members found.</p>
		@endif
	</div>
</div>
@endif

<div id="add_member_dialog" data-id="{{ $group->id }}" title="Add users to {{ $group->name }}" class="dialog membership-dialog">
	<form id="form_{{ $group->id }}" method="post">
		<fieldset>
		<div class="form-group">
			<label for="addmembers">Enter names, usernames, or email addresses</label>
			<select class="form-control" name="members" id="addmembers" multiple="multiple" data-api="{{ route('api.users.index') }}" data-group="{{ $group->id }}" placeholder="Username, email address, etc.">
			</select>
		</div>

		<div class="form-group">
			<label for="new_membertype">Membership type</label>
			<select class="form-control" id="new_membertype">
				<option value="1">Member</option>
				<option value="2">Manager</option>
				<option value="3">Usage Viewer</option>
			</select>
		</div>

		@if (count($resources))
			<fieldset>
				<legend>Queue Selection</legend>

				<table id="queue-selection" class="table table-hover mb-0 groupSelect">
					<caption class="sr-only">Queues by Resource</caption>
					<tbody>
						@foreach ($resources as $name => $queues)
							<tr>
								<th scope="row" class="rowHead">{{ $name }}</th>
								<td class="rowData">
								@foreach ($queues as $queue)
									<div class="form-check">
										<input type="checkbox" class="form-check-input add-queue-member" name="queue[]" id="queue{{ $queue->id }}" value="{{ $queue->id }}" />
										<label class="form-check-label" for="queue{{ $queue->id }}">{{ $queue->name }}</label>
									</div>
								@endforeach
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</fieldset>
		@endif

		@if (count($unixgroups))
			<fieldset>
				<legend>Unix Group Selection</legend>

				<div id="unix-group-selection" class="row groupSelect">
					@foreach ($unixgroups as $name)
						<div class="col-sm-4 unixData">
							<div class="form-check">
								<input type="checkbox" class="form-check-input add-unixgroup-member" name="unixgroup[]" id="unixgroup{{ $name->id }}" value="{{ $name->id }}" />
								<label class="form-check-label" for="unixgroup{{ $name->id }}">{{ $name->longname }}</label>
							</div>
						</div>
					@endforeach
				</div>
			</fieldset>
		@endif

		<div class="dialog-footer">
			<div class="row">
				<div class="col-md-12 text-right">
					<input type="button" disabled="disabled" id="add_member_save" class="btn btn-success"
						data-group="{{ $group->id }}"
						data-api="{{ route('api.groups.members.create') }}"
						data-api-unixgroupusers="{{ route('api.unixgroups.members.create') }}"
						data-api-queueusers="{{ route('api.queues.users.create') }}"
						value="{{ trans('global.button.save') }}" />
				</div>
			</div>
		</div>
		</fieldset>
	</form>
</div>

<form id="csv_form_{{ $group->id }}" class="csv_form hide" method="post" action="{{ route('site.groups.export') }}">
	<input type="hidden" name="data" value='<?php echo json_encode($csv_data); ?>' />
	<input type="hidden" name="id" value="{{ $group->id }}" />
	<input type="hidden" name="filename" value="group_{{ $group->id }}_members" />
	<!-- Allow form submission with keyboard without duplicating the dialog button -->
	<input type="submit" tabindex="-1" />
</form>
