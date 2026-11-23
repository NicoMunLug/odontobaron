<?php
require_once __DIR__ . '/includes/auth.php';

$titulo = SITIO_NOMBRE . ' · ' . SITIO_LEMA . ' en Bogotá';
$tratamientos = tratamientos_activos();
$sede = sede_principal();

$formacion = [
  ['2022', 'Aplicaciones quirúrgicas y terapéuticas de los láseres de diodo', 'Teórico-práctico'],
  ['2022', 'Diplomado en armonización orofacial', 'Soepicar'],
  ['2022', 'Restauraciones estéticas en dientes anteriores', 'Entrenamiento práctico OMI'],
  ['2022', 'Biomateriales en cirugía plástica periodontal y periimplantar', 'Straumann Group'],
  ['2021', 'Implant Dentistry', 'The University of Hong Kong'],
  ['2021', 'Odontología estética con sistema Invisalign Go', ''],
  ['2021', 'Restauración inmediata de dientes en la zona estética', 'Stephen J. Chu · Valencia'],
  ['2021', 'Farmacología para odontólogos', 'UNAM'],
  ['2020', 'Carillas en resina compuesta', 'Instituto Felipe Bezerra'],
  ['2020', 'Radioprotección', 'Universidad Nacional de Colombia'],
  ['2019', 'Diseño y fabricación de lentes de contacto dentales', 'Dentsply Sirona'],
  ['2019', 'Restauraciones en anteriores con resina Ceram.x SphereTEC', 'Dentsply Sirona'],
];

// Agrupada por año: cada franja de la sección reúne los cursos de un año.
$formacion_por_anio = [];
foreach ($formacion as $f) {
    $formacion_por_anio[$f[0]][] = $f;
}

require __DIR__ . '/includes/header.php';
?>

<section class="portada">
  <div class="envoltura">
    <div class="portada__lienzo">

      <div class="portada__texto">
        <h1>Odontología<br>pensada<br><em>para usted</em></h1>
        <p class="entrada">
          Planeamos cada tratamiento sobre imágenes y modelos, y se lo explicamos
          antes de empezar. Así usted sabe a dónde vamos y cuánto cuesta llegar.
        </p>
        <div class="portada__acciones">
          <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar mi cita</a>
          <a class="boton boton--circulo" href="<?= base_url('tratamientos.php') ?>"
             aria-label="Ver los tratamientos">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M7 17 17 7M8 7h9v9" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
        </div>
      </div>

      <div class="portada__media">
        <img class="portada__foto" src="<?= base_url('assets/img/doctora/retrato.png') ?>"
             alt="Dra. Laura Alejandra Barón Malagón en su consultorio"
             width="594" height="888" fetchpriority="high">

        <!-- Anotaciones sobre la foto, como las de una ficha clínica -->
        <figure class="nota nota--alta">
          <figcaption><b>Valoración sin costo</b>Revisión, diagnóstico y plan por escrito</figcaption>
        </figure>
        <figure class="nota nota--baja">
          <figcaption><b>Agenda en línea</b>Elige día y hora, con confirmación al instante</figcaption>
        </figure>
      </div>

      <div class="portada__aval">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"
                stroke="var(--lila)" stroke-width="1.6" stroke-linejoin="round"/>
          <circle cx="12" cy="10" r="2.4" stroke="var(--lila)" stroke-width="1.6"/>
        </svg>
        <p><b>Palermo, Teusaquillo</b>Atendiendo en Bogotá desde 2019</p>
      </div>

      <ul class="portada__fichas">
        <li>Diseño digital</li>
        <li>Plan por escrito</li>
        <li>Pago por abonos</li>
        <li>Control incluido</li>
      </ul>
    </div>

    <ul class="portada__pie">
      <li><b>Primero entender</b><span>la valoración no cuesta</span></li>
      <li><b>Después planear</b><span>sobre imágenes y modelos</span></li>
      <li><b>Y luego tratar</b><span>con el control hasta el final</span></li>
    </ul>
  </div>
</section>

<section class="seccion" id="tratamientos">
  <div class="envoltura">
    <div class="rotulo">
      <h2>Lo que hacemos</h2>
      <p class="plomo">
        Siete tratamientos, un mismo método: valoración, plan a la vista y
        seguimiento hasta el control final.
      </p>
    </div>

    <div class="tratamientos">
      <?php foreach ($tratamientos as $i => $t): ?>
        <a class="pieza <?= $i === 0 ? 'pieza--mayor' : '' ?>"
           href="<?= base_url('tratamiento.php?t=' . urlencode($t['slug'])) ?>">
          <div class="pieza__foto">
            <img src="<?= base_url(e($t['imagen'])) ?>" alt="<?= e($t['nombre']) ?>" loading="lazy">
          </div>
          <div class="pieza__cuerpo">
            <h3><?= e($t['nombre']) ?></h3>
            <p><?= e($t['resumen']) ?></p>
            <div class="pieza__pie">
              <span>Conocer el tratamiento</span>
              <span class="tenue menudo"><?= (int) $t['duracion_min'] ?> min</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion seccion--lavanda" id="sobre-mi">
  <div class="envoltura perfil">
    <div class="perfil__foto">
      <img src="<?= base_url('assets/img/doctora/sobre-mi.png') ?>"
           alt="Dra. Alejandra Barón" loading="lazy" width="500" height="625">
    </div>
    <div>
      <h2>Dra. Laura Alejandra Barón</h2>
      <p class="entrada">Odontóloga. Práctica centrada en estética y rehabilitación digital.</p>
      <p class="plomo">
        Me caracterizo por ser una persona tranquila, apasionada y responsable en todos los
        aspectos de la vida. Me apasiona la música y disfruto asistir a conciertos: allí no solo
        encuentro entretenimiento, también una vía para recargar energías.
      </p>
      <p class="plomo">
        Otro aspecto fundamental de mi vida es la búsqueda constante de nuevas experiencias.
        Siempre estoy dispuesta a aprovechar cualquier oportunidad para explorar, y cada escapada
        se convierte en una oportunidad para aprender y crecer. Esa misma curiosidad es la que me
        lleva a seguir formándome cada año en lo que hago.
      </p>
      <p class="firma">— Alejandra</p>
    </div>
  </div>
</section>

<section class="seccion" id="galeria">
  <div class="envoltura">
    <div class="rotulo">
      <h2>Trabajos</h2>
      <p class="plomo">
        Casos reales del consultorio. Si quiere ver más, en Instagram publico
        el paso a paso de varios tratamientos.
      </p>
    </div>
    <?php
    // Las fotos vienen en pares del mismo paciente, así que cada caso se
    // muestra completo: el antes y el después juntos, que es lo que deja
    // ver el trabajo.
    $casos = [
      ['antes' => 2, 'despues' => 3, 'titulo' => 'Ortodoncia',          'nota' => 'Brackets estéticos'],
      ['antes' => 4, 'despues' => 5, 'titulo' => 'Corona en zirconio',  'nota' => 'Pieza posterior'],
      ['antes' => 6, 'despues' => 7, 'titulo' => 'Rehabilitación oral', 'nota' => 'Dientes anteriores'],
    ];
    ?>
    <div class="galeria">
      <?php foreach ($casos as $c): ?>
        <figure class="caso">
          <div class="caso__par">
            <div class="caso__foto">
              <img src="<?= base_url("assets/img/galeria/trabajo-{$c['antes']}.jpg") ?>"
                   alt="<?= e($c['titulo']) ?>, antes del tratamiento" loading="lazy">
              <span>Antes</span>
            </div>
            <div class="caso__foto">
              <img src="<?= base_url("assets/img/galeria/trabajo-{$c['despues']}.jpg") ?>"
                   alt="<?= e($c['titulo']) ?>, después del tratamiento" loading="lazy">
              <span>Después</span>
            </div>
          </div>
          <figcaption>
            <b><?= e($c['titulo']) ?></b>
            <span><?= e($c['nota']) ?></span>
          </figcaption>
        </figure>
      <?php endforeach; ?>

      <a class="galeria__social" href="<?= e(instagram_url()) ?>" target="_blank" rel="noopener">
        <span>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="2.5" y="2.5" width="19" height="19" rx="5.5" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="12" cy="12" r="4.2" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="17.4" cy="6.6" r="1.2" fill="currentColor"/>
          </svg>
          Ver el paso a paso en Instagram
        </span>
        <svg class="galeria__flecha" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M7 17 17 7M8 7h9v9" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
    </div>
  </div>
</section>

<section class="seccion seccion--lavanda" id="formacion">
  <div class="envoltura">
    <div class="rotulo">
      <h2>Formación</h2>
      <p class="plomo">
        Cada año de actualización está detrás de los materiales y las técnicas
        que uso en el consultorio.
      </p>
    </div>
    <div class="trayecto">
      <?php foreach ($formacion_por_anio as $anio => $cursos): ?>
        <div class="hito">
          <div class="hito__anio"><?= e((string) $anio) ?></div>
          <ul class="hito__cursos">
            <?php foreach ($cursos as $c): ?>
              <li>
                <strong><?= e($c[1]) ?></strong>
                <?php if ($c[2]): ?><span><?= e($c[2]) ?></span><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($sede): ?>
<section class="seccion" id="consultorio">
  <div class="envoltura">
    <div class="sede">
      <div class="sede__texto">
        <h2>El consultorio</h2>
        <p class="entrada"><?= e($sede['direccion']) ?></p>
        <p class="plomo">
          Estamos en <?= e($sede['barrio']) ?>, en <?= e($sede['ciudad']) ?>, a dos
          cuadras del parque. El edificio tiene portería y ascensor.
        </p>

        <dl class="sede__datos">
          <div>
            <dt>Lunes a viernes</dt>
            <dd>8:00 a.&nbsp;m. – 6:00 p.&nbsp;m.</dd>
          </div>
          <div>
            <dt>Sábados</dt>
            <dd>9:00 a.&nbsp;m. – 1:00 p.&nbsp;m.</dd>
          </div>
          <div>
            <dt>Teléfono</dt>
            <dd><?= e(CONTACTO_TELEFONO) ?></dd>
          </div>
        </dl>

        <div class="fila" style="margin-top:1.75rem">
          <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar mi cita</a>
          <a class="boton boton--contorno" href="<?= base_url('sede.php') ?>">Cómo llegar</a>
        </div>
      </div>

      <?php
      $mapa = 'https://www.google.com/maps/search/?api=1&query='
            . urlencode($sede['direccion'] . ', ' . $sede['barrio'] . ', ' . $sede['ciudad']);
      ?>
      <a class="sede__mapa" href="<?= e($mapa) ?>" target="_blank" rel="noopener">
        <span class="sede__pin" aria-hidden="true">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
            <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"
                  stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6"/>
          </svg>
        </span>
        <span class="sede__mapa-texto">
          <b><?= e($sede['direccion']) ?></b>
          <span><?= e($sede['barrio']) ?>, <?= e($sede['ciudad']) ?></span>
        </span>
        <span class="sede__mapa-accion">
          Abrir en Google Maps
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 17 17 7M8 7h9v9" stroke="currentColor" stroke-width="1.8"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
