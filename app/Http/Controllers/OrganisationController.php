<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrganisationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $organisations = $user->organisations;

        return response()->json([
            'status' => 'success',
            'data' => ['organisations' => $organisations]
        ]);
    }

    public function show($userId, Request $request)
    {
        $organisation = Organisation::find($userId);
        if (!$organisation || !$request->user()->organisations->contains($organisation)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $organisation
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

    try {
        $organisation = Organisation::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $request->user()->organisations()->attach($organisation->orgId);

        return response()->json([
            'status' => 'success',
            'message' => 'Organisation created successfully',
            'data' => $organisation
        ], 201);
    } catch (\Exception $e) {
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Registration unsuccessful',
                'statusCode' => 400
            ], 400);
        }
}

    public function addUser($id, Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'userId' => 'required|string|exists:users,userId',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        
        $organisation = Organisation::find($id);
        if (!$organisation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Organisation not found',
                'statusCode' => 404
            ]);
        }

       
        $user = User::find($request->userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'statusCode' => 404
            ]);
        }

        
        $organisation->users()->attach($user->userId);

        return response()->json([
            'status' => 'success',
            'message' => 'User added to organisation successfully',
        ], 200);
    }
}
