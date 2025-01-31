<?php

namespace App\Modules\ContactReports\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Validator;
use App\Modules\ContactReports\Models\Type;

/**
 * Types
 *
 * @apiUri    /contactreports/types
 */
class TypesController extends Controller
{
	/**
	 * Display a listing of contact report types
	 *
	 * @apiMethod GET
	 * @apiUri    /contactreports/types
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "tagresources",
	 * 		"description":   "Filter by `tagresources` value",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "location",
	 * 		"description":   "Filter by `location` value",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "future",
	 * 		"description":   "Filter by `future` value",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "ongoing",
	 * 		"description":   "Filter by `ongoing` value",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
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
	 * 		"name":          "limit",
	 * 		"description":   "Number of result per page.",
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
	 * 			"default":   "datetimecreated",
	 * 			"enum": [
	 * 				"id",
	 * 				"motd",
	 * 				"datetimecreated",
	 * 				"datetimeremoved"
	 * 			]
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "order_dir",
	 * 		"description":   "Direction to sort results by.",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"default":   "desc",
	 * 			"enum": [
	 * 				"asc",
	 * 				"desc"
	 * 			]
	 * 		}
	 * }
	 * @return ResourceCollection
	 */
	public function index(Request $request)
	{
		// Get filters
		$filters = array(
			'search'       => null,
			'tagresources' => null,
			'location'     => null,
			'future'       => null,
			'ongoing'      => null,
			'limit'        => config('list_limit', 20),
			'page'         => 1,
			'order'        => Type::$orderBy,
			'order_dir'    => Type::$orderDir,
		);

		foreach ($filters as $key => $default)
		{
			$val = $request->input($key);
			$val = !is_null($val) ? $val : $default;

			$filters[$key] = $val;
		}

		if (!in_array($filters['order'], ['id', 'name']))
		{
			$filters['order'] = Type::$orderBy;
		}

		if (!in_array($filters['order_dir'], ['asc', 'desc']))
		{
			$filters['order_dir'] = Type::$orderDir;
		}

		$query = Type::query();

		if ($filters['search'])
		{
			$query->where('name', 'like', '%' . $filters['search'] . '%');
		}

		$rows = $query
			->withCount('reports')
			->orderBy($filters['order'], $filters['order_dir'])
			->paginate($filters['limit'], ['*'], 'page', $filters['page'])
			->appends(array_filter($filters));

		$rows->each(function ($item, $key)
		{
			$item->api = route('api.contactreports.types.read', ['id' => $item->id]);
		});

		return new ResourceCollection($rows);
	}

	/**
	 * Create a contact report type
	 *
	 * @apiMethod POST
	 * @apiUri    /contactreports/types
	 * @apiAuthorization  true
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "comment",
	 * 		"description":   "The comment being made",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "string"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "name",
	 * 		"description":   "The name of the type",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "timeperiodid",
	 * 		"description":   "ID of a timeperiod",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "timeperiodcount",
	 * 		"description":   "Number of time periods",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "timeperiodlimit",
	 * 		"description":   "Timeperiod limit",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "waitperiodid",
	 * 		"description":   "ID of a timeperiod",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "waitperiodcount",
	 * 		"description":   "Number of wait periods",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"201": {
	 * 			"description": "Successful entry creation",
	 * 			"content": {
	 * 				"application/json": {
	 * 					"example": {
	 * 						"id": 1,
	 * 						"name": "Examples",
	 * 						"timeperiodid": 0,
	 * 						"timeperiodcount": 0,
	 * 						"timeperiodlimit": 0,
	 * 						"waitperiodid": 0,
	 * 						"waitperiodcount": 0,
	 * 						"reports_count": 442,
	 * 						"api": "https://example.org/api/contactreports/types/1"
	 * 					}
	 * 				}
	 * 			}
	 * 		},
	 * 		"409": {
	 * 			"description": "Invalid data"
	 * 		}
	 * }
	 * @param   Request  $request
	 * @return  Response|JsonResource
	 */
	public function create(Request $request)
	{
		$rules = [
			'name'            => 'required|string|max:32',
			'timeperiodid'    => 'nullable|integer',
			'timeperiodcount' => 'nullable|integer',
			'timeperiodlimit' => 'nullable|integer',
			'waitperiodid'    => 'nullable|integer',
			'waitperiodcount' => 'nullable|integer',
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()], 415);
		}

		$row = new Type($request->all());

		if (!$row->save())
		{
			return response()->json(['message' => trans('global.messages.creation failed')], 500);
		}

		$row->api = route('api.contactreports.types.read', ['id' => $row->id]);
		$row->reports_count = 0;

		return new JsonResource($row);
	}

	/**
	 * Read a contact report type
	 *
	 * @apiMethod GET
	 * @apiUri    /contactreports/types/{id}
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
	 * 			"description": "Successful entry read",
	 * 			"content": {
	 * 				"application/json": {
	 * 					"example": {
	 * 						"id": 1,
	 * 						"name": "Examples",
	 * 						"timeperiodid": 0,
	 * 						"timeperiodcount": 0,
	 * 						"timeperiodlimit": 0,
	 * 						"waitperiodid": 0,
	 * 						"waitperiodcount": 0,
	 * 						"reports_count": 442,
	 * 						"api": "https://example.org/api/contactreports/types/1"
	 * 					}
	 * 				}
	 * 			}
	 * 		},
	 * 		"404": {
	 * 			"description": "Record not found"
	 * 		}
	 * }
	 * @param  int  $id
	 * @return JsonResource
	 */
	public function read($id)
	{
		$row = Type::findOrFail((int)$id);

		$row->api = route('api.contactreports.types.read', ['id' => $row->id]);
		$row->reports_count = $row->reports()->count();

		return new JsonResource($row);
	}

	/**
	 * Update a contact report type
	 *
	 * @apiMethod PUT
	 * @apiUri    /contactreports/types/{id}
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
	 * 		"description":   "The name of the type",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "timeperiodid",
	 * 		"description":   "ID of a timeperiod",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "timeperiodcount",
	 * 		"description":   "Number of time periods",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "timeperiodlimit",
	 * 		"description":   "Timeperiod limit",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "waitperiodid",
	 * 		"description":   "ID of a timeperiod",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "waitperiodcount",
	 * 		"description":   "Number of wait periods",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"204": {
	 * 			"description": "Successful entry modification",
	 * 			"content": {
	 * 				"application/json": {
	 * 					"example": {
	 * 						"id": 1,
	 * 						"name": "Examples",
	 * 						"timeperiodid": 0,
	 * 						"timeperiodcount": 0,
	 * 						"timeperiodlimit": 0,
	 * 						"waitperiodid": 0,
	 * 						"waitperiodcount": 0,
	 * 						"reports_count": 442,
	 * 						"api": "https://example.org/api/contactreports/types/1"
	 * 					}
	 * 				}
	 * 			}
	 * 		},
	 * 		"404": {
	 * 			"description": "Record not found"
	 * 		},
	 * 		"409": {
	 * 			"description": "Invalid data"
	 * 		}
	 * }
	 * @param   Request  $request
	 * @param   int  $id
	 * @return  Response|JsonResource
	 */
	public function update(Request $request, $id)
	{
		$rules = [
			'name'            => 'nullable|string|max:32',
			'timeperiodid'    => 'nullable|integer',
			'timeperiodcount' => 'nullable|integer',
			'timeperiodlimit' => 'nullable|integer',
			'waitperiodid'    => 'nullable|integer',
			'waitperiodcount' => 'nullable|integer',
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()], 415);
		}

		$row = Type::findOrFail($id);
		$row->name = $request->input('name');
		if ($request->has('timeperiodid'))
		{
			$row->timeperiodid = $request->input('timeperiodid');
		}
		if ($request->has('timeperiodcount'))
		{
			$row->timeperiodcount = $request->input('timeperiodcount');
		}
		if ($request->has('timeperiodlimit'))
		{
			$row->timeperiodlimit = $request->input('timeperiodlimit');
		}
		if ($request->has('waitperiodid'))
		{
			$row->waitperiodid = $request->input('waitperiodid');
		}
		if ($request->has('waitperiodcount'))
		{
			$row->waitperiodcount = $request->input('waitperiodcount');
		}

		if (!$row->save())
		{
			return response()->json(['message' => trans('global.messages.creation failed')], 500);
		}

		$row->api = route('api.contactreports.types.read', ['id' => $row->id]);
		$row->reports_count = $row->reports()->count();

		return new JsonResource($row);
	}

	/**
	 * Delete a contact report type
	 *
	 * @apiMethod DELETE
	 * @apiUri    /contactreports/types/{id}
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
		$row = Type::findOrFail($id);

		if (!$row->delete())
		{
			return response()->json(['message' => trans('global.messages.delete failed', ['id' => $row->id])]);
		}

		return response()->json(null, 204);
	}
}
