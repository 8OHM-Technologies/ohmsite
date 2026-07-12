<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_active_at',
        'avatar',
        'status',
        'total_spent',
        'total_orders',
        'is_vip',
        'is_banned',
        'ban_reason',
    ];

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'last_active_at' => 'datetime',
            'total_spent' => 'decimal:2',
            'total_orders' => 'integer',
            'is_vip' => 'boolean',
            'is_banned' => 'boolean',
        ];
    }

    /**
     * The products that the user has favorited.
     */
    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if the user has subscribed to the Pro Analytics package.
     */
    public function isSubscribed(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->orders()
            ->where('payment_status', 'paid')
            ->whereHas('items.product', function ($query) {
                $query->where('name', 'Analytics Dashboard');
            })
            ->exists();
    }

    /**
     * Get the redirect URL after login or verification.
     */
    public function getRedirectUrl(): string
    {
        if ($this->isAdmin()) {
            return route('dashboard');
        }

        if ($this->isSubscribed()) {
            return route('pro-dashboard.index');
        }

        return route('profile.edit');
    }
}
