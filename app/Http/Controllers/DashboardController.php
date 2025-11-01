<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Usuario;
use App\Models\Tarea;
use App\Models\Proyecto;
use App\Models\Rendimiento;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{

    public function dashboard()
    {

        $hoy = Carbon::today()->toDateString();
        $ayer = Carbon::yesterday();
        $estaSemana = Carbon::now()->startOfWeek();
        $esteMes = Carbon::now()->startOfMonth();

        $mesAnterior = Carbon::now()->subMonth()->startOfMonth();
        $finMesAnterior = Carbon::now()->subMonth()->endOfMonth();

        //Usuarios
        $totalUsuarios = Usuario::where('estado', '=', '1')->count();
        $usuariosNuevos = Usuario::where('fecha_registro', '>=', $esteMes)
            ->where('estado', '=', '1')->count();
        $usuariosMesAnterior = Usuario::whereBetween('fecha_registro', [$mesAnterior, $finMesAnterior])
            ->where('estado', '=', '1')
            ->count();

        $usuariosNow = $usuariosNuevos - $usuariosMesAnterior;
        $usuActuales = ($usuariosNow >= 0 ? '+' : '') . $usuariosNow;

        //Proyectos
        $totalProyectos = Proyecto::all()->count();
        $proNuevos = Proyecto::where('fecha_inicio', '>=', $esteMes)
            ->where('completado', 0)->count();
        $proActivos = Proyecto::where('completado', 0)->count();
        $proCompletados = Proyecto::where('completado', 1)->count();
        $proCompletadosMes = Proyecto::where('fecha_completado', '>=', $esteMes)->count();

        //Tareas
        $tareasTotal = Tarea::count();
        $tareasCompletas = Tarea::where('estado', '=', 'Hecho')->count();
        $tareasActivas = Tarea::where('estado', '!=', 'Hecho')->count();
        $tareasNuevas = Tarea::whereDate('fecha_creacion', $estaSemana)->count();
        $tareasPendientes = Tarea::where('estado', '=', 'Por hacer')->count();
        $tareasHoy = Tarea::whereDate('fecha_completado', $ayer)->count();

        //Rendimiento
        $promedioRendimiento = round(Rendimiento::avg('rendimiento') * 100);

        return response()->json([
            'status' => 'success',
            'data' => [
                'usuarios' => [
                    'total' => $totalUsuarios,
                    'ahora' => "{$usuActuales} este mes"
                ],
                'proyectos' => [
                    'total' => $totalProyectos,
                    'activos' => $proActivos,
                    'nuevos' => "+{$proNuevos} este mes",
                    'completados' => $proCompletados,
                    'mes' => "+{$proCompletadosMes} este mes",
                ],
                'tareas' => [
                    'total' => $tareasTotal,
                    'completadas' => $tareasCompletas,
                    'activas' => $tareasActivas,
                    'nuevas' => "+{$tareasNuevas} esta semana",
                    'pendientes' => $tareasPendientes,
                    'hoy' => "-{$tareasHoy} hoy"
                ],
                'rendimiento' => [
                    'promedio' => $promedioRendimiento
                ]
            ]
        ]);
    }

    public function usuariosPorMes()
    {
        $meses = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonth($i);
            $meses[] = $fecha->format('M');

            $total = Usuario::whereYear('fecha_registro', $fecha->year)
                ->whereMonth('fecha_registro', $fecha->month)->count();

            $data[] = $total;
        }

        return response()->json([
            'status' => 'success',
            'labels' => $meses,
            'data' => $data
        ]);
    }

    public function proyectosChart()
    {
        $completados = Proyecto::where('completado', 1)->count();
        $activos = Proyecto::where('completado', 0)->count();
        $pausa = Proyecto::where('completado', 2)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'completados' => $completados,
                'activos' => $activos,
                'pausa' => $pausa
            ]
        ]);
    }

    public function tareasColaborador($id){

        $hoy = Carbon::today()->toDateString();
        $tareasTotal = Tarea::where('id_asignado', $id)->count();
        $tareasCompletadas = Tarea::where('id_asignado', $id)->where('estado', 'Hecho')->count();
        $tareasPendientes = Tarea::where('id_asignado', $id)->where('estado', '=', 'Por hacer')->count();
        $tareasHoy = Tarea::where('id_asignado', $id)->whereDate('fecha_vencimiento', $hoy)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $tareasTotal,
                'completadas' => $tareasCompletadas,
                'pendientes' => $tareasPendientes,
                'hoy' => $tareasHoy
            ]
        ]);
    }
}
