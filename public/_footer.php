</div>
</main>
<nav class="mobile-nav">
 <a href="<?=APP_URL?>/index.php"><i class="fa-solid fa-grid-2"></i><span>Início</span></a>
 <?php if(Tecnodata\CRM\Core\Auth::can('collector')):?><a href="<?=APP_URL?>/cobranca.php"><i class="fa-solid fa-hand-holding-dollar"></i><span>Cobrança</span></a><?php else:?><a href="<?=APP_URL?>/carteira.php"><i class="fa-solid fa-address-book"></i><span><?=$sellerMode==='collection'?'Cobrança':'Carteira'?></span></a><?php endif;?>
 <?php if(Tecnodata\CRM\Core\Auth::can('supervisor','admin')):?><a href="<?=APP_URL?>/vendedores.php"><i class="fa-solid fa-user-tie"></i><span>Vendedores</span></a><?php elseif(Tecnodata\CRM\Core\Auth::can('collector')):?><a href="<?=APP_URL?>/cobranca-agenda.php"><i class="fa-regular fa-calendar-check"></i><span>Agenda</span></a><?php else:?><a href="<?=APP_URL?>/agenda.php"><i class="fa-regular fa-calendar-check"></i><span>Agenda</span></a><?php endif;?>
 <a href="<?=APP_URL?>/resultado.php"><i class="fa-solid fa-chart-line"></i><span>Resultado</span></a>
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
<script src="<?=APP_URL?>/assets/js/app.js"></script>
</body></html>
