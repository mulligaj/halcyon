<?php

namespace App\Modules\ContactReports\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Validator;
use App\Modules\ContactReports\Models\Follow;
use Carbon\Carbon;

/**
 * Follow Users
 *
 * @apiUri    /contactreports/followusers
 */
class FollowUsersController extends Controller
{
	/**
	 * Display a listing of contact reports user followings
	 *
	 * @apiMethod GET
	 * @apiUri    /contactreports/followusers
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "targetuserid",
	 * 		"description":   "Target User ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "query",
	 * 		"name":          "userid",
	 * 		"description":   "User ID",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   0
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
	 * 			"default":   "datecreated",
	 * 			"enum": [
	 * 				"id",
	 * 				"userid",
	 * 				"datecreated",
	 * 				"targetuserid"
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
	 * @param  Request $request
	 * @return ResourceCollection
	 */
	public function index(Request $request)
	{
		// Get filters
		$filters = array(
			'userid'       => 0,
			'targetuserid' => 0,
			'limit'        => config('list_limit', 20),
			'page'         => 1,
			'order'        => Follow::$orderBy,
			'order_dir'    => Follow::$orderDir,
		);

		foreach ($filters as $key => $default)
		{
			$val = $request->input($key);
			$val = !is_null($val) ? $val : $default;

			$filters[$key] = $val;
		}

		if (!in_array($filters['order'], ['id', 'userid', 'datecreated', 'targetuserid']))
		{
			$filters['order'] = Follow::$orderBy;
		}

		if (!in_array($filters['order_dir'], ['asc', 'desc']))
		{
			$filters['order_dir'] = Follow::$orderDir;
		}

		$query = Follow::query();

		if ($filters['userid'])
		{
			$query->where('userid', '=', $filters['userid']);
		}

		if ($filters['targetuserid'])
		{
			$query->where('targetuserid', '=', $filters['targetuserid']);
		}

		$rows = $query
			->orderBy($filters['order'], $filters['order_dir'])
			->paginate($filters['limit'], ['*'], 'page', $filters['page']);

		$rows->each(function($item, $key)
		{
			$item->api = route('api.contactreports.followusers.read', ['id' => $item->id]);
		});

		return new ResourceCollection($rows);
	}

	/**
	 * Create a contact report user following
	 *
	 * @apiMethod POST
	 * @apiUri    /contactreports/followusers
	 * @apiAuthorization  true
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "targetuserid",
	 * 		"description":   "User ID to follow",
	 * 		"required":      true,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "userid",
	 * 		"description":   "User ID of follower",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer",
	 * 			"default":   "Current user's ID"
	 * 		}
	 * }
	 * @apiResponse {
	 * 		"201": {
	 * 			"description": "Successful entry creation",
	 * 			"content": {
	 * 				"application/json": {
	 * 					"example": {
	 * 						"id": 1,
	 * 						"userid": 1,
	 * 						"targetuserid": 2,
	 * 						"membertype": 10,
	 * 						"datecreated": "2021-09-01 09:12:01",
	 * 						"dateremoved": null,
	 * 						"datelastseen": null,
	 * 						"api": "https://example.org/api/contactreports/followusers/1"
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
			'targetuserid' => 'required|integer|min:1',
			'userid' => 'nullable|integer',
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()], 415);
		}

		$row = new Follow();
		$row->targetuserid = $request->input('targetuserid');
		$row->userid = $request->input('userid', auth()->user() ? auth()->user()->id : 0);

		if (!$row->save())
		{
			return response()->json(['message' => trans('global.messages.create failed')], 500);
		}

		$row->api = route('api.contactreports.followusers.read', ['id' => $row->id]);

		return new JsonResource($row);
	}

	/**
	 * Retrieve a contact report user following
	 *
	 * @apiMethod GET
	 * @apiUri    /contactreports/followusers/{id}
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
	 * 						"userid": 1,
	 * 						"targetuserid": 2,
	 * 						"membertype": 10,
	 * 						"datecreated": "2021-09-01 09:12:01",
	 * 						"dateremoved": null,
	 * 						"datelastseen": null,
	 * 						"api": "https://example.org/api/contactreports/followusers/1"
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
		$row = Follow::findOrFail((int)$id);

		$row->api = route('api.contactreports.followusers.read', ['id' => $row->id]);

		return new JsonResource($row);
	}

	/**
	 * Update a contact report user following
	 *
	 * @apiMethod PUT
	 * @apiUri    /contactreports/followusers/{id}
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
	 * 		"name":          "targetuserid",
	 * 		"description":   "User ID to follow",
	 * 		"required":      false,
	 * 		"schema": {
	 * 			"type":      "integer"
	 * 		}
	 * }
	 * @apiParameter {
	 * 		"in":            "body",
	 * 		"name":          "userid",
	 * 		"description":   "User ID of follower",
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
	 * 						"userid": 1,
	 * 						"targetuserid": 2,
	 * 						"membertype": 10,
	 * 						"datecreated": "2021-09-01 09:12:01",
	 * 						"dateremoved": null,
	 * 						"datelastseen": null,
	 * 						"api": "https://example.org/api/contactreports/followusers/1"
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
			'targetuserid' => 'nullable|integer',
			'userid' => 'nullable|integer',
		];

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails())
		{
			return response()->json(['message' => $validator->messages()], 415);
		}

		$row = Follow::findOrFail($id);
		if ($request->has('targetuserid'))
		{
			$row->targetuserid = $request->input('targetuserid');
		}
		if ($request->has('userid'))
		{
			$row->userid = $request->input('userid');
		}

		if (!$row->save())
		{
			return response()->json(['message' => trans('global.messages.update failed')], 500);
		}

		$row->api = route('api.contactreports.followusers.read', ['id' => $row->id]);

		return new JsonResource($row);
	}

	/**
	 * Delete a contact report user following
	 *
	 * @apiMethod DELETE
	 * @apiUri    /contactreports/followusers/{id}
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
		$row = Follow::findOrFail($id);

		if (!$row->delete())
		{
			return response()->json(['message' => trans('global.messages.delete failed', ['id' => $row->id])], 500);
		}

		return response()->json(null, 204);
	}
}
