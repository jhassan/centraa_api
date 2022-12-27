<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Helper;
use App\Models\BookingOfferPrice;
use App\Models\CabOrderSum;
use App\Models\AdditionalInfo;
use App\Http\Controllers\BaseController as BaseController;

class BookingController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search_data = $request->all();
        if(empty($search_data['user_id'])){
            return $this->sendError('User ID not empty', "");
            exit;
        }
        $user_id = $search_data['user_id'];
        // get login user data
        $user_data = User::where('id', $user_id)->first(); 

        if($user_data->user_type == 1)
            $bookings = Booking::query()->where('is_accept_offer',0)->where('is_deleted',0)->whereDate('offer_ends_date', '>', Carbon::now())->orderByDesc('created_at');
        else if($user_data->user_type == 2)
            $bookings = Booking::query()->where('is_accept_offer',0)->where('is_deleted',0)->whereDate('offer_ends_date', '>', Carbon::now())->orderByDesc('created_at');  
        else if($user_data->user_type == 3)
            $bookings = Booking::query()->where('booking_move_to',0)->whereDate('offer_ends_date', '>', Carbon::now())->where('is_deleted',0)->orderByDesc('created_at');        
        else if($user_data->user_type == 4)
            $bookings = Booking::query()->where('show_booking_sub_admin',1)->where('booking_move_to',0)->whereDate('offer_ends_date', '>', Carbon::now())->where('is_deleted',0)->orderByDesc('created_at');        
        $company_id = $driver_id = 0;
        $all_vehcile = $all_car_types = $all_booking_type = $pick_date = $end_date = $search_all = "";
        if($user_data->user_type == 1){
            $company_id = $user_data->company_id;
        }
        if($user_data->user_type == 2){
            $driver_id = $user_data->driver_id;
        }
        if($search_data){
            if(!empty($search_data['search_all']))
                $search_all = $search_data['search_all'];
            if(!empty($search_data['company_id']))    
                $company_id = $search_data['company_id'];
            if(!empty($search_data['driver_id']))    
                $driver_id = $search_data['driver_id'];
            // vehcile type
            if(!empty($search_data['all_vehcile']))
                $all_vehcile = explode(',',$search_data['all_vehcile']);
            // car types
            if(!empty($search_data['car_types']))
                $all_car_types = explode(',',$search_data['car_types']);
            // booking types
            if(!empty($search_data['booking_type']))
                $all_booking_type = explode(',',$search_data['booking_type']);  
            // booking pickup date
            if(!empty($search_data['pick_date']))
                $pick_date = $search_data['pick_date'];  
            // booking end date
            if(!empty($search_data['end_date']))
                $end_date = $search_data['end_date'];         
        }
        $data_array = array('user_id' => $user_id ,'search_all' => $search_all ,'driver_id' => $driver_id, 'end_date' => $end_date, 'pick_date' => $pick_date, 'company_id' => $company_id, 'all_vehcile' => $all_vehcile, 'all_car_types' => $all_car_types, 'all_booking_type' => $all_booking_type);
        // Start Query
        if(!empty($all_vehcile)){
            $bookings->whereIn('vehicle_id', \collect($data_array['all_vehcile']));
        }
        if(!empty($all_car_types)){
            $bookings->whereIn('car_type', \collect($data_array['all_car_types']));
        }
        if(!empty($all_booking_type)){
            $bookings->whereIn('booking_trip_type', \collect($data_array['all_booking_type']));
        }
        if(!empty($pick_date) && !empty($end_date)){
            $bookings->whereBetween('pick_date', [$pick_date, $end_date]);
        }
        if($search_all){
            $bookings->where(function ($q) use ($data_array) {
                $q->where('rand_cart_order_id','LIKE','%'.$data_array['search_all'].'%')
                    ->orWhere('cart_order_id','LIKE','%'.$data_array['search_all'].'%')
                    ->orWhere('pasenger_name','LIKE','%'.$data_array['search_all'].'%')
                    ->orWhere('passenger_phone','LIKE','%'.$data_array['search_all'].'%')
                    ->orWhere('passenger_email','LIKE','%'.$data_array['search_all'].'%');
            });
        }
        // Company 
        if($user_data->user_type == 1){
            $bookings->where(function ($q) use ($data_array) {
                $q->whereRaw("find_in_set('".$data_array['company_id']."',get_companies_bin)");
            });
            $bookings->whereNotIn('cab_booking_bin.id', function ($query) use ($data_array) {
                $query->select('booking_id')->from('cab_booking_offer_price')->where('delete_offer_from_admin', 0)->where('user_id', $data_array['user_id']);
            });
        } else if($user_data->user_type == 2){ // Driver
            $bookings->where(function ($q) use ($data_array) {
                $q->whereRaw("find_in_set('".$data_array['driver_id']."',drivers_ids)");
            });
            $bookings->whereNotIn('cab_booking_bin.id', function ($query) use ($data_array) {
                $query->select('booking_id')->from('cab_booking_offer_price')->where('delete_offer_from_admin', 0)->where('user_id', $data_array['user_id']);
            });
        } else if($user_data->user_type == 3 || $user_data->user_type == 4){ // Admin Or Sub Admin
            $bookings->whereNotIn('cab_booking_bin.id', function ($query) {
                $query->select('booking_id')->from('cab_booking_offer_price')->where('delete_offer_from_admin', 0)->where('admin_send_waiting_confirmation', 0);
            });
            if(!empty($search_data['company_id']))
                $company_id = $search_data['company_id'];
            if(!empty($company_id)){
                $bookings->where(function ($q) use ($company_id) {
                    $q->whereRaw("find_in_set('".$company_id."', get_companies_bin)");
                });
            }
            if(!empty($search_data['driver_id']))
                $driver_id = $search_data['driver_id'];
            if(!empty($driver_id)){
                $bookings->where(function ($q) use ($driver_id) {
                    $q->whereRaw("find_in_set('".$driver_id."', drivers_ids)");
                });
            }
        }
        $result = $bookings->select('cab_booking_bin.*', 'cart_order_id as rand_cart_order_id' ,DB::raw("TIME_FORMAT(cab_booking_bin.return_time , '%H:%i:%s')  as return_time"))->get();
            if(count($result) > 0){
                foreach($result as $key => $data){
                    $booking_data[$key] = $data;

                    // Note replace Special Characters
                    if($booking_data[$key]['note']){
                        $booking_data[$key]['note'] = $booking_data[$key]['note'];
                    }

                    // Additional Note replace Special Characters
                    if($booking_data[$key]['additional_note']){
                        $booking_data[$key]['additional_note'] = $booking_data[$key]['additional_note'];
                    }

                    // Note Message replace Special Characters
                    if($booking_data[$key]['note_message']){
                        $booking_data[$key]['note_message'] = $booking_data[$key]['note_message'];
                    }
                    
                    // offer days and time expire 
                    $days = 0;
                    $today_date = date("Y-m-d H:i:s");
                    $offer_ends_date = $data->offer_ends_date;
                    $days = (strtotime($offer_ends_date) - strtotime($today_date)) / (60 * 60 * 24);
                    if($days){
                        if((int)$days > 0){
                            $booking_data[$key]['job_expire_days'] = (int)$days;
                            $booking_data[$key]['job_expire_hours'] = 0;
                        } else {
                            $t1 = strtotime( $data->offer_ends_date );
                            $t2 = strtotime( $today_date );
                            $diff = $t1 - $t2;
                            $hours = $diff / ( 60 * 60 );
                            $booking_data[$key]['job_expire_hours'] = (int)$hours;
                            $booking_data[$key]['job_expire_days'] = 0;
                        }    
                    } else {
                        $booking_data[$key]['job_expire_days'] = 0;
                        $booking_data[$key]['job_expire_hours'] = 0;
                    }

                    // Get offer Price
                    $offer_price = "";
                    $offer_price = $this->checkUserOfferPrice($data->id, $user_id);
                    if($offer_price){
                        $booking_data[$key]['rebidding_allow_agian'] = $offer_price['rebidding_allow_agian'];
                        $booking_data[$key]['delete_offer_from_admin'] = $offer_price['delete_offer_from_admin'];
                    } else {
                        $booking_data[$key]['rebidding_allow_agian'] = 0;
                        $booking_data[$key]['delete_offer_from_admin'] = 0;
                    }

                    // Get due amount form cab order sum
                    $cab_order_due_amount = $total_distance = $total_duration = $pasenger_name = $passenger_phone = "";
                    $cab_order_data = $this->get_data_cab_order_sum($data->cart_order_id);
                    if($cab_order_data->due_amount){
                        $booking_data[$key]['cab_order_due_amount'] = $cab_order_data->due_amount;
                    } else {
                        $booking_data[$key]['cab_order_due_amount'] = 0;
                    }
                    if($cab_order_data->total_distance){
                        $booking_data[$key]['total_distance'] = $cab_order_data->total_distance;
                    } else {
                        $booking_data[$key]['total_distance'] = "";
                    }
                    if($cab_order_data->total_duration){
                        $booking_data[$key]['total_duration'] = $cab_order_data->total_duration;
                    } else {
                        $booking_data[$key]['total_duration'] = 0;
                    }

                    if($cab_order_data->pickup_latitude){
                        $booking_data[$key]['pickup_latitude'] = $cab_order_data->pickup_latitude;
                    } else {
                        $booking_data[$key]['pickup_latitude'] = 0;
                    }
                    if($cab_order_data->pickup_longtitude){
                        $booking_data[$key]['pickup_longtitude'] = $cab_order_data->pickup_longtitude;
                    } else {
                        $booking_data[$key]['pickup_longtitude'] = 0;
                    }

                    if($cab_order_data->dropoff_latitude){
                        $booking_data[$key]['dropoff_latitude'] = $cab_order_data->dropoff_latitude;
                    } else {
                        $booking_data[$key]['dropoff_latitude'] = 0;
                    }
                    if($cab_order_data->dropoff_longtitude){
                        $booking_data[$key]['dropoff_longtitude'] = $cab_order_data->dropoff_longtitude;
                    } else {
                        $booking_data[$key]['dropoff_longtitude'] = 0;
                    }

                    // get total booking bids
                    $total_bids = 0;
                    $total_bids = $this->totalBids($data->id);
                    if($total_bids){
                        $booking_data[$key]['total_bids'] = $total_bids;
                    } else {
                        $booking_data[$key]['total_bids'] = 0;
                    }

                    // set job return date and time
                if($data->booking_type == 1 || $data->booking_type == 2){ // For Single or Return Job
                    $booking_data[$key]['return_date'] = "";
                    $booking_data[$key]['return_time'] = "";
                    $booking_data[$key]['return_flight'] = "";
                } 

                // get additional info
                $single_flight = $return_flight = "";
                $addinfo = $this->AdditionalInfo($data->cart_order_id);
                
                $booking_data[$key]['flight_go'] = "";
                $booking_data[$key]['flight_come'] = "";
                // For Single Job Flight No
                if($data->booking_type == 1){ 
                    if($addinfo)
                        $booking_data[$key]['flight_go'] = $addinfo->return_flight;
                } elseif($data->booking_type == 2){ // For Return Job Flight No 
                    if($addinfo)
                        $booking_data[$key]['flight_come'] = $addinfo->single_flight;
                } else { // For Both Job Flight No 
                    if($addinfo){
                        $booking_data[$key]['flight_go'] = $addinfo->return_flight;
                        $booking_data[$key]['flight_come'] = $addinfo->single_flight;
                    }
                }

                // set return job cart id with R
                if($data->booking_type == 2){
                    $booking_data[$key]['rand_cart_order_id'] = $data->rand_cart_order_id."-R";
                }
                }
                return $this->sendResponse($booking_data, 'Booking Bin Data get successfully.');
            } else
                return $this->sendError('Booking Bin Data not found.', '');  
    }

    public function index2(Request $request)
    {
        $search_data = $request->all();
            if(empty($search_data['user_id'])){
                return $this->sendError('User ID not empty', "");
                exit;
            }
            $user_id = $search_data['user_id'];
            // get login user data
            $user_data = User::where('id', $user_id)->first(); 

            if($user_data->user_type == 1)
                $bookings = Booking::query()->where('is_accept_offer',0)->where('is_deleted',0)->whereDate('offer_ends_date', '>', Carbon::now())->orderByDesc('created_at');
            else if($user_data->user_type == 2)
                $bookings = Booking::query()->where('is_accept_offer',0)->where('is_deleted',0)->whereDate('offer_ends_date', '>', Carbon::now())->orderByDesc('created_at');  
            else if($user_data->user_type == 3)
                $bookings = Booking::query()->where('booking_move_to',0)->whereDate('offer_ends_date', '>', Carbon::now())->where('is_deleted',0)->orderByDesc('created_at');        
            else if($user_data->user_type == 4)
                $bookings = Booking::query()->where('show_booking_sub_admin',1)->where('booking_move_to',0)->whereDate('offer_ends_date', '>', Carbon::now())->where('is_deleted',0)->orderByDesc('created_at');        
            $company_id = $driver_id = 0;
            $all_vehcile = $all_car_types = $all_booking_type = $pick_date = $end_date = $search_all = "";
            if($user_data->user_type == 1){
                $company_id = $user_data->company_id;
            }
            if($user_data->user_type == 2){
                $driver_id = $user_data->driver_id;
            }
            if($search_data){
                if(!empty($search_data['search_all']))
                    $search_all = $search_data['search_all'];
                if(!empty($search_data['company_id']))    
                    $company_id = $search_data['company_id'];
                if(!empty($search_data['driver_id']))    
                    $driver_id = $search_data['driver_id'];
                // vehcile type
                if(!empty($search_data['all_vehcile']))
                    $all_vehcile = explode(',',$search_data['all_vehcile']);
                // car types
                if(!empty($search_data['car_types']))
                    $all_car_types = explode(',',$search_data['car_types']);
                // booking types
                if(!empty($search_data['booking_type']))
                    $all_booking_type = explode(',',$search_data['booking_type']);  
                // booking pickup date
                if(!empty($search_data['pick_date']))
                    $pick_date = $search_data['pick_date'];  
                // booking end date
                if(!empty($search_data['end_date']))
                    $end_date = $search_data['end_date'];         
            }
            $data_array = array('user_id' => $user_id ,'search_all' => $search_all ,'driver_id' => $driver_id, 'end_date' => $end_date, 'pick_date' => $pick_date, 'company_id' => $company_id, 'all_vehcile' => $all_vehcile, 'all_car_types' => $all_car_types, 'all_booking_type' => $all_booking_type);
            // Start Query
            if(!empty($all_vehcile)){
                $bookings->whereIn('vehicle_id', \collect($data_array['all_vehcile']));
            }
            if(!empty($all_car_types)){
                $bookings->whereIn('car_type', \collect($data_array['all_car_types']));
            }
            if(!empty($all_booking_type)){
                $bookings->whereIn('booking_trip_type', \collect($data_array['all_booking_type']));
            }
            if(!empty($pick_date) && !empty($end_date)){
                $bookings->whereBetween('pick_date', [$pick_date, $end_date]);
            }
            if($search_all){
                $bookings->where(function ($q) use ($data_array) {
                    $q->where('rand_cart_order_id','LIKE','%'.$data_array['search_all'].'%')
                        ->orWhere('cart_order_id','LIKE','%'.$data_array['search_all'].'%')
                        ->orWhere('pasenger_name','LIKE','%'.$data_array['search_all'].'%')
                        ->orWhere('passenger_phone','LIKE','%'.$data_array['search_all'].'%')
                        ->orWhere('passenger_email','LIKE','%'.$data_array['search_all'].'%');
                });
            }
            // Company 
            if($user_data->user_type == 1){
                $bookings->where(function ($q) use ($data_array) {
                    $q->whereRaw("find_in_set('".$data_array['company_id']."',get_companies_bin)");
                });
                $bookings->whereNotIn('cab_booking_bin.id', function ($query) use ($data_array) {
                    $query->select('booking_id')->from('cab_booking_offer_price')->where('delete_offer_from_admin', 0)->where('user_id', $data_array['user_id']);
                });
            } else if($user_data->user_type == 2){ // Driver
                $bookings->where(function ($q) use ($data_array) {
                    $q->whereRaw("find_in_set('".$data_array['driver_id']."',drivers_ids)");
                });
                $bookings->whereNotIn('cab_booking_bin.id', function ($query) use ($data_array) {
                    $query->select('booking_id')->from('cab_booking_offer_price')->where('delete_offer_from_admin', 0)->where('user_id', $data_array['user_id']);
                });
            } else if($user_data->user_type == 3 || $user_data->user_type == 4){ // Admin Or Sub Admin
                $bookings->whereNotIn('cab_booking_bin.id', function ($query) {
                    $query->select('booking_id')->from('cab_booking_offer_price')->where('delete_offer_from_admin', 0)->where('admin_send_waiting_confirmation', 0);
                });
                if(!empty($search_data['company_id']))
                    $company_id = $search_data['company_id'];
                if(!empty($company_id)){
                    $bookings->where(function ($q) use ($company_id) {
                        $q->whereRaw("find_in_set('".$company_id."', get_companies_bin)");
                    });
                }
                if(!empty($search_data['driver_id']))
                    $driver_id = $search_data['driver_id'];
                if(!empty($driver_id)){
                    $bookings->where(function ($q) use ($driver_id) {
                        $q->whereRaw("find_in_set('".$driver_id."', drivers_ids)");
                    });
                }
            }
            $result = $bookings->select('cab_booking_bin.*', 'cart_order_id as rand_cart_order_id' ,DB::raw("TIME_FORMAT(cab_booking_bin.return_time , '%H:%i:%s')  as return_time"))->get();
                if(count($result) > 0){
                    foreach($result as $key => $data){
                        $booking_data[$key] = $data;

                        // Note replace Special Characters
                        // if($booking_data[$key]['note']){
                        //     $booking_data[$key]['note'] = \General::replace_special_characters($booking_data[$key]['note']);
                        // }

                        // // Additional Note replace Special Characters
                        // if($booking_data[$key]['additional_note']){
                        //     $booking_data[$key]['additional_note'] = \General::replace_special_characters($booking_data[$key]['additional_note']);
                        // }

                        // // Note Message replace Special Characters
                        // if($booking_data[$key]['note_message']){
                        //     $booking_data[$key]['note_message'] = \General::replace_special_characters($booking_data[$key]['note_message']);
                        // }
                        
                        // offer days and time expire 
                        $days = 0;
                        $today_date = date("Y-m-d H:i:s");
                        $offer_ends_date = $data->offer_ends_date;
                        $days = (strtotime($offer_ends_date) - strtotime($today_date)) / (60 * 60 * 24);
                        if($days){
                            if((int)$days > 0){
                                $booking_data[$key]['job_expire_days'] = (int)$days;
                                $booking_data[$key]['job_expire_hours'] = 0;
                            } else {
                                $t1 = strtotime( $data->offer_ends_date );
                                $t2 = strtotime( $today_date );
                                $diff = $t1 - $t2;
                                $hours = $diff / ( 60 * 60 );
                                $booking_data[$key]['job_expire_hours'] = (int)$hours;
                                $booking_data[$key]['job_expire_days'] = 0;
                            }    
                        } else {
                            $booking_data[$key]['job_expire_days'] = 0;
                            $booking_data[$key]['job_expire_hours'] = 0;
                        }

                        // Get offer Price
                        $offer_price = "";
                        $offer_price = $this->checkUserOfferPrice($data->id, $user_id);
                        if($offer_price){
                            $booking_data[$key]['rebidding_allow_agian'] = $offer_price['rebidding_allow_agian'];
                            $booking_data[$key]['delete_offer_from_admin'] = $offer_price['delete_offer_from_admin'];
                        } else {
                            $booking_data[$key]['rebidding_allow_agian'] = 0;
                            $booking_data[$key]['delete_offer_from_admin'] = 0;
                        }

                        // Get due amount form cab order sum
                        $cab_order_due_amount = $total_distance = $total_duration = $pasenger_name = $passenger_phone = "";
                        $cab_order_data = $this->get_data_cab_order_sum($data->cart_order_id);
                        if($cab_order_data->due_amount){
                            $booking_data[$key]['cab_order_due_amount'] = $cab_order_data->due_amount;
                        } else {
                            $booking_data[$key]['cab_order_due_amount'] = 0;
                        }
                        if($cab_order_data->total_distance){
                            $booking_data[$key]['total_distance'] = $cab_order_data->total_distance;
                        } else {
                            $booking_data[$key]['total_distance'] = "";
                        }
                        if($cab_order_data->total_duration){
                            $booking_data[$key]['total_duration'] = $cab_order_data->total_duration;
                        } else {
                            $booking_data[$key]['total_duration'] = 0;
                        }

                        if($cab_order_data->pickup_latitude){
                            $booking_data[$key]['pickup_latitude'] = $cab_order_data->pickup_latitude;
                        } else {
                            $booking_data[$key]['pickup_latitude'] = 0;
                        }
                        if($cab_order_data->pickup_longtitude){
                            $booking_data[$key]['pickup_longtitude'] = $cab_order_data->pickup_longtitude;
                        } else {
                            $booking_data[$key]['pickup_longtitude'] = 0;
                        }

                        if($cab_order_data->dropoff_latitude){
                            $booking_data[$key]['dropoff_latitude'] = $cab_order_data->dropoff_latitude;
                        } else {
                            $booking_data[$key]['dropoff_latitude'] = 0;
                        }
                        if($cab_order_data->dropoff_longtitude){
                            $booking_data[$key]['dropoff_longtitude'] = $cab_order_data->dropoff_longtitude;
                        } else {
                            $booking_data[$key]['dropoff_longtitude'] = 0;
                        }

                        // get total booking bids
                        $total_bids = 0;
                        $total_bids = $this->totalBids($data->id);
                        if($total_bids){
                            $booking_data[$key]['total_bids'] = $total_bids;
                        } else {
                            $booking_data[$key]['total_bids'] = 0;
                        }

                        // set job return date and time
                    if($data->booking_type == 1 || $data->booking_type == 2){ // For Single or Return Job
                        $booking_data[$key]['return_date'] = "";
                        $booking_data[$key]['return_time'] = "";
                        $booking_data[$key]['return_flight'] = "";
                    } 

                    // get additional info
                    $single_flight = $return_flight = "";
                    $addinfo = $this->AdditionalInfo($data->cart_order_id);
                    
                    $booking_data[$key]['flight_go'] = "";
                    $booking_data[$key]['flight_come'] = "";
                    // For Single Job Flight No
                    if($data->booking_type == 1){ 
                        if($addinfo)
                            $booking_data[$key]['flight_go'] = $addinfo->return_flight;
                    } elseif($data->booking_type == 2){ // For Return Job Flight No 
                        if($addinfo)
                            $booking_data[$key]['flight_come'] = $addinfo->single_flight;
                    } else { // For Both Job Flight No 
                        if($addinfo){
                            $booking_data[$key]['flight_go'] = $addinfo->return_flight;
                            $booking_data[$key]['flight_come'] = $addinfo->single_flight;
                        }
                    }

                    // set return job cart id with R
                    if($data->booking_type == 2){
                        $booking_data[$key]['rand_cart_order_id'] = $data->rand_cart_order_id."-R";
                    }
                    }
                    return $this->sendResponse($booking_data, 'Booking Bin Data get successfully.');
                } else
                    return $this->sendError('Booking Bin Data not found.', '');  
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    // check user booking offer
    public function checkUserOfferPrice($booking_id, $user_id){
        // get login user data
        $user_data = User::where('id',$user_id)->first(); 
        // get claim now record of current user
        if($user_data->user_type == 3 || $user_data->user_type == 4){ // Admin Or Sub Admin
            $user_data = BookingOfferPrice::where('booking_id', $booking_id)->first();
        } else {
            $user_data = BookingOfferPrice::where('user_id', $user_id)->where('booking_id', $booking_id)->first();
        }
        if($user_data)
            return $user_data;
    }

    // get cab order actual amount
    public function get_data_cab_order_sum($cart_order_id){
        $data = CabOrderSum::where('cart_order_id', $cart_order_id)->first();
        if($data)
            return $data;
        else
            return false;    
    }

    // total bids
    public function totalBids($booking_id){
        // $total_bookings = BookingOfferPrice::where('booking_id', $booking_id)->where('delete_offer_from_admin', 0)->get(); 
        $total_bookings = BookingOfferPrice::where('booking_id', $booking_id)->where('admin_send_waiting_confirmation', 0)->get(); 
        $count_bookings = $total_bookings->count();
        return $count_bookings;
    }

    // get additional info
    public function AdditionalInfo($cart_order_id){
        $data = AdditionalInfo::where('cart_order_id', $cart_order_id)->first();
        if($data)
            return $data;
        else
            return false; 
    }
}