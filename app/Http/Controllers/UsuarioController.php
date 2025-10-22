<?php

namespace App\Http\Controllers;

use App\Models\Rendimiento;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with(['perfil', 'rendimiento'])
        ->whereHas('perfil')
        ->whereHas('rendimiento')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $usuarios
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|string|max:200|unique:usuario,correo',
            'rol' => 'required|string|max:50',
            'passwordd' => 'required|string|min:8|confirmed',
        ], [
            'correo.required' => 'El correo es obligatorio.',
            'correo.unique' => 'Este correo ya está registrado.',
            'passwordd.required' => 'La contraseña es obligatoria.',
            'passwordd.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'passwordd.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Usuario::create([
            'correo' => $request->correo,
            'rol' => $request->rol,
            'passwordd' => Hash::make($request->passwordd)
        ]);

        Rendimiento::create(['id_usu' => $user->id]);

        return response()->json([
            'status' => 'success',
            'success' => 'Usuario creado',
            'user' => $user
        ], 201);
    }

    public function show($id)
    {
        $usuario = Usuario::with(['perfil', 'proyectos', 'rendimiento'])->findOrFail($id);

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => $usuario
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'correo' => 'required|string|max:200|unique:usuario,correo',
            'rol' => 'required|string|max:50',
            'passwordd' => 'required|string|min:8|confirmed',
        ], [
            'correo.required' => 'El correo es obligatorio.',
            'correo.unique' => 'Este correo ya está registrado.',
            'passwordd.required' => 'La contraseña es obligatoria.',
            'passwordd.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'passwordd.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $usuario->update($request->only([
            'correo',
            'rol',
            'password'
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $usuario->load(['perfil', 'proyectos', 'contenidos'])
        ]);
    }

    public function destroy($id)
    {
        $usuario = Usuario::find($id );

        if(!$usuario){
            return response()->json([
                'status' => 'error',
                'errors' => "Usuario inexistente"
            ], 404);
        }

        $usuario->delete();

        return response()->json([
            'status' => 'success',
            "message" => 'Usuario eliminado'
        ]);
    }

    public function totalUsuarios(){
        $total = Usuario::all()->count();

        return response()->json([
            'status' => 'success',
            'data' => $total
        ],200);
    }
}
