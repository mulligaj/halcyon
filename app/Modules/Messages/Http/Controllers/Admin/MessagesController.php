<?php

namespace App\Modules\Messages\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Modules\Messages\Models\Message;
use App\Modules\Messages\Models\Type;
use App\Halcyon\Http\StatefulRequest;
use Carbon\Carbon;

class MessagesController extends Controller
{
	/**
	 * Display a listing of articles
	 *
	 * @param  StatefulRequest  $request
	 * @return Response
	 */
	public function index(StatefulRequest $request)
	{
		$weekago = Carbon::now()->modify('-1 week');

		// Get filters
		$filters = array(
			'state'     => 'published',
			'status'    => '*',
			'start'     => $weekago->format('Y-m-d'),
			'stop'      => null,
			'type'      => '',
			'limit'     => config('list_limit', 20),
			'page'      => 1,
			'order'     => Message::$orderBy,
			'order_dir' => Message::$orderDir,
			'type'      => null,
		);

		$reset = false;
		$request = $request->mergeWithBase();
		foreach ($filters as $key => $default)
		{
			if ($key != 'page'
			 && $request->has($key) && session()->has('messages.index.filter_' . $key)
			 && $request->input($key) != session()->get('messages.index.filter_' . $key))
			{
				$reset = true;
			}
			$filters[$key] = $request->state('messages.index.filter_' . $key, $key, $default);
		}
		$filters['page'] = $reset ? 1 : $filters['page'];

		if (!in_array($filters['order'], array_keys((new Message)->getAttributes())))
		{
			$filters['order'] = Message::$orderBy;
		}

		if (!in_array($filters['order_dir'], ['asc', 'desc']))
		{
			$filters['order_dir'] = Message::$orderDir;
		}

		$query = Message::query()
			->with('type');

		if ($filters['state'] == 'complete')
		{
			$query->where('datetimestarted', '!=', '0000-00-00 00:00:00')
				->where('datetimecompleted', '!=', '0000-00-00 00:00:00');
		}
		elseif ($filters['state'] == 'incomplete')
		{
			$query->where('datetimestarted', '!=', '0000-00-00 00:00:00')
				->where('datetimecompleted', '=', '0000-00-00 00:00:00');
		}
		elseif ($filters['state'] == 'pending')
		{
			$query->where('datetimestarted', '=', '0000-00-00 00:00:00')
				->where('datetimecompleted', '=', '0000-00-00 00:00:00');
		}

		if ($filters['start'])
		{
			$query->where('datetimesubmitted', '>', $filters['start'] . ' 00:00:00');
		}

		if ($filters['status'] == 'success')
		{
			$query->where('returnstatus', '=', 0);
		}
		elseif ($filters['status'] == 'failure')
		{
			$query->where('returnstatus', '>', 0);
		}

		if ($filters['stop'])
		{
			$query->where('datetimesubmitted', '<=', $filters['stop'] . ' 00:00:00');
		}

		if ($filters['type'])
		{
			$query->where('messagequeuetypeid', '=', $filters['type']);
		}

		$rows = $query
			->orderBy($filters['order'], $filters['order_dir'])
			->paginate($filters['limit'], ['*'], 'page', $filters['page']);

		$types = Type::query()
			->orderBy('name', 'asc')
			->get();

		$t = (new Type)->getTable();
		$m = (new Message)->getTable();

		$stats = new \stdClass;
		$stats->failed = Message::query()
			->whereStarted()
			->whereCompleted($filters['start'])
			->whereNotSuccessful()
			->count();
		$stats->failedtypes = Type::query()
			->select($t . '.*', DB::raw('COUNT(' . $m . '.id) AS total'))
			->join($m, $m . '.messagequeuetypeid', $t . '.id')
			->where(function($where) use ($m)
			{
				$where->whereNotNull($m . '.datetimestarted')
					->where($m . '.datetimestarted', '!=', '0000-00-00 00:00:00');
			})
			->where(function($where) use ($m, $filters)
			{
				$where->whereNotNull($m . '.datetimecompleted')
					->where($m . '.datetimecompleted', '!=', '0000-00-00 00:00:00')
					->where($m . '.datetimecompleted', '>', $filters['start']);
			})
			->where('returnstatus', '>', 0)
			->groupBy($m . '.messagequeuetypeid')
			->groupBy($t . '.id')
			->groupBy($t . '.name')
			->groupBy($t . '.resourceid')
			->groupBy($t . '.classname')
			->orderBy($t . '.name')
			->get();
	
		$stats->succeeded = Message::query()
			->whereStarted()
			->whereCompleted($filters['start'])
			->whereSuccessful()
			->count();
		$stats->succeededtypes = Type::query()
			->select($t . '.*', DB::raw('COUNT(' . $m . '.id) AS total'))
			->join($m, $m . '.messagequeuetypeid', $t . '.id')
			->where(function($where) use ($m)
			{
				$where->whereNotNull($m . '.datetimestarted')
					->where($m . '.datetimestarted', '!=', '0000-00-00 00:00:00');
			})
			->where(function($where) use ($m, $filters)
			{
				$where->whereNotNull($m . '.datetimecompleted')
					->where($m . '.datetimecompleted', '!=', '0000-00-00 00:00:00')
					->where($m . '.datetimecompleted', '>', $filters['start']);
			})
			->where('returnstatus', '=', 0)
			->groupBy($m . '.messagequeuetypeid')
			->groupBy($t . '.id')
			->groupBy($t . '.name')
			->groupBy($t . '.resourceid')
			->groupBy($t . '.classname')
			->orderBy($t . '.name')
			->get();

		$stats->pending = Message::query()
			->where('datetimecompleted', '=', '0000-00-00 00:00:00')
			->count();

		$stats->pendingtypes = Type::query()
			->select($t . '.*', DB::raw('COUNT(' . $m . '.id) AS total'))
			->join($m, $m . '.messagequeuetypeid', $t . '.id')
			->where($m . '.datetimecompleted', '=', '0000-00-00 00:00:00')
			->groupBy($m . '.messagequeuetypeid')
			->groupBy($t . '.id')
			->groupBy($t . '.name')
			->groupBy($t . '.resourceid')
			->groupBy($t . '.classname')
			->orderBy($t . '.name')
			->get();

		return view('messages::admin.messages.index', [
			'filters' => $filters,
			'rows'    => $rows,
			'types'   => $types,
			'stats'   => $stats
		]);
	}

	/**
	 * Show the form for creating a new article
	 *
	 * @return  Response
	 */
	public function create()
	{
		$row = new Message();

		$types = Type::orderBy('name', 'asc')->get();

		foreach ($types as $type)
		{
			if ($type->id == config('modules.news.default_type', 0))
			{
				$row->newstypeid = $type->id;
				break;
			}
		}

		return view('messages::admin.messages.edit', [
			'row'   => $row,
			'types' => $types
		]);
	}

	/**
	 * Store a newly created entry
	 *
	 * @param   Request  $request
	 * @return  Response
	 */
	public function store(Request $request)
	{
		$rules = [
			'fields.messagequeuetypeid' => 'required|integer|min:1',
			'fields.targetobjectid' => 'required|integer|min:1',
			'fields.userid' => 'nullable|integer',
			'fields.messagequeueoptionsid' => 'nullable|integer',
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return redirect()->back()
				->withInput($request->input())
				->withErrors($validator->messages());
		}

		$id = $request->input('id');

		$row = $id ? Message::findOrFail($id) : new Message();
		$row->messagequeuetypeid = $request->input('messagequeuetypeid');
		$row->targetobjectid = $request->input('targetobjectid');
		if ($request->has('userid'))
		{
			$row->userid = $request->input('userid');
		}
		if ($request->has('messagequeueoptionsid'))
		{
			$row->messagequeueoptionsid = $request->input('messagequeueoptionsid');
		}

		if (!$row->save())
		{
			return redirect()->back()
				->withInput($request->input())
				->with('error', trans('global.messages.creation failed'));
		}

		return $this->cancel()->withSuccess(trans('global.messages.item created'));
	}

	/**
	 * Show the form for editing the specified entry
	 *
	 * @param   integer  $id
	 * @return  Response
	 */
	public function edit($id)
	{
		$row = Message::findOrFail($id);

		if ($fields = app('request')->old('fields'))
		{
			$row->fill($fields);
		}

		$types = Type::orderBy('name', 'asc')->get();

		return view('messages::admin.messages.edit', [
			'row'   => $row,
			'types' => $types
		]);
	}

	/**
	 * Remove the specified entry
	 *
	 * @param  Request  $request
	 * @return  Response
	 */
	public function delete(Request $request)
	{
		// Incoming
		$ids = $request->input('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		$success = 0;

		foreach ($ids as $id)
		{
			// Delete the entry
			// Note: This is recursive and will also remove all descendents
			$row = Message::findOrFail($id);

			if (!$row->delete())
			{
				$request->session()->flash('error', $row->getError());
				continue;
			}

			$success++;
		}

		if ($success)
		{
			$request->session()->flash('success', trans('global.messages.item deleted', ['count' => $success]));
		}

		return $this->cancel();
	}

	/**
	 * Rerun the specified messages
	 *
	 * @param   Request  $request
	 * @return  Response
	 */
	public function rerun(Request $request)
	{
		// Incoming
		$ids = $request->input('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		$success = 0;

		foreach ($ids as $id)
		{
			// Delete the entry
			// Note: This is recursive and will also remove all descendents
			$row = Message::findOrFail($id);

			if (!$row->completed())
			{
				continue;
			}

			$row->forceRestore([
				'datetimestarted',
				'datetimecompleted'
			]);

			if (!$row->update([
				'pid' => 0,
				'returnstatus' => 0
			]))
			{
				$request->session()->flash('error', trans('messages::messages.error.failed to rerun'));
				continue;
			}

			$success++;
		}

		if ($success)
		{
			$request->session()->flash('success', trans('messages::messages.item reset', ['count' => $success]));
		}

		return $this->cancel();
	}

	/**
	 * Return to default page
	 *
	 * @return  Response
	 */
	public function cancel()
	{
		return redirect(route('admin.messages.index'));
	}
}
