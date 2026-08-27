<?php
/**
 * AVORA Premium Brand Logo Component
 * Dynamic reusable logo helper for light & dark backgrounds
 * 
 * @param string $variant 'auto', 'light' (for light cards/pages), 'dark' (for dark navbar/sidebar)
 * @param string $size 'sm', 'md', 'lg'
 * @param string $link Destination URL (defaults to index.php)
 * @return string HTML logo output
 */
function renderAvoraLogo($variant = 'auto', $size = 'md', $link = 'index.php') {
    $sizeClass = 'logo-size-' . $size;
    $variantClass = 'logo-variant-' . $variant;
    $href = !empty($link) ? htmlspecialchars($link) : 'index.php';

    ob_start();
    ?>
    <a href="<?= $href ?>" class="avora-brand-logo <?= $sizeClass ?> <?= $variantClass ?>" title="AVORA Luxury Store">
      <div class="avora-logo-crest">
        <span class="avora-crest-inner">A</span>
      </div>
      <div class="avora-logo-typography">
        <span class="avora-title-main"><span class="avora-accent-letter">A</span>VORA</span>
        <span class="avora-tagline-sub">LUXURY COLLECTION</span>
      </div>
    </a>
    <?php
    return ob_get_clean();
}
?>
