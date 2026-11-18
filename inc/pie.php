</main>

<footer class="pie">
  <div class="container pie-fila">
    <div>
      <div class="logo logo-pie"><img class="logo-img" src="<?= BASE ?>/imgs/logo.png" alt="" width="40" height="40"> Bella <em>y</em> Limpia</div>
      <p class="mb-0">Maquillaje y productos para dama.<br>Tienda familiar, ahora también en línea.</p>
    </div>
    <div>
      <strong>Compra</strong>
      <a href="<?= BASE ?>/catalogo.php">Catálogo</a>
      <a href="<?= BASE ?>/catalogo.php?ofertas=1">Ofertas</a>
      <a href="<?= BASE ?>/deseos.php">Mis deseos</a>
    </div>
    <div>
      <strong>Mi cuenta</strong>
      <a href="<?= BASE ?>/cuenta.php">Mis datos</a>
      <a href="<?= BASE ?>/mis_pedidos.php">Mis pedidos</a>
      <?php if (WHATSAPP !== ''): ?><a href="https://wa.me/<?= e(WHATSAPP) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
    </div>
  </div>
  <div class="pie-copy">© <?= date('Y') ?> <?= e(TIENDA) ?> · BYL</div>
</footer>
</body>
</html>
