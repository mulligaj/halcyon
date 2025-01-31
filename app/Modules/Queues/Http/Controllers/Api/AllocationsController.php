<?php

namespace App\Modules\Queues\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Queues\Http\Resources\AllocationResourceCollection;
use App\Modules\Queues\Models\Queue;
use App\Modules\Queues\Models\Scheduler;
use App\Modules\Queues\Models\SchedulerPolicy;
use App\Modules\Queues\Events\AllocationCreate;
use App\Modules\Queues\Events\AllocationUpdate;
use App\Modules\Queues\Events\AllocationDelete;
use App\Modules\Queues\Events\AllocationList;
use App\Modules\Resources\Models\Asset;
use App\Modules\Resources\Models\Child;
use App\Modules\Resources\Models\Subresource;
//use App\Modules\Users\Models\UserUsername;
//use App\Halcyon\Access\Map;
use Carbon\Carbon;

/**
 * Queue Allocations
 *
 * @apiUri    /allocations
 */
class AllocationsController extends Controller
{
	/**
	 * Display a listing of allocations.
	 *
	 * @apiMethod GET
	 * @apiUri    /allocations/{hostname?}
	 * @apiParameter {
	 * 		"in":            "path",
	 * 		"name":          "hostname",
	 * 		"description":   "Scheduler hostname.",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "format",
	 * 		"description":   "Output format. JSON is the default but output can be formatted to accommodate schedulers such as SLURM.",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"enum": [
	 * 				"json",
	 * 				"slurmcfg"
	 * 			]
	 * 		}
	 * }
	 * @param   Request  $request
	 * @param   string   $hostname
	 * @return  Response|AllocationResourceCollection
	 */
	public function index(Request $request, $hostname = null)
	{
		/*
		"SELECT
			queues.name AS queuename,
			queues.enabled,
			queues.started,
			queues.cluster,
			queues.priority,
			queues.defaultwalltime,
			queues.maxjobsqueued,
			queues.maxjobsqueueduser,
			queues.maxjobsrun,
			queues.maxjobsrunuser,
			queues.maxjobcores,
			queues.nodecoresmin,
			queues.nodecoresmax,
			queues.nodememmin,
			queues.nodememmax,
			queues.aclgroups,
			SUM(queuecores.corecount) AS corecount,
			MAX(queuewalltimes.walltime) AS walltime,
			IF(schedulers.datetimedraindown IS NULL OR schedulers.datetimedraindown = '0000-00-00 00:00:00', '0', '1') AS draindown,
			IF((UNIX_TIMESTAMP(schedulers.datetimedraindown) - UNIX_TIMESTAMP(NOW())) > '0', (UNIX_TIMESTAMP(schedulers.datetimedraindown) - UNIX_TIMESTAMP(NOW())), '0') AS draindown_timeremaining,
			queues.aclusersenabled,
			uniqaclusers.username,
			nodeaccesspolicy.code AS nodeaccesspolicy,
			defaultnodeaccesspolicy.code AS defaultnodeaccesspolicy,
			subresources.nodecores,
			queues.reservation,
			queues.maxijobfactor,
			queues.maxijobuserfactor,
			queues.groupid,
			subresources.nodegpus
		FROM
			schedulers
			INNER JOIN queues ON schedulers.id = queues.schedulerid
				AND  queues.datetimecreated       < NOW()
				AND (queues.datetimeremoved       > NOW() OR queues.datetimeremoved IS NULL OR queues.datetimeremoved = '0000-00-00 00:00:00')
			INNER JOIN schedulerpolicies AS nodeaccesspolicy ON nodeaccesspolicy.id = queues.schedulerpolicyid
			INNER JOIN schedulerpolicies AS defaultnodeaccesspolicy ON defaultnodeaccesspolicy.id = schedulers.schedulerpolicyid
			INNER JOIN subresources ON subresources.id = queues.subresourceid
			LEFT OUTER JOIN groups ON queues.groupid = groups.id
			LEFT OUTER JOIN (
				SELECT queueid, datetimestart, datetimestop, corecount FROM queuesizes
				UNION
				SELECT queueid, datetimestart, datetimestop, corecount FROM queueloans
				) AS queuecores ON queues.id = queuecores.queueid
					AND  queuecores.datetimestart     < NOW()
					AND (queuecores.datetimestop      > NOW() OR queuecores.datetimestop IS NULL OR queuecores.datetimestop = '0000-00-00 00:00:00')
			LEFT OUTER JOIN queuewalltimes ON queues.id = queuewalltimes.queueid
				AND  queuewalltimes.datetimestart < NOW()
				AND (queuewalltimes.datetimestop  > NOW() OR queuewalltimes.datetimestop IS NULL OR queuewalltimes.datetimestop = '0000-00-00 00:00:00')
			LEFT OUTER JOIN (
				SELECT DISTINCT
					queueid,
					username
				FROM
				(
					SELECT DISTINCT
						queues.id AS queueid,
						userusernames.username
					FROM schedulers
					INNER JOIN queues ON schedulers.id = queues.schedulerid
					INNER JOIN queueusers ON queues.id = queueusers.queueid
						AND  queueusers.membertype = '1'
						AND  queueusers.datetimecreated < NOW()
						AND (queueusers.datetimeremoved > NOW() OR queueusers.datetimeremoved IS NULL OR queueusers.datetimeremoved = '0000-00-00 00:00:00')
					INNER JOIN userusernames ON queueusers.userid = userusernames.userid
						AND  userusernames.datecreated  < NOW()
						AND (userusernames.dateremoved  > NOW() OR userusernames.dateremoved IS NULL OR userusernames.dateremoved = '0000-00-00 00:00:00')
					WHERE
						schedulers.hostname = 'cluster-adm.yourinstution'
				UNION
					SELECT DISTINCT
						queues.id AS queueid,
						userusernames.username
					FROM schedulers
					INNER JOIN queues ON schedulers.id = queues.schedulerid
					INNER JOIN groupusers ON queues.groupid = groupusers.groupid
						AND  groupusers.membertype = '2'
						AND  groupusers.datecreated     < NOW()
						AND (groupusers.dateremoved     > NOW() OR groupusers.dateremoved IS NULL OR groupusers.dateremoved = '0000-00-00 00:00:00')
					INNER JOIN userusernames ON groupusers.userid = userusernames.userid
						AND  userusernames.datecreated  < NOW()
						AND (userusernames.dateremoved  > NOW() OR userusernames.dateremoved IS NULL OR userusernames.dateremoved = '0000-00-00 00:00:00')
					WHERE
						schedulers.hostname = 'cluster-adm.yourinstution'
				) AS aclusers
			) AS uniqaclusers ON (queues.id = uniqaclusers.queueid OR uniqaclusers.queueid = '0')
		WHERE
			schedulers.hostname = 'cluster-adm.yourinstution'
			AND schedulers.batchsystem = '1'
		GROUP BY
			queuename,
			username,
			enabled,
			started,
			cluster,
			priority,
			defaultwalltime,
			maxjobsqueued,
			maxjobsqueueduser,
			maxjobsrun,
			maxjobsrunuser,
			maxjobcores,
			nodecoresmin,
			nodecoresmax,
			nodememmin,
			nodememmax,
			aclgroups,
			draindown,
			draindown_timeremaining,
			aclusersenabled,
			username,
			nodeaccesspolicy,
			defaultnodeaccesspolicy,
			nodecores,
			reservation,
			maxijobfactor,
			maxijobuserfactor,
			groupid,
			nodegpus";
		*/

		$isAdmin = (auth()->user() && auth()->user()->can('manage resources'));

		$q = (new Queue)->getTable();
		$s = (new Scheduler)->getTable();
		$r = (new Subresource)->getTable();
		$c = (new Child)->getTable();
		$a = (new Asset)->getTable();
		$p = (new SchedulerPolicy)->getTable();

		$now = Carbon::now();

		$query = Queue::query()
			->select(
				$q . '.*',
				$p . '.code AS nodeaccesspolicy',
				$r . '.nodecores',
				$r . '.nodegpus'
			)
			->join($s, $s . '.id', $q . '.schedulerid')
			->join($r, $r . '.id', $q . '.subresourceid')
			->join($p, $p . '.id', $q . '.schedulerpolicyid')
			->join($c, $c . '.subresourceid', $r . '.id')
			->join($a, $a . '.id', $c . '.resourceid')
			->where($q . '.datetimecreated', '<', $now->toDateTimeString())
			->whereNull($s . '.datetimeremoved')
			->whereNull($r . '.datetimeremoved');

		if (!$isAdmin)
		{
			$query->whereNull($a . '.datetimeremoved');
		}
			//->where($s . '.batchsystem', '=', 1)

		if ($hostname)
		{
			$query->where($s . '.hostname', '=', $hostname);
		}

		$queues = $query
			->orderBy($r . '.name', 'asc')
			->orderBy($q . '.name', 'asc')
			->get();

		/*$queues->reject(function ($queue, $key)
		{
			// Count loans
			$allocations = $queue->loans()
				->where('datetimestart', '<', Carbon::now()->toDateTimeString())
				->where(function($where)
				{
					$where->whereNull('datetimestop')
						->orWhere('datetimestop', '>', Carbon::now()->toDateTimeString());
				})
				->count();

			// Count purchases
			$allocations += $queue->sizes()
				->where('datetimestart', '<', Carbon::now()->toDateTimeString())
				->where(function($where)
				{
					$where->whereNull('datetimestop')
						->orWhere('datetimestop', '>', Carbon::now()->toDateTimeString());
				})
				->count();

			// Count walltimes
			$allocations += $queue->walltimes()
				->where('datetimestart', '<', Carbon::now()->toDateTimeString())
				->where(function($where)
				{
					$where->whereNull('datetimestop')
						->orWhere('datetimestop', '>', Carbon::now()->toDateTimeString());
				})
				->count();

			return $allocations <= 0;
		});*/

		event($event = new AllocationList($hostname, $queues, $request->input('format', '')));

		if ($event->response)
		{
			return $event->response;
		}

		return new AllocationResourceCollection($queues);
	}

	/**
	 * Create an allocation
	 *
	 * @apiMethod POST
	 * @apiUri    /allocations
	 * @apiAuthorization  true
	 * @apiResponse {
	 * 		"201": {
	 * 			"description": "Successful entry creation"
	 * 		},
	 * 		"401": {
	 * 			"description": "Unauthorized"
	 * 		},
	 * 		"409": {
	 * 			"description": "Invalid data"
	 * 		}
	 * }
	 * @param  Request  $request
	 * @return JsonResource
	 */
	public function create(Request $request)
	{
		$data = $request->all();

		event($event = new AllocationCreate($data));

		return new JsonResource($event->response);
	}

	/**
	 * Update an allocation
	 *
	 * @apiMethod PUT
	 * @apiUri    /allocations/{id}
	 * @apiAuthorization  true
	 * @apiParameter {
	 * 		"in":            "path",
	 * 		"name":          "id",
	 * 		"description":   "Entry identifier",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"201": {
	 * 			"description": "Successful entry creation"
	 * 		},
	 * 		"401": {
	 * 			"description": "Unauthorized"
	 * 		},
	 * 		"409": {
	 * 			"description": "Invalid data"
	 * 		}
	 * }
	 * @param   int  $id
	 * @param   Request  $request
	 * @return  JsonResource
	 */
	public function update($id, Request $request)
	{
		$data = $request->all();

		event($event = new AllocationUpdate($id, $data));

		return new JsonResource($event->response);
	}

	/**
	 * Delete an allocation
	 *
	 * @apiMethod DELETE
	 * @apiUri    /allocations/{id}
	 * @apiAuthorization  true
	 * @apiParameter {
	 * 		"in":            "path",
	 * 		"name":          "id",
	 * 		"description":   "Entry identifier",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"204": {
	 * 			"description": "Successful entry deletion"
	 * 		},
	 * 		"404": {
	 * 			"description": "Record not found"
	 * 		}
	 * }
	 * @param   int  $id
	 * @return  Response
	 */
	public function delete($id)
	{
		event($event = new AllocationDelete($id));

		return response()->json(null, 204);
	}
}
