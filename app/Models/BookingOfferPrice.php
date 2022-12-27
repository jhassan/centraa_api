<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingOfferPrice extends Model
{
    use HasFactory;
    // protected $connection = 'mysql2';
    public $timestamps = true;
    public $table = "cab_booking_offer_price";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'booking_id',
        'offer_price',
        'user_name',
        'user_type',
        'is_accept',
        'offer_accept',
        'is_claim',
        'awaiting_confirmation',
        'additional_note',
        'admin_send_waiting_confirmation',
        'job_cancel_price',
        'job_cancel_status',
        'company_id',
        'driver_id',
        'group_id',
        'awaiting_confirmation_price'
    ];
}