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
        'paystack_customer_code',
        'subscription_code',
        'subscription_status',
        'subscribed_at',
        'first_name',
        'last_name',
        'company_name',
        'phone',
        'country',
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
            'subscribed_at' => 'datetime',
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
                $query->where('slug', 'pro-analytics');
            })
            ->exists();
    }

    /**
     * Check if the user has purchased the Once-off Dataset.
     */
    public function hasOnceOffDatasetAccess(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->orders()
            ->where('payment_status', 'paid')
            ->whereHas('items.product', function ($query) {
                $query->where('slug', 'once-off-dataset');
            })
            ->exists();
    }

    /**
     * Check if the user has an active Developer API subscription.
     */
    public function hasApiSubscriptionAccess(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->orders()
            ->where('payment_status', 'paid')
            ->whereHas('items.product', function ($query) {
                $query->where('slug', 'developer-api');
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

    /**
     * Route notifications for the Telegram channel.
     */
    public function routeNotificationForTelegraph($notification): ?string
    {
        if ($this->isAdmin()) {
            return config('telegraph.chat_id') ?? env('TELEGRAM_CHAT_ID');
        }

        return null;
    }
}
