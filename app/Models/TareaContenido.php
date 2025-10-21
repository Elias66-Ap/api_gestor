<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareaContenido extends Model
{
    use HasFactory;

    protected  $table = 'tarea_contenido';

    protected $fillable = [
        'id_tarea',
        'tipo',
        'valor'
    ];

    public $timestamps = false;

    public function tarea(){
        return $this->belongsTo(Tarea::class, 'id_tarea');
    }
}
