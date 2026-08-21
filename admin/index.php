<?php include 'partials/html.php' ?>

<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

session_start();
include_once "services/database.php";
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
include_once "l.php";
$csrf = new CSRF_Protect();

checa_login_adm();

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    echo "<script>setTimeout(function() { 
        var modal = new bootstrap.Modal(document.getElementById('modal2FA'));
        modal.show();
    }, 500);</script>";
}

$depositos_dias = depositos_por_dia();
$saques_dias = saques_por_dia();
$data = qtd_usuarios(); 
$labels_depositos = json_encode(array_column($depositos_dias, 'dia'));
$dados_depositos = json_encode(array_column($depositos_dias, 'total'));
$labels_saques = json_encode(array_column($saques_dias, 'dia'));
$dados_saques = json_encode(array_column($saques_dias, 'total'));

$total_online = get_online_count();

?>

<head>
    <?php $title = "dash";
    include 'partials/title-meta.php' ?>
    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
    
</head>

<style>

/* FUNDO */
body{
    background: radial-gradient(circle at top,#0f172a,#020617);
    color:#e2e8f0;
    font-family: 'Inter', sans-serif;
}

/* PARTICULAS */
#particles-js{
    position:fixed;
    width:100%;
    height:100%;
    z-index:-1;
}

/* CARDS */
.card{
    background: rgba(15,23,42,.7);
    backdrop-filter: blur(14px);
    border:1px solid rgba(255,255,255,.05);
    border-radius:16px;
    transition:.35s;
}

/* HOVER */
.card:hover{
    transform: translateY(-6px);
    box-shadow:0 20px 50px rgba(0,0,0,.7);
}

/* BORDA NEON */
.card-accent-left{
    border-left:4px solid #22c55e;
}

/* TITULOS */
.stat-title{
    color:#94a3b8;
}

/* VALORES */
.stat-value{
    font-size:26px;
    font-weight:700;
    color:#22c55e;
}

/* ICONES */
.thumb-xl{
    background:linear-gradient(135deg,#1e293b,#020617);
    box-shadow:0 0 20px rgba(34,197,94,.4);
}

/* TABELAS */
.table{
    color:#e2e8f0;
}

.table-light{
    background:#020617;
}

/* MODAL */
.modal-content{
    background:rgba(2,6,23,.9);
    backdrop-filter:blur(15px);
    border:1px solid rgba(255,255,255,.05);
}

/* BOTÃO */
.btn-primary{
    background:linear-gradient(90deg,#22c55e,#16a34a);
    border:none;
}

.btn-primary:hover{
    background:linear-gradient(90deg,#16a34a,#15803d);
}

/* SCROLL */
::-webkit-scrollbar{
    width:8px;
}

::-webkit-scrollbar-track{
    background:#020617;
}

::-webkit-scrollbar-thumb{
    background:#22c55e;
    border-radius:20px;
}

</style>

<style>

/* FUNDO FUTURISTA */
body{
    background: radial-gradient(circle at top, #0f172a, #020617);
    color:#e2e8f0;
}

/* CARDS MODERNOS */
.card{
    background: rgba(15,23,42,0.7);
    backdrop-filter: blur(12px);
    border:1px solid rgba(255,255,255,0.05);
    border-radius:16px;
    transition: all .35s ease;
}

/* HOVER NOS CARDS */
.card:hover{
    transform: translateY(-6px);
    box-shadow:0 15px 40px rgba(0,0,0,0.6);
}

/* LINHA LATERAL NEON */
.card-accent-left{
    border-left:4px solid #22c55e;
}

/* TITULOS */
.stat-title{
    color:#94a3b8;
    font-size:13px;
    letter-spacing:.4px;
}

/* VALORES */
.stat-value{
    font-weight:700;
    font-size:26px;
    color:#22c55e;
}

/* ICONES */
.thumb-xl{
    background:linear-gradient(135deg,#1e293b,#020617);
    box-shadow:0 0 15px rgba(34,197,94,.3);
}

/* TABELAS */
.table{
    color:#e2e8f0;
}

.table thead{
    background:#020617;
}

.table tbody tr:hover{
    background:rgba(255,255,255,0.03);
}

/* MODAL 2FA */
.modal-content{
    background:rgba(2,6,23,.95);
    border:1px solid rgba(255,255,255,0.05);
    backdrop-filter: blur(15px);
    border-radius:14px;
}

/* BOTÕES */
.btn-primary{
    background:linear-gradient(90deg,#22c55e,#16a34a);
    border:none;
}

.btn-primary:hover{
    background:linear-gradient(90deg,#16a34a,#15803d);
}

/* GRAFICOS */
#chart-saques,
#chart-depositos{
    padding-top:10px;
}

/* SCROLLBAR */
::-webkit-scrollbar{
    width:8px;
}

::-webkit-scrollbar-track{
    background:#020617;
}

::-webkit-scrollbar-thumb{
    background:#22c55e;
    border-radius:20px;
}

</style>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="modal fade" id="modal2FA" tabindex="-1" aria-labelledby="modal2FALabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal2FALabel"><?= admin_t('twofa_title') ?></h5>
                </div>
                <div class="modal-body">
                    <div id="alert-2fa" class="alert alert-danger d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="alert-message"><?= admin_t('twofa_error') ?></span>
                    </div>
                    
                    <p><?= admin_t('twofa_instruction') ?></p>
                    <input type="text" id="token2fa" class="form-control" placeholder="<?= admin_t('twofa_placeholder') ?>" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="btn-submit-2fa">
                        <span id="btn-text"><?= admin_t('twofa_validate') ?></span>
                        <span id="btn-spinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                    </button>
                    </div>
                </div>
                </div>

                
    </div>

    <script>
    function showAlert(message, type = 'danger') {
        const alert = document.getElementById('alert-2fa');
        const alertMessage = document.getElementById('alert-message');
        
        alert.className = `alert alert-${type}`;
        alertMessage.textContent = message;
        alert.classList.remove('d-none');
        
        setTimeout(() => {
            alert.classList.add('d-none');
        }, 5000);
    }
    
    function hideAlert() {
        document.getElementById('alert-2fa').classList.add('d-none');
    }
    
    function setLoading(loading) {
        const btn = document.getElementById('btn-submit-2fa');
        const btnText = document.getElementById('btn-text');
        const btnSpinner = document.getElementById('btn-spinner');
        
        if (loading) {
            btn.disabled = true;
            btnText.textContent = '<?= admin_t('twofa_validating') ?>';
            btnSpinner.classList.remove('d-none');
        } else {
            btn.disabled = false;
            btnText.textContent = '<?= admin_t('twofa_validate') ?>';
            btnSpinner.classList.add('d-none');
        }
    }
    
    document.getElementById('btn-submit-2fa').addEventListener('click', function() {
        var token2fa = document.getElementById('token2fa').value.trim();
        
        hideAlert();
        
        if (token2fa === '') {
            showAlert('Por favor, insira o token 2FA!');
            document.getElementById('token2fa').focus();
            return;
        }
        
        setLoading(true);
        
        fetch('validar_2fa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'token=' + encodeURIComponent(token2fa)
            })
            .then(response => response.json())
            .then(data => {
                setLoading(false);
                
                if (data.success) {
                    showAlert('Token validado com sucesso!', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showAlert(data.message || 'Token inválido. Tente novamente!');
                    document.getElementById('token2fa').value = '';
                    document.getElementById('token2fa').focus();
                }
            })
            .catch(error => {
                setLoading(false);
                showAlert('Erro de conexão. Tente novamente!');
            });
    });
    
    document.getElementById('token2fa').addEventListener('input', function() {
        hideAlert();
    });
    
    document.getElementById('token2fa').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('btn-submit-2fa').click();
        }
    });
    </script>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">

                      <div class="col-md-6 col-lg-4">
                        <div class="card rounded-4 card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="mb-0 fw-semibold fs-14 stat-title"><?= admin_t('dash_users_online') ?></p>
                                        <h3 class="mt-2 mb-0 stat-value" id="online-count"><?= $_SESSION['2fa_verified'] == true ? (int)$total_online : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-user h1 align-self-center mb-0"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_total_registrations') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? qtd_usuarios() : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-group h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($_SESSION['2fa_verified'] == true) { ?>
                    <script>
                    (function(){
                        function refreshAdminOnline(){
                            fetch('/api/v1/online_ping?count=1')
                                .then(function(r){return r.json()})
                                .then(function(d){
                                    if(d && d.success){
                                        var el = document.getElementById('online-count');
                                        if(el){ el.textContent = d.count; }
                                    }
                                })
                                .catch(function(){});
                        }
                        refreshAdminOnline();
                        setInterval(refreshAdminOnline, 60000);
                    })();
                    </script>
                    <?php } ?>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_today_registrations') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? qtd_usuarios_diarios() : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-user-plus h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_90d_registrations') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? qtd_usuarios_90d() : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-user-plus h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_total_user_balance') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? "R$ ". Reais2(total_saldos_usuarios()) : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-wallet h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_profit') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? "R$ ". Reais2(saldo_cassino()) : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-graph-up h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['data_adm']['email'] == 'vxciian@gmail.com'){ ?>
                    <?php } else { ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_deposits_no_link') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? "R$ ". Reais2(depositos_totalsemlink()) : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-graph-up h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_deposits_bloggers') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? "R$ ". Reais2(depositos_blogueiros()) : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-hand-cash h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_withdrawals_bloggers') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? "R$ ". Reais2(saques_total()) : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-graph-down h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_withdrawals_no_link') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? "R$ ". Reais2(saques_totalsemlink()) : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i class="iconoir-hand-cash h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card card-accent-left">
                            <div class="card-body p-3">
                                <div class="row d-flex justify-content-center align-items-center">
                                    <div class="col-9">
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_total_access') ?></p>
                                        <h3 class="mt-2 mb-0 fw-bold"><?= $_SESSION['2fa_verified'] == true ? visitas_count('total') : "Token não informado." ?></h3>
                                    </div>
                                    <div class="col-3 align-self-center">
                                        <div
                                            class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                            <i
                                                class="iconoir-server-connection h1 align-self-center mb-0 text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="col-md-6 col-lg-4"> 
                        <div class="card card-accent-left rounded-4"> 
                            <div class="card-body p-3"> 
                                <div class="row d-flex justify-content-center align-items-center"> 
                                    <div class="col-9"> 
                                        <p class="text-dark mb-0 fw-semibold fs-14"><?= admin_t('dash_most_accessed_place') ?></p> 
                                        <?php 
                                        $lugar_mais_acessado = visitas_count2('total'); 
                                        ?> 
                                        <h3 class="mt-2 mb-0 fw-bold"> 
                                        <?php 
                                        if ($lugar_mais_acessado['cidade'] && $lugar_mais_acessado['estado']) { 
                                            echo $lugar_mais_acessado['cidade'] . ', ' . $lugar_mais_acessado['estado']; 
                                        } elseif (!empty($lugar_mais_acessado['mac_os'])) { 
                                            echo $lugar_mais_acessado['mac_os']; 
                                        } else { 
                                            echo admin_t('dash_no_data'); 
                                        } 
                                        ?> 
                                        </h3> 
                                    </div> 
                                    <div class="col-3 align-self-center"> 
                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto"> 
                                            <i class="iconoir-user h1 align-self-center mb-0 text-secondary"></i> 
                                        </div> 
                                    </div> 
                                </div> 
                            </div> 
                        </div> 
                    </div>

                </div>

                <div class="row mt-4">

                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="card card-accent-left">
                            <div class="card-body">
                                <h5 class="card-title"><?= admin_t('dash_daily_withdrawals') ?></h5>
                                <div id="chart-saques"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <div class="card card-accent-left">
                            <div class="card-body">
                                <h5 class="card-title"><?= admin_t('dash_daily_deposits') ?></h5>
                                <div id="chart-depositos"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card card-h-100 card-accent-left">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h4 class="card-title"><?= admin_t('dash_approved_withdrawals') ?></h4>
                                        <p class="fs-11 fst-bold text-muted"><?= admin_t('dash_approved_withdrawals_subtitle') ?><a href="#!"
                                                class="link-danger ms-1"><i
                                                    class="align-middle iconoir-refresh"></i></a></p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive browser_users">
                                    <table class="table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-top-0"><?= admin_t('dash_table_id') ?></th>
                                                <th class="border-top-0"><?= admin_t('dash_table_user') ?></th>
                                                <th class="border-top-0"><?= admin_t('dash_table_datetime') ?></th>
                                                <th class="border-top-0"><?= admin_t('dash_table_amount') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            include_once 'services/database.php';
                                            include_once 'services/funcao.php';
                                            include_once 'services/crud.php';
                                            include_once 'services/crud-adm.php';
                                            include_once 'services/checa_login_adm.php';
                                            checa_login_adm();

                                            global $mysqli;
                                            $pagina = 1; // Página atual
                                            $qnt_result_pg = 5; // Quantidade de resultados por página
                                            $inicio = ($pagina * $qnt_result_pg) - $qnt_result_pg;

                                            $result_usuario = "SELECT * FROM solicitacao_saques WHERE status = '1' ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
                                            $resultado_usuario = mysqli_query($mysqli, $result_usuario);

                                            if ($resultado_usuario && mysqli_num_rows($resultado_usuario) > 0) {
                                                while ($data = mysqli_fetch_assoc($resultado_usuario)) {
                                                    $data_return = data_user_id($data['id_user']);
                                                    ?>
                                            <tr>
                                                <td><?= $data['id']; ?></td>
                                                <td><?= $data_return['mobile']; ?></td>
                                                <td><?= ver_data($data['data_registro']); ?></td>
                                                <td>R$ <?= Reais2($data['valor']); ?></td>
                                            </tr>
                                            <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center'>".admin_t('dash_no_rows')."</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card card-h-100 card-accent-left">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h4 class="card-title"><?= admin_t('dash_paid_deposits') ?></h4>
                                        <p class="fs-11 fst-bold text-muted"><?= admin_t('dash_paid_deposits_subtitle') ?><a href="#!"
                                                class="link-danger ms-1"><i
                                                    class="align-middle iconoir-refresh"></i></a></p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-top-0"><?= admin_t('dash_table_id') ?></th>
                                                <th class="border-top-0"><?= admin_t('dash_table_user') ?></th>
                                                <th class="border-top-0"><?= admin_t('dash_table_datetime') ?></th>
                                                <th class="border-top-0"><?= admin_t('dash_table_amount') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $result_visitas = "SELECT * FROM transacoes WHERE status = 'pago' ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
                                            $resultado_visitas = mysqli_query($mysqli, $result_visitas);

                                            if ($resultado_visitas && mysqli_num_rows($resultado_visitas) > 0) {
                                                while ($visit = mysqli_fetch_assoc($resultado_visitas)) {
                                                    // Define o ícone de mudança baseado no valor
                                                    $valorNumero = is_numeric($visit['valor']) ? (float)$visit['valor'] : 0;
                                                    $change_icon = ($valorNumero >= 0) ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger';
                                                    ?>
                                            <tr>
                                                <td><?= $visit['id']; ?></td>
                                                <td><?= $visit['usuario']; ?></td>
                                                <td><?= $visit['data_registro']; ?></td>
                                                <td><?= $visit['valor']; ?> <i class="fas <?= $change_icon; ?> font-16"></i></td>
                                            </tr>
                                            <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center'>".admin_t('dash_no_rows')."</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

           

            <?php include 'partials/endbar.php' ?>
            <?php include 'partials/footer.php' ?>
        </div>
    </div>
    <?php include 'partials/vendorjs.php' ?>

    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/data/stock-prices.js"></script>
    <script src="assets/libs/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/libs/jsvectormap/maps/world.js"></script>
    <script src="assets/js/pages/index.init.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js"></script>



    <script>
    var labelsDepositos = <?= $labels_depositos; ?>;
    var depositosData = <?= $dados_depositos; ?>;

    var labelsSaques = <?= $labels_saques; ?>;
    var saquesData = <?= $dados_saques; ?>;

    var optionsDepositos = {
        series: [{
            name: 'Depósitos',
            data: depositosData
        }],
        chart: {
            type: 'bar',
            height: 350
        },
        xaxis: {
            categories: labelsDepositos
        },
        plotOptions: {
            bar: {
                columnWidth: '5%',
                distributed: true // Dá a cada barra uma cor diferente
            }
        },
        colors: ['#28a745'], // Verde para depósitos
        dataLabels: {
            enabled: false // Oculta os rótulos de dados nas barras
        },
        tooltip: {
            enabled: true // Ativa o tooltip para aparecer ao passar o mouse
        },
        title: {
            //            text: 'Depósitos',
            align: 'left'
        }
    };

    var chartDepositos = new ApexCharts(document.querySelector("#chart-depositos"), optionsDepositos);
    chartDepositos.render();

    var optionsSaques = {
        series: [{
            name: 'Saques',
            data: saquesData
        }],
        chart: {
            type: 'bar',
            height: 350
        },
        xaxis: {
            categories: labelsSaques
        },
        plotOptions: {
            bar: {
                columnWidth: '5%',
                distributed: true
            }
        },
        colors: ['#dc3545'], // Vermelho para saques
        dataLabels: {
            enabled: false // Oculta os rótulos de dados nas barras
        },
        tooltip: {
            enabled: true // Ativa o tooltip para aparecer ao passar o mouse
        },
        title: {
            //            text: 'Saques',
            align: 'left'
        }
    };

    var chartSaques = new ApexCharts(document.querySelector("#chart-saques"), optionsSaques);
    chartSaques.render();
    </script>

</body>

</html>
