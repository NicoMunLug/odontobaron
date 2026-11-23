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
  <div class="envoltura portada__caja">
    <div class="portada__texto">
      <h1>Una sonrisa que <em>se diseña</em> antes de tocarla.</h1>
      <p class="entrada">
        Odontología digital y estética en Palermo, Bogotá. Planeamos cada tratamiento
        sobre imágenes y modelos, para que usted vea a dónde vamos antes de empezar.
      </p>
      <div class="portada__acciones">
        <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar mi cita</a>
        <a class="boton boton--contorno" href="<?= base_url('tratamientos.php') ?>">Ver tratamientos</a>
      </div>
    </div>

    <div class="portada__media">
      <div class="portada__retrato">
        <img src="<?= base_url('assets/img/doctora/retrato.png') ?>"
             alt="Dra. Laura Alejandra Barón Malagón en su consultorio" width="600" height="800">
      </div>
      <div class="sello">
        <strong>12</strong>
        formaciones<br>entre 2019 y 2022
      </div>
    </div>
  </div>

  <div class="envoltura" style="margin-top:clamp(2.5rem,2rem+2vw,4rem)">
    <div class="cinta">
      <div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M20 6 9 17l-5-5" stroke="var(--lila)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Diseño de sonrisa digital
      </div>
      <div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M20 6 9 17l-5-5" stroke="var(--lila)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Agenda en línea las 24 horas
      </div>
      <div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M20 6 9 17l-5-5" stroke="var(--lila)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Pago total o por abonos
      </div>
      <div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M20 6 9 17l-5-5" stroke="var(--lila)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?= e($sede['barrio'] ?? 'Palermo, Bogotá') ?>
      </div>
    </div>
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
        <span class="social__icono" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <rect x="2.5" y="2.5" width="19" height="19" rx="5.5" stroke="currentColor" stroke-width="1.7"/>
            <circle cx="12" cy="12" r="4.2" stroke="currentColor" stroke-width="1.7"/>
            <circle cx="17.4" cy="6.6" r="1.3" fill="currentColor"/>
          </svg>
        </span>
        <span class="social__texto">
          <b>@<?= e(CONTACTO_INSTAGRAM) ?></b>
          <span>El paso a paso de cada tratamiento, caso por caso</span>
        </span>
        <span class="social__boton">
          Seguir
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 17 17 7M8 7h9v9" stroke="currentColor" stroke-width="1.9"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
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

      <div class="sede__mapa">
        <?php if (!empty($sede['mapa_embed'])): ?>
          <iframe src="<?= e($sede['mapa_embed']) ?>" width="100%" height="100%"
                  style="border:0;display:block" allowfullscreen loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  title="Mapa del consultorio en <?= e($sede['barrio']) ?>"></iframe>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
