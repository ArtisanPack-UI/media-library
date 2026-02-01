---
title: API Authentication
---

# API Authentication

The Media Library API uses Laravel Sanctum for authentication. This guide covers token generation and usage.

## Overview

All API endpoints require authentication using Bearer tokens. Tokens are generated through Laravel Sanctum and included in the `Authorization` header.

## Generating Tokens

### For Users

Create a token for an authenticated user:

```php
$token = $user->createToken('media-api')->plainTextToken;
```

### API Endpoint for Token Creation

Create a login endpoint in your application:

```php
// routes/api.php
Route::post('/auth/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken('media-api')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});
```

## Using Tokens

Include the token in the `Authorization` header:

```bash
curl -X GET "https://example.com/api/media" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

## Token Abilities

Restrict tokens to specific actions:

```php
$token = $user->createToken('media-api', [
    'media:view',
    'media:upload',
])->plainTextToken;
```

Check abilities in controllers:

```php
if ($request->user()->tokenCan('media:upload')) {
    // Allow upload
}
```

## Revoking Tokens

### Revoke Current Token

```php
$request->user()->currentAccessToken()->delete();
```

### Revoke All Tokens

```php
$request->user()->tokens()->delete();
```

### Revoke Specific Token

```php
$request->user()->tokens()->where('id', $tokenId)->delete();
```

## Configuration

Ensure Sanctum is configured in `config/sanctum.php`:

```php
'middleware' => [
    'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
],
```

## Next Steps

- Review [API Endpoints](Endpoints)
- See [Integration Guide](Integration-Cms-Module)
