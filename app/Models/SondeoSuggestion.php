<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SondeoSuggestion extends Model
{
    protected $table = 'sondeo_suggestions';

    protected $fillable = [
        'message',
        'contact_email',
        'fingerprint_hash',
    ];
}
