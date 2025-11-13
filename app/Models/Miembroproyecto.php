<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiembroProyecto extends Model
{
    use HasFactory;

    protected $table = 'miembros_proyecto';

    protected $fillable = [
        'id_proyecto',
        'id_usuario',
        'rol'
    ];

    public $timestamps = false;

    public function proyectos(){
        return $this->belongsTo(Proyecto::class, 'id_proyecto',);
    }

    public function miembros(){
        return $this->belongsTo(Perfil::class, 'id_usuario');
    }

}
