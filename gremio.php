<?php

declare(strict_types=1);
session_start();

const RANGOS = ['E', 'D', 'C', 'B', 'A', 'S'];

$misiones = [
    ['id' => 1, 'titulo' => 'Recolección de hierbas', 'rango' => 'E', 'recompensa' => 120, 'dias' => 1, 'descripcion' => 'Recoge hierbas medicinales en el bosque cercano.'],
    ['id' => 2, 'titulo' => 'Limpieza del sótano', 'rango' => 'E', 'recompensa' => 180, 'dias' => 1, 'descripcion' => 'Ahuyenta a las ratas gigantes de una bodega.'],
    ['id' => 3, 'titulo' => 'Escolta al mercader', 'rango' => 'D', 'recompensa' => 350, 'dias' => 2, 'descripcion' => 'Acompaña un carro hasta la villa vecina.'],
    ['id' => 4, 'titulo' => 'Rastros en el molino', 'rango' => 'D', 'recompensa' => 420, 'dias' => 2, 'descripcion' => 'Investiga las huellas que rodean el viejo molino.'],
    ['id' => 5, 'titulo' => 'Recuperar el estandarte', 'rango' => 'C', 'recompensa' => 480, 'dias' => 2, 'descripcion' => 'Recupera un estandarte perdido en las colinas.'],
    ['id' => 6, 'titulo' => 'Bandidos del desfiladero', 'rango' => 'B', 'recompensa' => 1650, 'dias' => 3, 'descripcion' => 'Despeja el desfiladero de una banda de saqueadores.'],
    ['id' => 7, 'titulo' => 'El dragón de Obsidiana', 'rango' => 'B', 'recompensa' => 4000, 'dias' => 5, 'descripcion' => 'Investiga la guarida del dragón en las montañas negras.'],
    ['id' => 8, 'titulo' => 'La torre sumergida', 'rango' => 'C', 'recompensa' => 900, 'dias' => 4, 'descripcion' => 'Explora unas ruinas que emergen durante la marea baja.'],
    ['id' => 9, 'titulo' => 'Duelo en la frontera', 'rango' => 'A', 'recompensa' => 7200, 'dias' => 6, 'descripcion' => 'Detén a un campeón que amenaza los pueblos fronterizos.'],
    ['id' => 10, 'titulo' => 'La corona del rey caído', 'rango' => 'S', 'recompensa' => 15000, 'dias' => 10, 'descripcion' => 'Recupera la corona perdida en una antigua ciudad.'],
];

$_SESSION['zurron'] ??= [];

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function etiquetaRango(string $rango): string
{
    return match ($rango) {
        'E' => 'Novato',
        'D' => 'Aprendiz',
        'C' => 'Veterano',
        'B' => 'Experto',
        'A' => 'Élite',
        'S' => 'Leyenda',
        default => 'Desconocido',
    };
}

function claseRango(string $rango): string
{
    return match ($rango) {
        'E', 'D' => 'facil',
        'C', 'B' => 'media',
        'A', 'S' => 'dificil',
        default => 'facil',
    };
}

function formatearOro(int $oro): string
{
    return number_format($oro, 0, ',', '.') . ' mo';
}

function leerRangoFiltro(array $get): ?string
{
    $rango = $get['rango'] ?? null;
    return is_string($rango) && in_array($rango, RANGOS, true) ? $rango : null;
}

function leerOroMinimo(array $get): int
{
    $valor = $get['oro'] ?? null;
    if (!is_string($valor) && !is_int($valor)) {
        return 0;
    }
    $oro = filter_var($valor, FILTER_VALIDATE_INT);
    return $oro !== false && $oro >= 0 && $oro <= 10000 ? $oro : 0;
}

function filtrarMisiones(array $misiones, ?string $rango, int $oroMinimo): array
{
    $resultado = [];
    foreach ($misiones as $mision) {
        if (($rango === null || $mision['rango'] === $rango) && $mision['recompensa'] >= $oroMinimo) {
            $resultado[] = $mision;
        }
    }
    return $resultado;
}

function buscarMision(array $misiones, int $id): ?array
{
    foreach ($misiones as $mision) {
        if ($mision['id'] === $id) {
            return $mision;
        }
    }
    return null;
}

function totalRecompensa(array $misiones, array $idsZurron): int
{
    $total = 0;
    foreach ($idsZurron as $id) {
        $mision = buscarMision($misiones, (int) $id);
        if ($mision !== null) {
            $total += $mision['recompensa'];
        }
    }
    return $total;
}

function redirigir(): never
{
    $query = $_GET === [] ? '' : '?' . http_build_query($_GET);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . $query, true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id < 1) {
        $_SESSION['aviso'] = 'La misión indicada no es válida.';
    } elseif ($accion === 'aceptar') {
        if (buscarMision($misiones, $id) === null) {
            $_SESSION['aviso'] = 'La misión no existe.';
        } elseif (in_array($id, $_SESSION['zurron'], true)) {
            $_SESSION['aviso'] = 'Esa misión ya está en tu zurrón.';
        } else {
            $_SESSION['zurron'][] = $id;
            $_SESSION['aviso'] = 'Misión añadida al zurrón.';
        }
    } elseif ($accion === 'abandonar') {
        if (!in_array($id, $_SESSION['zurron'], true)) {
            $_SESSION['aviso'] = 'Esa misión no está en tu zurrón.';
        } else {
            $_SESSION['zurron'] = array_values(array_filter(
                $_SESSION['zurron'],
                static fn (int $misionId): bool => $misionId !== $id
            ));
            $_SESSION['aviso'] = 'Misión abandonada.';
        }
    }
    redirigir();
}

$rangoFiltro = leerRangoFiltro($_GET);
$oroMinimo = leerOroMinimo($_GET);
$misionesVisibles = filtrarMisiones($misiones, $rangoFiltro, $oroMinimo);
$misionesZurron = [];
foreach ($_SESSION['zurron'] as $idZurron) {
    $mision = buscarMision($misiones, (int) $idZurron);
    if ($mision !== null) {
        $misionesZurron[] = $mision;
    }
}
$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gremio de Aventureros · Tablón</title>
    <style>
        :root{font-family:system-ui,sans-serif;color:#29251f;background:#f3eee4}body{max-width:1100px;margin:0 auto;padding:1.5rem}header,.panel{background:#fffaf1;padding:1.2rem;border:1px solid #d5c8ae;border-radius:12px;margin-bottom:1rem}h1,h2{margin-top:0}.layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:1rem}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.8rem}.card{background:#fffaf1;border:2px solid #999;border-radius:10px;padding:1rem}.facil{border-color:#39844b}.media{border-color:#d88b23}.dificil{border-color:#c5413b}button{background:#354d35;color:white;border:0;border-radius:6px;padding:.55rem .8rem;cursor:pointer}input,select{padding:.45rem;margin:.2rem}.notice{background:#e7f2dc;padding:.8rem;border-radius:8px}table{width:100%;border-collapse:collapse}th,td{text-align:left;border-bottom:1px solid #ddd;padding:.5rem}.gold{white-space:nowrap}@media(max-width:760px){.layout{grid-template-columns:1fr}}
    </style>
</head>
<body>
<header>
    <h1>El Gremio de Aventureros · Villa Roble</h1>
    <p>Tablón de encargos y zurrón del aventurero</p>
    <?php if ($aviso !== null): ?><p class="notice" role="status"><?= e($aviso) ?></p><?php endif; ?>
</header>
<div class="layout">
    <main class="panel">
        <h2>Tablón de misiones</h2>
        <form method="get" action="">
            <label for="rango">Rango</label>
            <select name="rango" id="rango">
                <option value="">Todos</option>
                <?php foreach (RANGOS as $rango): ?>
                    <option value="<?= e($rango) ?>" <?= $rangoFiltro === $rango ? 'selected' : '' ?>><?= e($rango . ' · ' . etiquetaRango($rango)) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="oro">Oro mínimo</label>
            <input type="number" name="oro" id="oro" min="0" max="10000" value="<?= e($oroMinimo) ?>">
            <button type="submit">Filtrar</button>
        </form>
        <p><?= count($misionesVisibles) ?> misiones encontradas</p>
        <div class="cards">
            <?php foreach ($misionesVisibles as $mision): ?>
                <article class="card <?= e(claseRango($mision['rango'])) ?>">
                    <h3><?= e($mision['titulo']) ?></h3>
                    <p><strong>Rango:</strong> <?= e($mision['rango']) ?> · <?= e(etiquetaRango($mision['rango'])) ?></p>
                    <p><strong>Recompensa:</strong> <span class="gold"><?= e(formatearOro($mision['recompensa'])) ?></span></p>
                    <p><strong>Duración:</strong> <?= e($mision['dias']) ?> <?= $mision['dias'] === 1 ? 'día' : 'días' ?></p>
                    <p><?= e($mision['descripcion']) ?></p>
                    <form method="post" action="">
                        <input type="hidden" name="id" value="<?= e($mision['id']) ?>">
                        <input type="hidden" name="accion" value="aceptar">
                        <button type="submit">Aceptar misión</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
    <aside class="panel">
        <h2>Tu zurrón</h2>
        <?php if ($misionesZurron === []): ?>
            <p>Aún no has aceptado ninguna misión.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Misión</th><th>Recompensa</th><th>Acción</th></tr></thead>
                <tbody>
                <?php foreach ($misionesZurron as $mision): ?>
                    <tr>
                        <td><?= e($mision['titulo']) ?></td>
                        <td class="gold"><?= e(formatearOro($mision['recompensa'])) ?></td>
                        <td><form method="post" action=""><input type="hidden" name="id" value="<?= e($mision['id']) ?>"><input type="hidden" name="accion" value="abandonar"><button type="submit">Abandonar</button></form></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p><strong>Total:</strong> <?= e(formatearOro(totalRecompensa($misiones, $_SESSION['zurron']))) ?></p>
    </aside>
</div>
</body>
</html>
