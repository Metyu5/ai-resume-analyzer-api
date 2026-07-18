<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    protected $fillable = [
        'user_id',
        'file_name',
        'job_description',
        'score',
        'keywords_matched',
        'keywords_total',
        'summary',
        'findings',
        'missing_keywords',
        'suggestions',
    ];

    protected $casts = [
        'findings' => 'array',
        'missing_keywords' => 'array',
        'suggestions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
