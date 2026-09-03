<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_superadmin',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

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
            'is_superadmin' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission');
    }

    /**
     * Get all permissions: role-based + direct user permissions (merged).
     */
    public function getAllPermissions()
    {
        if ($this->is_superadmin) {
            return Permission::all();
        }

        $rolePermIds = $this->roles()->with('permissions')->get()->pluck('permissions')->flatten()->pluck('id');
        $directPermIds = $this->directPermissions()->pluck('permissions.id');

        return Permission::whereIn('id', $rolePermIds->concat($directPermIds)->unique())->get();
    }

    /**
     * Check if user has a specific permission (role + direct merged).
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        // Check role permissions
        $hasViaRole = $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->contains('name', $permissionName);

        if ($hasViaRole) {
            return true;
        }

        // Check direct permissions
        return $this->directPermissions()->where('name', $permissionName)->exists();
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        return $this->getAllPermissions()->contains(fn ($p) => in_array($p->name, $permissionNames));
    }

    /**
     * Get flat array of permission names (role + direct merged).
     */
    public function getPermissionNames(): array
    {
        if ($this->is_superadmin) {
            return Permission::pluck('name')->toArray();
        }

        return $this->getAllPermissions()->pluck('name')->unique()->values()->toArray();
    }
}
