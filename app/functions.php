<?php
// Funciones comunes: salida segura, permisos, formularios, consultas y auditoría.
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $page='inicio', array $extra=[]): string { return 'index.php?' . http_build_query(['p'=>$page]+$extra); }
function go(string $page='inicio', array $extra=[]): never { header('Location: '.url($page,$extra)); exit; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function logged_in(): bool { return current_user() !== null; }
function is_admin(): bool { return logged_in() && current_user()['rol'] === 'Administrador'; }
function require_login(): void { if (!logged_in()) go('login'); }
function require_admin(): void { require_login(); if (!is_admin()) { http_response_code(403); exit('No tiene permiso para acceder a esta sección.'); } }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.h(csrf_token()).'">'; }
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('El formulario venció. Vuelva atrás y recargue la página.');
    }
}
function flash(string $type,string $msg): void { $_SESSION['flash']=['type'=>$type,'msg'=>$msg]; }
function show_flash(): void {
    // Los avisos se muestran como notificaciones flotantes; no ocupan espacio ni mueven la página.
    if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; echo '<div class="toast-notice alert alert-'.h($f['type']).' shadow" role="status">'.h($f['msg']).'</div>'; unset($_SESSION['flash']); }
}
function audit(PDO $db,string $action,string $detail=''): void {
    $uid=current_user()['id'] ?? null;
    $st=$db->prepare('INSERT INTO auditoria(usuario_id,accion,detalle) VALUES(?,?,?)');
    $st->execute([$uid,$action,$detail]);
}
function categories(PDO $db): array {
    // Las categorías son únicamente las especialidades donde se pueden asignar documentos.
    // Los cuatro títulos de grupo son encabezados visuales y nunca tienen ID ni aceptan documentos.
    try { return $db->query('SELECT * FROM categorias ORDER BY grupo_orden, orden, nombre')->fetchAll(); } catch (PDOException $e) { return $db->query('SELECT * FROM categorias ORDER BY grupo, nombre')->fetchAll(); }
}
// Devuelve categorías agrupadas para que los tres portales usen el mismo árbol de especialidades.
function categories_grouped(PDO $db): array {
    $groups=[];
    foreach (categories($db) as $category) $groups[$category['grupo'] ?? 'Especialidades Clínicas'][]=$category;
    return $groups;
}
function selected_categories(PDO $db,string $table,string $fk,int $id): array {
    $st=$db->prepare("SELECT categoria_id FROM $table WHERE $fk=?"); $st->execute([$id]); return array_column($st->fetchAll(),'categoria_id');
}
function category_checkboxes(PDO $db,string $name='categorias[]',array $selected=[]): void {
    // Se muestran los grupos como texto y solo las especialidades hijas como opciones seleccionables.
    $last='';
    foreach (categories($db) as $category) {
        $group=$category['grupo'] ?? '';
        if ($group !== $last) {
            $last=$group;
            echo '<div class="w-100 fw-semibold text-primary mt-3 mb-2">'.h($last).'</div>';
        }
        $id=(int)$category['id'];
        $checked=in_array($id,array_map('intval',$selected),true) ? 'checked' : '';
        echo '<label class="form-check me-3 mb-2"><input class="form-check-input" type="checkbox" name="'.h($name).'" value="'.$id.'" '.$checked.'> <span class="form-check-label">'.h($category['nombre']).'</span></label>';
    }
}
function save_categories(PDO $db,string $table,string $fk,int $id,array $ids): void {
    $db->prepare("DELETE FROM $table WHERE $fk=?")->execute([$id]);
    $st=$db->prepare("INSERT IGNORE INTO $table($fk,categoria_id) VALUES(?,?)");
    foreach(array_unique(array_map('intval',$ids)) as $cid) $st->execute([$id,$cid]);
}
function recaptcha_ok(array $config): bool {
    $token=$_POST['g-recaptcha-response'] ?? '';
    if ($token==='') return false;
    $body=http_build_query(['secret'=>$config['recaptcha_secret_key'],'response'=>$token,'remoteip'=>$_SERVER['REMOTE_ADDR'] ?? '']);
    $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-type: application/x-www-form-urlencoded\r\n",'content'=>$body,'timeout'=>5]]);
    $response=@file_get_contents('https://www.google.com/recaptcha/api/siteverify',false,$ctx);
    if ($response===false) return false;
    $data=json_decode($response,true);
    return !empty($data['success']);
}
function page_header(string $title='Hospital de Clínicas'): void {
    $u=current_user();
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>'.h($title).'</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/style.css" rel="stylesheet">';
    echo '</head><body><nav class="navbar navbar-expand-lg navbar-dark bg-hospital"><div class="container"><a class="navbar-brand fw-bold" href="'.url().'">Hospital de Clínicas</a><button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="nav"><ul class="navbar-nav ms-auto">';
    $isResultsPage = (($_GET['p'] ?? '') === 'estadisticas');
    $items = $isResultsPage ? [] : ['inicio'=>'Inicio','documentos'=>'Documentación','encuestas'=>'Encuestas'];
    if (!$isResultsPage && is_admin()) $items['admin']='Administración';
    elseif (!$isResultsPage && $u) $items['panel']='Mi sesión';
    foreach($items as $k=>$v) echo '<li class="nav-item"><a class="nav-link" href="'.url($k).'">'.h($v).'</a></li>';
    if (!$isResultsPage) {
        if ($u) echo '<li class="nav-item"><span class="nav-link text-white-50">'.h($u['nombre']).'</span></li><li class="nav-item"><a class="nav-link" href="'.url('logout').'">Cerrar sesión</a></li>';
        else echo '<li class="nav-item"><a class="nav-link" href="'.url('login').'">Acceso personal</a></li>';
    }
    echo '</ul></div></div></nav><main class="container py-4">';
    show_flash();
}
function page_footer(): void {
    echo '</main><footer class="footer mt-5 py-4"><div class="container"><div class="row"><div class="col-md-8"><strong>Hospital de Clínicas · SIGSM</strong><p class="mb-0 small">Portal informativo y de gestión documental desarrollado por Nordlyn.</p></div><div class="col-md-4 small"><strong>Preguntas frecuentes</strong><details><summary>¿Necesito una cuenta para consultar documentos?</summary><p>La documentación pública y las encuestas públicas se pueden consultar sin iniciar sesión.</p></details><details><summary>¿Las encuestas identifican a quien responde?</summary><p>No. El sistema guarda las respuestas sin nombre, cédula ni cuenta del visitante.</p></details><details><summary>¿Cómo solicito ayuda?</summary><p>Use el botón Soporte para enviar un mensaje a los administradores.</p></details></div></div><hr><div class="small text-center">Proyecto académico · Nordlyn · 2026</div></div></footer><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script><script src="assets/app.js"></script></body></html>';
}
function support_button(): void {
    echo '<button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#supportModal">Soporte</button>'; 
}
function support_modal(): void {
    echo '<div class="modal fade" id="supportModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post">'.csrf_field().'<input type="hidden" name="action" value="support"><div class="modal-header"><h5 class="modal-title">Enviar consulta a administración</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Nombre (opcional)</label><input class="form-control mb-3" name="nombre" maxlength="160"><label class="form-label">Describa su problema</label><textarea class="form-control" name="mensaje" rows="4" maxlength="3000" required></textarea></div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancelar</button><button class="btn btn-primary">Enviar</button></div></form></div></div>';
}
function stats(PDO $db): array {
    return [
      'documentos'=>(int)$db->query("SELECT COUNT(*) FROM documentos WHERE estado='publicado'")->fetchColumn(),
      'encuestas'=>(int)$db->query("SELECT COUNT(*) FROM encuestas WHERE estado='publicada'")->fetchColumn(),
      'usuarios'=>(int)$db->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn(),
      'respuestas'=>(int)$db->query("SELECT COUNT(*) FROM envios_encuesta")->fetchColumn()
    ];
}
