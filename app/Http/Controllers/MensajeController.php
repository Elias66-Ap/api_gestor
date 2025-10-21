<?php

namespace App\Http\Controllers;

use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MensajeController extends Controller
{
    public function index()
    {
        return response()->json(Mensaje::all());
    }

    public function mensajesRemitente($id){
        $mensaje = Mensaje::where('id_remitente', $id)->get();

        if($mensaje->isEmpty()){
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron mensaje para este usuario',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'mensajes' => $mensaje
        ], 200);
    }
    public function mensajesUsuarios($idr, $idd){
        $mensaje = Mensaje::where('id_remitente',  'id_destinatario', $idr,$idd)->get();

        if($mensaje->isEmpty()){
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron mensaje para este usuario',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'mensajes' => $mensaje
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'contenido' => 'required|string',
                'id_remitente' => 'required|integer',
                'id_destinatario' => 'required|integer'
            ],
            [
                'contenido.required' => "Escribe algo",
                'id_destinatario' => 'Mensaje sin destinatario'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $mensaje = Mensaje::create($request->all());

        return response()->json([
            'status' => 'success',
            'success' => 'Mensaje enviado',
            'mensaje' => $mensaje
        ], 201);
    }

    public function show($id)
    {
        $mensaje = Mensaje::find($id);
        if (!$mensaje) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'success' => $mensaje
        ]);
    }

    public function update(Request $request, $id)
    {
        $mensaje = Mensaje::find($id);
        if (!$mensaje) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        $mensaje->update($request->all());

        return response()->json([
            'status' => 'success',
            'success' => 'Mensaje actualizado',
            'mensaje' => $mensaje
        ]);
    }

    public function destroy($id)
    {
        $mensaje = Mensaje::find($id);

        if (!$mensaje) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mensaje no encontrado'
            ], 404);
        }

        $mensaje->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Mensaje eliminado correctamente']);
    }
}
