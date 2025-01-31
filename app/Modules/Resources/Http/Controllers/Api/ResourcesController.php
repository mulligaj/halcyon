<?php

namespace App\Modules\Resources\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use App\Modules\Resources\Models\Asset;
use App\Modules\Resources\Models\Facet;
use App\Modules\Resources\Http\Resources\AssetResourceCollection;
use App\Modules\Resources\Http\Resources\AssetResource;
use App\Modules\Resources\Http\Resources\MemberResourceCollection;
use App\Modules\Users\Models\User;
use App\Modules\Users\Models\Userusername;

/**
 * Resources
 *
 * @apiUri    /resources
 */
class ResourcesController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @apiMethod GET
	 * @apiUri    /resources
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "search",
	 * 		"description":   "A word or phrase to search for.",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "type",
	 * 		"description":   "Filter by resource type ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "state",
	 * 		"description":   "Filter by state",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"default":   "active",
	 * 			"enum": [
	 * 				"active",
	 * 				"inactive",
	 * 				"all"
	 * 			]
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "limit",
	 * 		"description":   "Number of result to return.",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   20
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "page",
	 * 		"description":   "Number of where to start returning results.",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   1
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "order",
	 * 		"description":   "Field to sort results by.",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"default":   "name",
	 * 			"enum": [
	 * 				"id",
	 * 				"name",
	 * 				"datetimecreated",
	 * 				"datetimeremoved",
	 * 				"parentid"
	 * 			]
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "order_dir",
	 * 		"description":   "Direction to order results by.",
	 * 		"type":          "string",
	 * 		"required":      false,
	 * 		"default":       "desc",
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"default":   "asc",
	 * 			"enum": [
	 * 				"asc",
	 * 				"desc"
	 * 			]
	 * 		}
	 * }
	 * @param  Request  $request
	 * @return AssetResourceCollection
	 */
	public function index(Request $request)
	{
		$filters = array(
			'search'    => $request->input('search', ''),
			'state'     => $request->input('state', 'active'),
			'type'      => $request->input('type', null),
			// Paging
			'limit'     => $request->input('limit', config('list_limit', 20)),
			'page'      => $request->input('page', 1),
			// Sorting
			'order'     => $request->input('order', 'name'),
			'order_dir' => $request->input('order_dir', 'asc')
		);

		if (!in_array($filters['order'], ['id', 'name', 'datetimecreated', 'datetimeremoved', 'parentid']))
		{
			$filters['order'] = 'name';
		}

		if (!in_array($filters['order_dir'], ['asc', 'desc']))
		{
			$filters['order_dir'] = 'asc';
		}

		$query = Asset::query()
			->with('descendents')
			->with('subresources');

		if ($filters['search'])
		{
			$query->where('name', 'like', '%' . $filters['search'] . '%');
		}

		if ($filters['state'])
		{
			if ($filters['state'] == 'all')
			{
				$query->withTrashed();
			}
			elseif ($filters['state'] == 'inactive')
			{
				$query->onlyTrashed();
			}
		}

		if (is_numeric($filters['type']))
		{
			$query->where('resourcetype', '=', $filters['type']);
		}

		$rows = $query
			->orderBy($filters['order'], $filters['order_dir'])
			->paginate($filters['limit'], ['*'], 'page', $filters['page'])
			->appends(array_filter($filters));

		return new AssetResourceCollection($rows);
	}

	/**
	 * Create a resource
	 *
	 * @apiMethod POST
	 * @apiUri    /resources
	 * @apiAuthorization  true
	 * @apiParameter {
	 * 		"in":            "body",
	 *		"name":          "name",
	 * 		"description":   "The name of the resource type",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 32
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "parentid",
	 * 		"description":   "Parent resource ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "resourcetype",
	 * 		"description":   "Resource type ID",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "producttype",
	 * 		"description":   "Product type ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "rolename",
	 * 		"description":   "An alias containing only alpha-numeric characters, dashes, and underscores",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 32
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "listname",
	 * 		"description":   "An alias containing only alpha-numeric characters, dashes, and underscores",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 32
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "description",
	 * 		"description":   "A short description of the resource",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 2000
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"201": {
	 * 			"description": "Successful entry creation"
	 * 		},
	 * 		"409": {
	 * 			"description": "Invalid data"
	 * 		}
	 * }
	 * @param  Request  $request
	 * @return JsonResponse|AssetResource
	 */
	public function create(Request $request)
	{
		$rules = [
			'name'         => 'required|string|max:32',
			'parentid'     => 'required|integer|min:1',
			'batchsystem'  => 'required|integer|min:1',
			'resourcetype' => 'required|integer|min:1',
			'producttype'  => 'nullable|integer',
			'rolename'     => 'nullable|string|max:32',
			'listname'     => 'nullable|string|max:32',
			'facets'       => 'nullable|array',
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()], 415);
		}

		$exist = Asset::findByName($request->input('name'));

		if ($exist)
		{
			return new AssetResource($exist);
		}

		$row = Asset::create($request->all());

		if ($facets = $request->input('facets', []))
		{
			foreach ($facets as $key => $value)
			{
				$ft = $row->type->facetTypes->where('name', '=', $key)->first();

				if (!$ft)
				{
					continue;
				}

				$facet = $row->facets->where('facet_type_id', '=', $ft->id)->first();

				if (!$value)
				{
					if ($facet)
					{
						$facet->delete();
					}
					continue;
				}

				$facet = $facet ?: new Facet;
				$facet->asset_id = $row->id;
				$facet->facet_type_id = $ft->id;
				$facet->value = $value;
				$facet->save();
			}
		}

		return new AssetResource($row);
	}

	/**
	 * Read a resource
	 *
	 * @apiMethod GET
	 * @apiUri    /resources/{id}
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
	 * 		"200": {
	 * 			"description": "Successful entry read"
	 * 		},
	 * 		"404": {
	 * 			"description": "Record not found"
	 * 		}
	 * }
	 * @param   int  $id
	 * @return  AssetResource
	 */
	public function read($id)
	{
		$row = Asset::findOrFail($id);

		return new AssetResource($row);
	}

	/**
	 * Update a resource
	 *
	 * @apiMethod PUT
	 * @apiUri    /resources/{id}
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
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "name",
	 * 		"description":   "The name of the resource type",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 32
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "parentid",
	 * 		"description":   "Parent resource ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "resourcetype",
	 * 		"description":   "Resource type ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "producttype",
	 * 		"description":   "Product type ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "rolename",
	 * 		"description":   "An alias containing only alpha-numeric characters, dashes, and underscores",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 32
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "listname",
	 * 		"description":   "An alias containing only alpha-numeric characters, dashes, and underscores",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 32
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "description",
	 * 		"description":   "A short description of the resource",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 2000
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"204": {
	 * 			"description": "Successful entry modification"
	 * 		},
	 * 		"404": {
	 * 			"description": "Record not found"
	 * 		},
	 * 		"409": {
	 * 			"description": "Invalid data"
	 * 		}
	 * }
	 * @param   int  $id
	 * @param   Request  $request
	 * @return  JsonResponse|AssetResource
	 */
	public function update($id, Request $request)
	{
		$rules = [
			'name'         => 'nullable|string|max:32',
			'parentid'     => 'nullable|integer|min:1',
			'batchsystem'  => 'nullable|integer|min:1',
			'resourcetype' => 'nullable|integer|min:1',
			'producttype'  => 'nullable|integer',
			'rolename'     => 'nullable|string|max:32',
			'listname'     => 'nullable|string|max:32',
			'status'       => 'nullable|string',
			'facets'       => 'nullable|array',
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()], 415);
		}

		$row = Asset::findOrFail($id);
		$row->fill($request->all());

		if ($row->name != $row->getOriginal('name'))
		{
			$exist = Asset::findByName($request->input('name'));

			if ($exist && $exist->id != $row->id)
			{
				return response()->json(['message' => trans('Entry already exists for name `:name`', ['name' => $row->name])], 415);
			}
		}

		$row->save();

		if ($facets = $request->input('facets', []))
		{
			foreach ($facets as $key => $value)
			{
				$ft = $row->type->facetTypes->where('name', '=', $key)->first();

				if (!$ft)
				{
					continue;
				}

				$facet = $row->facets->where('facet_type_id', '=', $ft->id)->first();

				if (!$value)
				{
					if ($facet)
					{
						$facet->delete();
					}
					continue;
				}

				$facet = $facet ?: new Facet;
				$facet->asset_id = $row->id;
				$facet->facet_type_id = $ft->id;
				$facet->value = $value;
				$facet->save();
			}
		}

		return new AssetResource($row);
	}

	/**
	 * Delete a resource
	 *
	 * @apiMethod DELETE
	 * @apiUri    /resources/{id}
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
	 * @return  JsonResponse
	 */
	public function delete($id)
	{
		$row = Asset::findOrFail($id);

		if (!$row->trashed())
		{
			if (!$row->delete())
			{
				return response()->json(['message' => trans('global.messages.delete failed', ['id' => $id])], 500);
			}
		}

		return response()->json(null, 204);
	}

	/**
	 * Read a resource
	 *
	 * @apiMethod GET
	 * @apiUri    /resources/{id}
	 * @apiParameter {
	 * 		"in":            "path",
	 * 		"name":          "id",
	 * 		"description":   "Entry identifier",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @param   int  $id
	 * @return  MemberResourceCollection
	 */
	public function members($id)
	{
		$resource = Asset::findOrFail($id);

		$subresources = $resource->subresources;

		$userids = array();

		foreach ($subresources as $sub)
		{
			$queues = $sub->queues;

			foreach ($queues as $queue)
			{
				$userids += $queue->users()
					->get()
					->pluck('userid')
					->toArray();
			}
		}

		$userids = array_unique($userids);

		$u = (new User)->getTable();
		$uu = (new Userusername)->getTable();

		$rows = User::query()
			->select($u . '.*')
			->join($uu, $uu . '.userid', $u . '.id')
			->whereNull($uu . '.dateremoved')
			->whereIn($u . '.id', $userids)
			->get();

		return new MemberResourceCollection($rows);
	}
}
