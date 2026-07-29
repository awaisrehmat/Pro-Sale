<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
    public function login(Request $r){
        $data=$r->validate(['email'=>'required|email','password'=>'required|string']);
        if(!Auth::attempt($data)) throw ValidationException::withMessages(['email'=>'The credentials are incorrect.']);
        $user=$r->user();
        if(! $user->is_active){Auth::logout();throw ValidationException::withMessages(['email'=>'This user account is inactive.']);}
        $user->load('roles'); return response()->json(['success'=>true,'message'=>'Login successful.','data'=>['user'=>$user,'permissions'=>$user->getAllPermissions()->pluck('name'),'token'=>$user->createToken('inventory-app')->plainTextToken]]);
    }
    public function logout(Request $r){$r->user()->currentAccessToken()?->delete();return response()->json(['success'=>true,'message'=>'Logged out successfully.','data'=>null]);}
}
