<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Services\ApiResponseService;
use App\Mail\ForgotPasswordMail;
use App\Mail\UserVerificationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{
    /**
     * @param ApiResponseService $apiResponseService
     */
    public function __construct(ApiResponseService $apiResponseService)
    {
        $this->apiResponseService = $apiResponseService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return $this->apiResponseService->respondWithResourceCollection(UserResource::collection(User::all()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request)
    {
        $stored_user = DB::transaction(function () use ($request) {
                $user_data = $request->getSanitized();
                $user_data['status'] = 0;
                $user_data['password'] = Hash::make($user_data['password']);
                $stored_user = User::create($user_data);
                if($request->get('role'))
                    $stored_user->attachRole($request->get('role'));
                if($stored_user)
                    $email_sent = $this->sendVerificationEmail($stored_user);
                if($email_sent)
                    return $stored_user;
                return $this->apiResponseService->respondError('Email could not be sent');
            });
            return $this->apiResponseService->respondWithResource(new UserResource($stored_user), 'User created', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function activateUser(Request $request)
    {
        try {
            $arr = explode('-', $request->token);
            $user_id = decrypt($arr[0]);
            $time1 = decrypt($arr[1]);
            $user = User::where('id', $user_id)->first();
            if ($user && decrypt($user->verify_token) == $time1) {
                $time2 = Carbon::now()->timestamp;
                if ($time1 < $time2) {
                    return $this->apiResponseService->respondError('Link has expired');
                } else {
                    $user->is_email_verified = 1;
                    $user->email_verified_at = Carbon::now();
                    $user->verify_token=null;
                    $user->save();
                    return $this->apiResponseService->respondSuccess("Your email address is verified now.");
                }
            } else {
                return $this->apiResponseService->respondError('Invalid token');
            }
        } catch (\Exception $ex) {
            return $this->apiResponseService->respondError('Invalid token');
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        $expire_time = Carbon::now()->addDays(7)->timestamp;
        $user->verify_token=encrypt($expire_time);
        $user->save();
        $link = env('APP_URL').'/forgot-password?token='.encrypt($user->id).'-'.encrypt($expire_time);
        $email_data = [
            'TO_NAME'=>$user->first_name,
            'FORGOT_PASSWORD_LINK'=>$link,
        ];
        Mail::to($user->email)->send(new ForgotPasswordMail($email_data));

        return $this->apiResponseService->respondSuccess('Email sent to reset password');
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();
        try {
            $arr = explode('-', $data['token']);
            $user_id = decrypt($arr[0]);
            $time1 = decrypt($arr[1]);
            $user = User::where('id', $user_id)->first();
            if ($user && decrypt($user->verify_token) == $time1) {
                $time2 = Carbon::now()->timestamp;
                if ($time1 < $time2) {
                    return $this->apiResponseService->respondError("Link is expired.");
                } else {
                    $user->password = Hash::make($data['password']);
                    $user->verify_token=null;
                    $user->save();
                    $this->apiResponseService->respondSuccess("Password has been reset");
                }
            } else {
                return $this->apiResponseService->respondError("Invalid token");
            }
        } catch (\Exception $ex) {
            return $this->apiResponseService->respondError("Invalid token");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user)
    {
        return $this->apiResponseService->respondWithResource(new UserResource($user),'User found');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user_dataToUpdate = $request->getSanitized();
        $userUpdated = $user->update($user_dataToUpdate);
        if($userUpdated)
            return $this->apiResponseService->respondSuccess('User updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user)
    {
        $user_deleted = $user->delete();
        if($user_deleted)
            return $this->apiResponseService->respondSuccess('User deleted');
    }

    private function sendVerificationEmail(User $user)
    {
        $expire_time = Carbon::now()->addDays(7)->timestamp;
        $user->verify_token=encrypt($expire_time);
        $user->save();
        $link = env('APP_URL').'/account-verification?token='.encrypt($user->id).'-'.encrypt($expire_time);
        $email_data = [
            'TO_NAME'=>$user->first_name,
            'EMAIL_VERIFY_LINK'=>$link,
        ];
        ## after 5 sec delay mail will be delivered
//        Mail::to($user->email)->later(5, new UserVerificationMail('USER_EMAIL_VERIFICATION', $email_data));
        ## no delay instantly mail will be delivered
        try {
            Mail::to($user->email)->send(new UserVerificationMail($email_data));

            return true;
        } catch (\Exception $ex) {
            // Debug via $ex->getMessage();
            return "We've got errors!";
        }
    }
}
