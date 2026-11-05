<?php

namespace App\Http\Requests;

/**
 * Mensajes de validación en español para el alta y edición de empleados.
 */
final class MensajesEmpleado
{
    public const MENSAJES = [
        'nombre.required' => 'Escribe el nombre del empleado.',
        'usuario.required' => 'Escribe un nombre de usuario.',
        'usuario.alpha_dash' => 'El usuario solo puede tener letras, números, guiones y guiones bajos.',
        'usuario.unique' => 'Ese nombre de usuario ya está en uso.',
        'pin.required' => 'Asigna un PIN.',
        'pin.regex' => 'El PIN debe tener de 4 a 6 dígitos.',
        'rol.required' => 'Elige un rol.',
        'rol.exists' => 'El rol no existe.',
        'email.required_if' => 'Un administrador necesita un correo de Google para iniciar sesión.',
        'email.email' => 'El correo no es válido.',
        'email.unique' => 'Ese correo ya pertenece a otro usuario.',
        'fecha_ingreso.date' => 'La fecha de ingreso no es válida.',
    ];
}
