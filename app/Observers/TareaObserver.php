<?php

namespace App\Observers;    
use App\Models\Tarea;

class TareaObserver
{
    public function created(Tarea $tarea){
        $this->actualizarProgreso($tarea);
    }

    public function updated(Tarea $tarea){
        $this->actualizarProgreso($tarea);
    }
    
    public function deleted(Tarea $tarea){
        $this->actualizarProgreso($tarea);
    }

    public function actualizarProgreso(Tarea $tarea)
    {
        $proyecto = $tarea->proyecto()->first();

        if ($proyecto) {
            $total = $proyecto->tareas()->count();
            if ($total === 0) {
                $proyecto->progreso = 0;
            } else {
                $completados = $proyecto->tareas()->where('estado', 'hecho')->count();
                $proyecto->progreso = round(($completados / $total)*100);
            }
            $proyecto->save();
        }
    }
}
