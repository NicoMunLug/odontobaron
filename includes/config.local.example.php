<?php
/**
 * OdontoBarón · Credenciales del servidor
 * -----------------------------------------------------------------
 * Copie este archivo como "config.local.php" en esta misma carpeta y
 * escriba allí los datos reales. config.local.php está en .gitignore,
 * así que nunca llega a GitHub.
 *
 * Los valores están en hPanel > Bases de datos > MySQL. El nombre de la
 * base y el del usuario empiezan por el número de su cuenta, algo como
 * u123456789_odontobaron.
 */

return [
    'driver'  => 'mysql',                  // mysql en el servidor, sqlite para pruebas locales
    'host'    => 'localhost',              // en Hostinger siempre localhost
    'nombre'  => 'u000000000_odontobaron', // nombre de la base
    'usuario' => 'u000000000_admin',       // usuario de la base
    'clave'   => '',                       // contraseña del usuario de la base

    'url'     => 'https://sudominio.com',  // sin barra final; sale en los correos

    // Deje esto en false mientras no tenga un correo del dominio configurado.
    'correo_activo' => false,

    // Solo para buscar un error: muestra el detalle en pantalla.
    // Vuelva a ponerlo en false apenas termine.
    'depurar' => false,
];
