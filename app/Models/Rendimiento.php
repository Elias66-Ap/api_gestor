<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rendimiento extends Model
{
    use HasFactory;

    protected $table = 'rendimiento';

    protected $fillable = [
        'id',
        'id_usu',
        'tar_total',
        'tar_completadas',
        'tar_tarde',
        'rendimiento',
        'fecha_registo'
    ];

    public $timestamps = false;

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'id_usu');
    }
}
