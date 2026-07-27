<?php

/**
 * LoginModel — Queries SQL del login.
 *
 * No contiene lógica HTTP ni presentación.
 * Una sola responsabilidad: buscar un usuario por correo.
 */
class LoginModel
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Busca un usuario activo por correo.
     * Devuelve null si no existe.
     *
     * @return array{id:int, nombre:string, correo:string, password:string, rol:string}|null
     */
    public function getUsuarioPorCorreo(string $correo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ? LIMIT 1'
        );
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }
}
