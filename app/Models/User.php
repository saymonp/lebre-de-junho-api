<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'aceitou_termos', 'termos_versao'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens, HasRoles;
    use SoftDeletes;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'slug',
        'aceitou_termos',
        'termos_versao'
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function address(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->slug = static::generateUniqueSlug($user->name);
        });
    }

    private static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);

        // Verifica se já existe alguém com esse slug na tabela de USERS
        $count = static::where('slug', 'like', "{$slug}%")->count();

        // Se já existir, concatena o número da contagem + 1 para ser único
        return $count ? "{$slug}-" . ($count + 1) : $slug;
    }
}
