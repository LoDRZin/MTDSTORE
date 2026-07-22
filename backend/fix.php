<?php
$files = [
    'app/Console/Commands/CategorizeProducts.php',
    'app/Filament/Admin/Pages/ReviewSettingsPage.php',
    'app/Filament/Admin/Resources/Coupons/Schemas/CouponForm.php',
    'app/Filament/Admin/Resources/Posts/PostResource.php',
    'app/Filament/Admin/Resources/Posts/Schemas/PostForm.php',
    'app/Filament/Admin/Resources/Posts/Tables/PostsTable.php',
    'app/Filament/Admin/Resources/Reviews/ReviewResource.php',
    'app/Http/Controllers/Api/SuccessController.php',
    'app/Models/Coupon.php',
    'app/Models/Post.php',
    'app/Services/CartService.php',
    'app/Services/CouponService.php',
    'resources/views/filament/admin/pages/review-settings.blade.php'
];

$replacements = [
    'AvaliaÃ§Ãµes' => 'Avaliações',
    'ConteÃºdo' => 'Conteúdo',
    'ConfiguraÃ§Ãµes' => 'Configurações',
    'avaliaÃ§Ãµes' => 'avaliações',
    'avaliaÃ§Ã£o' => 'avaliação',
    'AÃ§Ãµes' => 'Ações',
    'PadrÃ£o' => 'Padrão',
    'ExclusÃ£o' => 'Exclusão',
    'RestriÃ§Ãµes' => 'Restrições',
    'ComentÃ¡rio' => 'Comentário',
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    $updated = strtr($content, $replacements);
    
    if ($content !== $updated) {
        file_put_contents($file, $updated);
        echo "Fixed $file\n";
    }
}
