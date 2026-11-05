<?php
// Punto de entrada. Recibe las acciones POST, comprueba permisos y muestra la pantalla solicitada.
require_once __DIR__ . '/../app/bootstrap.php';
check_csrf();
$page=$_GET['p'] ?? 'inicio';
$action=$_POST['action'] ?? '';

// LOGIN: valida reCAPTCHA, controla intentos y crea la sesión del usuario.
if ($action==='login') {
    $username=trim($_POST['usuario'] ?? '');
    $ip=$_SERVER['REMOTE_ADDR'] ?? 'local';
    $st=$pdo->prepare("SELECT COUNT(*) FROM intentos_login WHERE usuario=? AND ip=? AND correcto=0 AND creado_en > (NOW()-INTERVAL 15 MINUTE)");
    $st->execute([$username,$ip]);
    if ((int)$st->fetchColumn()>=3) { flash('danger','Se alcanzó el límite de 3 intentos. Espere 15 minutos.'); go('login'); }
    $ok=recaptcha_ok($config);
    $st=$pdo->prepare('SELECT u.*,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.usuario=? AND u.activo=1 LIMIT 1');
    $st->execute([$username]); $user=$st->fetch();
    if ($ok && $user && password_verify($_POST['clave'] ?? '',$user['password_hash'])) {
        $pdo->prepare('INSERT INTO intentos_login(usuario,ip,correcto) VALUES(?,?,1)')->execute([$username,$ip]);
        session_regenerate_id(true);
        $_SESSION['user']=['id'=>$user['id'],'usuario'=>$user['usuario'],'nombre'=>$user['nombre'],'apellido'=>$user['apellido'],'rol'=>$user['rol']];
        audit($pdo,'Inicio de sesión','Ingreso correcto');
        go(is_admin()?'admin':'panel');
    }
    $pdo->prepare('INSERT INTO intentos_login(usuario,ip,correcto) VALUES(?,?,0)')->execute([$username,$ip]);
    flash('danger',!$ok?'Complete la verificación reCAPTCHA.':'Usuario o contraseña incorrectos.');
    go('login');
}
// LOGOUT: elimina los datos de sesión y la cookie de sesión.
if ($action==='logout') { $_SESSION=[]; if(ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); go(); }
// SOPORTE: registra el mensaje para que todos los administradores lo vean en su bandeja.
if ($action==='support') {
    $msg=trim($_POST['mensaje'] ?? '');
    if ($msg!=='') { $pdo->prepare("INSERT INTO soporte(tipo,nombre,mensaje) VALUES('consulta',?,?)")->execute([trim($_POST['nombre'] ?? ''),$msg]); audit($pdo,'Nuevo mensaje de soporte','Se recibió una consulta.'); flash('success','Su consulta fue enviada a los administradores.'); }
    go($page);
}
// RECUPERACIÓN: crea un aviso rojo para administración; no cambia la contraseña automáticamente.
if ($action==='forgot') {
    $ced=trim($_POST['cedula'] ?? '');
    if ($ced!=='') {
        $message="El usuario con cédula $ced olvidó su contraseña. Contactarlo para verificar su identidad y asistirlo.";
        $pdo->prepare("INSERT INTO soporte(tipo,cedula,mensaje) VALUES('olvido_clave',?,?)")->execute([$ced,$message]);
        flash('success','El aviso fue enviado a los administradores.'); 
    } else flash('danger','Ingrese su cédula.');
    go('login');
}
// PERSONAL: crea o modifica usuarios y sus categorías de trabajo.
if ($action==='save_user') {
    require_admin();
    $id=(int)($_POST['id'] ?? 0); $usuario=trim($_POST['usuario'] ?? '');
    $nombre=trim($_POST['nombre'] ?? ''); $apellido=trim($_POST['apellido'] ?? '');
    $correo=trim($_POST['correo'] ?? ''); $ced=trim($_POST['cedula'] ?? '');
    $rol=(int)($_POST['rol_id'] ?? 2); $cats=$_POST['categorias'] ?? [];
    if ($usuario===''||$nombre===''||$apellido===''||!filter_var($correo,FILTER_VALIDATE_EMAIL)) { flash('danger','Complete los campos obligatorios y use un correo válido.'); go('admin',['tab'=>'personal']); }
    if ($id) {
        $pdo->prepare('UPDATE usuarios SET usuario=?,cedula=?,nombre=?,apellido=?,correo=?,celular=?,rol_id=? WHERE id=?')
            ->execute([$usuario,$ced?:null,$nombre,$apellido,$correo,trim($_POST['celular']??''),$rol,$id]);
        if (!empty($_POST['clave'])) $pdo->prepare('UPDATE usuarios SET password_hash=? WHERE id=?')->execute([password_hash($_POST['clave'],PASSWORD_DEFAULT),$id]);
        $pdo->prepare('DELETE FROM usuario_categoria WHERE usuario_id=?')->execute([$id]);
    } else {
        $pw=$_POST['clave'] ?? '';
        if (strlen($pw)<8) { flash('danger','La contraseña inicial debe tener al menos 8 caracteres.'); go('admin',['tab'=>'personal']); }
        $pdo->prepare('INSERT INTO usuarios(usuario,cedula,nombre,apellido,correo,celular,password_hash,rol_id) VALUES(?,?,?,?,?,?,?,?)')
            ->execute([$usuario,$ced?:null,$nombre,$apellido,$correo,trim($_POST['celular']??''),password_hash($pw,PASSWORD_DEFAULT),$rol]);
        $id=(int)$pdo->lastInsertId();
    }
    save_categories($pdo,'usuario_categoria','usuario_id',$id,$cats);
    audit($pdo,'Guardar usuario',"Usuario $usuario"); flash('success','Los datos del usuario fueron guardados.'); go('admin',['tab'=>'personal']);
}
if ($action==='change_password') {
    require_login();
    $st=$pdo->prepare('SELECT password_hash FROM usuarios WHERE id=?'); $st->execute([current_user()['id']]); $hash=$st->fetchColumn();
    $old=$_POST['clave_actual']??''; $new=$_POST['clave_nueva']??'';
    if (!password_verify($old,$hash) || strlen($new)<8 || $new!==($_POST['confirmar']??'')) {
        flash('danger','Revise la contraseña actual. La nueva debe tener 8 caracteres y coincidir con la confirmación.');
    } else {
        $pdo->prepare('UPDATE usuarios SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),current_user()['id']]);
        audit($pdo,'Cambio de contraseña','El usuario actualizó su contraseña.'); flash('success','Contraseña actualizada.');
    }
    go(is_admin()?'admin':'panel',['tab'=>'sesion']);
}
// BAJA LÓGICA: desactiva o reactiva una cuenta sin borrar su historial.
if ($action==='toggle_user') {
    require_admin(); $id=(int)$_POST['id'];
    if ($id!==current_user()['id']) { $pdo->prepare('UPDATE usuarios SET activo=1-activo WHERE id=?')->execute([$id]); audit($pdo,'Cambiar estado de usuario',"ID $id"); }
    flash('success','Estado actualizado.'); go('admin',['tab'=>'personal']);
}
// DOCUMENTOS: guarda PDF, datos, autor y categorías; también permite reemplazar el archivo.
if ($action==='save_document') {
    require_login();
    $id=(int)($_POST['id']??0); $title=trim($_POST['titulo']??''); $desc=trim($_POST['descripcion']??''); $cats=$_POST['categorias']??[];
    if ($title==='') { flash('danger','El título es obligatorio.'); go(is_admin()?'admin':'panel',['tab'=>'documentos']); }
    if ($id) {
        // Cualquier funcionario autenticado puede actualizar documentos, como solicitó el proyecto.
        $pdo->prepare('UPDATE documentos SET titulo=?,descripcion=? WHERE id=?')->execute([$title,$desc,$id]);
        save_categories($pdo,'documento_categoria','documento_id',$id,$cats);
        if (!empty($_FILES['pdf']['name'])) {
            $file=store_pdf($_FILES['pdf']);
            if (!$file) go(is_admin()?'admin':'panel',['tab'=>'documentos']);
            if ($file) {
                $pdo->prepare('INSERT INTO versiones_documento(documento_id,archivo,descripcion_cambio,usuario_id) VALUES(?,?,?,?)')->execute([$id,$file,trim($_POST['cambio']??'Reemplazo del archivo'),current_user()['id']]);
                $pdo->prepare('UPDATE documentos SET archivo=?,nombre_original=? WHERE id=?')->execute([$file,basename($_FILES['pdf']['name']),$id]);
            }
        }
        audit($pdo,'Editar documento',"Documento $id");
    } else {
        if (empty($_FILES['pdf']['name'])) { flash('danger','Seleccione un archivo PDF.'); go(is_admin()?'admin':'panel',['tab'=>'documentos']); }
        $file=store_pdf($_FILES['pdf']); if (!$file) go(is_admin()?'admin':'panel',['tab'=>'documentos']);
        $pdo->prepare("INSERT INTO documentos(titulo,descripcion,archivo,nombre_original,autor_id) VALUES(?,?,?,?,?)")
            ->execute([$title,$desc,$file,basename($_FILES['pdf']['name']),current_user()['id']]);
        $id=(int)$pdo->lastInsertId(); save_categories($pdo,'documento_categoria','documento_id',$id,$cats);
        $pdo->prepare('INSERT INTO versiones_documento(documento_id,archivo,descripcion_cambio,usuario_id) VALUES(?,?,?,?)')->execute([$id,$file,'Carga inicial',current_user()['id']]);
        audit($pdo,'Subir documento',"Documento $title");
    }
    flash('success','Documento guardado.'); go(is_admin()?'admin':'panel',['tab'=>'documentos']);
}
// ELIMINAR DOCUMENTO: borra sus relaciones y versiones de la base de datos y luego los PDF asociados.
if ($action==='delete_document') {
    require_admin(); $id=(int)($_POST['id']??0);
    $st=$pdo->prepare('SELECT archivo FROM documentos WHERE id=?'); $st->execute([$id]); $main=$st->fetchColumn();
    $st=$pdo->prepare('SELECT archivo FROM versiones_documento WHERE documento_id=?'); $st->execute([$id]); $versions=$st->fetchAll(PDO::FETCH_COLUMN);
    $pdo->prepare('DELETE FROM documentos WHERE id=?')->execute([$id]);
    foreach(array_unique(array_filter(array_merge([$main],$versions))) as $file){$path=__DIR__.'/../docs/'.basename($file);if(is_file($path)) @unlink($path);}
    audit($pdo,'Eliminar documento',"Documento $id"); flash('success','El documento fue eliminado.'); go('admin',['tab'=>'documentos']);
}
// ENCUESTAS: guarda datos generales, categorías y preguntas creadas desde el constructor.
if ($action==='save_survey') {
    require_admin();
    $id=(int)($_POST['id']??0); $title=trim($_POST['titulo']??''); $desc=trim($_POST['descripcion']??'');
    $audience=$_POST['publico']??'publico'; $state=$_POST['estado']??'borrador'; $cats=$_POST['categorias']??[];
    $questions=json_decode($_POST['preguntas_json']??'[]',true);
    if ($title===''||!in_array($audience,['publico','personal'],true)||!in_array($state,['borrador','publicada','cerrada'],true)||!is_array($questions)||count($questions)<1) {
        flash('danger','Complete el título y agregue al menos una pregunta.'); go('admin',['tab'=>'encuestas']);
    }
    if ($id) {
        $owner=$pdo->prepare('SELECT creador_id,estado FROM encuestas WHERE id=?'); $owner->execute([$id]); $ownerRow=$owner->fetch();
        if (!$ownerRow || ($ownerRow['estado']==='borrador' && (int)$ownerRow['creador_id']!==(int)current_user()['id'])) { flash('danger','No puede modificar este borrador.'); go('admin',['tab'=>'encuestas']); }
        $st=$pdo->prepare('SELECT COUNT(*) FROM envios_encuesta WHERE encuesta_id=?'); $st->execute([$id]);
        if ((int)$st->fetchColumn()>0) { flash('danger','Esta encuesta ya tiene respuestas. Duplíquela para crear una nueva versión y conservar el historial.'); go('admin',['tab'=>'encuestas']); }
        $pdo->prepare('UPDATE encuestas SET titulo=?,descripcion=?,publico=?,estado=? WHERE id=?')->execute([$title,$desc,$audience,$state,$id]);
        $pdo->prepare('DELETE FROM preguntas WHERE encuesta_id=?')->execute([$id]);
    } else {
        $pdo->prepare('INSERT INTO encuestas(titulo,descripcion,publico,estado,creador_id) VALUES(?,?,?,?,?)')->execute([$title,$desc,$audience,$state,current_user()['id']]);
        $id=(int)$pdo->lastInsertId();
    }
    save_categories($pdo,'encuesta_categoria','encuesta_id',$id,$cats);
    foreach($questions as $i=>$q) {
        $type=$q['tipo']??'texto'; $text=trim($q['texto']??'');
        if ($text===''||!in_array($type,['unica','multiple','select','valoracion','texto'],true)) continue;
        $pdo->prepare('INSERT INTO preguntas(encuesta_id,enunciado,tipo,obligatoria,orden) VALUES(?,?,?,?,?)')->execute([$id,$text,$type,!empty($q['obligatoria'])?1:0,$i+1]);
        $pid=(int)$pdo->lastInsertId();
        if (in_array($type,['unica','multiple','select'],true)) {
            foreach(($q['opciones']??[]) as $j=>$opt) if(trim($opt)!=='') $pdo->prepare('INSERT INTO opciones_pregunta(pregunta_id,texto,orden) VALUES(?,?,?)')->execute([$pid,trim($opt),$j+1]);
        }
    }
    audit($pdo,'Guardar encuesta',"Encuesta $title"); flash('success','Encuesta guardada.'); go('admin',['tab'=>'encuestas']);
}
// PUBLICACIÓN: cambia el estado de una encuesta y permite cerrarla.
if ($action==='survey_state') {
    require_admin(); $id=(int)$_POST['id']; $state=$_POST['estado'];
    if(in_array($state,['publicada','cerrada','borrador'],true)) $pdo->prepare('UPDATE encuestas SET estado=? WHERE id=?')->execute([$state,$id]);
    audit($pdo,'Cambiar estado de encuesta',"Encuesta $id: $state"); flash('success','Estado actualizado.'); go('admin',['tab'=>'encuestas']);
}
// NUEVA VERSIÓN: copia preguntas y opciones a un borrador independiente.
if ($action==='duplicate_survey') {
    require_admin(); $id=(int)$_POST['id'];
    $st=$pdo->prepare('SELECT * FROM encuestas WHERE id=? AND creador_id=?'); $st->execute([$id,current_user()['id']]); $e=$st->fetch();
    if($e) {
        $pdo->prepare("INSERT INTO encuestas(titulo,descripcion,publico,estado,creador_id) VALUES(?,?,?,'borrador',?)")->execute([$e['titulo'].' - nueva versión',$e['descripcion'],$e['publico'],current_user()['id']]);
        $new=(int)$pdo->lastInsertId();
        foreach(selected_categories($pdo,'encuesta_categoria','encuesta_id',$id) as $c) $pdo->prepare('INSERT INTO encuesta_categoria VALUES(?,?)')->execute([$new,$c]);
        $st=$pdo->prepare('SELECT * FROM preguntas WHERE encuesta_id=? ORDER BY orden'); $st->execute([$id]);
        foreach($st->fetchAll() as $q) {
            $pdo->prepare('INSERT INTO preguntas(encuesta_id,enunciado,tipo,obligatoria,orden) VALUES(?,?,?,?,?)')->execute([$new,$q['enunciado'],$q['tipo'],$q['obligatoria'],$q['orden']]); $newq=(int)$pdo->lastInsertId();
            $op=$pdo->prepare('SELECT texto,orden FROM opciones_pregunta WHERE pregunta_id=?'); $op->execute([$q['id']]);
            foreach($op->fetchAll() as $o) $pdo->prepare('INSERT INTO opciones_pregunta(pregunta_id,texto,orden) VALUES(?,?,?)')->execute([$newq,$o['texto'],$o['orden']]);
        }
        flash('success','Se creó un borrador nuevo.'); audit($pdo,'Duplicar encuesta',"Origen $id, nueva $new");
    }
    go('admin',['tab'=>'encuestas']);
}
// ELIMINAR ENCUESTA: elimina respuestas, envíos, preguntas y categorías asociadas en una transacción.
if ($action==='delete_survey') {
    require_admin(); $id=(int)($_POST['id']??0);
    $pdo->beginTransaction();
    try { $pdo->prepare('DELETE r FROM respuestas r JOIN envios_encuesta e ON e.id=r.envio_id WHERE e.encuesta_id=?')->execute([$id]); $pdo->prepare('DELETE FROM envios_encuesta WHERE encuesta_id=?')->execute([$id]); $pdo->prepare('DELETE FROM encuestas WHERE id=?')->execute([$id]); $pdo->commit(); audit($pdo,'Eliminar encuesta',"Encuesta $id"); flash('success','La encuesta y sus respuestas fueron eliminadas.'); } catch(Throwable $e){$pdo->rollBack();flash('danger','No se pudo eliminar la encuesta.');}
    go('admin',['tab'=>'encuestas']);
}
// RESPUESTAS: valida cada tipo de pregunta y guarda los datos sin identificar al visitante.
if ($action==='answer_survey') {
    $id=(int)$_POST['encuesta_id'];
    $st=$pdo->prepare("SELECT * FROM encuestas WHERE id=? AND estado='publicada'"); $st->execute([$id]); $survey=$st->fetch();
    if(!$survey || ($survey['publico']==='personal' && !logged_in())) { flash('danger','No tiene acceso a esta encuesta.'); go('encuestas'); }
    $token=$_POST['token_envio']??'';
    if($token==='' || !hash_equals($_SESSION['survey_tokens'][$id]??'', $token)) { flash('danger','Este formulario ya fue enviado o venció.'); go('encuestas'); }
    $st=$pdo->prepare('SELECT * FROM preguntas WHERE encuesta_id=? ORDER BY orden'); $st->execute([$id]); $questions=$st->fetchAll();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO envios_encuesta(encuesta_id,token_envio) VALUES(?,?)')->execute([$id,hash('sha256',$token)]);
        $send=(int)$pdo->lastInsertId();
        foreach($questions as $q) {
            $value=$_POST['q'][$q['id']]??null;
            if($q['tipo']==='multiple') {
                $selected=is_array($value)?array_map('trim',$value):[];
                $op=$pdo->prepare('SELECT texto FROM opciones_pregunta WHERE pregunta_id=?'); $op->execute([$q['id']]); $allowed=array_column($op->fetchAll(),'texto');
                foreach($selected as $choice) if(!in_array($choice,$allowed,true)) throw new RuntimeException('Una de las opciones seleccionadas no es válida.');
                $value=implode(' | ',$selected);
            } else {
                if(is_array($value)) $value='';
                $value=trim((string)$value);
                if(in_array($q['tipo'],['unica','select'],true) && $value!=='') {
                    $op=$pdo->prepare('SELECT COUNT(*) FROM opciones_pregunta WHERE pregunta_id=? AND texto=?'); $op->execute([$q['id'],$value]);
                    if((int)$op->fetchColumn()===0) throw new RuntimeException('La opción seleccionada no es válida.');
                }
            }
            if($q['obligatoria'] && $value==='') throw new RuntimeException('Complete todas las preguntas obligatorias.');
            if($q['tipo']==='valoracion' && $value!=='' && !in_array($value,['1','2','3','4','5'],true)) throw new RuntimeException('La valoración debe estar entre 1 y 5.');
            if($q['tipo']==='texto' && strlen($value)>2000) throw new RuntimeException('El texto no puede superar los 2000 caracteres.');
            if($value!=='') $pdo->prepare('INSERT INTO respuestas(envio_id,pregunta_id,contenido) VALUES(?,?,?)')->execute([$send,$q['id'],$value]);
        }
        $pdo->commit(); unset($_SESSION['survey_tokens'][$id]); flash('success','Gracias. Su respuesta fue registrada de forma anónima.');
    } catch(Throwable $e) { $pdo->rollBack(); flash('danger',$e->getMessage()); }
    go('encuestas');
}
if ($action==='delete_ticket') { require_admin(); $pdo->prepare('DELETE FROM soporte WHERE id=?')->execute([(int)$_POST['id']]); audit($pdo,'Eliminar mensaje de soporte','Ticket '.$_POST['id']); flash('success','Mensaje eliminado.'); go('admin',['tab'=>'soporte']); }
if ($action==='read_ticket') { require_admin(); $pdo->prepare('UPDATE soporte SET leido=1 WHERE id=?')->execute([(int)$_POST['id']]); go('admin',['tab'=>'soporte','ticket'=>(int)$_POST['id']]); }

// ARCHIVOS: valida tamaño y contenido MIME antes de mover el PDF a la carpeta docs.
function store_pdf(array $file): ?string {
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) { flash('danger','No se pudo recibir el archivo.'); return null; }
    if (($file['size']??0)>2*1024*1024) { flash('danger','El PDF no puede superar 2 MB.'); return null; }
    $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
    if ($mime!=='application/pdf' || strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='pdf') { flash('danger','Solo se permiten archivos PDF válidos.'); return null; }
    $name='doc_'.date('Ymd_His').'_'.bin2hex(random_bytes(5)).'.pdf';
    if(!move_uploaded_file($file['tmp_name'],__DIR__.'/../docs/'.$name)) { flash('danger','No se pudo guardar el archivo.'); return null; }
    return $name;
}

// Las descargas se procesan antes de imprimir HTML para no dañar los archivos PDF o CSV.
if ($page==='logout') { $_SESSION=[]; session_destroy(); header('Location: index.php'); exit; }
if ($page==='ver_documento' || $page==='ver_admin_documento') {
    if ($page==='ver_admin_documento') require_admin();
    $id=(int)($_GET['id']??0);
    $query=$page==='ver_documento' ? "SELECT * FROM documentos WHERE id=? AND estado='publicado'" : "SELECT * FROM documentos WHERE id=? AND estado<>'baja'";
    $st=$pdo->prepare($query); $st->execute([$id]); $doc=$st->fetch();
    $path=$doc ? __DIR__.'/../docs/'.$doc['archivo'] : '';
    if (!$doc || !is_file($path)) { http_response_code(404); exit('Documento no disponible.'); }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.rawurlencode(basename($doc['nombre_original'])).'"');
    header('Content-Length: '.filesize($path)); readfile($path); exit;
}
if ($page==='exportar') {
    require_admin(); $id=(int)($_GET['id']??0);
    $own=$pdo->prepare('SELECT creador_id,estado FROM encuestas WHERE id=?'); $own->execute([$id]); $ownRow=$own->fetch();
    if (!$ownRow || ($ownRow['estado']==='borrador' && (int)$ownRow['creador_id']!==(int)current_user()['id'])) { http_response_code(403); exit('No tiene permiso para ver este borrador.'); }
    $st=$pdo->prepare('SELECT e.titulo,p.enunciado,r.contenido,env.creado_en FROM respuestas r JOIN preguntas p ON p.id=r.pregunta_id JOIN envios_encuesta env ON env.id=r.envio_id JOIN encuestas e ON e.id=env.encuesta_id WHERE e.id=? ORDER BY env.id,p.orden'); $st->execute([$id]);
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="resultados_encuesta_'.$id.'.csv"');
    $out=fopen('php://output','w'); fputcsv($out,['Encuesta','Pregunta','Respuesta','Fecha'],';'); foreach($st as $row) fputcsv($out,$row,';'); fclose($out); exit;
}
// Renderiza las pantallas con las plantillas basadas en los HTML originales recibidos.
$templatePages = [
    'inicio' => 'index.php', 'login' => 'login.php', 'documentos' => 'portal_usuario.php',
    'encuestas' => 'encuestas_buscar.php', 'responder' => 'encuesta.php',
    'panel' => 'portal_funcionario.php', 'admin' => 'portal_admin.php'
];
if (isset($templatePages[$page])) {
    require __DIR__ . '/../app/templates/' . $templatePages[$page];
    exit;
}

page_header(match($page){'login'=>'Acceso del personal','admin'=>'Administración SIGSM','documentos'=>'Documentación','encuestas'=>'Encuestas','panel'=>'Mi sesión','estadisticas'=>'Resultados de encuesta',default=>'Hospital de Clínicas'});
// Las páginas auxiliares (resultados y archivos) se presentan después de comprobar permisos.
if ($page==='estadisticas') {

    require_admin();$id=(int)($_GET['id']??0);$st=$pdo->prepare('SELECT * FROM encuestas WHERE id=?');$st->execute([$id]);$e=$st->fetch();
    echo '<section class="hero rounded-4 mb-4" style="background:linear-gradient(135deg,#0dcaf0,#0aa2c0);color:white;padding:42px 20px;text-align:center"><div class="container"><h1 class="h2 mb-1">Resultados de la encuesta</h1><p class="mb-0">Resumen de las respuestas recibidas</p></div></section><div class="container pb-4">';
    if(!$e)echo '<div class="alert alert-warning">Encuesta no encontrada.</div>';
    elseif($e['estado']==='borrador' && (int)$e['creador_id']!==(int)current_user()['id']) { http_response_code(403); echo '<p>No tiene permiso para ver este borrador.</p>'; }
    else{
        $st=$pdo->prepare('SELECT COUNT(*) FROM envios_encuesta WHERE encuesta_id=?');$st->execute([$id]);$count=(int)$st->fetchColumn();
        echo '<h1 class="h3">Resultados: '.h($e['titulo']).'</h1><p>Total de respuestas: <strong>'.$count.'</strong></p>';
        $st=$pdo->prepare('SELECT * FROM preguntas WHERE encuesta_id=? ORDER BY orden');$st->execute([$id]);
        foreach($st as $q){$ans=$pdo->prepare('SELECT r.contenido,COUNT(*) cantidad FROM respuestas r JOIN envios_encuesta e ON e.id=r.envio_id WHERE r.pregunta_id=? GROUP BY r.contenido ORDER BY r.contenido');$ans->execute([$q['id']]);$rows=$ans->fetchAll();
            echo '<div class="card mb-3"><div class="card-body"><h2 class="h6">'.h($q['enunciado']).'</h2>';
            if($q['tipo']==='valoracion'){$avg=$pdo->prepare('SELECT AVG(CAST(r.contenido AS DECIMAL(4,2))) FROM respuestas r JOIN envios_encuesta e ON e.id=r.envio_id WHERE r.pregunta_id=?');$avg->execute([$q['id']]);echo '<p>Promedio: <strong>'.number_format((float)$avg->fetchColumn(),2,',','.').'/5</strong></p>';}
            if(!$rows)echo '<p class="text-muted">Todavía no hay respuestas.</p>';else foreach($rows as $r)echo '<div class="d-flex justify-content-between border-bottom py-1"><span>'.h($r['contenido']).'</span><strong>'.(int)$r['cantidad'].'</strong></div>';
            echo '</div></div>';
        }
    }
    echo '</div>';
}
elseif ($page==='exportar') {
    require_admin();$id=(int)($_GET['id']??0);
    $st=$pdo->prepare('SELECT e.titulo,p.enunciado,r.contenido,env.creado_en FROM respuestas r JOIN preguntas p ON p.id=r.pregunta_id JOIN envios_encuesta env ON env.id=r.envio_id JOIN encuestas e ON e.id=env.encuesta_id WHERE e.id=? ORDER BY env.id,p.orden');$st->execute([$id]);
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="resultados_encuesta_'.$id.'.csv"');
    $out=fopen('php://output','w');fputcsv($out,['Encuesta','Pregunta','Respuesta','Fecha'], ';');foreach($st as $row)fputcsv($out,$row,';');fclose($out);exit;
}
elseif ($page==='ver_admin_documento') {
    require_admin();$id=(int)($_GET['id']??0);$st=$pdo->prepare('SELECT * FROM documentos WHERE id=?');$st->execute([$id]);$d=$st->fetch();
    if(!$d||$d['estado']==='baja') {http_response_code(404);echo 'Documento no disponible.';}
    else {$path=__DIR__.'/../docs/'.$d['archivo'];if(is_file($path)){header('Content-Type: application/pdf');header('Content-Disposition: inline');readfile($path);exit;}echo 'Archivo no encontrado.';}
}
else { http_response_code(404); echo '<h1>Página no encontrada</h1>'; }
page_footer();
