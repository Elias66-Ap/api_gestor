<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Usuario;
use App\Models\MiembroProyecto;
use App\Models\Tarea;
use App\Models\Proyecto;
use App\Models\Rendimiento;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{

    public function dashboard()
    {

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

    public function tareasColaborador($id)
    {

        $hoy = Carbon::today()->toDateString();
        $tareasTotal = Tarea::where('id_asignado', $id)->count();
        $tareasCompletadas = Tarea::where('id_asignado', $id)->where('estado', 'Hecho')->count();
        $tareasPendientes = Tarea::where('id_asignado', $id)->where('estado', '=', 'Por hacer')->count();
        $tareasHoy = Tarea::where('id_asignado', $id)->where('fecha_vencimiento', '=', $hoy)->count();

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

    public function dashboardLider($id)
    {

        $hoy = Carbon::today()->toDateString();
        $ayer = Carbon::yesterday();
        $semana = Carbon::now()->startOfWeek();
        $mes = Carbon::now()->startOfMonth();

        //Tareas Pendientes: 
        $proyectos = MiembroProyecto::where('id_usuario', $id)->pluck('id_proyecto');
        $tareasPendientes = Tarea::whereIn('id_proyecto', $proyectos)->where('estado', '=', 'Por hacer')->count();
        $tareasPendientesHoy = Tarea::whereIn('id_proyecto', $proyectos)
            ->where('estado', '=', 'Hecho')
            ->whereDate('fecha_completado', '=', $hoy)
            ->count();
        //----

        $proyectoCreados = Proyecto::where('id_creador', '=', $id)->count();
        $proyectosCreadosMes = Proyecto::where('id_creador', $id)->whereDate('fecha_inicio', '>=', $mes)->count();

        $proyectosActivos = Proyecto::whereHas('miembros', function ($query) use ($id) {
            $query->where('id_usuario', $id);
        })
            ->where('completado', 0)
            ->count();
        $proyectosActivosMes = Proyecto::whereHas('miembros', function ($query) use ($id) {
            $query->where('id_usuario', $id);
        })
            ->where('completado', 0)
            ->whereDate('fecha_inicio', '>=', $mes)
            ->count();

        $proyectosCompletadosMes = Proyecto::whereHas('miembros', function ($query) use ($id) {
            $query->where('id_usuario', $id);
        })
            ->where('completado', 1)
            ->whereDate('fecha_completado', '>=', $mes)
            ->count();
        $proyectosCompletados = Proyecto::whereHas('miembros', function ($query) use ($id) {
            $query->where('id_usuario', $id);
        })
            ->where('completado', 1)
            ->count();

        $tareasAsignadas = Tarea::where('id_asignado', $id)->count();
        $tareasAsignadasSemana = Tarea::where('id_asignado', $id)
            ->where('fecha_creacion', '>=', $semana)
            ->count();
        $tareasCreadas = Tarea::where('id_creador', $id)->count();
        $creadasSemana = Tarea::where('id_creador', $id)->where('fecha_creacion', '>=', $semana)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'proyectos' => [
                    'creados' => $proyectoCreados,
                    'creadosMes' => "+{$proyectosCreadosMes} este mes",
                    'activos' => $proyectosActivos,
                    'activosMes' => "+{$proyectosActivosMes} este mes",
                    'completados' => $proyectosCompletados,
                    'completadosMes' => "+{$proyectosCompletadosMes} este mes"
                ],
                'tareas' => [
                    'creadas' => $tareasCreadas,
                    'creadasSemana' => "+{$creadasSemana} esta semana",
                    'pendientes' => $tareasPendientes,
                    'pendientesHoy' => "-{$tareasPendientesHoy} hoy",
                    'asignadas' => $tareasAsignadas,
                    'asignadasSemana' => "+{$tareasAsignadasSemana} esta semana"
                ],
            ],
        ]);
    }

    public function proyectosLiderDashboard($id)
    {

        $proyectosActivos = Proyecto::whereHas('miembros', function ($query) use ($id) {
            $query->where('id_usuario', $id);
        })
            ->where('completado', 0)->count();

        $proyectosCompletados = Proyecto::whereHas('miembros', function ($query) use ($id) {
            $query->where('id_usuario', $id);
        })
            ->where('completado', 1)->count();

        $proyectosPausados = Proyecto::whereHas('miembros', function ($query) use ($id) {
            $query->where('id_usuario', $id);
        })
            ->where('completado', 2)->count();


        return response()->json([
            'status' => 'success',
            'proyectos' => [
                'activos' => $proyectosActivos,
                'completados' => $proyectosCompletados,
                'pausados' => $proyectosPausados
            ],
        ]);
    }

    public function tareasLiderDashboard($id) {
        $hoy = Carbon::today()->toDateString();

        $tareasTotal = Tarea::where('id_asignado', $id)->count();
        $tareasActivas = Tarea::where('id_asignado', $id)->where('estado', '!=', 'Hecho')->count();
        $tareasCompletadas = Tarea::where('id_asignado', $id)->where('estado', 'Hecho')->count();
        $tareasHoy = Tarea::where('id_asignado', $id)->whereDate('fecha_vencimiento', $hoy)->count();

        return response()->json([
            'status'=> 'success',
            'tareas' => [
                'total' => $tareasTotal,
                'activas' => $tareasActivas,
                'completadas' => $tareasCompletadas,
                'hoy' => $tareasHoy
            ],
        ]);
    }
}
