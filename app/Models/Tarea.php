<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TareaContenido;

class Tarea extends Model
{
    use HasFactory;

    protected $table = 'tarea';

    protected $fillable = [
        'id',
        'titulo',
        'descripcion',
        'estado',
        'prioridad',
        'fecha_creacion',
        'fecha_vencimiento',
        'fecha_completado',
        'id_proyecto',
        'id_asignado',
        'id_creador',
    ];

    public $timestamps = false;

    public function proyecto() {
        return $this->belongsTo(Proyecto::class, 'id_proyecto');
    }

    public function asignado() {
        return $this->belongsTo(Perfil::class, 'id_asignado');
    }

    public function contenidos(){
        return $this->hasMany(TareaContenido::class, 'id_tarea');
    }
}
