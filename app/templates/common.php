<?php
// Elementos compartidos que mantienen los colores, la navegación y el pie del diseño original.
function site_start(string $title, string $accent, string $heroTitle, string $heroText, array $links, bool $hero=true): void {
    $gradients = ['primary'=>'linear-gradient(135deg,#0d6efd,#0a58ca)','info'=>'linear-gradient(135deg,#0dcaf0,#0aa2c0)','success'=>'linear-gradient(135deg,#198754,#146c43)','purple'=>'linear-gradient(135deg,#6f42c1,#4b2a83)'];
    $gradient = $gradients[$accent] ?? $gradients['primary'];
    $footerColors = ['primary'=>'#0d6efd','info'=>'#0dcaf0','success'=>'#198754','purple'=>'#6f42c1'];
    $footerColor = $footerColors[$accent] ?? '#0d6efd';
    $heroPadding = (str_contains($title, 'Inicio') || str_contains($title, 'Iniciar Sesión')) ? '70px' : '60px';
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>'.h($title).'</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f8f9fa}.hero{background:'.$gradient.';color:white;padding:'.$heroPadding.' 20px;text-align:center}.card{border:none;transition:.3s}.card:hover{transform:translateY(-3px)}.icono{font-size:60px}footer{background:'.$footerColor.';color:white}.badge-categoria{font-size:.85rem}.login-card{max-width:500px;margin:auto;margin-top:-60px;border:none;border-radius:20px}.qr-hero{width:142px;height:142px;object-fit:contain;background:#fff;padding:9px;border-radius:12px}.table td,.table th{vertical-align:middle}.toast-notice{position:fixed;left:18px;bottom:18px;z-index:3000;max-width:min(420px,calc(100vw - 36px));margin:0;animation:toastIn .2s ease-out}@keyframes toastIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}.support-card{height:100%}.support-card .card-body{display:flex;flex-direction:column}.support-card .card-body p{flex:1}</style></head><body>';
    echo '<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top"><div class="container"><a class="navbar-brand fw-bold text-'.h($accent==='purple'?'primary':$accent).'" '.($accent==='purple'?'style="color:#6f42c1;"':'').' href="index.html">Hospital de Clínicas</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="menu"><ul class="navbar-nav ms-auto">';
    foreach ($links as $href=>$label) { $logout = (str_contains($href, 'logout') || mb_strtolower($label)==='cerrar sesión'); echo '<li class="nav-item"><a class="nav-link '.($logout?'text-danger fw-bold':'').'" href="'.h($href).'">'.h($label).'</a></li>'; }
    echo '</ul></div></div></nav>';
    if ($hero) echo '<section class="hero"><div class="container"><h1 class="display-5 fw-bold">'.h($heroTitle).'</h1><p class="lead mb-0">'.h($heroText).'</p></div></section>';
    echo '<main><div class="container pt-3">'; show_flash(); echo '</div>'; 
}
function site_end(string $label='Sistema de Gestión Documental y Encuestas'): void {
    echo '</main><footer class="text-center py-4 mt-5"><h5>Hospital de Clínicas</h5><p class="mb-1">'.h($label).'</p><p class="mb-1">UTU Arroyo Seco</p><p class="mb-1">Proyecto Integrador</p><p class="mb-0">Nordlyn © 2026</p></footer><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script><script src="assets/app.js"></script></body></html>';
}
function template_support(string $accent='primary'): void { echo '<section class="container pb-4"><div class="card shadow support-card"><div class="card-header bg-'.h($accent).' text-white"><h4 class="mb-0">¿Necesita ayuda?</h4></div><div class="card-body"><p>Si presenta inconvenientes, envíe una consulta al equipo administrador.</p>'; support_button(); echo '</div></div></section>'; support_modal(); }
