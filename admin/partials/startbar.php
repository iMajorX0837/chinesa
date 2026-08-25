<?php
if (!function_exists('admin_t')) {
    include_once "l.php";
}

if (!function_exists('admin_nav_current_slug')) {
    function admin_nav_current_slug() {
        static $slug = null;
        if ($slug !== null) {
            return $slug;
        }

        $file_slugs = [
            'index' => 'dashboard',
            'pixeis' => 'pixel',
            'ge-nomes' => 'gerenciamento-nomes',
            'ge-cupons' => 'cupons',
            'ge-vips' => 'niveis',
            'editar-banners' => 'banners',
            'editar-popups' => 'popups',
            'editar-festival' => 'festival',
            'editar-floats' => 'iconesfloat',
            'editar-promocoes' => 'promocoes',
            'editar-mensagens' => 'mensagens',
            'imagens-plataforma' => 'identidade-visual',
            'contasdemos' => 'contas-demos',
            'historico_jogadas' => 'historicosplay',
            'logs_cupons' => 'logsbonus',
            'logs_niveis' => 'niveislogs',
            'webhook' => 'webhooks',
            'popup-baixar' => 'baixarpop',
            'beeplay' => 'gamesbeeplay',
            'pfiverapi' => 'chavesplayfiver',
            'detalhes_usuario' => 'usuarios',
            'checkin_config' => 'checkin',
        ];

        $script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
        if ($script !== '' && isset($file_slugs[$script])) {
            return $slug = $file_slugs[$script];
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $parts = array_values(array_filter(explode('/', trim($path, '/'))));
        $last = $parts ? end($parts) : '';
        $last = preg_replace('/\.php$/', '', $last);

        if ($last === 'admin' || $last === '') {
            return $slug = 'dashboard';
        }
        if (isset($file_slugs[$last])) {
            return $slug = $file_slugs[$last];
        }

        return $slug = ($last ?: ($file_slugs[$script] ?? $script ?: 'dashboard'));
    }

    function admin_nav_is($slugs) {
        return in_array(admin_nav_current_slug(), (array) $slugs, true);
    }

    function admin_nav_link_class($slugs) {
        return admin_nav_is($slugs) ? ' active' : '';
    }

    function admin_nav_collapse_class($slugs) {
        return admin_nav_is($slugs) ? ' collapse show' : ' collapse';
    }

    function admin_nav_expanded($slugs) {
        return admin_nav_is($slugs) ? 'true' : 'false';
    }
}

$nav_settings_pages = [
    'configuracoes', 'baus', 'gerenciamento-afiliados', 'gateway', 'chavespix', 'niveis',
    'checklist', 'checkin', 'cupons', 'gerenciamento-nomes', 'atendimento', 'baixarpop',
    'alterapainel', 'webhooks', 'pixel', 'roleta_boas_vindas', 'roleta', 'envelope_vermelho',
    'mensagens', 'tutorial',
];
$nav_temas_pages = [
    'identidade-visual', 'modal', 'banners', 'promocoes', 'temas', 'iconesfloat',
    'popups', 'festival', 'jackpot', 'notificacoes',
];
$nav_depositos_pages = ['depositos_pagos', 'depositos_pendentes', 'depositos_expirados'];
$nav_saques_pages = ['saques_aprovados', 'saques_pendentes', 'saques_recusados'];
$nav_saques_aff_pages = ['saques_afiliados_aprovados', 'saques_afiliados_pendentes', 'saques_afiliados_recusados'];
$nav_users_pages = ['usuarios', 'afiliados', 'contas-demos', 'administradores'];
$nav_historicos_pages = ['historicosplay', 'logsbonus', 'niveislogs'];
$nav_api_pages = ['api', 'jogos', 'provedores', 'chavesplayfiver', 'gamesplayfiver', 'gamesbeeplay', 'igamewinjogos'];
?>
<div class="startbar d-print-none">
    <style>
        .startbar{position:fixed;top:0;left:0;height:100vh;width:230px;background:#ffffff;border-right:1px solid #e5e7eb;box-shadow:0 0 30px rgba(15,23,42,.06);z-index:1040;display:flex;flex-direction:column}
        .startbar .brand{padding:18px 16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;background:#fff}
        .startbar .logo-sm{max-height:40px;object-fit:contain}
        .startbar-menu{flex:1;overflow:auto;padding:8px 10px;scrollbar-width:none;-ms-overflow-style:none}
        .startbar-menu::-webkit-scrollbar{display:none;width:0;height:0}
        .startbar .simplebar-content-wrapper{scrollbar-width:none;-ms-overflow-style:none}
        .startbar .simplebar-content-wrapper::-webkit-scrollbar{display:none;width:0;height:0}
        .startbar .simplebar-track{display:none!important}
        .startbar-footer-card{padding:8px 10px 12px}
        .startbar .menu-label{padding:12px 4px 4px}
        .startbar .menu-label span{display:block;font-size:10px;font-weight:600;letter-spacing:.08em;color:#94a3b8}
        .startbar .nav-item{margin:1px 0;border-radius:8px}
        .startbar .nav-link{color:#0f172a;display:flex;align-items:center;gap:7px;padding:7px 9px;font-size:12px;font-weight:500;border-radius:8px}
        .startbar .nav-link .menu-icon{font-size:17px;color:#64748b}
        .startbar .nav-link span{flex:1}
        .startbar .nav-link:hover,.startbar .nav-link:focus{background:#f1f5f9;color:#0f172a}
        .startbar .nav-link:hover .menu-icon{color:var(--bs-primary)}
        .startbar .collapse .nav-link{padding-left:32px;font-size:12px}
        .startbar .badge{vertical-align:middle;display:inline-flex;align-items:center;justify-content:center;font-size:9px;height:16px;line-height:16px;padding:1px 6px;border-radius:999px}
        .startbar .trail{margin-left:auto;display:inline-flex;align-items:center;gap:6px}
        .startbar .chev{font-size:14px;color:#94a3b8;transition:transform .2s ease}
        .startbar .nav-link[aria-expanded="true"] .chev{transform:rotate(90deg);color:var(--bs-primary)}
        .startbar .nav-link.active{background:#dbeafe;color:#1e40af;font-weight:600;border-radius:8px}
        .startbar .nav-link.active .menu-icon,.startbar .nav-link.active>i:not(.chev){color:var(--bs-primary)!important}
        html[data-bs-theme=dark] .startbar .nav-link.active{background:rgba(93,135,255,.18)!important;color:#fff!important;font-weight:600}
        .startbar .border-dashed-bottom{border-bottom:1px dashed #e5e7eb;margin:10px 4px}
        body.startbar-open{padding-left:230px}
        .startbar-overlay{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:1035;opacity:0;visibility:hidden;transition:opacity .25s ease,visibility .25s ease}
        .startbar.show + .startbar-overlay{opacity:1;visibility:visible}
        @media(min-width:992px){.startbar-overlay{display:none}}
        @media(max-width:992px){.startbar{transform:translateX(-100%);transition:transform .25s ease}.startbar.show{transform:translateX(0)}body.startbar-open{padding-left:0}}
    </style>
    <script>
        (function(){
            var startbar=document.querySelector('.startbar');
            function syncDesktop(){
                if(!startbar)return;
                if(window.innerWidth>=992){
                    startbar.classList.add('show');
                    document.body.classList.add('startbar-open');
                }else{
                    startbar.classList.remove('show');
                    document.body.classList.remove('startbar-open');
                }
            }
            syncDesktop();
            window.addEventListener('resize',syncDesktop);
            var btn=document.getElementById('togglemenu');
            if(btn){
                btn.addEventListener('click',function(e){
                    if(!startbar)return;
                    if(window.innerWidth<992){
                        var isOpen=startbar.classList.toggle('show');
                        if(isOpen){
                            document.body.classList.add('startbar-open');
                        }else{
                            document.body.classList.remove('startbar-open');
                        }
                    }
                });
            }
            document.addEventListener('click',function(e){
                if(!startbar)return;
                if(window.innerWidth>=992)return;
                var isOpen=startbar.classList.contains('show');
                if(!isOpen)return;
                var toggle=document.getElementById('togglemenu');
                var clickInsideStartbar=startbar.contains(e.target);
                var clickOnToggle=toggle&&toggle.contains(e.target);
                if(!clickInsideStartbar&&!clickOnToggle){
                    startbar.classList.remove('show');
                    document.body.classList.remove('startbar-open');
                }
            });
        })();
    </script>
    <div class="brand">
        <a href="index.php" class="logo">
            <span>
                <img src="../uploads/<?= $dataconfig['logo'] ?>" alt="logo-small" class="logo-sm">
            </span>
            <span class=""></span>
        </a>
    </div>
    
    <div class="startbar-menu">
        <div class="startbar-collapse" id="startbarCollapse" data-simplebar>
            <div class="d-flex align-items-start flex-column w-100">
                <ul class="navbar-nav mb-auto w-100">
                    
                    <li class="menu-label pt-0 mt-0">
                        <span><?= admin_t('menu_reports') ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= admin_nav_link_class('dashboard') ?>" href="dashboard">
                            <i class="iconoir-home-simple menu-icon"></i>
                            <span><?= admin_t('menu_dashboard') ?></span>
                        </a>
                    </li>

                    
                    
                    <li class="menu-label mt-2">
                        <small class="label-border">
                            <div class="border_left hidden-xs"></div>
                            <div class="border_right"></div>
                        </small>
                        <span><?= admin_t('menu_platform') ?></span>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#sidebarMaps" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_settings_pages) ?>" aria-controls="sidebarMaps">
                            <i class="iconoir-html5 menu-icon"></i>
                            <span><?= admin_t('menu_settings') ?></span><span class="trail"><i class="ti ti-chevron-right chev"></i></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_settings_pages)) ?>" id="sidebarMaps">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="configuracoes"><i class="ti ti-settings"></i><span><?= admin_t('menu_values') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="baus"><i class="ti ti-file-text"></i><span><?= admin_t('menu_affiliates_settings') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="gerenciamento-afiliados"><i class="ti ti-affiliate"></i><span><?= admin_t('menu_affiliate_management') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="gateway"><i class="ti ti-credit-card"></i><span><?= admin_t('menu_payments') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="chavespix"><i class="ti ti-key"></i><span><?= admin_t('menu_pix_keys') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="niveis"><i class="ti ti-stars"></i><span><?= admin_t('menu_vips') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="checklist"><i class="ti ti-circle-check"></i><span><?= admin_t('menu_daily_checklist') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="checkin"><i class="ti ti-calendar-check"></i><span><?= admin_t('menu_checkin') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="cupons"><i class="ti ti-ticket"></i><span><?= admin_t('menu_coupons') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="gerenciamento-nomes"><i class="ti ti-users"></i><span><?= admin_t('menu_names') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="atendimento"><i class="ti ti-headset"></i><span><?= admin_t('menu_support_channels') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="baixarpop"><i class="ti ti-download"></i><span><?= admin_t('menu_app_download') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="alterapainel"><i class="ti ti-layout"></i><span><?= admin_t('menu_change_panel') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="webhooks"><i class="ti ti-hierarchy-2"></i><span><?= admin_t('menu_webhooks') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="pixel"><i class="ti ti-chart-arcs"></i><span><?= admin_t('menu_pixels') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="roleta_boas_vindas"><i class="ti ti-360-view"></i><span>Roleta Boas-Vindas</span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="roleta"><i class="ti ti-rotate-clockwise"></i><span><?= admin_t('menu_wheel_config') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="envelope_vermelho"><i class="ti ti-gift"></i><span>Envelope Vermelho</span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="mensagens"><i class="ti ti-message"></i><span><?= admin_t('menu_messages') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="tutorial"><i class="ti ti-book"></i><span><?= admin_t('menu_tutorial') ?></span></a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#temas" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_temas_pages) ?>" aria-controls="temas">
                            <i class="iconoir-design-pencil menu-icon"></i>
                            <span><?= admin_t('menu_customization') ?></span><span class="trail"><i class="ti ti-chevron-right chev"></i><span class="badge rounded text-danger bg-danger-subtle">(new)</span></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_temas_pages)) ?>" id="temas">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="identidade-visual"><i class="ti ti-photo"></i><span><?= admin_t('menu_platform_images') ?></span></a>
                                </li>
                            </ul>
                             <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="modal"><i class="ti ti-app-window"></i><span><?= admin_t('menu_modals') ?></span></a>
                                </li>
                            </ul>
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="banners"><i class="ti ti-photo"></i><span><?= admin_t('menu_banners') ?></span></a>
                                </li>
                            </ul>
                            
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="promocoes"><i class="ti ti-percentage"></i><span><?= admin_t('menu_promotions') ?></span></a>
                                </li>
                            </ul>
                            
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="temas"><i class="ti ti-palette"></i><span><?= admin_t('menu_themes') ?></span></a>
                                </li>
                            </ul>
                            
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="iconesfloat"><i class="ti ti-pin"></i><span><?= admin_t('menu_float_icons') ?></span></a>
                                </li>
                            </ul>
                            
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="popups"><i class="ti ti-app-window"></i><span><?= admin_t('menu_popups') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="festival"><i class="ti ti-confetti"></i><span><?= admin_t('menu_festival') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="jackpot"><i class="ti ti-trophy"></i><span><?= admin_t('menu_jackpot') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="notificacoes"><i class="ti ti-bell"></i><span><?= admin_t('menu_general_notifications') ?></span></a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    
                    
                    <li class="menu-label mt-2">
                        <small class="label-border">
                            <div class="border_left hidden-xs"></div>
                            <div class="border_right"></div>
                        </small>
                        <span><?= admin_t('menu_finance') ?></span>
                    </li>
                    
                    <?php
                    $query_depositos_processamento = "SELECT COUNT(*) as total_processamento FROM transacoes WHERE status = 'processamento'";
                    $result_depositos_processamento = mysqli_query($mysqli, $query_depositos_processamento);
                    $row_depositos_processamento = mysqli_fetch_assoc($result_depositos_processamento);
                    $total_depositos_processamento = $row_depositos_processamento['total_processamento'];
                    
                    $query_depositos_aprovados = "SELECT COUNT(*) as total_aprovados FROM transacoes WHERE status = 'pago'";
                    $result_depositos_aprovados = mysqli_query($mysqli, $query_depositos_aprovados);
                    $row_depositos_aprovados = mysqli_fetch_assoc($result_depositos_aprovados);
                    $total_depositos_aprovados = $row_depositos_aprovados['total_aprovados'];
                    
                    $query_depositos_recusados = "SELECT COUNT(*) as total_recusados FROM transacoes WHERE status = 'expirado'";
                    $result_depositos_recusados = mysqli_query($mysqli, $query_depositos_recusados);
                    $row_depositos_recusados = mysqli_fetch_assoc($result_depositos_recusados);
                    $total_depositos_recusados = $row_depositos_recusados['total_recusados'];
                    
                    $total_depositos = $total_depositos_processamento + $total_depositos_aprovados + $total_depositos_recusados;
                    ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#sidebarElements" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_depositos_pages) ?>" aria-controls="sidebarElements">
                            <i class="iconoir-receive-dollars menu-icon"></i>
                            <span><?= admin_t('menu_deposits') ?></span><span class="trail"><i class="ti ti-chevron-right chev"></i><span class="badge rounded text-warning bg-warning-subtle"><?= $total_depositos; ?></span></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_depositos_pages)) ?>" id="sidebarElements">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="depositos_pagos"><i class="ti ti-circle-check"></i><span><?= admin_t('menu_paid') ?></span> <span class="badge rounded text-success bg-success-subtle ms-1"><?= $total_depositos_aprovados; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="depositos_pendentes"><i class="ti ti-hourglass"></i><span><?= admin_t('menu_pending') ?></span> <span class="badge rounded text-warning bg-warning-subtle ms-1"><?= $total_depositos_processamento; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="depositos_expirados">
                                        <i class="ti ti-circle-x"></i><span><?= admin_t('menu_expired') ?></span>
                                        <span class="badge rounded text-danger bg-danger-subtle ms-1"><?= $total_depositos_recusados; ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <?php
                    $query_saques_pendentes = "SELECT COUNT(*) as total_pendentes FROM solicitacao_saques WHERE status = '0' AND tipo_saque = '0'";
                    $result_saques_pendentes = mysqli_query($mysqli, $query_saques_pendentes);
                    $row_saques_pendentes = mysqli_fetch_assoc($result_saques_pendentes);
                    $total_saques_pendentes = $row_saques_pendentes['total_pendentes'];
                    
                    $query_saques_aprovados = "SELECT COUNT(*) as total_aprovados FROM solicitacao_saques WHERE status = '1' AND tipo_saque = '0'";
                    $result_saques_aprovados = mysqli_query($mysqli, $query_saques_aprovados);
                    $row_saques_aprovados = mysqli_fetch_assoc($result_saques_aprovados);
                    $total_saques_aprovados = $row_saques_aprovados['total_aprovados'];
                    
                    $query_saques_recusados = "SELECT COUNT(*) as total_recusados FROM solicitacao_saques WHERE status = '2' AND tipo_saque = '0'";
                    $result_saques_recusados = mysqli_query($mysqli, $query_saques_recusados);
                    $row_saques_recusados = mysqli_fetch_assoc($result_saques_recusados);
                    $total_saques_recusados = $row_saques_recusados['total_recusados'];
                    
                    $total_saques = $total_saques_pendentes + $total_saques_aprovados + $total_saques_recusados;
                    ?>
                    
                    <?php
                    $query_saques_afiliados_pendentes = "SELECT COUNT(*) as total_pendentes FROM solicitacao_saques WHERE status = '0' AND tipo_saque = '1'";
                    $result_saques_afiliados_pendentes = mysqli_query($mysqli, $query_saques_afiliados_pendentes);
                    $row_saques_afiliados_pendentes = mysqli_fetch_assoc($result_saques_afiliados_pendentes);
                    $total_saques_afiliados_pendentes = $row_saques_afiliados_pendentes['total_pendentes'];
                    
                    $query_saques_afiliados_aprovados = "SELECT COUNT(*) as total_aprovados FROM solicitacao_saques WHERE status = '1' AND tipo_saque = '1'";
                    $result_saques_afiliados_aprovados = mysqli_query($mysqli, $query_saques_afiliados_aprovados);
                    $row_saques_afiliados_aprovados = mysqli_fetch_assoc($result_saques_afiliados_aprovados);
                    $total_saques_afiliados_aprovados = $row_saques_afiliados_aprovados['total_aprovados'];
                    
                    $query_saques_afiliados_recusados = "SELECT COUNT(*) as total_recusados FROM solicitacao_saques WHERE status = '2' AND tipo_saque = '1'";
                    $result_saques_afiliados_recusados = mysqli_query($mysqli, $query_saques_afiliados_recusados);
                    $row_saques_afiliados_recusados = mysqli_fetch_assoc($result_saques_afiliados_recusados);
                    $total_saques_afiliados_recusados = $row_saques_afiliados_recusados['total_recusados'];
                    
                    $total_saques_afiliados = $total_saques_afiliados_pendentes + $total_saques_afiliados_aprovados + $total_saques_afiliados_recusados;
                    ?>

                    <li class="nav-item">
                        <a class="nav-link" href="#sidebarAdvancedUI" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_saques_pages) ?>" aria-controls="sidebarAdvancedUI">
                            <i class="iconoir-send-dollars menu-icon"></i>
                            <span><?= admin_t('menu_withdrawals') ?></span>
                            <span class="trail"><i class="ti ti-chevron-right chev"></i><span class="badge rounded text-warning bg-warning-subtle"><?= $total_saques; ?></span></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_saques_pages)) ?>" id="sidebarAdvancedUI">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="saques_aprovados"><i class="ti ti-circle-check"></i><span><?= admin_t('menu_paid') ?></span>
                                        <span class="badge rounded text-success bg-success-subtle ms-1"><?= $total_saques_aprovados; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="saques_pendentes"><i class="ti ti-hourglass"></i><span><?= admin_t('menu_pending') ?></span>
                                        <span class="badge rounded text-warning bg-warning-subtle ms-1"><?= $total_saques_pendentes; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="saques_recusados"><i class="ti ti-circle-x"></i><span><?= admin_t('menu_refused') ?></span>
                                    <span class="badge rounded text-danger bg-danger-subtle ms-1"><?= $total_saques_recusados; ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#sidebarAffiliateWithdrawals" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_saques_aff_pages) ?>" aria-controls="sidebarAffiliateWithdrawals">
                            <i class="iconoir-hand-cash menu-icon"></i>
                            <span><?= admin_t('menu_affiliate_withdrawals') ?></span>
                            <span class="trail"><i class="ti ti-chevron-right chev"></i><span class="badge rounded text-warning bg-warning-subtle"><?= $total_saques_afiliados; ?></span></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_saques_aff_pages)) ?>" id="sidebarAffiliateWithdrawals">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="saques_afiliados_aprovados"><i class="ti ti-circle-check"></i><span><?= admin_t('menu_paid') ?></span>
                                        <span class="badge rounded text-success bg-success-subtle ms-1"><?= $total_saques_afiliados_aprovados; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="saques_afiliados_pendentes"><i class="ti ti-hourglass"></i><span><?= admin_t('menu_pending') ?></span>
                                        <span class="badge rounded text-warning bg-warning-subtle ms-1"><?= $total_saques_afiliados_pendentes; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="saques_afiliados_recusados"><i class="ti ti-circle-x"></i><span><?= admin_t('menu_refused') ?></span>
                                        <span class="badge rounded text-danger bg-danger-subtle ms-1"><?= $total_saques_afiliados_recusados; ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    
                    
                    <li class="menu-label mt-2">
                        <small class="label-border">
                            <div class="border_left hidden-xs"></div>
                            <div class="border_right"></div>
                        </small>
                        <span><?= admin_t('menu_users_section') ?></span>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#sidebarForms" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_users_pages) ?>" aria-controls="sidebarForms">
                            <i class="iconoir-community menu-icon"></i>
                            <span><?= admin_t('users') ?></span><span class="trail"><i class="ti ti-chevron-right chev"></i></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_users_pages)) ?>" id="sidebarForms">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="usuarios"><i class="ti ti-users"></i><span><?= admin_t('menu_all_users') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="afiliados"><i class="ti ti-users"></i><span><?= admin_t('menu_all_influencers') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="contas-demos"><i class="ti ti-device-gamepad-2"></i><span><?= admin_t('menu_create_demo_account') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="administradores"><i class="ti ti-shield-lock"></i><span><?= admin_t('operators') ?></span></a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="menu-label mt-2">
                        <small class="label-border">
                            <div class="border_left hidden-xs"></div>
                            <div class="border_right"></div>
                        </small>
                        <span><?= admin_t('menu_history_section') ?></span>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#historicos" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_historicos_pages) ?>" aria-controls="historicos">
                            <span><?= admin_t('menu_histories') ?></span><span class="trail"><i class="ti ti-chevron-right chev"></i></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_historicos_pages)) ?>" id="historicos">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="historicosplay"><i class="ti ti-device-gamepad-2"></i><span><?= admin_t('menu_bets') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="logsbonus"><i class="ti ti-percentage"></i><span><?= admin_t('menu_bonus') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="niveislogs"><i class="ti ti-stars"></i><span><?= admin_t('menu_levels') ?></span></a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    
                    
                    <li class="menu-label mt-2">
                        <small class="label-border">
                            <div class="border_left hidden-xs"></div>
                            <div class="border_right"></div>
                        </small>
                        <span><?= admin_t('menu_games_section') ?></span>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#chavesapi" data-bs-toggle="collapse" role="button"
                            aria-expanded="<?= admin_nav_expanded($nav_api_pages) ?>" aria-controls="chavesapi">
                            <i class="iconoir-key-plus menu-icon"></i>
                            <span><?= admin_t('menu_api_games') ?></span><span class="trail"><i class="ti ti-chevron-right chev"></i></span>
                        </a>
                        <div class="<?= trim(admin_nav_collapse_class($nav_api_pages)) ?>" id="chavesapi">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="api"><i class="ti ti-shield-lock"></i><span><?= admin_t('menu_credentials') ?></span></a>
                                </li>
                            </ul>
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="jogos"><i class="ti ti-device-gamepad-2"></i><span><?= admin_t('menu_games') ?></span></a>
                                </li>
                            </ul>
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="provedores"><i class="ti ti-server"></i><span><?= admin_t('menu_providers') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="chavesplayfiver"><i class="ti ti-key"></i><span><?= admin_t('menu_playfiver_credentials') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="gamesplayfiver"><i class="ti ti-device-gamepad"></i><span><?= admin_t('menu_playfiver_games') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="gamesbeeplay"><i class="ti ti-bee"></i><span><?= admin_t('menu_beeplay_games') ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="igamewinjogos"><i class="ti ti-device-gamepad-2"></i><span><?= admin_t('menu_igamewin_games') ?></span></a>
                                </li>
                            </ul>
                        </div>
                    </li>

                </ul>

            </div>
        </div>
    </div>

    <script>
        (function(){
            var slugMap={
                index:'dashboard',pixeis:'pixel','ge-nomes':'gerenciamento-nomes','ge-cupons':'cupons','ge-vips':'niveis',
                'editar-banners':'banners','editar-popups':'popups','editar-festival':'festival','editar-floats':'iconesfloat',
                'editar-promocoes':'promocoes','editar-mensagens':'mensagens','imagens-plataforma':'identidade-visual',
                contasdemos:'contas-demos',historico_jogadas:'historicosplay',logs_cupons:'logsbonus',logs_niveis:'niveislogs',
                webhook:'webhooks','popup-baixar':'baixarpop',beeplay:'gamesbeeplay',pfiverapi:'chavesplayfiver',
                detalhes_usuario:'usuarios',checkin_config:'checkin'
            };
            function highlightStartbarNav(){
                var path=window.location.pathname.replace(/\/+$/,'');
                var slug=(path.split('/').pop()||'').replace(/\.php$/,'')||'dashboard';
                if(slugMap[slug])slug=slugMap[slug];
                if(slug==='admin'||slug==='')slug='dashboard';

                document.querySelectorAll('.startbar a.nav-link[href]').forEach(function(link){
                    var href=(link.getAttribute('href')||'').split('?')[0];
                    if(!href||href.charAt(0)==='#')return;
                    var linkSlug=href.split('/').pop();
                    if(linkSlug!==slug)return;

                    link.classList.add('active');

                    var panel=link.closest('.collapse');
                    if(!panel)return;

                    panel.classList.add('show');
                    panel.style.height='auto';

                    var toggle=document.querySelector('.startbar a.nav-link[href="#'+panel.id+'"]');
                    if(!toggle)return;

                    toggle.setAttribute('aria-expanded','true');
                });
            }

            highlightStartbarNav();
        })();
    </script>
    
</div>
<div class="startbar-overlay d-print-none"></div>
