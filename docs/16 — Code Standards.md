# Code Standards — SH3 Event Management

## 1. Layered Architecture

```
Presentation Layer     → Blade views, API Resources, Middleware, Form Requests
         ↓
Business Layer         → Controllers, Services, DTOs
         ↓
Data Layer             → Repositories, Models, Migrations, Seeders
         ↓
Database               → MySQL, Redis
```

### Rules

- Controllers **must not** contain business logic — delegate to Services.
- Services **must not** contain database queries — delegate to Repositories.
- Repositories **must not** contain business logic — only data access.
- Views **must not** contain raw queries or complex logic.

---

## 2. Naming Conventions

### PHP

| Element | Convention | Example |
|---------|-----------|---------|
| Namespaces | `App\{Layer}\{Module}` | `App\Services`, `App\Repositories` |
| Classes | `PascalCase` | `EventService`, `EventRepository` |
| Methods / Functions | `camelCase` | `findPublic()`, `registerParticipant()` |
| Properties | `camelCase` | `$eventRepository`, `$perPage` |
| Constants | `UPPER_SNAKE_CASE` | `PRICES`, `MAX_UPLOAD_SIZE` |
| Variables | `camelCase` | `$events`, `$participant` |

### Database

| Element | Convention | Example |
|---------|-----------|---------|
| Tables | `snake_case`, plural | `event_participants`, `membership_plans` |
| Columns | `snake_case` | `created_by`, `payment_status` |
| Pivot tables | singular alphabetical | `event_sponsor` (events x sponsors) |
| Foreign keys | `singular_id` | `event_id`, `participant_id` |
| Primary keys | `id` | `id` |
| Timestamps | `created_at`, `updated_at` | always include |
| Soft deletes | `deleted_at` | when applicable |

### API

| Element | Convention | Example |
|---------|-----------|---------|
| Routes | `kebab-case` | `/api/v1/events/upcoming` |
| JSON keys | `snake_case` (current) → prefer `camelCase` | `payment_status`, `created_at` |
| Query params | `snake_case` | `?search=foo&category_id=1` |

---

## 3. File & Folder Structure

```
app/
├── DTO/                          # Data Transfer Objects
├── Helpers/                      # Static helper classes
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                # Web admin controllers (Blade)
│   │   └── API/                  # REST API controllers
│   ├── Middleware/                # Custom middleware
│   ├── Requests/                  # Form Request validation
│   └── Resources/                # API Resource classes
├── Models/                       # Eloquent models
├── Repositories/                 # Repository pattern (extend BaseRepository)
├── Services/                     # Business logic layer
└── Providers/                    # Service providers
```

### Naming per Module

For every module (e.g. Event, Participant, Payment):

```
Services/EventService.php
Repositories/EventRepository.php
Models/Event.php
Http/Controllers/API/EventController.php
Http/Controllers/Admin/EventController.php
Http/Requests/EventRequest.php
Http/Resources/EventResource.php
```

---

## 4. Controller Standards

### API Controllers

```php
class EventController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventService $eventService,
    ) {}

    public function index(): JsonResponse
    {
        $events = $this->eventRepository->findPublic(['category']);

        return response()->json([
            'data' => EventResource::collection($events)
        ]);
    }

    public function store(EventRequest $request): JsonResponse
    {
        $event = $this->eventRepository->create($request->validated());

        return response()->json([
            'data' => new EventResource($event),
            'message' => 'Event berhasil dibuat'
        ], 201);
    }
}
```

- Inject dependencies via constructor property promotion (`private Type $property`).
- Return type hints on all methods (`: JsonResponse`, `: RedirectResponse`).
- Use `$request->validated()` — never access `$request->all()`.
- Messages in **Bahasa Indonesia**.
- Use `new Resource()` for single, `Resource::collection()` for lists.

### Admin Controllers

```php
class EventController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private CategoryRepository $categoryRepository,
    ) {}

    public function index()
    {
        $events = $this->eventRepository->search(request()->only(['search', 'status']));
        $categories = $this->categoryRepository->findActive();

        return view('events.index', compact('events', 'categories'));
    }
}
```

- Return `view()` with `compact()`.
- Flash messages via `->with('success', '...')`.
- Redirect with `redirect()->route('admin.*')`.

---

## 5. Service Standards

```php
class EventService
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventParticipantRepository $eventParticipantRepository,
        private NotificationService $notificationService,
    ) {}

    public function registerParticipant(Event $event, Participant $participant, ?int $paymentId): void
    {
        // Business logic here
        // Delegate queries to repositories
        // Dispatch notifications
    }
}
```

- One class per concern.
- No direct Eloquent queries — use repositories.
- Throw `ValidationException` for business rule failures.
- Use `DB::transaction()` for multi-step operations.
- Return `void` or simple values (DTO, bool, array) — never response objects.

---

## 6. Repository Standards

```php
class EventRepository extends BaseRepository
{
    public function __construct(Event $model)
    {
        parent::__construct($model);
    }

    public function findPublic(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->whereIn('status', ['publish', 'ongoing', 'completed'])
            ->orderBy('start_date')
            ->get();
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate();
    }
}
```

- All repositories extend `BaseRepository` which provides: `all()`, `paginate()`, `findById()`, `create()`, `update()`, `delete()`, `findBy()`, `findFirstBy()`, `count()`.
- Custom query methods only — no business logic.
- Return `Collection`, `Model`, `LengthAwarePaginator`, or `int`.
- Eager load relations via parameter `$relations = []`.

---

## 7. Model Standards

```php
class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'price' => 'decimal:2',
            'is_free_for_members' => 'boolean',
            'quota' => 'integer',
        ];
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'event_participants')
            ->withPivot(['qr_code', 'payment_status', 'is_attended'])
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Computed attributes / helpers
    public function remainingQuota(): int
    {
        if (!$this->quota) return -1;

        return max(0, $this->quota - $this->eventParticipants()
            ->whereIn('payment_status', ['pending', 'confirmed'])
            ->count());
    }
}
```

- Use `$guarded = []` for mass assignment protection.
- Always define `casts()` method.
- One model per table.
- Relationship methods return type-hinted (`BelongsTo`, `HasMany`, `BelongsToMany`).
- Use `protected static function booted()` for lifecycle events (slug generation, etc.).

---

## 8. Form Request Validation Standards

```php
class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policy
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'start_date' => ['required', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama event wajib diisi.',
            'category_id.exists' => 'Kategori tidak valid.',
        ];
    }
}
```

- One file per form/request.
- Messages in **Bahasa Indonesia**.
- Use `['required', 'string', ...]` array syntax.
- File validation includes `max` in kilobytes.

---

## 9. API Resource Standards

```php
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'start_date' => $this->start_date,
            'price' => $this->price,
            'image_url' => $this->image ? ImageHelper::getUrl($this->image) : null,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'schedules' => EventScheduleResource::collection($this->whenLoaded('schedules')),
            'registered_count' => $this->eventParticipants()->count(),
            'created_at' => $this->created_at,
        ];
    }
}
```

- Use `$this->whenLoaded('relation')` to prevent N+1.
- Always resolve URLs for file fields via `ImageHelper::getUrl()`.
- Nest related resources when loaded.

---

## 10. DTO Standards

```php
class EventDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $startDate,
        public readonly ?float $price,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: Str::slug($data['name']),
            description: $data['description'] ?? null,
            startDate: $data['start_date'],
            price: isset($data['price']) ? (float) $data['price'] : null,
        );
    }
}
```

- Use `readonly` properties.
- Named constructor `fromRequest()` or `fromModel()`.
- Immutable (no setters).

---

## 11. API Response Standards

### Success Format

```json
{
  "data": { "...": "payload" },
  "message": "Deskripsi aksi"
}
```

- List/detail: `{ "data": ... }`
- Mutation: `{ "data": ..., "message": "..." }` or `{ "message": "..." }`
- All responses include `meta` via `EnsureApiMeta` middleware: `{ "meta": { "timestamp": "...", "request_id": "..." } }`

### Error Format

```json
{
  "message": "Deskripsi error",
  "errors": { "field": ["Pesan validasi per field"] }
}
```

### HTTP Status Codes

| Code | Usage |
|------|-------|
| 200 | Success (list, detail, update) |
| 201 | Created (store, register, subscribe) |
| 422 | Validation / business rule failure |
| 401 | Unauthenticated |
| 403 | Forbidden (role) |
| 404 | Not found |
| 429 | Rate limit |

---

## 12. Route Standards

### API Routes (`routes/api.php`)

```php
Route::prefix('v1')->group(function () {
    // Public
    Route::get('events/upcoming', [EventController::class, 'upcoming']);
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{id}', [EventController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('events/{eventId}/register', [EventController::class, 'register']);
        Route::post('events', [EventController::class, 'store']);
        Route::put('events/{id}', [EventController::class, 'update']);
        Route::delete('events/{id}', [EventController::class, 'destroy']);
    });
});
```

### Admin Routes (`routes/web.php`)

```php
Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::middleware('role:admin_full_access,organizer')->group(function () {
        Route::resource('events', Admin\EventController::class)->names('admin.events');
    });

    Route::middleware('role:admin_full_access')->group(function () {
        Route::resource('users', Admin\UserController::class)->names('admin.users');
    });
});
```

- Always use named routes with `->name('admin.*')` or `->names('admin.*')`.
- Group by role middleware.
- Place `auth.php` routes in separate file, included in `bootstrap/app.php`.

---

## 13. Middleware Standards

- `auth` — session authentication for admin.
- `auth:sanctum` — token authentication for API.
- `role:role1,role2` — custom role check (defined in `RoleMiddleware`).
- Custom middleware in `app/Http/Middleware/` with descriptive name.

```php
// app/Http/Middleware/RoleMiddleware.php
public function handle(Request $request, Closure $next, ...$roles): mixed
{
    if (!auth()->check()) {
        return redirect('/login');
    }

    if (!in_array(auth()->user()->role, $roles)) {
        abort(403, 'Unauthorized access');
    }

    return $next($request);
}
```

---

## 14. Migration Standards

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->restrictOnDelete();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 15, 2)->nullable();
    $table->enum('status', ['draft', 'publish', 'ongoing', 'completed'])->default('draft');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

- Use `foreignId()` + `constrained()` for foreign keys.
- Explicitly define `cascadeOnDelete()`, `nullOnDelete()`, or `restrictOnDelete()`.
- Use `enum()` for fixed set of values.
- Use `nullable()` for optional columns.
- Always include `timestamps()`.
- One migration = one table or one logical change.

---

## 15. Blade / View Standards

### Layout

```blade
@extends('layouts.admin')

@section('content')
<div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    ...
</div>
@endsection
```

### Table Wrapper (Mandatory)

```html
<div class="w-full overflow-x-auto rounded-2xl border border-slate-200">
    <table class="min-w-full whitespace-nowrap">
        <thead>...</thead>
        <tbody>...</tbody>
    </table>
</div>
```

### Form Inputs

- All inputs use `w-full`.
- Responsive grid: `grid-cols-1 md:grid-cols-2 xl:grid-cols-3`.
- No fixed widths (`w-[1400px]`, `min-w-screen`).

### Cards

- Always `w-full` with `overflow-hidden`.
- Never exceed parent width.

### Critical Rules

- Body must never have horizontal scrollbar.
- Only tables may scroll horizontally.
- Use `max-w-full`, `min-w-0`, `overflow-hidden` on parent containers.
- See `docs/13 - Responsive Layout & Table Rules.md` for full detail.

---

## 16. Notification Standards

Real-time notifications via Laravel Reverb (WebSocket) + database.

```php
class EventRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
        public Participant $participant,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Pendaftaran Event Baru',
            'message' => "{$this->participant->name} mendaftar event {$this->event->name}",
            'url' => route('admin.events.show', $this->event->id),
            'type' => 'event_registration',
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("admin.{$this->event->created_by}")];
    }
}
```

- Must implement `ShouldQueue` (use `Queueable`).
- Always store to database AND broadcast.
- URL must use named route (not absolute URL with host).
- Type field for frontend filtering.

---

## 17. Image Upload Standards

```php
use App\Helpers\ImageHelper;

// Upload
$path = ImageHelper::upload($request->file('image'), 'events');

// Delete
ImageHelper::delete($event->image);

// Get URL
$url = ImageHelper::getUrl($imagePath);
```

- Always use `ImageHelper` for upload/delete/URL.
- Store on `public` disk.
- Validate file type + size in FormRequest.
- Delete old file on update before uploading new.
- Max upload: images 2MB, banners 5MB, payment proofs 5MB.

---

## 18. Transaction Standards

```php
use Illuminate\Support\Facades\DB;

public function registerParticipant(Event $event, Participant $participant): void
{
    DB::transaction(function () use ($event, $participant) {
        $registration = $this->eventParticipantRepository->create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'registered',
        ]);

        if ($event->price && $event->price > 0) {
            $this->paymentService->createPaymentForRegistration($registration, $event->price);
        }

        $this->notificationService->notifyEventRegistration($event, $participant);
    });
}
```

- Use `DB::transaction()` for any operation that spans multiple tables.
- All repository calls inside transaction.
- Notifications inside transaction (queue will process after commit).

---

## 19. Security Standards

- Validate all input via FormRequest.
- Authorize via middleware `role:` or Policies.
- Escape all output in Blade (default with `{{ }}`).
- Use `$guarded = []` or `$fillable` for mass assignment protection.
- Use CSRF protection on all forms.
- Never expose stack traces in production (`APP_DEBUG=false`).
- File uploads: validate type, size, and store outside public path.
- API rate limit: 60 requests/minute.

---

## 20. Performance Standards

- Always eager load relationships (`with()`, `load()`).
- Use pagination for all list endpoints (`paginate()`).
- Cache static/quasi-static data (categories, config).
- Queue notifications and heavy jobs (`ShouldQueue`).
- Avoid N+1 — use `whenLoaded()` in resources.
- Use Redis for session, cache, and queue.
- Use `chunk()` or `cursor()` for large dataset processing.

---

## 21. Error Handling

```php
// Business rule failure
throw ValidationException::withMessages([
    'event' => 'Kuota event sudah penuh.'
]);

// Not found — handled by BaseRepository::findById() (findOrFail)
// Unauthorized — handled by middleware
// Server error — handled by Laravel exception handler
```

- Business rule violations → `ValidationException` with status 422.
- Resource not found → `findOrFail()` (404).
- Authorization → middleware or `Gate` (403/401).
- Never catch exceptions to return generic messages — let Laravel handle.

---

## 22. Testing Standards

```bash
php artisan test
./vendor/bin/pint    # Code style fix
```

- Feature tests for API endpoints and admin flows.
- Unit tests for Services (with mocked repositories).
- Test both success and failure paths.
- Use `RefreshDatabase` trait for feature tests.
- Name tests with `{Action}_{Scenario}_{ExpectedResult}` pattern.

---

## 23. Git & Commit Standards

```
type: brief description

Types: feat, fix, chore, refactor, docs, test, style
Examples:
  feat: add event registration endpoint
  fix: resolve N+1 on event participants list
  chore: update composer dependencies
  refactor: extract payment logic to PaymentService
```

- No secrets in commits (use `.gitignore`).
- Keep commits atomic (one logical change per commit).
- Branch naming: `feature/*`, `fix/*`, `chore/*`.

---

## 24. Dependency Injection

```php
// Constructor injection (preferred)
public function __construct(
    private EventRepository $eventRepository,
    private EventService $eventService,
) {}

// Method injection (for Request, etc.)
public function store(EventRequest $request): JsonResponse
{
    // $request already injected
}
```

- Always use constructor injection for services and repositories.
- Register bindings in `AppServiceProvider` if needed (most auto-resolve).
- Never use `app()` helper or `resolve()` in business code.

---

## 25. Coding Style (Pint)

Run `./vendor/bin/pint` before committing.

Rules enforced:
- PSR-12 coding style.
- No trailing whitespace.
- Single blank line between methods.
- `elseif` → `else if` (Laravel convention).
- Array syntax `[]` not `array()`.
- Short closure syntax where possible.

---

## Checklist Before Submitting Code

- [ ] Follows Layered Architecture (Controller → Service → Repository)
- [ ] No business logic in Controller
- [ ] No database queries in Service
- [ ] FormRequest validation used
- [ ] API Resources used for responses
- [ ] Messages in Bahasa Indonesia
- [ ] No N+1 queries (eager loading checked)
- [ ] DB::transaction for multi-table operations
- [ ] ImageHelper used for file handling
- [ ] Responsive layout (no horizontal scroll on body)
- [ ] `./vendor/bin/pint` run
- [ ] Tests pass (`php artisan test`)
- [ ] No secrets or hardcoded values