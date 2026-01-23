<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketTransfer extends Model
{
  use HasFactory;

  protected $fillable = [
    'ticket_id',
    'from_user_id',
    'from_role',
    'to_role',
    'to_user_id',
    'notes',
    'status' // pending, accepted, rejected
  ];

  // Tiket induk
  public function ticket()
  {
    return $this->belongsTo(Ticket::class);
  }

  // Pengirim (Siapa yang mengoper)
  public function fromUser()
  {
    return $this->belongsTo(User::class, 'from_user_id');
  }

  // Penerima (Siapa yang dituju - Opsional jika hanya role)
  public function toUser()
  {
    return $this->belongsTo(User::class, 'to_user_id');
  }
}
