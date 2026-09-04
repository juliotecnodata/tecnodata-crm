</div>
</main>
<nav class="mobile-nav">
 <?php if(Tecnodata\CRM\Core\Auth::can('collector')):?>
   <a href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-hand-holding-dollar"></i><span>Carteira</span></a>
   <a href="<?=APP_URL?>/cobranca-agenda.php"><i class="fa-regular fa-calendar-check"></i><span>Agenda</span></a>
   <a href="<?=APP_URL?>/cobranca-atendimentos.php"><i class="fa-solid fa-clock-rotate-left"></i><span>Histórico</span></a>
   <a href="<?=APP_URL?>/resultado.php"><i class="fa-solid fa-chart-line"></i><span>Resultado</span></a>
 <?php elseif(Tecnodata\CRM\Core\Auth::can('admin','supervisor')):?>
   <a href="<?=APP_URL?>/gestao.php"><i class="fa-solid fa-gauge-high"></i><span>Painel</span></a>
   <a href="<?=APP_URL?>/clientes.php"><i class="fa-solid fa-users"></i><span>Clientes</span></a>
   <a class="mobile-nav-primary" href="<?=APP_URL?>/pedido-novo.php"><i class="fa-solid fa-plus"></i><span>Pedido</span></a>
   <a href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-hand-holding-dollar"></i><span>Cobrança</span></a>
 <?php else:?>
   <a href="<?=APP_URL?>/index.php"><i class="fa-solid fa-bolt"></i><span>Hoje</span></a>
   <a href="<?=APP_URL?>/carteira.php"><i class="fa-solid fa-users"></i><span>Clientes</span></a>
   <?php if($navHasSales):?><a class="mobile-nav-primary" href="<?=APP_URL?>/pedido-novo.php"><i class="fa-solid fa-plus"></i><span>Pedido</span></a><?php else:?><a href="<?=APP_URL?>/agenda.php"><i class="fa-regular fa-calendar-check"></i><span>Agenda</span></a><?php endif;?>
   <a href="<?=APP_URL?>/resultado.php"><i class="fa-solid fa-chart-line"></i><span>Resultado</span></a>
 <?php endif;?>
</nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/3.0.3/js/dataTables.min.js"></script>
<script>
window.TDCRM_CONFIG={
 baseUrl:<?=json_encode(APP_URL,JSON_UNESCAPED_SLASHES)?>,
 csrf:<?=json_encode(Tecnodata\CRM\Core\Security::csrf())?>,
 notificationPollSeconds:<?=json_encode((int)($GLOBALS['config']['alerts']['poll_seconds']??60))?>
};
</script>
<script src="<?=APP_URL?>/assets/js/app.js?v=<?=rawurlencode((string)@filemtime(APP_ROOT.'/public/assets/js/app.js'))?>"></script>
</body></html>
