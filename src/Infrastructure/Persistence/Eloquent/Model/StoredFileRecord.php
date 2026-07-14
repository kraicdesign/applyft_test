<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

final class StoredFileRecord extends Model
{
    protected $table = 'stored_files';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'size_bytes' => 'integer',
            'uploaded_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
