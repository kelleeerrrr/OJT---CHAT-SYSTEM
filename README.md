# Laravel Real-Time Chat Application

A private chat system with real-time delivery via **Laravel Reverb** (self-hosted) or **Pusher**, bad-word filtering, XSS/injection sanitization, and an admin panel to deny or restore user chat access.

---

## Features

| Feature | Details |
|---|---|
| Private messages | Authenticated users only; private broadcast channels |
| Real-time delivery | Laravel Echo + Reverb or Pusher |
| XSS/injection prevention | `MessageSanitizer` strips all HTML, null bytes, and non-printable characters before storage and broadcast |
| Bad-word filtering | `BadWordFilter` detects and masks offensive words; originals stored separately for audit |
| Role system | `user`, `admin` roles on the `users` table |
| Admin deny/restore | Admins can suspend any user's chat; superadmin can suspend other admins; real-time notification to the target user |
| Soft-delete messages | Admins can remove individual messages; soft-delete preserves audit trail |
| Optimistic UI | Message appears instantly; replaced with server-confirmed version on response |
| Pagination | Older messages loaded on demand |

---

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- Postgres

---

## Installation

### 1. Clone or setup the project

```bash
# Navigate to the project directory
cd c:/Users/HP/Downloads/files

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 2. Configure environment

```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure `.env` for database and broadcasting

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_chat
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### Using Reverb (self-hosted WebSocket - Recommended)

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
REVERB_HOST=localhost
REVERB_PORT=9000
REVERB_SCHEME=http

VITE_BROADCAST_DRIVER=reverb
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```
### 4. Run migrations

```bash
php artisan migrate
```

### 5. Build frontend assets

```bash
npm run build
# For development with hot reload:
npm run dev
```

### 6. Start the services

Open multiple terminal windows and run:

```bash
# Terminal 1: Start Reverb WebSocket server (if using Reverb)
php artisan reverb:start

# Terminal 2: Start queue worker (for broadcast jobs)
php artisan queue:work

# Terminal 3: Start Laravel development server
php artisan serve
```

The application will be available at `http://localhost:8000`

---

## File Structure

```
app/
  Events/
    ChatAccessChanged.php   – broadcast when admin denies/restores a user
    MessageSent.php         – broadcast on private channel when a message is sent
  Http/
    Controllers/
      ChatController.php        – send, history, mark-read, conversation view
      AdminChatController.php   – deny, restore, audit, delete messages
    Middleware/
      EnsureChatNotDenied.php   – rejects denied users from chat routes
    Requests/
      SendMessageRequest.php    – validates receiver + body before hitting the controller
  Models/
    ChatDenyLog.php   – log of every deny/restore action
    Message.php       – message with sanitized body, raw body (hidden), soft-delete
    User.php          – role helpers: isSuperAdmin(), isAdmin(), isChatDenied()
  Policies/
    ChatPolicy.php    – fine-grained authorization rules
  Services/
    BadWordFilter.php     – detects and masks bad words; configurable word list
    MessageSanitizer.php  – strips HTML/XSS, normalizes whitespace, enforces length
  Providers/
    AppServiceProvider.php  – Gate definitions, service singletons
config/
  badwords.php      – word list (extend or load from DB)
  broadcasting.php  – broadcasting configuration for Reverb/Pusher
database/
  migrations/
    2024_01_01_000001_create_chat_tables.php
  seeders/
    DatabaseSeeder.php
resources/
  css/
    app.css           – Tailwind CSS entry point
  js/
    app.js           – Alpine.js init
    bootstrap.js     – Echo wired to Reverb or Pusher
  views/
    chat/
      index.blade.php  – user selector
      show.blade.php   – real-time chat view (Alpine.js)
    admin/chat/
      index.blade.php  – admin user management table
    layouts/
      app.blade.php    – base layout
routes/
  channels.php   – private channel authorization
  console.php    – console routes
  web.php        – all chat + admin routes
public/
  index.php      – application entry point
bootstrap/
  app.php        – application bootstrap (Laravel 11)
artisan          – CLI tool
```

---

## Usage

### Creating Users

Create users through Laravel's authentication system or use tinker:

```bash
php artisan tinker
```

```php
use App\Models\User;

// Create a regular user
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
    'role' => 'user',
]);

// Create an admin
User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'role' => 'admin',
]);

// Create a superadmin
User::create([
    'name' => 'Super Admin',
    'email' => 'super@example.com',
    'password' => bcrypt('password'),
    'role' => 'superadmin',
]);
```

### Accessing the Application

1. Login at `http://localhost:8000/login`
2. Navigate to chat at `http://localhost:8000/`
3. Select a user to start chatting
4. Admins can access the admin panel at `http://localhost:8000/admin/chat`

---

## Seeding roles

```php
// In a seeder or tinker:
User::where('email', 'admin@example.com')->update(['role' => 'admin']);
User::where('email', 'super@example.com')->update(['role' => 'superadmin']);
```

---

## Security notes

- All message bodies pass through `MessageSanitizer::sanitize()` **before** storage and broadcast — no raw HTML ever reaches the database or the wire.
- Blade templates use `{{ }}` (auto-escaped) and `x-text` (Alpine, always text-only) — safe against second-order XSS.
- Private channels are gated in `routes/channels.php`; the auth cookie is checked by Reverb/Pusher on subscription.
- `body_raw` (pre-filter content) is stored only when bad words were detected, is marked `$hidden` on the model, and never appears in API responses or broadcasts.
- CSRF protection is active on all non-GET routes.

---

## Extending the bad-word list

Edit `config/badwords.php`, or load dynamically from a database model:

```php
// config/badwords.php
'words' => \App\Models\BadWord::pluck('word')->toArray(),
```

Run `php artisan config:clear` after any change.

---

## Troubleshooting

### Reverb connection issues

- Ensure Reverb server is running: `php artisan reverb:start`
- Check `.env` configuration matches Reverb settings
- Verify firewall allows connections to the configured port

### Queue not processing broadcasts

- Ensure queue worker is running: `php artisan queue:work`
- Check `QUEUE_CONNECTION` in `.env`
- Verify database queue table exists

### Real-time messages not appearing

- Check browser console for WebSocket connection errors
- Verify Laravel Echo is properly configured in `resources/js/bootstrap.js`
- Ensure private channel authorization in `routes/channels.php` is correct
