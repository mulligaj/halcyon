<?php

namespace App\Modules\Pages\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Models\Version;
use App\Halcyon\Http\StatefulRequest;
use Illuminate\Config\Repository;

class PagesController extends Controller
{
	/**
	 * Display a listing of the resource.
	 * 
	 * @param  StatefulRequest $request
	 * @return Response
	 */
	public function index(StatefulRequest $request)
	{
		// Get filters
		$filters = array(
			'search'   => null,
			'state'    => 'published',
			'access'   => null,
			'parent'   => 0,
			// Paging
			'limit'    => config('list_limit', 20),
			'page'     => 1,
			//'start'    => $request->input('limitstart', 0),
			// Sorting
			'order'     => 'path',
			'order_dir' => 'asc',
		);

		foreach ($filters as $key => $default)
		{
			$filters[$key] = $request->state('pages.filter_' . $key, $key, $default);
		}

		if (!in_array($filters['order'], ['id', 'title', 'path', 'state', 'access', 'updated_at']))
		{
			$filters['order'] = 'lft';
		}

		if (!in_array($filters['order_dir'], ['asc', 'desc']))
		{
			$filters['order_dir'] = 'asc';
		}

		// Get records
		$p = new Page;

		$query = $p->query();

		$page = $p->getTable();
		/*$version = (new Version())->getTable();

		$query
			->select([$page . '.*', $version . '.title AS name'])
			->join($version, $version . '.id', $page . '.version_id');*/

		if ($filters['search'])
		{
			$query->where(function($query) use ($filters, $page)
			{
				$query->where($page . '.content', 'like', '%' . $filters['search'] . '%')
					->orWhere($page . '.title', 'like', '%' . $filters['search'] . '%');
			});
		}

		if ($filters['state'] == 'published')
		{
			$query->where($page . '.state', '=', 1);
		}
		elseif ($filters['state'] == 'unpublished')
		{
			$query->where($page . '.state', '=', 0);
		}
		elseif ($filters['state'] == 'trashed')
		{
			$query->onlyTrashed(); //->whereNotNull($page . '.deleted_at');
		}
		else
		{
			$query->withTrashed();
		}

		if ($filters['access'] > 0)
		{
			$query->where($page . '.access', '=', (int)$filters['access']);
		}

		if ($filters['parent'])
		{
			$parent = Page::findOrFail($filters['parent']);

			$query
				->where($page . '.lft', '>', $parent->lft)
				->where($page . '.rgt', '<', $parent->rgt);
		}

		$rows = $query
			//->withCount('versions')
			->orderBy($page . '.' . $filters['order'], $filters['order_dir'])
			->paginate($filters['limit'], ['*'], 'page', $filters['page']);

		return view('pages::admin.index', [
			'rows'    => $rows,
			'filters' => $filters
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 * 
	 * @return Response
	 */
	public function create()
	{
		$row = new Page;
		$row->access = 1;
		$row->state = 1;

		$parents = Page::query()
			->select('id', 'title', 'path', 'level')
			->where('level', '>', 0)
			->orderBy('lft', 'asc')
			->get();

		return view('pages::admin.edit', [
			'row'     => $row,
			'parents' => $parents
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 * 
	 * @param  integer  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$row = Page::findOrFail($id);

		if ($fields = app('request')->old('fields'))
		{
			$row->fill($fields);
		}

		// Fail if checked out not by 'me'
		if ($row->checked_out
		 && $row->checked_out <> auth()->user()->id)
		{
			return redirect(route('admin.pages.index'))->with('warning', trans('global.checked out'));
		}

		$parents = Page::query()
			->select('id', 'title', 'path', 'level')
			->where('level', '>', 0)
			->orderBy('lft', 'asc')
			->get();

		return view('pages::admin.edit', [
			'row'     => $row,
			'parents' => $parents
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param   Request $request
	 * @return  Response
	 */
	public function store(Request $request)
	{
		$request->validate([
			'fields.title' => 'required',
			'fields.content' => 'required',
			'fields.access'  => 'nullable|min:1'
		]);

		$id = $request->input('id');

		$row = $id ? Page::findOrFail($id) : new Page();
		$row->fill($request->input('fields'));

		if ($params = $request->input('params', []))
		{
			foreach ($params as $key => $val)
			{
				$params[$key] = is_array($val) ? array_filter($val) : $val;
			}

			$row->params = new Repository($params);
		}

		if (!$row->save())
		{
			$error = $row->getError() ? $row->getError() : trans('global.messages.save failed');

			return redirect()->back()->withError($error);
		}

		// Rebuild the set
		$root = Page::rootNode();
		$row->rebuild($root->id);

		return redirect(route('admin.pages.index'))->withSuccess(trans('global.messages.item ' . ($id ? 'created' : 'updated')));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param   Request $request
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
			$row = Page::findOrFail($id);

			if ($row->trashed())
			{
				if (!$row->forceDelete())
				{
					$request->session()->flash('error', $row->getError());
					continue;
				}
			}
			elseif (!$row->delete())
			{
				$request->session()->flash('error', $row->getError());
				continue;
			}

			$success++;
		}

		if ($success)
		{
			$request->session()->flash('success', trans('global.messages.item deleted', ['number' => $success]));
		}

		return redirect(route('admin.pages.index'));
	}

	/**
	 * Sets the state of one or more entries
	 * 
	 * @param   Request $request
	 * @param   integer  $id
	 * @return  Response
	 */
	public function state(Request $request, $id = null)
	{
		$action = app('request')->segment(count($request->segments()) - 1);
		$state  = $action == 'publish' ? 1 : 0;

		// Incoming
		$ids = $request->input('id', array($id));
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Check for an ID
		if (count($ids) < 1)
		{
			$request->session()->flash('warning', trans($state ? 'pages::pages.select to publish' : 'pages::pages.select to unpublish'));
			return $this->cancel();
		}

		$success = 0;

		// Update record(s)
		foreach ($ids as $id)
		{
			$row = Page::findOrFail(intval($id));

			if ($row->state == $state)
			{
				continue;
			}

			$row->timestamps = false;
			$row->state = $state;

			if (!$row->save())
			{
				$request->session()->flash('error', $row->getError());
				continue;
			}

			$success++;
		}

		// Set message
		if ($success)
		{
			$msg = $state
				? 'pages::pages.items published'
				: 'pages::pages.items unpublished';

			$request->session()->flash('success', trans($msg, ['count' => $success]));
		}

		return $this->cancel();
	}

	/**
	 * Sets the state of one or more entries
	 * 
	 * @param   Request $request
	 * @return  Response
	 */
	public function restore(Request $request)
	{
		// Incoming
		$ids = $request->input('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Check for an ID
		if (count($ids) < 1)
		{
			$request->session()->flash('warning', trans('pages::pages.select to restore'));
			return $this->cancel();
		}

		$success = 0;

		// Update record(s)
		foreach ($ids as $id)
		{
			$row = Page::withTrashed()->findOrFail(intval($id));

			if (!$row->restore())
			{
				$request->session()->flash('error', $row->getError());
				continue;
			}

			$success++;
		}

		// Set message
		if ($success)
		{
			$request->session()->flash('success', trans('pages::pages.items restored', ['count' => $success]));
		}

		return $this->cancel();
	}

	/**
	 * Reorder entries
	 *
	 * @param   Request $request
	 * @return  Response
	 */
	public function reorder(Request $request)
	{
		// Incoming
		$id = $request->input('id', array());
		if (is_array($id))
		{
			$id = (!empty($id) ? $id[0] : 0);
		}

		// Ensure we have an ID to work with
		if (!$id)
		{
			$request->session()->flash('warning', trans('pages::pages.error.no id'));
			return $this->cancel();
		}

		// Get the element being moved
		$model = Page::findOrFail($id);

		$move = ($request->input('action') == 'orderup') ? -1 : +1;

		if (!$model->move($move))
		{
			$request->session()->flash('error', $model->getError());
		}

		// Redirect
		return $this->cancel();
	}

	/**
	 * Rebuild the tree
	 *
	 * @param   Request $request
	 * @return  Response
	 */
	public function rebuild(Request $request)
	{
		// Get the root of the tree
		$model = Page::rootNode();

		if (!$model->rebuild($model->id))
		{
			$request->session()->flash('error', $model->getError());
		}

		// Redirect
		return $this->cancel();
	}

	/**
	 * Copy an entry and all associated data
	 *
	 * @param   Request $request
	 * @return  Response
	 */
	public function copy(Request $request)
	{
		// Article to copy
		$from = $request->input('from_id', 0);
		// Parent to copy article to
		$to   = $request->input('to_id', 0);
		// Copy descendents as well?
		$recursive = $request->input('recursive', 0);

		if (!$from || !$to)
		{
			$request->session()->flash('warning', trans('pages::pages.error.no id'));
			return $this->cancel();
		}

		$from = Page::findOrFail($from);
		$to   = Page::findOrFail($to);

		if (!$from->id)
		{
			$request->session()->flash('warning', trans('pages::pages.error.no id'));
			return $this->cancel();
		}

		// Copy article
		if (!$from->duplicate($to->id, $recursive))
		{
			$request->session()->flash('error', trans('pages::pages.error.copy failed') . ': ' . $from->getError());
			return $this->cancel();
		}

		// Redirect back to the courses page
		$request->session()->flash('success', trans('pages::pages.item copied'));

		return $this->cancel();
	}

	/**
	 * Return to default page
	 *
	 * @return  Response
	 */
	public function cancel()
	{
		return redirect(route('admin.pages.index'));
	}
}
