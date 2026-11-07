<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Name given to the bearer token used by the user's MCP clients.
     */
    public const string MCP_TOKEN_NAME = 'mcp';

    /**
     * Get the trips registered by the user.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get the user's current MCP bearer token, if any.
     */
    public function mcpToken(): ?PersonalAccessToken
    {
        return $this->tokens()->where('name', self::MCP_TOKEN_NAME)->latest()->first();
    }

    /**
     * Issue a new MCP bearer token valid for one year, replacing any previous one.
     */
    public function issueMcpToken(): NewAccessToken
    {
        $this->revokeMcpToken();

        return $this->createToken(self::MCP_TOKEN_NAME, ['*'], now()->plus(years: 1));
    }

    /**
     * Delete the user's MCP bearer tokens.
     */
    public function revokeMcpToken(): void
    {
        $this->tokens()->where('name', self::MCP_TOKEN_NAME)->delete();
    }

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
}
