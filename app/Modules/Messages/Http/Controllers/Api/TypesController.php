<?php

namespace App\Modules\Messages\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use App\Modules\Messages\Http\Resources\TypeResource;
use App\Modules\Messages\Http\Resources\TypeResourceCollection;
use App\Modules\Messages\Models\Type;

/**
 * Message Types
 *
 * @apiUri    /messages/types
 */
class TypesController extends Controller
{
	/**
	 * Display a listing of entries
	 *
	 * @apiMethod GET
	 * @apiUri    /messages/types
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
	 * 		"name":          "resourceid",
	 * 		"description":   "Resource ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "classname",
	 * 		"description":   "Class name",
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
	 * 			"enum": [
	 * 				"id",
	 * 				"resourceid",
	 * 				"name",
	 * 				"classname"
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
	 * 			"default":   "asc",
	 * 			"enum": [
	 * 				"asc",
	 * 				"desc"
	 * 			]
	 * 		}
	 * }
	 * @param  Request $request
	 * @return TypeResourceCollection
	 */
	public function index(Request $request)
	{
		// Get filters
		$filters = array(
			'search'     => null,
			'resourceid' => null,
			'classname'  => null,
			'limit'      => config('list_limit', 20),
			'order'      => Type::$orderBy,
			'order_dir'  => Type::$orderDir,
		);

		foreach ($filters as $key => $default)
		{
			$filters[$key] = $request->input($key, $default);
		}

		if (!in_array($filters['order'], ['id', 'name', 'resourceid', 'classname']))
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

		if (!is_null($filters['resourceid']))
		{
			$query->where('resourceid', '=', $filters['resourceid']);
		}

		if (!is_null($filters['classname']))
		{
			$query->where('classname', '=', $filters['classname']);
		}

		$rows = $query
			->orderBy($filters['order'], $filters['order_dir'])
			->paginate($filters['limit'])
			->appends(array_filter($filters));

		return new TypeResourceCollection($rows);
	}

	/**
	 * Create an entry
	 *
	 * @apiMethod POST
	 * @apiUri    /messages/types
	 * @apiAuthorization  true
	 * @apiParameter {
	 * 		"name":          "name",
	 * 		"description":   "Name",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 24
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"name":          "classname",
	 * 		"description":   "Class name",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 24
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"name":          "resourceid",
	 * 		"description":   "Resource ID",
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
	 * 						"name": "get gpfs quota",
	 * 						"resourceid": 64,
	 * 						"classname": "storagedir",
	 * 						"api": "https://example.org/api/messages/types/1"
	 * 					}
	 * 				}
	 * 			}
	 * 		},
	 * 		"409": {
	 * 			"description": "Invalid data"
	 * 		}
	 * }
	 * @param  Request $request
	 * @return JsonResponse|TypeResource
	 */
	public function create(Request $request)
	{
		$rules = [
			'resourceid' => 'nullable|integer|min:1',
			'classname' => 'nullable|string|max:24',
			'name' => 'required|string|max:24'
		];
		// [!] Legacy compatibility
		if ($request->segment(1) == 'ws')
		{
			$rules = [
				'resource' => 'nullable|string',
				'classname' => 'nullable|string|max:24',
				'name' => 'required|string|max:24'
			];
		}
		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()->first()], 409);
		}

		$row = new Type;
		$row->name = $request->input('name');
		if ($request->has('resourceid') || $request->has('resource'))
		{
			$row->resourceid = $request->input('resourceid', $request->input('resource'));

			if (!$row->resource)
			{
				return response()->json(['message' => 'messages::messages.error.invalid resource id'], 409);
			}
		}
		if ($request->has('classname'))
		{
			$row->classname = $request->input('classname');
		}

		$row->save();

		return new TypeResource($row);
	}

	/**
	 * Retrieve an entry
	 *
	 * @apiMethod GET
	 * @apiUri    /messages/types/{id}
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
	 * 						"name": "get gpfs quota",
	 * 						"resourceid": 64,
	 * 						"classname": "storagedir",
	 * 						"api": "https://example.org/api/messages/types/1"
	 * 					}
	 * 				}
	 * 			}
	 * 		},
	 * 		"404": {
	 * 			"description": "Record not found"
	 * 		}
	 * }
	 * @param  int $id
	 * @return TypeResource
	 */
	public function read(int $id)
	{
		$row = Type::findOrFail($id);

		return new TypeResource($row);
	}

	/**
	 * Update an entry
	 *
	 * @apiMethod PUT
	 * @apiUri    /messages/types/{id}
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
	 * 		"name":          "name",
	 * 		"description":   "Name",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 24
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"name":          "classname",
	 * 		"description":   "Class name",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "string",
	 * 			"maxLength": 24
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"name":          "resourceid",
	 * 		"description":   "Resource ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"202": {
	 * 			"description": "Successful entry modification",
	 * 			"content": {
	 * 				"application/json": {
	 * 					"example": {
	 * 						"id": 1,
	 * 						"name": "get gpfs quota",
	 * 						"resourceid": 64,
	 * 						"classname": "storagedir",
	 * 						"api": "https://example.org/api/messages/types/1"
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
	 * @param  Request $request
	 * @param  int $id
	 * @return JsonResponse|TypeResource
	 */
	public function update(Request $request, int $id)
	{
		$rules = [
			'resourceid' => 'nullable|integer|min:1',
			'classname' => 'nullable|string|max:24',
			'name' => 'nullable|string|max:24',
		];
		// [!] Legacy compatibility
		if ($request->segment(1) == 'ws')
		{
			$rules['resource'] = 'nullable|string';
		}
		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()->first()], 409);
		}

		$row = Type::findOrFail($id);

		if ($request->has('name'))
		{
			$row->name = $request->input('name');
		}
		if ($request->has('resourceid') || $request->has('resource'))
		{
			$row->resourceid = $request->input('resourceid', $request->input('resource'));
		}
		if ($request->has('classname'))
		{
			$row->classname = $request->input('classname');
		}

		if (!$row->save())
		{
			return response()->json(['message' => trans('global.messages.save failed')], 409);
		}

		return new TypeResource($row);
	}

	/**
	 * Delete an entry
	 *
	 * @apiMethod DELETE
	 * @apiUri    /messages/types/{id}
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
	 * @param   int $id
	 * @return  JsonResponse
	 */
	public function delete(int $id)
	{
		$row = Type::findOrFail($id);

		if (!$row->delete())
		{
			return response()->json(['message' => trans('global.messages.delete failed', ['id' => $id])], 500);
		}

		return response()->json(null, 204);
	}
}
