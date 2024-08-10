<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Picture extends Model
{
    use HasFactory;
    protected $table = 'pictures';

    protected $fillable = [
        'title',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pictureList()
    {
        return $this->hasMany(PictureList::class);
        // return 'hello world';
        // return $this->hasMany(PictureList::class, 'picture_id', 'id');
    }
}
