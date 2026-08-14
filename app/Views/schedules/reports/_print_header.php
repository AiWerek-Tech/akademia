<?php
$headerUnit = $unit ?? [];
$leftPath = trim((string)($headerUnit['logo_path'] ?? $context['logo_path'] ?? ''));
$rightPath = trim((string)($headerUnit['logo_right_path'] ?? $context['logo_right_path'] ?? ''));
$leftLogo = base_url($leftPath !== '' ? ltrim($leftPath, '/') : 'assets/img/brand-mark.svg');
$rightLogo = base_url($rightPath !== '' ? ltrim($rightPath, '/') : 'assets/img/brand-mark.svg');
?>
<header class="official-header">
    <div class="official-logo logo-left"><img src="<?= esc($leftLogo) ?>" alt="Logo kiri"></div>
    <div class="official-logo logo-right"><img src="<?= esc($rightLogo) ?>" alt="Logo kanan"></div>
    <div class="kop-line kop-1"><?= esc($context['header_line_1'] ?? 'YAYASAN PENDIDIKAN ADVENT PAPUA') ?></div>
    <div class="kop-line kop-2"><?= esc($context['header_line_2'] ?? 'WAMENA MOUNTAIN VIEW ADVENTIST ACADEMY') ?></div>
    <div class="kop-line kop-3"><?= esc($context['header_line_3'] ?? ($headerUnit['name'] ?? 'SMP-SMA ADVENT SOGOKMO')) ?></div>
    <div class="kop-line kop-4"><?= esc($context['header_line_4'] ?? 'Jalan Wamena - Kurima, Desa Sogokmo, Distrik Asotipo, Kabupaten Jayawijaya - Papua') ?></div>
</header>
