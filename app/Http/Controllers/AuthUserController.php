<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;



class AuthUserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:5',
            'phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $errors = collect($validator->errors()->messages())
                ->map(function ($error, $field) {
                    return [
                        'field' => $field,
                        'message' => $error[0],
                    ];
                })
                ->values()
                ->toArray();

            return response()->json(['errors' => $errors], 422);
        }

        try {
            $user = User::create([
                'firstName' => $request->firstName,
                'lastName' => $request->lastName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            $organisation = Organisation::create([
                'name' => "{$request->firstName}'s Organisation",
                'description' => '',
            ]);

            $user->organisations()->attach($organisation->orgId);

            $token = $user->createToken('Personal Access Token')->accessToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful',
                'data' => [
                    'accessToken' => $token,
                    'user' => $user
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Bad request',
                'message' => 'Registration unsuccessful',
                'statusCode' => 400
            ], 400);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'accessToken' => $token,
                'user' => $user
            ]
        ]);

    }

    public function show($userId, Request $request)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'statusCode' => 404
            ]);
        }

       // dd($user);

        
        $authenticatedUser = $request->user();
        $organisations = $authenticatedUser->organisations->pluck('orgId');
        $userOrganisations = $user->organisations->pluck('orgId');

        if ($authenticatedUser->userId !== $userId && !$organisations->intersect($userOrganisations)->count()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
                'statusCode' => 403
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }
}
