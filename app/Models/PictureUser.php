<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PictureUser extends Model
{
    use HasFactory;

    protected $table = 'picture_user';

    protected $fillable = [
        'file_name',
        'picture_id',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function picture()
    {
        return $this->belongsTo(Picture::class);
    }

    public function pictures(){
        // return $this->belongsTo(Picture::class);
        return $this->belongsTo(Picture::class, 'picture_id', 'id');

    }
}
