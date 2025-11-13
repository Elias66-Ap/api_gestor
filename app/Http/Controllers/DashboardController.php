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
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function dashboardUsuarios(){
        $usuario_total = Usuario::all()->count();
        $usuarios_sinperfil = Usuario::where('tiene_perfil', 0)->count();
        $usuarios_inactivos = Usuario::where('estado', 0)->count();

        return response()->json([
            'status' => 'success',
            'usuarios' => [
                'totales' => $usuario_total,
                'sin_perfil' => $usuarios_sinperfil,
                'inactivos' => $usuarios_inactivos
            ],
        ]);
    }

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

    public function tareasLiderDashboard($id)
    {
        $hoy = Carbon::today()->toDateString();

        $tareasTotal = Tarea::where('id_asignado', $id)->count();
        $tareasActivas = Tarea::where('id_asignado', $id)->where('estado', '!=', 'Hecho')->count();
        $tareasCompletadas = Tarea::where('id_asignado', $id)->where('estado', 'Hecho')->count();
        $tareasHoy = Tarea::where('id_asignado', $id)->whereDate('fecha_vencimiento', $hoy)->count();

        return response()->json([
            'status' => 'success',
            'tareas' => [
                'total' => $tareasTotal,
                'activas' => $tareasActivas,
                'completadas' => $tareasCompletadas,
                'hoy' => $tareasHoy
            ],
        ]);
    }

    public function resumen()
    {
        // ==== 1️⃣ Resumen mensual ====
        $resumenMensual = Tarea::selectRaw('
            MONTH(fecha_creacion) as mes,
            SUM(CASE WHEN estado = "Hecho" THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado != "Hecho" THEN 1 ELSE 0 END) as pendientes,
            COUNT(*) as total_tareas
        ')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $mensual = collect(range(1, 12))->map(function ($mes) use ($resumenMensual) {
            $t = $resumenMensual->firstWhere('mes', $mes);
            return [
                'mes' => $mes,
                'nombre_mes' => Carbon::create()->month($mes)->locale('es')->monthName,
                'total_tareas' => $t->total_tareas ?? 0,
                'completadas' => $t->completadas ?? 0,
                'pendientes' => $t->pendientes ?? 0,
            ];
        });

        // ==== 2️⃣ Resumen semanal dentro de cada mes ====
        // Calcular la semana relativa dentro del mes
        $tareasSemanal = Tarea::selectRaw('
            MONTH(fecha_creacion) as mes,
            FLOOR((DAY(fecha_creacion) - 1) / 7) + 1 as semana_mes,
            SUM(CASE WHEN estado = "Hecho" THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado != "Hecho" THEN 1 ELSE 0 END) as pendientes,
            COUNT(*) as total_tareas
        ')
            ->groupBy('mes', 'semana_mes')
            ->get();

        // Crear estructura mensual con 4 semanas
        $mensualConSemanas = collect(range(1, 12))->map(function ($mes) use ($tareasSemanal) {
            return [
                'mes' => $mes,
                'nombre_mes' => \Carbon\Carbon::create()->month($mes)->locale('es')->monthName,
                'semanas' => collect(range(1, 4))->map(function ($semana) use ($mes, $tareasSemanal) {
                    $t = $tareasSemanal->first(function ($item) use ($mes, $semana) {
                        return $item->mes == $mes && $item->semana_mes == $semana;
                    });

                    return [
                        'semana' => $semana,
                        'total_tareas' => $t->total_tareas ?? 0,
                        'completadas' => $t->completadas ?? 0,
                        'pendientes' => $t->pendientes ?? 0,
                    ];
                }),
            ];
        });

        // ==== 3️⃣ Respuesta JSON ====
        return response()->json([
            'mensual' => $mensual,
            'mensual_con_semanas' => $mensualConSemanas,
        ]);
    }

    public function getTareasPorMes($mes)
    {
        // Validar mes entre 1 y 12
        if ($mes < 1 || $mes > 12) {
            return response()->json(['error' => 'Mes inválido'], 400);
        }

        // Agrupar por semanas dentro del mes (1–4)
        $tareas = Tarea::selectRaw('
            FLOOR((DAY(fecha_creacion) - 1) / 7) + 1 as semana_mes,
            SUM(CASE WHEN estado = "Hecho" THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado != "Hecho" THEN 1 ELSE 0 END) as pendientes
        ')
            ->whereMonth('fecha_creacion', $mes)
            ->groupBy('semana_mes')
            ->orderBy('semana_mes')
            ->get();

        // Crear arreglo con 4 semanas (rellenar con ceros si faltan)
        $resultado = collect(range(1, 4))->map(function ($semana) use ($tareas) {
            $fila = $tareas->firstWhere('semana_mes', $semana);
            return [
                'semana' => "Semana {$semana}",
                'completadas' => $fila->completadas ?? 0,
                'pendientes' => $fila->pendientes ?? 0
            ];
        });

        return response()->json($resultado);
    }
    /**
     * Retorna el conteo de proyectos por estado para un mes específico
     * @param int $mes Número del mes 1-12
     */
    public function proyectosPorMes($mes)
    {
        $year = Carbon::now()->year;

        // Traemos proyectos que iniciaron en el mes indicado
        $proyectos = Proyecto::whereYear('fecha_inicio', $year)
            ->whereMonth('fecha_inicio', $mes)
            ->get();

        $activos = $proyectos->where('completado', 0)->count();
        $terminados = $proyectos->where('completado', 1)->count();
        // En pausa: proyectos no completados y sin progreso
        $pausa = $proyectos->where('completado', 2)->count();

        return response()->json([
            'activos' => $activos,
            'terminados' => $terminados,
            'pausa' => $pausa,
        ]);
    }
}
