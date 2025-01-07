<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Veterinarian extends Model
{
    use HasFactory;

    protected $table = 'veterinarians';
    protected $primaryKey = 'id_veterinarian';

    protected $fillable = [
        'fk_id_user',
        'crmv',
        'uf',
        'created_by',
        'updated_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id_user');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fk_id_user', 'id_user');
    }
}
