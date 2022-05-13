<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageManipulation extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // cuz we don't have an 'updated_at' column and we'd get an error otherwise. https://laravel.com/docs/9.x/eloquent#timestamps
    const TYPE_RESIZE = 'resize';

    protected $fillable = ['name', 'path', 'type', 'data', 'output_path', 'user_id', 'album_id'];
    // protected $guarded = [];
}
