<?php
// Portal público de documentación con la estructura visual azul de portal_usuario.html.
require __DIR__.'/common.php';
$q=trim($_GET['q']??'');$cat=(int)($_GET['categoria']??0);
$sql="SELECT d.*,u.nombre,u.apellido,GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') categorias FROM documentos d JOIN usuarios u ON u.id=d.autor_id LEFT JOIN documento_categoria dc ON dc.documento_id=d.id LEFT JOIN categorias c ON c.id=dc.categoria_id WHERE d.estado='publicado'";$params=[];
if($q!==''){$sql.=' AND (d.titulo LIKE ? OR d.descripcion LIKE ? OR c.nombre LIKE ? OR c.grupo LIKE ?)';for($i=0;$i<4;$i++)$params[]="%$q%";}if($cat){$sql.=' AND EXISTS(SELECT 1 FROM documento_categoria x WHERE x.documento_id=d.id AND x.categoria_id=?)';$params[]=$cat;}$sql.=' GROUP BY d.id ORDER BY d.creado_en DESC';$st=$pdo->prepare($sql);$st->execute($params);$docs=$st->fetchAll();$stats=stats($pdo);
site_start('Hospital de Clínicas | Portal del Usuario','primary','Hospital de Clínicas','Portal de Documentación para Usuarios',['index.html'=>'Inicio','encuestas_buscar.html'=>'Encuestas','login.html'=>'Personal','#documentos'=>'Documentación','#informacion'=>'Información','#faq'=>'FAQ','#ayuda'=>'Ayuda']);
?>

<section class="container py-5"><div class="row g-4">
<div class="col-md-3"><div class="card shadow text-center h-100"><div class="card-body"><h2 class="text-primary"><?=count(categories($pdo))?></h2><h5>Servicios</h5><p class="text-muted mb-0">Categorías disponibles</p></div></div></div>
<div class="col-md-3"><div class="card shadow text-center h-100"><div class="card-body"><h2 class="text-primary"><?=$stats['documentos']?></h2><h5>Documentos</h5><p class="text-muted mb-0">Material publicado</p></div></div></div>
<div class="col-md-3"><div class="card shadow text-center h-100"><div class="card-body"><h2 class="text-primary">24/7</h2><h5>Acceso</h5><p class="text-muted mb-0">Disponible desde cualquier dispositivo</p></div></div></div>
<div class="col-md-3"><div class="card shadow text-center h-100"><div class="card-body"><h2 class="text-primary"><?=h($pdo->query("SELECT DATE_FORMAT(MAX(actualizado_en),'%d/%m') FROM documentos WHERE estado='publicado'")->fetchColumn() ?: '—')?></h2><h5>Última actualización</h5><p class="text-muted mb-0">Documentación publicada</p></div></div></div>
</div></section>

<section class="container pb-5">
<div class="row"><div class="col-12">
<div id="documentos" class="card shadow">
<div class="card-header bg-primary text-white"><h3 class="mb-0">Especialidades y documentación</h3></div>
<div class="card-body">
<form method="get" action="index.php" class="row g-2 mb-3"><input type="hidden" name="p" value="documentos"><div class="col-md-9"><input type="search" name="q" value="<?=h($q)?>" class="form-control" placeholder="Buscar documentos, especialidades o palabras clave..."></div><div class="col-md-3"><button class="btn btn-primary w-100">Buscar</button></div></form>
<p class="small text-muted">Las categorías entre títulos agrupan las especialidades. Solo las especialidades individuales contienen documentos.</p>
<div class="accordion" id="especialidades">
<?php foreach(categories($pdo) as $i=>$c): $groupLabel=$c['grupo']??'Especialidades Clínicas'; $matchesCategory=$q!=='' && (stripos($c['nombre'],$q)!==false || stripos($groupLabel,$q)!==false); $term=($q==='' || $matchesCategory)?'%':"%$q%"; $st=$pdo->prepare("SELECT d.*,u.nombre,u.apellido FROM documentos d JOIN documento_categoria dc ON dc.documento_id=d.id JOIN usuarios u ON u.id=d.autor_id WHERE d.estado='publicado' AND dc.categoria_id=? AND (d.titulo LIKE ? OR d.descripcion LIKE ?) ORDER BY d.creado_en DESC");$st->execute([$c['id'],$term,$term]);$categoryDocs=$st->fetchAll();$cid='servicio'.$c['id']; ?>
<?php if($i===0 || $groupLabel!==$previousGroup): ?><div class="fw-semibold text-primary mt-3 mb-2"><?=h($groupLabel)?></div><?php $previousGroup=$groupLabel; endif; ?>
<div class="accordion-item">
<h2 class="accordion-header">
<button class="accordion-button <?=$i===0?'':'collapsed'?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?=$cid?>" style="position:relative;padding-right:90px">
<span><?=h($c['nombre'])?></span>
<span class="badge bg-primary" style="position:absolute;right:45px;width:35px;text-align:center"><?=count($categoryDocs)?></span>
</button>
</h2>
<div id="<?=$cid?>" class="accordion-collapse collapse <?=$i===0?'show':''?>" data-bs-parent="#especialidades">
<div class="accordion-body">
<?php if(!$categoryDocs): ?>
<div class="alert alert-light mb-0">Actualmente no hay documentación publicada para este servicio.</div>
<?php else: ?>
<div class="list-group">
<?php foreach($categoryDocs as $d): ?>
<div class="list-group-item">
<div class="d-flex justify-content-between flex-wrap gap-2"><strong><?=h($d['titulo'])?></strong><a class="btn btn-sm btn-primary" target="_blank" href="index.php?p=ver_documento&id=<?=$d['id']?>">Ver PDF</a></div>
<p class="mb-1 mt-2"><?=h($d['descripcion'])?></p>
<small class="text-muted">Autor: <?=h($d['nombre'].' '.$d['apellido'])?> · <?=h($d['creado_en'])?></small>
</div><?php endforeach; ?></div><?php endif; ?></div></div></div><?php endforeach; ?></div></div></div></div></section>

<section id="faq" class="container pb-5"><div class="row g-4 justify-content-center"><div class="col-lg-8"><div class="card shadow"><div class="card-header bg-primary text-white"><h3 class="mb-0">Preguntas frecuentes</h3></div><div class="card-body"><div class="accordion" id="faqDocs"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#dFaq1">¿Necesito registrarme?</button></h2><div id="dFaq1" class="accordion-collapse collapse show"><div class="accordion-body">No, la documentación pública se puede consultar sin iniciar sesión.</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#dFaq2">¿Cómo encuentro un documento?</button></h2><div id="dFaq2" class="accordion-collapse collapse"><div class="accordion-body">Puede buscar por título o abrir la categoría del servicio correspondiente.</div></div></div></div></div></div></div></div></section>

<section id="ayuda" class="container pb-5"><div class="row g-4 justify-content-center"><div class="col-lg-6"><div class="card shadow"><div class="card-header bg-primary text-white"><h3 class="mb-0">¿Necesita ayuda?</h3></div><div class="card-body"><p>Envíe una consulta al equipo administrador.</p><?php support_button(); ?></div></div></div></div></section>

<?php support_modal(); site_end(); ?>
