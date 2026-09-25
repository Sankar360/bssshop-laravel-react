<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomeBanner extends Model
{
    use HasFactory;

    /**
     * Table name.
     * CI4 used singular 'home_banner' — kept the same so no DB changes needed.
     */
    protected $table = 'home_banner';

    /**
     * Primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Mass-assignable columns.
     * Laravel equivalent of CI4's $allowedFields.
     */
    protected $fillable = [
        'badge_text',
        'title_line1',
        'title_line2',
        'subtitle',
        'button_text',
        'button_link',
        'button_icon',
        'is_active',
    ];

    /**
     * Timestamps — enabled by default in Laravel.
     * Column names 'created_at' / 'updated_at' are Laravel defaults, so no
     * extra config needed. (CI4 needed $useTimestamps + field names.)
     */
    public $timestamps = true;

    /**
     * Optional: cast is_active to boolean.
     */
    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Scopes — nice-to-have, replaces ->where('is_active', 1) calls     */
    /* ------------------------------------------------------------------ */

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}