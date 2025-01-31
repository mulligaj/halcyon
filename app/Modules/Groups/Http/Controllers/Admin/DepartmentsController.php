<?php

namespace App\Modules\Groups\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use App\Halcyon\Http\StatefulRequest;
use App\Modules\Groups\Models\Department;
use App\Modules\Groups\Models\GroupDepartment;

class DepartmentsController extends Controller
{
	/**
	 * Display a listing of tags
	 *
	 * @param  StatefulRequest $request
	 * @return View
	 */
	public function index(StatefulRequest $request)
	{
		// Get filters
		$filters = array(
			'search'    => null,
			'parent'    => 1,
			// Paging
			'limit'     => config('list_limit', 20),
			'page'      => 1,
			// Sorting
			'order'     => Department::$orderBy,
			'order_dir' => Department::$orderDir,
		);

		$reset = false;
		$request = $request->mergeWithBase();
		foreach ($filters as $key => $default)
		{
			if ($key != 'page'
			 && $request->has($key) //&& session()->has('groups.deps.filter_' . $key)
			 && $request->input($key) != session()->get('groups.deps.filter_' . $key))
			{
				$reset = true;
			}
			$filters[$key] = $request->state('groups.deps.filter_' . $key, $key, $default);
		}
		$filters['page'] = $reset ? 1 : $filters['page'];
		$filters['start'] = ($filters['limit'] * $filters['page']) - $filters['limit'];

		if (!in_array($filters['order'], array('id', 'name')))
		{
			$filters['order'] = Department::$orderBy;
		}

		if (!in_array($filters['order_dir'], ['asc', 'desc']))
		{
			$filters['order_dir'] = Department::$orderDir;
		}

		if ($filters['search'])
		{
			$query = Department::query();

			if (is_numeric($filters['search']))
			{
				$query->where('id', '=', $filters['search']);
			}
			else
			{
				/*$filters['search'] = strtolower((string)$filters['search']);

				$query->where('name', 'like', '%' . $filters['search'] . '%');*/

				$query->where(function($where) use ($filters)
				{
					$search = strtolower((string)$filters['search']);
					$skipmiddlename = preg_replace('/ /', '% ', $search);

					$where->where('name', 'like', '% ' . $search . '%')
						->orWhere('name', 'like', $search . '%')
						->orWhere('name', 'like', '% ' . $skipmiddlename . '%')
						->orWhere('name', 'like', $skipmiddlename . '%');
				});
			}

			/*if ($filters['parent'])
			{
				$query->where('parentid', '=', $filters['parent']);
			}*/
			$query->where('parentid', '>', 0);

			$rows = $query
				->withCount('groups')
				->orderBy($filters['order'], $filters['order_dir'])
				->get();

			$total = count($rows);

			$rows = $rows->slice($filters['start'], $filters['limit']);
		}
		else
		{
			$rows = Department::tree($filters['order'], $filters['order_dir']);
			$root = array_shift($rows);

			$total = count($rows);

			$rows = array_slice($rows, $filters['start'], $filters['limit']);
		}

		$paginator = new \Illuminate\Pagination\LengthAwarePaginator($rows, $total, $filters['limit'], $filters['page']);
		$paginator->withPath(route('admin.groups.departments'));

		return view('groups::admin.departments.index', [
			'rows'    => $rows,
			'filters' => $filters,
			'paginator' => $paginator,
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return View
	 */
	public function create()
	{
		$parents = Department::tree();

		$row = new Department();

		if ($fields = app('request')->old('fields'))
		{
			$row->fill($fields);
		}

		return view('groups::admin.departments.edit', [
			'row' => $row,
			'parents' => $parents
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int $id
	 * @return View
	 */
	public function edit($id)
	{
		$parents = Department::tree();

		$row = Department::findOrFail($id);

		if ($fields = app('request')->old('fields'))
		{
			$row->fill($fields);
		}

		return view('groups::admin.departments.edit', [
			'row' => $row,
			'parents' => $parents,
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  Request $request
	 * @return RedirectResponse
	 */
	public function store(Request $request)
	{
		//$request->validate([
		$rules = [
			'fields.name' => 'required|string|max:255'
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return redirect()->back()
				->withInput($request->input())
				->withErrors($validator->messages());
		}

		$id = $request->input('id');

		$row = $id ? Department::findOrFail($id) : new Department();
		$row->fill($request->input('fields'));

		if (!$row->save())
		{
			return redirect()->back()->withError(trans('global.messages.save failed'));
		}

		return $this->cancel()->with('success', trans('global.messages.item saved'));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  Request $request
	 * @return RedirectResponse
	 */
	public function delete(Request $request)
	{
		$ids = $request->input('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		$success = 0;

		foreach ($ids as $id)
		{
			$row = Department::findOrFail($id);

			if (!$row->delete())
			{
				$request->session()->flash('error', trans('global.messages.delete failed'));
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
	 * Return to the main view
	 *
	 * @return  RedirectResponse
	 */
	public function cancel()
	{
		return redirect(route('admin.groups.departments'));
	}
}
