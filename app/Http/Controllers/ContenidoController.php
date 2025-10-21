<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Tarea;
use App\Models\TareaContenido;
use Illuminate\Support\Facades\Storage;

class ContenidoController extends Controller
{
    public function agregarContenido(Request $request, $id_tarea)
    {
        $tarea = Tarea::find($id_tarea);
        if (!$tarea) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'archivos.*' => 'nullable|file|max:10240', // máximo 10MB
            'links.*' => 'nullable|url',
            'textos.*' => 'nullable|string'
        ], [
            'archivos.*.file' => 'Cada archivo debe ser válido',
            'archivos.*.max' => 'Cada archivo no puede superar 10MB',
            'links.*.url' => 'Cada link debe ser una URL válida'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                $ruta = $archivo->store('tareas', 'public');
                TareaContenido::create([
                    'id_tarea' => $tarea->id,
                    'tipo' => 'archivo',
                    'valor' => $ruta
                ]);
            }
        }

        if ($request->has('links')) {
            foreach ($request->links as $link) {
                TareaContenido::create([
                    'id_tarea' => $tarea->id,
                    'tipo' => 'link',
                    'valor' => $link
                ]);
            }
        }

        if ($request->has('textos')) {
            foreach ($request->textos as $texto) {
                TareaContenido::create([
                    'id_tarea' => $tarea->id,
                    'tipo' => 'texto',
                    'valor' => $texto
                ]);
            }
        }

        $tarea->load('contenidos');

        return response()->json([
            'status' => 'success',
            'message' => 'Contenido agregado correctamente',
            'data' => $tarea
        ], 201);
    }

    public function updateContenido(Request $request, $id_contenido)
    {
        $contenido = TareaContenido::find($id_contenido);

        if (!$contenido) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contenido no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'valor' => 'required|string',
        ], [
            'valor.required' => 'El valor del contenido es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $contenido->valor = $request->valor;
        $contenido->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Contenido actualizado correctamente',
            'data' => $contenido
        ]);
    }

    public function destroyContenido($id_contenido)
    {
        $contenido = TareaContenido::find($id_contenido);

        if (!$contenido) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contenido no encontrado'
            ], 404);
        }

        // Si es archivo, eliminarlo del storage
        if ($contenido->tipo === 'archivo') {
            Storage::disk('public')->delete($contenido->valor);
        }

        $contenido->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Contenido eliminado correctamente'
        ], 200);
    }
}
