<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Usuario;
use App\Models\Tarea;
use App\Models\Proyecto;
use App\Models\Rendimiento;
use Illuminate\Support\Facades\Validator;


class TareaController extends Controller
{
    public function index()
    {
        $tareas = Tarea::with('contenidos')->get();

        return response()->json([
            'status' => 'success',
            'data' => $tareas
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string',
            'descripcion' => 'nullable|string',
            'estado' => 'nullable|string|in:Por Hacer,Proceso,Revisión,Hecho',
            'prioridad' => 'nullable|string',
            'fecha_creacion' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:' . now(),
            'id_proyecto' => 'required|integer',
            'id_asignado' => 'required|integer',
        ], [
            'titulo.required' => 'El titulo es necesario',
            'estado.in' => 'El estado debe ser Por Hacer, Proceso, Revisión o Hecho',
            'fecha_vencimiento.date_format' => 'La fecha de vencimiento debe tener el formato YYYY-MM-DD HH:MM:SS',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a hoy',
            'id_proyecto.required' => 'Debe indicar el proyecto al que pertenece la tarea',
            'id_asignado.required' => 'Debe indicar a quién se asigna la tarea'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarea = Tarea::create([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado ?? 'Por Hacer',
            'prioridad' => $request->prioridad,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'id_proyecto' => $request->id_proyecto,
            'id_asignado' => $request->id_asignado
        ]);

        $rendimiento = Rendimiento::firstOrCreate(
            ['id_usu' => $tarea->id_asignado],
            ['tar_total' => 0]
        );

        $rendimiento->increment('tar_total');

        return response()->json([
            'status' => 'success',
            'success' => 'Tarea creada',
            "tarea" => $tarea
        ], 201);
    }



    public function tareasUsuario($id)
    {
        $tareas = Tarea::where('id_asignado', $id)->get();

        if ($tareas->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron tareas para este usuario',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tareas
        ], 200);
    }

    public function tareasCompletas()
    {
        $tareas = Tarea::where('estado', '=', 'Hecho')->count();

        return response()->json([
            'status' => 'success',
            'data' => $tareas
        ]);
    }

    public function tareasProyecto($id_pro)
    {
        $tareas = Tarea::where('id_proyecto', $id_pro)->get();

        if ($tareas->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron tareas en este proyecto',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tareas
        ], 200);
    }

    public function cambiarEstado(Request $request, $id)
    {
        $tarea = Tarea::find($id);

        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|in:Por Hacer,En proceso,Revision,Hecho',
        ], [
            'estado.required' => 'Debes indicar el nuevo estado.',
            'estado.in' => 'El estado debe ser Por Hacer, En proceso, Revisión o Hecho.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarea->estado = $request->estado;
        $tarea->save();

        $tarea->refresh();

        if ($request->estado === 'Hecho') {
            $rendimiento = Rendimiento::firstOrCreate(
                ['id_usu' => $tarea->id_asignado],
                ['tar_total' => 0, 'tar_completadas' => 0, 'tar_tarde' => 0]
            );

            $ahora = now();
            $fecha_vencimiento = \Carbon\Carbon::parse($tarea->fecha_vencimiento);

            if ($ahora->lessThanOrEqualTo($fecha_vencimiento)) {
                // Tarea completada a tiempo
                $rendimiento->increment('tar_completadas');
            } else {
                // Tarea completada tarde
                $rendimiento->increment('tar_tarde');
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Estado actualizado y rendimiento calculado',
            'data' => $tarea->load('contenidos')
        ]);
    }

    public function show($id)
    {
        $tarea = Tarea::with(['contenidos', 'asignado', 'proyecto'])->find($id);

        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tarea
        ]);
    }

    public function update(Request $request, $id)
    {
        $tarea = Tarea::find($id);

        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|required|string',
            'descripcion' => 'sometimes|nullable|string',
            'estado' => 'sometimes|required|string|in:Por Hacer,En proceso,En revisión,Hecho',
            'prioridad' => 'sometimes|nullable|string',
            'fecha_vencimiento' => 'sometimes|date_format:Y-m-d H:i:s|after_or_equal:' . now(),
            'id_proyecto' => 'sometimes|required|integer',
            'id_asignado' => 'sometimes|required|integer',
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'fecha_vencimiento.date_format' => 'La fecha de limite debe tener el formato YYYY-MM-DD HH:MM:SS.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de limite no puede ser anterior a hoy.',
            'id_proyecto.required' => 'Debe indicar el proyecto al que pertenece la tarea.',
            'id_asignado.required' => 'Debe indicar a quién se asigna la tarea.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarea->update($request->only([
            'titulo',
            'descripcion',
            'estado',
            'prioridad',
            'fecha_vencimiento',
            'id_proyecto',
            'id_asignado'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Tarea actualizada correctamente',
            'data' => $tarea->load(['contenidos', 'asignado', 'proyecto'])
        ]);
    }

    public function destroy($id)
    {
        $tarea = Tarea::find($id);

        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $tarea->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tarea eliminada correctamente'
        ]);
    }


    public function cambiarEstadoColaborador(Request $request, $id)
    {
        $tarea = Tarea::find($id);
        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|in:En proceso,En revisión',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarea->estado = $request->estado;
        $tarea->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Estado actualizado correctamente',
            'data' => $tarea
        ]);
    }

    public function cambiarEstadoLider(Request $request, $id)
    {
        $tarea = Tarea::find($id);
        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|in:En proceso,Hecho',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarea->estado = $request->estado;
        $tarea->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Revisión completada',
            'data' => $tarea
        ]);
    }
}
