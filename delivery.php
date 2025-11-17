<?php
$page_title = 'FAST PIZZA — Доставка';
require __DIR__ . '/header.php';
?>
    <main class="container delivery-container">
      <h2>Вартість доставки</h2>
      
      <div class="delivery-wrapper">
        <div class="delivery-cards">
          <div class="delivery-card">
            <h3 class="card-title">Доставка до дому</h3>
            <p class="card-price">N грн.</p>
          </div>
          
          <div class="delivery-card">
            <h3 class="card-title">Доставка до дверей</h3>
            <p class="card-price">N грн.</p>
          </div>
          
          <div class="delivery-card">
            <h3 class="card-title">Доставка зі вказаного району</h3>
            <p class="card-price">N грн.<br><span class="card-info">(інформація)</span></p>
          </div>
        </div>
        
        <div class="delivery-note">
          <p><strong>Самовивіз - безкоштовно</strong></p>
        </div>
      </div>
    </main>

    <?php require __DIR__ . '/footer.php'; ?>