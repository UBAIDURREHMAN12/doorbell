<?php

namespace App\Http\Controllers;
//namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use MongoDB\Driver\Session;
use Validator;
use App\User;
use App\Door;
use App\DoorDay;
use App\DeviceToken;
use App\Host;
use App\Notification;
use App\Mail\PasswordReset;
use App\Mail\InvitationMail;
use App\Mail\VerificationMail;
use File;
use DB;
use Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Crypt;


class ApiController extends Controller
{

    public function generateId(){
        return  Str::uuid()->toString();
    }

    public function twillio_sms()
    {
        $receiverNumber = "RECEIVER_NUMBER";
        $message = "This is testing from ItSolutionStuff.com";

        try {

            $account_sid = getenv("TWILIO_SID");
            $auth_token = getenv("TWILIO_TOKEN");
            $twilio_number = getenv("TWILIO_FROM");

            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);

            dd('SMS Sent Successfully.');

        } catch (Exception $e) {
            dd("Error: ". $e->getMessage());
        }
    }

      public function sendWebNotification(Request $request)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $FcmToken = User::whereNotNull('device_key')->pluck('device_key')->all();

        $serverKey = 'AAAAtfwUgQ4:APA91bGzot7B2woEOVqZgl4akhd21nOzG17c3b-YQb732jq1_HMFZJmba-r7Px1r4XFqHhA-8dHyuH0e-kgSxh2XXi0B7PDs6TH_Wv1nux7mjFFDVaimXRWKXYNqexMnb6NsAzWZUWO2';

        $data = [
            "registration_ids" => $FcmToken,
            "notification" => [
                "title" => 'It is test title',
                "body" => 'It is test body',
            ]
        ];
        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

        // Execute post
        $result = curl_exec($ch);

        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        // Close connection
        curl_close($ch);

        // FCM response
//        dd($result);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users,email',
            'address' => 'required',
            'password' => 'required|string|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/'
        ]);

        if ($validator->passes()) {
            $input = $request->all();
            $input['password'] = bcrypt($input['password']);

            $digits = 6;

            $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);

            $input['code'] = $code;

            $user = User::create($input);

            if($user){

                Mail::to($request->email)->send(new VerificationMail($user->username,$code));

                $success['token'] =  $user->createToken('MyApp')->accessToken;
                $success['user_id'] =  $user->id;

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['User register successfully !']

                    ),
                    'data' => $success
                );

                return response()->json($response, 200);
            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['Something went wroong']

                    ),
                    'data' => null
                );

                return response()->json($response, 200);
            }




        } else {
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
            'device_id' => 'required|unique:devices_tokens,device_id',
//            'device_fcm_token' => 'required|unique:devices_tokens,fcm_token'
        ]);

        if ($validator->passes()) {

            if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){

                $user = Auth::user();

                $user['token'] =  $user->createToken('MyApp')->accessToken;


                    $form_data234 = array(
                        'user_id' => $user->id,
                        'device_id' => $request->device_id,
                        'fcm_token' => $request->device_fcm_token
                    );


                DeviceToken::create($form_data234);


                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Logged in successfully']
                    ),
                    'data' => $user
                );
                return response()->json($response, 200);

            }
            else{
                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['The email or password is incorrect !']
                    ),
                    'data' => null
                );
                return response()->json($response, 200);
            }
        } else {
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()
                ),
                'data' => null
            );
            return response()->json($response, 400);
        }

    }

    public function ResetPassword(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->passes()) {

            $userdata = User::where('email', $request->email)->first();
            $name = $userdata->username;

            $digits = 6;
            $code = rand(pow(10, $digits - 1), pow(10, $digits) - 1);

            $form_data = array(
                'password_reset_code' => $code
            );

            if(User::where('email', $request->email)->update($form_data)){

                Mail::to($request->email)->send(new PasswordReset($code,$name));

                $data['user_id'] = $userdata->id;

                    $response = array(
                        'meta' => array(
                            'responseCode' => 200,
                            'message' => ['Please check your email to get confirmation code']
                        ),
                        'data' => $data
                    );
                    return response()->json($response, 200);

            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['something went wrong']
                    ),
                    'data' => null
                );
                return response()->json($response, 200);

            }



        }else{

            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()
                ),
                'data' => null
            );
            return response()->json($response, 400);
        }
    }

    public function codeVerification(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'code' => 'required|min:6',
        ]);

        if ($validator->passes()) {

            $checkCount = User::where('id', $request->user_id)->
            where('password_reset_code', $request->code)->first();

            if($checkCount){
                $data['user_id'] = $checkCount->id;
                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['code matched successfully']
                    ),
                    'data' => $data
                );
                return response()->json($response, 200);

            }else{
                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['invalid code']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);
            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()
                ),
                'data' => null
            );
            return response()->json($response, 400);
        }
    }

    public function AccountConfirmation(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'code' => 'required|min:6|exists:users,code',
        ]);

        if ($validator->passes()) {

            $checkCount = User::where('id', $request->user_id)->
            where('code', $request->code)->first();

            if($checkCount){
                $data['user_id'] = $checkCount->id;

                $form_data = array(
                    'active' => 1,
                    'email_verified' => 1
                );

                if(User::where('id', $checkCount->id)->update($form_data)){

                    $response = array(
                        'meta' => array(
                            'responseCode' => 200,
                            'message' => ['Account confirmed successfully !']
                        ),
                        'data' => $checkCount
                    );
                    return response()->json($response, 200);
                }
                else{
                    $response = array(
                        'meta' => array(
                            'responseCode' => 400,
                            'message' => ['code matched successfully but status not updated !']
                        ),
                        'data' => null
                    );
                    return response()->json($response, 400);
                }


            }else{
                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['invalid code']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);
            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()
                ),
                'data' => null
            );
            return response()->json($response, 400);
        }
    }

    public function updatePassword(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'password' => 'required|min:8|required_with:password_confirmation|same:password_confirmation|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'password_confirmation' => 'min:8'
        ]);

        if ($validator->passes()) {

            $form_data = array(
                'password' => bcrypt($request->password)
            );

           if(User::where('id', $request->user_id)->update($form_data)){

               $response = array(
                   'meta' => array(
                       'responseCode' => 200,
                       'message' => ['password updated successfully']
                   ),
                   'data' => null
               );
               return response()->json($response, 200);

           }else{
               $response = array(
                   'meta' => array(
                       'responseCode' => 400,
                       'message' => ['something went wrong, user against this account may be deleted']
                   ),
                   'data' => null
               );
               return response()->json($response, 200);
           }


        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()
                ),
                'data' => null
            );
            return response()->json($response, 400);
        }
    }


    public function createDoor(Request $request)
    {

        if(isset($request->door_id) && !empty($request->door_id) ){
            $reportData  = [json_decode($request->visiting_houres)];

            $validator = Validator::make($request->all(), [
                'door_id' => 'required|exists:doors,id',
                'location' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'name' => 'required',
                'image' => 'required',
                'zone_of_operation' => 'required|digits_between: 1,3',
                'ring_limit' => 'required|digits_between: 1,2',
                'ring_tone' => 'required',
                'door_type' => 'required',
                'msg_in_operational_houres' => 'required',
                'msg_in_off_houres' => 'required'

            ]);

            if ($validator->passes()) {

                $input = $request->all();
                $input['user_id'] = Auth::id();

                $file = $request->file('image');
                $destinationPath =public_path() . '/door_images/';
                $imagename = time().$file->getClientOriginalName();

                $image_path = 'https://doorbell.ioptime.com/public/door_images/'.$imagename;
                $file->move($destinationPath,$imagename);


                $doorDAta = Door::find($request->door_id);

                $updatedDoorData = array(
                    'location'                  =>  $request->latitude,
                    'latitude'                  =>  $request->longitude,
                    'longitude'                 =>  $request->location,
                    'name'                      =>  $request->name,
                    'image'                     => $image_path,
                    'zone_of_operation'         => $request->zone_of_operation,
                    'ring_limit'                =>  $request->ring_limit,
                    'ring_tone'                 =>  $request->ring_tone,
                    'door_type'                 =>  $request->door_type,
                    'msg_in_operational_houres' => $request->msg_in_operational_houres,
                    'msg_in_off_houres'         => $request->msg_in_off_houres,
                );


                if($doorDAta->update($updatedDoorData)){

                    // $reportData[0]->Friday->data[0]->start;

                    for ($i=0; $i<count($reportData); $i++){

                        if(isset($reportData[0]->Friday) && !empty($reportData[0]->Friday)){
                            $days_timings2 = array(
                                'door_id'       =>   $doorDAta->id,
                                'day_id'        =>   1,
                                'from_hour'     =>   $reportData[0]->Friday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Friday->data[0]->end_time,
                            );
                          $doorDay1 = DoorDay::where('door_id', $doorDAta->id)->where('day_id', 1)->first();
                          if($doorDay1){
                              $doorDay1->update($days_timings2);
                          }

                        }
                        if(isset($reportData[0]->Saturday) && !empty($reportData[0]->Saturday)){
                            $days_timings2 = array(
                                'door_id'       =>  $doorDAta->id,
                                'day_id'        =>   2,
                                'from_hour'     =>   $reportData[0]->Saturday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Saturday->data[0]->end_time,
                            );
                            $doorDay2 = DoorDay::where('door_id', $doorDAta->id)->where('day_id', 2)->first();
                            if($doorDay2){
                                $doorDay2->update($days_timings2);
                            }
                        }
                        if(isset($reportData[0]->Sunday) && !empty($reportData[0]->Sunday)){
                            $days_timings2 = array(
                                'door_id'       =>  $doorDAta->id,
                                'day_id'        =>   3,
                                'from_hour'     =>   $reportData[0]->Sunday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Sunday->data[0]->end_time,
                            );
                            $doorDay3 = DoorDay::where('door_id', $doorDAta->id)->where('day_id', 3)->first();
                            if($doorDay3){
                                $doorDay3->update($days_timings2);
                            }
                        }
                        if(isset($reportData[0]->Monday) && !empty($reportData[0]->Monday)){
                            $days_timings2 = array(
                                'door_id'       =>  $doorDAta->id,
                                'day_id'        =>   4,
                                'from_hour'     =>   $reportData[0]->Monday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Monday->data[0]->end_time,
                            );
                            $doorDay4 = DoorDay::where('door_id', $doorDAta->id)->where('day_id', 4)->first();
                            if($doorDay4){
                                $doorDay4->update($days_timings2);
                            }
                        }
                        if(isset($reportData[0]->Tuesday) && !empty($reportData[0]->Tuesday)){
                            $days_timings2 = array(
                                'door_id'       =>  $doorDAta->id,
                                'day_id'        =>   5,
                                'from_hour'     =>   $reportData[0]->Tuesday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Tuesday->data[0]->end_time,
                            );
                            $doorDay5 = DoorDay::where('door_id', $doorDAta->id)->where('day_id', 5)->first();
                            if($doorDay5){
                                $doorDay5->update($days_timings2);
                            }
                        }

                        if(isset($reportData[0]->Wednesday) && !empty($reportData[0]->Wednesday)){
                            $days_timings2 = array(
                                'door_id'       =>  $doorDAta->id,
                                'day_id'        =>   6,
                                'from_hour'     =>   $reportData[0]->Wednesday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Wednesday->data[0]->end_time,
                            );
                            $doorDay6 = DoorDay::where('door_id', $doorDAta->id)->where('day_id', 6)->first();
                            if($doorDay6){
                                $doorDay6->update($days_timings2);
                            }
                        }
                        if(isset($reportData[0]->Thursday) && !empty($reportData[0]->Thursday)){
                            $days_timings2 = array(
                                'door_id'       =>  $doorDAta->id,
                                'day_id'        =>   7,
                                'from_hour'     =>   $reportData[0]->Thursday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Thursday->data[0]->end_time,
                            );
                            $doorDay7 = DoorDay::where('door_id', $doorDAta->id)->where('day_id', 7)->first();
                            if($doorDay7){
                                $doorDay7->update($days_timings2);
                            }
                        }
                    }

                    $data22 = Door::where('id', $request->door_id)->with('doordays')->get();

                    $response = array(
                        'meta' => array(
                            'responseCode' => 200,
                            'message' => ['Door updated successfully !']

                        ),
                        'data' => $data22
                    );

                    return response()->json($response, 200);
                }else{
                    $response = array(
                        'meta' => array(
                            'responseCode' => 400,
                            'message' => ['Door not updated !']

                        ),
                        'data' => null
                    );

                    return response()->json($response, 400);
                }




            } else {
                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => $validator->errors()->all()

                    ),
                    'data' => null

                );

                return response()->json($response, 400);
            }
        }else{
            $reportData  = [json_decode($request->visiting_houres)];

            // $reportData[0]->Friday->data[0]->start;


            $validator = Validator::make($request->all(), [
                'location' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'name' => 'required',
                'image' => 'required',
                'zone_of_operation' => 'required|digits_between: 1,3',
                'ring_limit' => 'required|digits_between: 1,2',
                'ring_tone' => 'required',
                'door_type' => 'required',
                'msg_in_operational_houres' => 'required',
                'msg_in_off_houres' => 'required'

            ]);

            if ($validator->passes()) {

                $input = $request->all();
                $input['user_id'] = Auth::id();

                $file = $request->file('image');
                $destinationPath =public_path() . '/door_images/';
                $imagename = time().$file->getClientOriginalName();

                $image_path = 'https://doorbell.ioptime.com/public/door_images/'.$imagename;
                $file->move($destinationPath,$imagename);

                $input['image'] = $image_path;

                $door = Door::create($input);

                if($door){

                    // $reportData[0]->Friday->data[0]->start;

                    for ($i=0; $i<count($reportData); $i++){

                        if(isset($reportData[0]->Friday) && !empty($reportData[0]->Friday)){
                            $days_timings2 = array(
                                'door_id'       =>   $door->id,
                                'day_id'        =>   1,
                                'from_hour'     =>   $reportData[0]->Friday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Friday->data[0]->end_time,
                            );
                            DoorDay::create($days_timings2);
                        }
                        if(isset($reportData[0]->Saturday) && !empty($reportData[0]->Saturday)){
                            $days_timings2 = array(
                                'door_id'       =>  $door->id,
                                'day_id'        =>   2,
                                'from_hour'     =>   $reportData[0]->Saturday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Saturday->data[0]->end_time,
                            );
                            DoorDay::create($days_timings2);
                        }
                        if(isset($reportData[0]->Sunday) && !empty($reportData[0]->Sunday)){
                            $days_timings2 = array(
                                'door_id'       =>  $door->id,
                                'day_id'        =>   3,
                                'from_hour'     =>   $reportData[0]->Sunday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Sunday->data[0]->end_time,
                            );
                            DoorDay::create($days_timings2);
                        }
                        if(isset($reportData[0]->Monday) && !empty($reportData[0]->Monday)){
                            $days_timings2 = array(
                                'door_id'       =>  $door->id,
                                'day_id'        =>   4,
                                'from_hour'     =>   $reportData[0]->Monday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Monday->data[0]->end_time,
                            );
                            DoorDay::create($days_timings2);
                        }
                        if(isset($reportData[0]->Tuesday) && !empty($reportData[0]->Tuesday)){
                            $days_timings2 = array(
                                'door_id'       =>  $door->id,
                                'day_id'        =>   5,
                                'from_hour'     =>   $reportData[0]->Tuesday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Tuesday->data[0]->end_time,
                            );
                            DoorDay::create($days_timings2);
                        }

                        if(isset($reportData[0]->Wednesday) && !empty($reportData[0]->Wednesday)){
                            $days_timings2 = array(
                                'door_id'       =>  $door->id,
                                'day_id'        =>   6,
                                'from_hour'     =>   $reportData[0]->Wednesday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Wednesday->data[0]->end_time,
                            );
                            DoorDay::create($days_timings2);
                        }
                        if(isset($reportData[0]->Thursday) && !empty($reportData[0]->Thursday)){
                            $days_timings2 = array(
                                'door_id'       =>  $door->id,
                                'day_id'        =>   7,
                                'from_hour'     =>   $reportData[0]->Thursday->data[0]->start_time,
                                'to_hour'       =>   $reportData[0]->Thursday->data[0]->end_time,
                            );
                            DoorDay::create($days_timings2);
                        }
                    }

                    $door['days_timings'] = DoorDay::where('door_id', $door->id)->get();

                    $response = array(
                        'meta' => array(
                            'responseCode' => 200,
                            'message' => ['Door created successfully !']

                        ),
                        'data' => $door
                    );

                    return response()->json($response, 200);
                }else{
                    $response = array(
                        'meta' => array(
                            'responseCode' => 400,
                            'message' => ['Door not created !']

                        ),
                        'data' => null
                    );

                    return response()->json($response, 400);
                }




            } else {
                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => $validator->errors()->all()

                    ),
                    'data' => null

                );

                return response()->json($response, 400);
            }
        }



    }

    public function UpdateDoor(Request $request)
    {
        dd($request->doordays);

        $validator = Validator::make($request->all(), [
            'location' => 'required',
            'name' => 'required',
            'image' => 'required',
            'zone_of_operation' => 'required|digits_between: 1,3',
            'ring_limit' => 'required|digits_between: 1,2',
            'ring_tone' => 'required',
            'door_type' => 'required',
            'msg_in_operational_houres' => 'required',
            'msg_in_off_houres' => 'required',

        ]);

        if ($validator->passes()) {

            $input = $request->all();
            $input['user_id'] = Auth::id();

            $file = $request->file('image');
            $destinationPath =public_path() . '/door_images/';
            $imagename = time().$file->getClientOriginalName();

            $image_path = 'https://doorbell.ioptime.com/public/door_images/'.$imagename;
            $file->move($destinationPath,$imagename);

            $input['image'] = $image_path;

            $door = Door::create($input);

            if($door){

                // $reportData[0]->Friday->data[0]->start;

                for ($i=0; $i<count($reportData); $i++){

                    if(isset($reportData[0]->Friday) && !empty($reportData[0]->Friday)){
                        $days_timings2 = array(
                            'door_id'       =>   $door->id,
                            'day_id'        =>   1,
                            'from_hour'     =>   $reportData[0]->Friday->data[0]->start_time,
                            'to_hour'       =>   $reportData[0]->Friday->data[0]->end_time,
                        );
                        DoorDay::create($days_timings2);
                    }
                    if(isset($reportData[0]->Saturday) && !empty($reportData[0]->Saturday)){
                        $days_timings2 = array(
                            'door_id'       =>  $door->id,
                            'day_id'        =>   2,
                            'from_hour'     =>   $reportData[0]->Saturday->data[0]->start_time,
                            'to_hour'       =>   $reportData[0]->Saturday->data[0]->end_time,
                        );
                        DoorDay::create($days_timings2);
                    }
                    if(isset($reportData[0]->Sunday) && !empty($reportData[0]->Sunday)){
                        $days_timings2 = array(
                            'door_id'       =>  $door->id,
                            'day_id'        =>   3,
                            'from_hour'     =>   $reportData[0]->Sunday->data[0]->start_time,
                            'to_hour'       =>   $reportData[0]->Sunday->data[0]->end_time,
                        );
                        DoorDay::create($days_timings2);
                    }
                    if(isset($reportData[0]->Monday) && !empty($reportData[0]->Monday)){
                        $days_timings2 = array(
                            'door_id'       =>  $door->id,
                            'day_id'        =>   4,
                            'from_hour'     =>   $reportData[0]->Monday->data[0]->start_time,
                            'to_hour'       =>   $reportData[0]->Monday->data[0]->end_time,
                        );
                        DoorDay::create($days_timings2);
                    }
                    if(isset($reportData[0]->Tuesday) && !empty($reportData[0]->Tuesday)){
                        $days_timings2 = array(
                            'door_id'       =>  $door->id,
                            'day_id'        =>   5,
                            'from_hour'     =>   $reportData[0]->Tuesday->data[0]->start_time,
                            'to_hour'       =>   $reportData[0]->Tuesday->data[0]->end_time,
                        );
                        DoorDay::create($days_timings2);
                    }

                    if(isset($reportData[0]->Wednesday) && !empty($reportData[0]->Wednesday)){
                        $days_timings2 = array(
                            'door_id'       =>  $door->id,
                            'day_id'        =>   6,
                            'from_hour'     =>   $reportData[0]->Wednesday->data[0]->start_time,
                            'to_hour'       =>   $reportData[0]->Wednesday->data[0]->end_time,
                        );
                        DoorDay::create($days_timings2);
                    }
                    if(isset($reportData[0]->Thursday) && !empty($reportData[0]->Thursday)){
                        $days_timings2 = array(
                            'door_id'       =>  $door->id,
                            'day_id'        =>   7,
                            'from_hour'     =>   $reportData[0]->Thursday->data[0]->start_time,
                            'to_hour'       =>   $reportData[0]->Thursday->data[0]->end_time,
                        );
                        DoorDay::create($days_timings2);
                    }
                }

                $door['days_timings'] = DoorDay::where('door_id', $door->id)->get();

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Door created successfully !']

                    ),
                    'data' => $door
                );

                return response()->json($response, 200);
            }else{
                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['Door not created !']

                    ),
                    'data' => null
                );

                return response()->json($response, 200);
            }




        } else {
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function FetchUsers(Request $request){

        $data = User::all();

        $response = array(
            'meta' => array(
                'responseCode' => 200,
                'message' => ['Users List']
            ),
            'data' => $data
        );
        return response()->json($response, 200);

    }

    public function FetchUser($id){

        $data = User::find($id);

        if(!$data){
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => ['invalied user id !']

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

        $response = array(
            'meta' => array(
                'responseCode' => 200,
                'message' => ['User Detail']
            ),
            'data' => $data
        );
        return response()->json($response, 200);

    }

    public function FetchDoorsForSingleUser($id){

            $data = Door::where('user_id', $id)->with('doordays')->get();

            $response = array(
                'meta' => array(
                    'responseCode' => 200,
                    'message' => ['Doors List']
                ),
                'data' => $data
            );
            return response()->json($response, 200);
    }

    public function GetSingleDoorData($id){

        $checkCound = Door::find($id);

        if(!$checkCound){
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => ['invalied door id !']

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

        $data = Door::where('id', $id)->with('doordays')->get();

        $response = array(
            'meta' => array(
                'responseCode' => 200,
                'message' => ['Door Details']
            ),
            'data' => $data
        );
        return response()->json($response, 200);
    }

    public function UpdateProfile(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'profile' => 'required'
        ]);


        if ($validator->passes()) {

            $userData = User::find($request->user_id);

            $file = $request->file('profile');

            $destinationPath =public_path() . '/users_images/';
            $imagename = time().$file->getClientOriginalName();

            $image_path = 'https://doorbell.ioptime.com/public/users_images/'.$imagename;
            $file->move($destinationPath,$imagename);

            $input['image'] = $image_path;

            $form_data = array(
                'profile' => $image_path
            );

            if(User::where('id', $request->user_id)->update($form_data)){

//       //////////////////////////////////

               $oldFileName =  explode('/' ,$userData->profile);
                $lastKey = key(array_slice($oldFileName, -1, 1, true));

                    if(File::exists(public_path('users_images/'.$oldFileName[$lastKey]))){
                        File::delete(public_path('users_images/'.$oldFileName[$lastKey]));
                    }

//                /////////////////////////////

                $userData2 = User::find($request->user_id);

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Profile updated successfully']
                    ),
                    'data' => $userData2
                );
                return response()->json($response, 200);
            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['Profile not updated']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);

            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function UpdateListedStatus(Request $request){

        $validator = Validator::make($request->all(), [
            'door_id' => 'required|exists:doors,id',
             'is_listed' => 'required'
        ]);

        if ($validator->passes()) {

            if($request->is_listed !='yes' && $request->is_listed !='no'){

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['value for is-listed status must be yes or no']

                    ),
                    'data' => null

                );

                return response()->json($response, 400);

            }

            $form_data = array(
                'is_listed' => $request->is_listed
            );

            if(Door::where('id', $request->door_id)->update($form_data)){

                $doorData = Door::find($request->door_id);

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Door listed status updated successfully']
                    ),
                    'data' => $doorData
                );
                return response()->json($response, 200);
            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['Door not updated']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);

            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function UpdateNotifyStatus(Request $request){

        $validator = Validator::make($request->all(), [
            'door_id' => 'required|exists:doors,id',
            'is_notify' => 'required'
        ]);

        if ($validator->passes()) {

            if($request->is_notify !='yes' && $request->is_notify !='no'){

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['value for is_notify status must be yes or no']

                    ),
                    'data' => null

                );

                return response()->json($response, 400);

            }

            $form_data = array(
                'is_notify' => $request->is_notify
            );

            if(Door::where('id', $request->door_id)->update($form_data)){

                $doorData = Door::find($request->door_id);

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Door notification status updated successfully']
                    ),
                    'data' => $doorData
                );
                return response()->json($response, 200);
            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['Notification status not updated']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);

            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function UpdatePublicStatus(Request $request){

        $validator = Validator::make($request->all(), [
            'door_id' => 'required|exists:doors,id',
            'door_type' => 'required'
        ]);

        if ($validator->passes()) {

            if($request->door_type !='public' && $request->door_type !='private'){

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['value for door-type  must be public or private']

                    ),
                    'data' => null

                );

                return response()->json($response, 400);

            }

            $form_data = array(
                'door_type' => $request->door_type
            );

            if(Door::where('id', $request->door_id)->update($form_data)){

                $doorData = Door::find($request->door_id);

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Door type updated successfully']
                    ),
                    'data' => $doorData
                );
                return response()->json($response, 200);
            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['Door not updated']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);

            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function DeleteDoor($id){

        $checkCound = Door::find($id);

        if(!$checkCound){
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => ['invalied door id !']

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

        if(Door::find($id)->delete()){

            $response = array(
                'meta' => array(
                    'responseCode' => 200,
                    'message' => ['Door deleted successfully !']

                ),
                'data' => null
            );

            return response()->json($response, 200);

        }else{

            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => ['Door not deleted something went wrong !']

                ),
                'data' => null
            );

            return response()->json($response, 400);
        }

    }

    public function test(Request $request){
        $response = array(
            'meta' => array(
                'responseCode' => 200,
                'message' => ['This is test response message']
            ),
            'data' => null
        );
        return response()->json($response, 200);

    }


    public function logout(Request $request){

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|exists:devices_tokens,device_id'
        ]);

        if ($validator->passes()) {

            if (Auth::check()) {
                $user = Auth::user()->token();
                $user->revoke();

            if(DeviceToken::where('user_id', Auth::id())->where('device_id', $request->device_id)->delete()){

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['logged out']
                    ),
                    'data' => null
                );
                return response()->json($response, 200);

            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['logged out but device fcm token cannot deleted']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);

            }

            }else{
                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['something went wrong']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);
            }

        }else{

            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function AddHost(Request $request){

        $validator = Validator::make($request->all(), [
            'door_id' => 'required|exists:doors,id',
            'host_email' => 'required|email|exists:users,email',
        ]);

        if ($validator->passes()) {

            $checkCount = Door::where('id', $request->door_id)
                ->where('user_id', Auth::id())->get();

            if(count($checkCount) < 1){

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['logged-in user has no access to add any host for this door']

                    ),
                    'data' => null

                );

                return response()->json($response, 400);

            }else{
                $invited_personData = User::where('email', $request->host_email)->get();

                $woner_nameData = User::find(Auth::id());

                $encrypted_woner_id = Crypt::encryptString($woner_nameData->id);
                $encrypted_host_id = Crypt::encryptString($invited_personData[0]->id);
                $encrypted_host_email = Crypt::encryptString($invited_personData[0]->email);
                $encrypted_door_id = Crypt::encryptString($checkCount[0]->id);

                $andriod_downlink = 'www.facebook.com';
                $ios_downloadlink = 'www.facebook.com';
                $join_link = 'https://doorbell.ioptime.com/add/host/sync/'.$encrypted_woner_id.'/'.$encrypted_host_id.'/'.$encrypted_host_email.'/'.$encrypted_door_id;

                $woner_name  = $woner_nameData->username;
                $invited_person  = $invited_personData[0]->username;

                Mail::to($request->host_email)->send(new InvitationMail($andriod_downlink,$ios_downloadlink,$join_link,$woner_name,$invited_person));

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Application download and invitation link successfully send!']

                    ),
                    'data' => null
                );

                return response()->json($response, 200);
            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function AddGuest2($id1,$id2,$id3,$id4){

       $checkCount =  Host::where('owner_id',Crypt::decryptString($id1))
            ->where('added_person_id', Crypt::decryptString($id2))
            ->where('added_person_email', Crypt::decryptString($id3))
            ->where('door_id', Crypt::decryptString($id4))->get();

       if(count($checkCount) > 0){
           echo  "your already accepted host invitation request";
       }else{
           $hostdata = array(
               'owner_id'               => Crypt::decryptString($id1),
               'added_person_id'        => Crypt::decryptString($id2),
               'added_person_email'     => Crypt::decryptString($id3),
               'door_id'                => Crypt::decryptString($id4)
           );
           Host::create($hostdata);

           echo "you have successfully accepted invitation request";
       }



    }

    public function UpdateDoorMessages(Request $request){

        $validator = Validator::make($request->all(), [
            'door_id' => 'required|exists:doors,id',
            'message_during_operation_hours' => 'required',
            'message_during_off_hours' => 'required'
        ]);


        if ($validator->passes()) {

            $checkCount = Door::where('id', $request->door_id)
                ->where('user_id', Auth::id())->get();

            if(count($checkCount) < 1){

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['logged-in user has no access to edit any message for this door']

                    ),
                    'data' => null

                );

                return response()->json($response, 400);

            }else{

                $form_data = array(
                    'msg_in_operational_houres' => $request->message_during_operation_hours,
                    'msg_in_off_houres' => $request->message_during_off_hours
                );

                if(Door::where('id', $request->door_id)->update($form_data)){

                    $data = Door::where('id', $request->door_id)->with('doordays')->get();

                    $response = array(
                        'meta' => array(
                            'responseCode' => 200,
                            'message' => ['Door message updated successfully !']
                        ),
                        'data' => $data
                    );
                    return response()->json($response, 200);

                }else{

                    $response = array(
                        'meta' => array(
                            'responseCode' => 400,
                            'message' => ['message not updated something went wrong']
                        ),
                        'data' => null
                    );
                    return response()->json($response, 400);

                }

            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }
    }

    public function RemoveHost(Request $request){

        $validator = Validator::make($request->all(), [
            'door_id' => 'required|exists:doors,id',
            'host_id' => 'required|exists:users,id'
        ]);


        if ($validator->passes()) {

            $checkCount = Door::where('id', $request->door_id)
                ->where('user_id', Auth::id())->get();

            if(count($checkCount) < 1){

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['logged-in user has no access to remove any host for this door']

                    ),
                    'data' => null

                );

                return response()->json($response, 400);

            }else{

                $hostData = Host::where('door_id', $request->door_id)
                    ->where('added_person_id', $request->host_id)->get();

                foreach ($hostData as $data){
                    $data->delete();
                }

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Host deleted successfully !']

                    ),
                    'data' => null
                );

                return response()->json($response, 200);
            }

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()

                ),
                'data' => null

            );

            return response()->json($response, 400);
        }

    }

    public function SendNotification(Request $request){

        $validator = Validator::make($request->all(), [
            'guest_id' => 'required|exists:users,id',
            'door_id' => 'required|exists:doors,id'
        ]);


        if ($validator->passes()) {

            $getHostsIds = Host::where('door_id', $request->door_id)->pluck('added_person_id')->all();
            $ownerId = Host::where('door_id', $request->door_id)->pluck('owner_id')->first();

            array_push($getHostsIds, $ownerId);


            $url = 'https://fcm.googleapis.com/fcm/send';
            $FcmToken = DeviceToken::whereIn('user_id', $getHostsIds)->whereNotNull('fcm_token')->pluck('fcm_token')->all();


            $serverKey = 'AAAAtfwUgQ4:APA91bGzot7B2woEOVqZgl4akhd21nOzG17c3b-YQb732jq1_HMFZJmba-r7Px1r4XFqHhA-8dHyuH0e-kgSxh2XXi0B7PDs6TH_Wv1nux7mjFFDVaimXRWKXYNqexMnb6NsAzWZUWO2';

            $guestName = User::where('id', $request->guest_id)->pluck('username')->first();
            $doorName = Door::where('id', $request->door_id)->pluck('name')->first();


            $data = [
                "registration_ids" => $FcmToken,
                "notification" => [
                    "title" => 'Doorbell Notification',
                    "body" => $guestName . ' is at your '. $doorName . ' door'
                ]
            ];

            $encodedData = json_encode($data);

            $headers = [
                'Authorization:key=' . $serverKey,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

            // Execute post
            $result = curl_exec($ch);

            if ($result === FALSE) {

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => die('Curl failed: ' . curl_error($ch))
                    ),
                    'data' => null

                );

                return response()->json($response, 400);

            }else{

                $title = 'Doorbell Notification';
                $body  = $guestName . ' is at your '. $doorName. ' door';

                foreach ($getHostsIds as $gethostid){

                    $formData55 = array(
                        'user_id' =>  $gethostid,
                        'title'   =>  $title,
                        'body'    =>  $body
                    );

                    Notification::create($formData55);

                }

                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Notification send successfully to all hosts !']

                    ),
                    'data' => null
                );

                return response()->json($response, 200);
            }

            // Close connection
            curl_close($ch);

            // FCM response

    //    dd($result);

        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()
                ),
                'data' => null

            );

            return response()->json($response, 400);
        }
    }

    public function GetNotifications(Request $request){

        if (Auth::check()) {
            $notifications = Notification::where('user_id', Auth::id())->get();

            $response = array(
                'meta' => array(
                    'responseCode' => 200,
                    'message' => ['notifications']

                ),
                'data' => $notifications
            );

            return response()->json($response, 200);


        }else{
            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => ['invalid login authentication token']
                ),
                'data' => null
            );
            return response()->json($response, 400);
        }

    }

    public function UpdateFcm(Request $request){

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|exists:devices_tokens,device_id',
            'device_fcm_token' => 'required|unique:devices_tokens,fcm_token',
        ]);

        if ($validator->passes()) {


            $form_data = array(
                'fcm_token' => $request->device_fcm_token
            );

            if(DeviceToken::where('device_id', $request->device_id)->update($form_data)){


                $response = array(
                    'meta' => array(
                        'responseCode' => 200,
                        'message' => ['Fcm token updated successfully']
                    ),
                    'data' => null
                );
                return response()->json($response, 200);

            }else{

                $response = array(
                    'meta' => array(
                        'responseCode' => 400,
                        'message' => ['something went wrong']
                    ),
                    'data' => null
                );
                return response()->json($response, 400);

            }



        }else{

            $response = array(
                'meta' => array(
                    'responseCode' => 400,
                    'message' => $validator->errors()->all()
                ),
                'data' => null
            );
            return response()->json($response, 400);
        }
    }

}
