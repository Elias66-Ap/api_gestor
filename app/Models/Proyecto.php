<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;

    protected $table = 'proyecto';

    protected $fillable = [
        'id',
        'nombre',
        'descripcion_breve',
        'descripcion_detalle',
        'fecha_inicio',
        'fecha_entrega',
        'progreso',
        'completado',
        'fecha_completado',
        'id_creador'
    ];

    public $timestamps = false;

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_proyecto');
    }

    public function miembros(){
        return $this->hasMany(Usuario::class, 'miembros_proyecto','id_proyecto', 'id_usuario')->withPivot('rol');
    }

    public function calcularProgreso()
    {
        $total = $this->tareas()->count();

        if ($total === 0){ return 0;}

        $completadas = $this->tareas()->where('estado', 'Hecho')->count();

        return round($completadas / $total * 100);
    }
}
