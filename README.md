<div align="center">

<img src="assets/img/marca/logo-negro.png" alt="Alejandra Barón · Odontología Integral, Digital y Estética" width="420">

# OdontoBarón

**Sitio web y sistema de agendamiento del Consultorio Odontológico de la Dra. Laura Alejandra Barón Malagón**

Palermo · Teusaquillo · Bogotá, D. C.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-8574A8)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-8574A8)
![Licencia](https://img.shields.io/badge/uso-privado-A892D7)

</div>

---

## Qué es

Una aplicación web que reemplaza el registro manual de citas en hoja de cálculo.
El paciente agenda, reprograma, cancela y consulta su historia clínica en línea;
el consultorio administra la agenda, los pacientes y los pagos desde un panel propio.

Sustituye por completo al sitio de 2022: mismo contenido y la misma identidad de
marca, pero con base de datos relacional, cuentas de usuario, panel de control y
un diseño pensado para móvil.

## Lo que hace

**Para el paciente**

- Crear cuenta con validación de documento y correo
- Recuperar la contraseña con un enlace que dura una hora y sirve una vez
- Ver los horarios realmente libres, actualizados al momento
- Agendar, reprogramar y cancelar hasta 24 horas antes
- Consultar su historia clínica en modo de solo lectura
- Ver el saldo pendiente de sus tratamientos

**Para el consultorio**

- Panel con citas del mes, citas del día, tasa de ausencias y pagos
- Gráficos de citas por mes y de tratamientos más pedidos
- Agenda del día y cambio de estado de cada cita
- Listado de pacientes con su historial y saldo
- Bandeja de mensajes del formulario de contacto
- Edición del catálogo de tratamientos sin tocar código
- Gestión de cuentas: crear usuarios de cualquier rol, restablecer
  contraseñas y desactivar cuentas (solo administración)

## Tecnologías

| Capa | Qué se usa |
|---|---|
| Presentación | HTML5, CSS3 (variables nativas, grid y flexbox), JavaScript sin dependencias |
| Lógica | PHP 8.1+ con PDO y consultas preparadas |
| Datos | MySQL 5.7+ / MariaDB 10.4+, InnoDB, `utf8mb4` |
| Tipografía | Rubik (Google Fonts) |
| Gráficos | SVG generado en el servidor — sin librerías externas |

Sin framework ni gestor de paquetes: se sube por FTP y funciona.

## Estructura

```
odontobaron/
├── index.php              Inicio
├── tratamientos.php       Listado
├── tratamiento.php        Detalle (?t=slug)
├── sede.php               Consultorio y mapa
├── pagos.php              Medios de pago
├── contacto.php           Formulario
├── agendar.php            Selección de día y hora
├── registro.php           Crear cuenta
├── ingresar.php           Inicio de sesión
├── confirmar.php          Validación del correo
├── salir.php
├── panel/                 Área del paciente
│   ├── index.php          Mis citas
│   ├── reprogramar.php    Cambio de fecha
│   └── historia.php       Historia clínica
├── admin/                 Panel del consultorio
│   ├── index.php          Resumen con indicadores
│   ├── citas.php          Agenda y estados
│   ├── pacientes.php      Listado con saldo
│   ├── mensajes.php       Bandeja de contacto
│   └── tratamientos.php   Catálogo
├── includes/
│   ├── config.php         ← credenciales y reglas del consultorio
│   ├── db.php             Conexión PDO
│   ├── auth.php           Sesiones y roles
│   ├── citas.php          Disponibilidad y reservas
│   ├── functions.php      Utilidades
│   ├── header.php  footer.php
│   └── admin-layout.php  admin-pie.php
├── assets/
│   ├── css/app.css        Sistema visual del sitio
│   ├── css/admin.css      Panel interno
│   ├── js/app.js  js/admin.js
│   └── img/
└── sql/
    ├── odontobaron.sql       ← importar en phpMyAdmin
    └── preparar-sqlite.php   Base de pruebas local
```

## Instalación en Hostinger

**1. Crear la base de datos**

En hPanel → *Bases de datos* → *MySQL*. Cree una base y un usuario, y anote el
nombre, el usuario y la contraseña. Hostinger les antepone un prefijo
(`u123456789_odontobaron`).

**2. Importar el esquema**

Entre a phpMyAdmin desde el mismo panel, seleccione la base, abra la pestaña
*Importar* y suba `sql/odontobaron.sql`. Crea las nueve tablas con sus relaciones
y carga el catálogo de tratamientos, la sede, los horarios de atención y datos de
ejemplo para probar el panel.

**3. Configurar las credenciales**

Las contraseñas **no van en `config.php`**, porque ese archivo está en el
repositorio y cualquiera que vea el código las leería. Van en
`includes/config.local.php`, que `.gitignore` excluye.

La forma rápida es abrir `https://sudominio.com/instalar.php`: pide el nombre de
la base, el usuario y la contraseña, prueba la conexión y, solo si funciona,
escribe el archivo. El asistente se apaga solo en cuanto el archivo existe, y
conviene borrarlo del servidor al terminar.

A mano es igual de válido: copie `includes/config.local.example.php` con el
nombre `config.local.php` y escriba allí los datos de hPanel:

```php
return [
    'driver'  => 'mysql',
    'host'    => 'localhost',
    'nombre'  => 'u123456789_odontobaron',
    'usuario' => 'u123456789_admin',
    'clave'   => 'la-contraseña-que-creó',
    'url'     => 'https://sudominio.com',
];
```

Si ese archivo no existe, el sitio usa los valores por defecto de `config.php` y
no puede conectarse. En ese caso muestra la pantalla que explica qué falta y
enlaza al asistente, en vez de un error sin explicación.

> **Al actualizar el sitio**, no sobrescriba `includes/config.local.php`: ahí
> están sus credenciales. `config.php` sí se puede reemplazar sin problema,
> porque ya no las contiene.

**4. Subir los archivos**

Por el Administrador de archivos o por FTP, suba todo el contenido a
`public_html/`. No suba la carpeta `storage/` ni `sql/preparar-sqlite.php`.

**5. Activar el correo**

Cree una cuenta de correo del dominio en hPanel y póngala en `MAIL_REMITENTE`.
Luego ponga `'correo_activo' => true` en `config.local.php` para que salgan las
confirmaciones, los recordatorios y los enlaces de recuperación de contraseña.

### Si ya tenía una versión anterior instalada

Ejecute `sql/migracion-02-restablecer-clave.sql` desde la pestaña *SQL* de
phpMyAdmin: agrega la tabla `restablecimientos`, que la recuperación de
contraseña necesita. En una instalación nueva no hace falta, porque esa tabla
ya viene en `odontobaron.sql`.

### Primer ingreso

| Rol | Correo | Contraseña |
|---|---|---|
| Administración | `admin@odontobaron.com` | `OdontoBaron2026*` |
| Odontóloga | `draalejandrabaron@odontobaron.com` | `OdontoBaron2026*` |

> **Cambie ambas contraseñas apenas entre.** Los datos de ejemplo
> (citas, pagos, mensajes) se pueden borrar desde phpMyAdmin.

## Probar en local

Sin servidor MySQL a mano, el proyecto corre con SQLite:

```bash
php sql/preparar-sqlite.php          # crea la base de pruebas con los mismos datos
OB_DRIVER=sqlite OB_DEBUG=1 php -S localhost:8080
```

Y en `http://localhost:8080` queda el sitio completo, panel incluido.

Con MySQL local basta con configurar `includes/config.php` y levantar el servidor
sin la variable `OB_DRIVER`.

## Base de datos

Nueve tablas en tercera forma normal, derivadas del diagrama de clases del proyecto:

| Tabla | Para qué |
|---|---|
| `usuarios` | Pacientes, odontólogos y administradores con su rol |
| `sedes` | Consultorios, dirección y mapa |
| `tratamientos` | Catálogo con duración y descripción |
| `horarios` | Franjas de atención por odontólogo y día |
| `bloqueos` | Vacaciones, festivos y cierres |
| `citas` | Reservas con su estado y código |
| `historia_clinica` | Registro por sesión atendida |
| `pagos` | Pagos totales y abonos |
| `mensajes` | Formulario de contacto |

### Cómo se evita la doble reserva

La tabla `citas` lleva `UNIQUE (odontologo_id, fecha, hora)`. Antes de insertar,
la aplicación vuelve a consultar la disponibilidad; si dos solicitudes llegan a la
vez, la segunda choca contra la restricción y recibe un mensaje pidiendo otro
horario. **Una sola reserva queda confirmada.**

## Reglas del consultorio

Están todas en `includes/config.php` y se cambian sin tocar el resto del código:

```php
define('CITA_DURACION_MIN', 45);        // minutos por bloque de agenda
define('CITA_HORAS_ANTICIPACION', 24);  // plazo para cancelar o reprogramar
define('CITA_DIAS_MAX', 60);            // hasta cuándo se puede agendar
```

Los horarios de atención viven en la tabla `horarios`, no en el código.

## Seguridad

- Contraseñas con `password_hash()` (bcrypt); nunca en texto plano
- Consultas preparadas con PDO en todas las operaciones
- Token CSRF en cada formulario que escribe
- Control de acceso por rol: el paciente solo ve lo suyo, la historia clínica
  solo la edita el odontólogo
- Escape de salida con `htmlspecialchars()` en todo lo que viene del usuario
- Cookies de sesión `HttpOnly` y `SameSite=Lax`
- Credenciales fuera del repositorio, en `config.local.php`
- Enlaces de recuperación guardados como huella SHA-256, con vigencia de una
  hora y un solo uso; pedir uno nuevo anula el anterior
- La página de recuperación responde igual exista o no el correo, para que no
  sirva de directorio de pacientes

### Por qué no se pueden ver las contraseñas

La tabla `usuarios` guarda `password_hash`, no la contraseña. bcrypt es de un
solo sentido: del texto se llega a la huella, pero de la huella no se vuelve al
texto. Al entrar, el sistema vuelve a calcular la huella de lo que se escribió y
la compara (`password_verify`). Por eso ni la administración ni el equipo de
desarrollo pueden leer la contraseña de un paciente, y si la base se filtrara
tampoco podría hacerlo quien la robe. Cuando alguien olvida la suya, no se
recupera: se reemplaza, con el enlace por correo o con una contraseña
provisional desde el panel.

El tratamiento de datos personales sigue la Ley 1581 de 2012, y la historia
clínica, la Resolución 1995 de 1999.

## Identidad visual

| | |
|---|---|
| Lila principal | `#8574A8` |
| Lila claro | `#A892D7` |
| Tinta | `#2A2235` |
| Lavanda | `#F2EEF8` |
| Hueso | `#FBFAFC` |
| Tipografía | Rubik 300 / 400 / 500 |

El motivo de la marca es el **arco**: aparece en el retrato de la portada, en la
galería y en los remates de sección. El lila se usa como tinta y en superficies
suaves, nunca como relleno plano de bloques enteros.

## Equipo

Proyecto de la asignatura **Ingeniería de Software II**
Escuela Tecnológica Instituto Técnico Central · Bogotá, D. C.

- Juan Sebastián Donato Carrillo
- Daniel Eduardo Sánchez Aldana
- Nicolás Fernando Muñoz Lugo

Docente: Ing. María Luz Ángela Loaiza Álvarez

## Licencia

Software desarrollado para el Consultorio Odontológico de la Dra. Laura Alejandra
Barón Malagón. El contenido, las fotografías y la marca son de la profesional;
su uso fuera de este proyecto requiere su autorización.
