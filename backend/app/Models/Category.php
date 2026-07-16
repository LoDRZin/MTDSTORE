<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'parent_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────

    /** Parent category (for subcategories) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Direct children of this category */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    /** Recursive: all active children with their own children */
    public function activeChildren(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->with('activeChildren');
    }

    /** Products assigned to this category */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    /** Only active categories */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Only root categories (no parent) */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
