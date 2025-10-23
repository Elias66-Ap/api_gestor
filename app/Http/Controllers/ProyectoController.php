<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProyectoController extends Controller
{
    public function index()
    {
        $proyectos = Proyecto::with('tareas')->get();
        
        return response()->json([
            'status' => 'success',
            'proyectos' => $proyectos
        ], 200);
    }

    // Crear un nuevo proyecto
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'fecha_inicio' => 'nullable|date',
                'fecha_entrega' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:' . now(),
                'progreso' => 'nullable|integer',
                'id_creador' => 'required|nullable|integer',
            ],
            [
                'nombre.required' => 'El campo nombre es obligatorio',
                'nombre.max' => 'El nombre es muy largo',
                'fecha_entrega.date_format' => 'Fecha con formato incorrecto',
                'fecha_entrega.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a hoy',
                'id_creador.required' => 'L'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }


        $proyecto = Proyecto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_entrega' => $request->fecha_entrega,
            'id_creador' => $request->id_creador
        ]);

        return response()->json([
            'status' => 'success',
            'success' => 'Proyecto creado',
            'data' => $proyecto
        ], 201);
    }

    public function completarProyecto($id)
    {
        $proyecto = Proyecto::find($id);

        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto inexistente'
            ], 404);
        }

        $proyecto->completado = 1;
        $proyecto->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Proyecto completado',
            'proyecto' => $proyecto
        ], 202);
    }

    // Mostrar un proyecto específico
    public function show($id)
    {
        $proyecto = Proyecto::with('tareas')->find($id);

        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'proyecto' => $proyecto
        ], 200);
    }

    // Actualizar un proyecto
    public function update(Request $request, $id)
    {
        $proyecto = Proyecto::find($id);
        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto no encontrado'
            ], 404);
        }

        $proyecto->update($request->all());
        return response()->json([
            'status' => 'success',
            'success' => 'Proyecto actualizado',
            'proyecto' => $proyecto
        ], 200);
    }

    // Eliminar un proyecto
    public function destroy($id)
    {
        $proyecto = Proyecto::find($id);
        if (!$proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyecto no encontrado'
            ], 404);
        }

        $proyecto->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Proyecto eliminado correctamente'
        ], 200);
    }

    public function totalProyectos(){
        $total = Proyecto::where('completado', '=', 0)->count();

        return response()->json([
            'status' => 'success',
            'data' => $total
        ], 200);
    }
}
