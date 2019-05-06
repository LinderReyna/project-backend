<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(User::paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'image' => 'nullable|image:jpeg,jpg,png,gif',
            'photo' => 'nullable|string'
        ]);

        $request = $this->savePhoto($request);

        DB::beginTransaction();

        try {
            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'photo' => $request->photo,
            ]);

            $user->save();

            DB::commit();

        } catch (\Exception $e){
            DB::rollback();
            return response()->json(['message' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Successfully created user!'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json(User::find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|string|email|unique:users',
            'password' => 'nullable|string|confirmed',
            'image' => 'nullable|image:jpeg,jpg,png,gif',
            'photo' => 'nullable|string'
        ]);

        $request = $this->savePhoto($request);

        DB::beginTransaction();

        try {
            $user = User::find($id);
            if (!empty($request->name)) $user->name = $request->name;
            if (!empty($request->email)) $user->email = $request->email;
            if (!empty($request->password)) $user->password = bcrypt($request->password);
            $user->photo = $request->photo;

            $user->update();

            DB::commit();

        } catch (\Exception $e){
            DB::rollback();
            return response()->json(['message' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Successfully updated user!'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::destroy($id);

        return response()->json(['message' => 'Successfully deleted user!'], 200);

    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        $credentials = request(['email','password']);
        if (!Auth::attempt($credentials)){
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at
        ]);

    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    private function savePhoto(Request $request)
    {
        $file = $request->file("image");
        if (!empty($file)){
            $filename = time().'.'.$file->getClientOriginalExtension();
            Storage::disk('local')->put($filename, File::get($file));
            $request['photo'] = Storage::url($filename);
        } else {
            $request['photo'] = null;
        }
        return $request;
    }

}
